<?php
class Unloq_Login_Block_Account extends Mage_Core_Block_Template
{

    public function __construct()
    {
        require_once(Mage::getBaseDir('lib') . '/Unloq/UnloqApi.php');
        if (Mage::app()->getStore()->isAdmin()) {
            Mage::getModel('core/cookie')->set('unloq_login_type', Unloq_Login_Model_Login::ADMIN_AREA);
        } else {
            Mage::getModel('core/cookie')->set('unloq_login_type', Unloq_Login_Model_Login::CUSTOMER_AREA);
        }
    }
	/**
	 * Returns the public plugin URL
	 * */
    public function getPluginUrl() {
        return UnloqApi::PLUGIN_URL;
    }

    public function getApiKey() {
        $key = Mage::getStoreConfig('unloq_login/api/key');
        return $key;
    }
    public function getTheme() {
        $theme = Mage::getStoreConfig('unloq_login/api/theme');
        return $theme;
    }

    public function _toHtml() {
        $active = Mage::getStoreConfig('unloq_login/status/active');
        if($active != '1') {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Will check if the module is enabled and that the admin
     * login is set to be allowed from configuration
     *
     * @author Diana Botean <diana.botean@evozon.com>
     * @return bool
     * */
    public function isAdminUnloqAllowed()
    {
        $active = Mage::getStoreConfig('unloq_login/status/active');
        if($active) {
            $usage = Mage::getStoreConfig('unloq_login/status/usage');
            if ($usage == Unloq_Login_Model_Login::ADMIN_LOGIN ||
                $usage == Unloq_Login_Model_Login::CUSTOMER_AND_ADMIN_LOGIN
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Will check if the module is enabled and that the customer
     * login is set to be allowed from configuration
     *
     * @author Diana Botean <diana.botean@evozon.com>
     * @return bool
     * */
    public function isCustomerUnloqAllowed()
    {
        $active = Mage::getStoreConfig('unloq_login/status/active');
        if($active) {
            $usage = Mage::getStoreConfig('unloq_login/status/usage');
            if ($usage == Unloq_Login_Model_Login::CUSTOMER_LOGIN ||
                $usage == Unloq_Login_Model_Login::CUSTOMER_AND_ADMIN_LOGIN
            ) {
                return true;
            }
        }
        return false;
    }

}