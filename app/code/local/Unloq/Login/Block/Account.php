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

}