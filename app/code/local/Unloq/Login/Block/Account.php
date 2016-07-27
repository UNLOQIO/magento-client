<?php
class Unloq_Login_Block_Account extends Mage_Core_Block_Template
{

    public function __construct() {
        require_once(Mage::getBaseDir('lib') . '/Unloq/UnloqApi.php');
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

    public function isAdminUnloqAllowed()
    {
        $usage = Mage::getStoreConfig('unloq_login/status/usage');
        if($usage == Unloq_Login_Model_Login::ADMIN_LOGIN ||
            $usage == Unloq_Login_Model_Login::CUSTOMER_AND_ADMIN_LOGIN) {
            return true;
        }
        return false;
    }

    public function isCustomerUnloqAllowed()
    {
        $usage = Mage::getStoreConfig('unloq_login/status/usage');
        if($usage == Unloq_Login_Model_Login::CUSTOMER_LOGIN ||
            $usage == Unloq_Login_Model_Login::CUSTOMER_AND_ADMIN_LOGIN) {
            return true;
        }
        return false;
    }

}