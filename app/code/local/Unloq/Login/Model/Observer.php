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
}