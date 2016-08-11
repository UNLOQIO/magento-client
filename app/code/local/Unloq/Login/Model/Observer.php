<?php

class Unloq_Login_Model_Observer {

    public function __construct() {
        require_once(Mage::getBaseDir('lib') . '/Unloq/UnloqApi.php');
    }

    /**
     * Verifies that the API credentials are correct and sets the
     * login/logout webhooks in place.
     */
    public function handle_optionsChanged() {
        $config = Mage::getStoreConfig('unloq_login/api');
        $status = Mage::getStoreConfig('unloq_login/status');
        if($status['active'] != "1") {    // we only setup the hook when it is enabled.
            return;
        }
        $api = new UnloqApi($config['key'], $config['secret']);
        $res = $api->updateHooks();
        if($res->error) {
            throw new Mage_Core_Exception($res->message);//("API Setup failed.");
        }
    }

    /**
     * Will change the namespace and event area of unloq
     *
     * @author Diana Botean <diana.botean@evozon.com>
     * @param Varien_Event_Observer $observer
     */
    public function loginPreDispatch(Varien_Event_Observer $observer)
    {
        if(Mage::getModel('core/cookie')->get('unloq_login_type')) {
            $area = Mage::getModel('core/cookie')->get('unloq_login_type');
            if ($area == Unloq_Login_Model_Login::ADMIN_AREA) {
                //get the controller action method
                $controller = $observer->getEvent()->getControllerAction();
            }
        }
    }
}