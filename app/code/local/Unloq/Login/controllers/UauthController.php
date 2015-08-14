<?php
require_once(Mage::getBaseDir('lib') . '/Unloq/UnloqApi.php');

class Unloq_Login_UauthController extends Mage_Core_Controller_Front_Action
{

    /**
     * Implementing the Unloq auth protocol, this route is called when a user is redirected back to us
     * with a valid access token found in the querystring. We then proceed to request the user information
     * from UNLOQ, and, based on the resulting user from the local database, we create it or just log him in.
     * */
    public function loginAction() {
        $request = Mage::app()->getRequest();
        $sess = Mage::getSingleton("core/session", array('name' => 'frontend'));
        $customerSession = Mage::getSingleton('customer/session');
        $token = $request->getParam('token');
        if(!$token) {
            $sess->addError('The authentication request is missing its token.');
            return $this->_redirect('customer/account/login');
        }
        $active = Mage::getStoreConfig('unloq_login/status/active');
        if(!$active) {
            $sess->addNotice('UNLOQ.io authentication is temporary disabled.');
            return $this->_redirect('customer/account/login');
        }
        $config = Mage::getStoreConfig('unloq_login/api');
        $api = new UnloqApi($config['key'], $config['secret']);
        // Proceed to get the user from UNLOQ
        $result = $api->getLoginToken($token);
        if($result->error) {
            $sess->addError('UNLOQ: ' . $result->message);
            return $this->_redirect('customer/account/login');
        }
        $user = $result->data;
        // Sanity check for user id and email
        if(!isset($user['id']) || !isset($user['email'])) {
            $sess->addError('UNLOQ: Failed to perform authentication.');
            return $this->_redirect('customer/account/login');
        }
        $collection = Mage::getModel('customer/customer')->getCollection();
        $collection->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('unloq_id')
            ->addAttributeToFilter('website_id', Mage::app()->getWebsite()->getId())
            ->addAttributeToFilter('store_id', Mage::app()->getStore()->getStoreId())
            ->addAttributeToFilter('email', $user['email']);
        // Step one, we try and find the user locally.
        $customer = $collection->getFirstItem();
        if($customer->isEmpty()) {
            $customer = $this->createCustomer($user);
            if(!$customer) {
                $sess->addError('Failed to create account. Please try again.');
                return $this->_redirect('customer/account/login');
            }
        } else {
            // If we do have a customer, we check if his account is disabled. If so, we stop.
            $isActive = (bool)$customer->getIsActive();
            if(!$isActive) {
                $sess->addNotice("Your account has been disabled.");
                return $this->_redirect('customer/account/login');
            }
            if(!$this->updateCustomer($customer, $user)) {
                $sess->addError('Failed to update data. Please try again.');
                return $this->_redirect('customer/account/login');
            }
        }
        // At this point, we create the session and log the user in.
        if(!$customerSession->loginById($customer->getId())) {
            $sess->addError("Failed to log you in. Please try again.");
            return $this->_redirect('customer/account/login');
        }
        // Finally, we send the session ID for remote logout.
        $duration = (int) Mage::getStoreConfig('web/cookie/cookie_lifetime');
        $sessionId = $customerSession->getSessionId();
        $api->sendTokenSession($token, $sessionId, $duration);
        // Everything is done, we can just redirect back to account
        $redirect = (!$customer->getFirstname() || !$customer->getLastname()) ? "customer/account/edit" : "customer/account";
        $this->_redirect($redirect);
    }


    /**
     * When this endpoint is called with valid arguments, coming from UNLOQ or the user's device,
     * it will perform a remote logout.
     * */
    public function logoutAction() {
        $request = Mage::app()->getRequest();
        // Request validation
        if(!$request->isPost()) {
            return $this->stop('Invalid HTTP method');
        }
        $key = $request->getParam('key');
        $unloqId = $request->getParam('id');
        $sessionId = $request->getParam('sid');
        if(!$key || !$unloqId || !$sessionId) {
            return $this->stop('Invalid arguments');
        }
        $active = Mage::getStoreConfig('unloq_login/status/active');
        if(!$active) {
            return $this->stop('UNLOQ Authentication is disabled.', 401);
        }
        // Validate the request's signature and create the API object
        $config = Mage::getStoreConfig('unloq_login/api');
        $api = new UnloqApi($config['key'], $config['secret']);
        if(!$api->verifySignature($api->getHook('logout'), $_POST)) {
            return $this->stop('Invalid signature');
        }
        // If we've reached this point, we need to read the customer from the db, to validate his existance.
        $collection = Mage::getModel('customer/customer')->getCollection();
        $app = Mage::app();
        $collection->addAttributeToFilter('unloq_id', $unloqId)
            ->addAttributeToFilter('website_id', $app->getWebsite()->getId())
            ->addAttributeToFilter('store_id', Mage::app()->getStore()->getStoreId());
        $customer = $collection->getFirstItem();
        if($customer->isEmpty()) {
            return $this->stop('User not found.', 404);
        }
        // Finally, we destroy the session.
        $customerSession = Mage::getSingleton('customer/session');
        $customerSession->setSessionId($sessionId);
        $customerSession->setCustomer($customer);
        $customerSession->logout();
        echo "Logged out.";
    }

    /**
     * Internal function for stopping the request and sending back some data.
     * This is used when something goes wrong in login/logout or data is not valid.
     * */
    private function stop($msg = "", $code = 500) {
        header(' ', true, $code);
        echo $msg;
        exit;
    }

    /**
     * Creates a new customer based on the given user information.
     * */
    private function createCustomer($user) {
        $app = Mage::app();
        $websiteId = $app->getWebsite()->getId();
        $store = $app->getStore();
        $customer = Mage::getModel('customer/customer');
        // Attaching information to our new customer
        $customer->setWebsiteId($websiteId)
            ->setStore($store)
            ->setEmail($user['email'])
            ->setConfirmationRequired(false)
            ->setUnloqId($user['id']);
        if(isset($user['first_name'])) {
            $customer->setFirstname($user['first_name']);
        }
        if(isset($user['last_name'])) {
            $customer->setLastname($user['last_name']);
        }
        try {
            $customer->save();
            Mage::dispatchEvent('customer_register_success',
                array('unloq_controller' => $this, 'customer' => $customer)
            );
            return $customer;
        } catch(Exception $e) {
            Zend_debug::dump($e->getMessage());
            return false;
        }
    }

    /**
     * Updates the given customer with new information about him.
     * The information always contain the unloqId, but might contain additional
     * properties, such as first_name or last_name.
     * */
    private function updateCustomer($customer, $user) {
        if(!$customer->getUnloqId()) {
            $customer->setUnloqId($user['id']);
        }
        if(strlen($customer->getFirstname()) == 0 && isset($user['first_name'])) {
            $customer->setFirstname($user['first_name']);
        }
        if(strlen($customer->getLastname()) == 0 && isset($user['last_name'])) {
            $customer->setLastname($user['last_name']);
        }
        try {
            $customer->save();
            return true;
        } catch (Exception $e) {
            Zend_debug::dump($e->getMessage());
            return false;
        }
    }

}