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
        if(!isset($status['installed']) || $status['installed'] == '0') {
            //$this->installModule();
        }
    }

    /**
     * This acts as an installer. The first time the admin sets up the plugin script,
     * we will execute our ALTER on the customer table, by adding the unloq_id field.
     */
    private function installModule() {
        $config = Mage::getModel('core/config');
        $res = Mage::getSingleton('core/resource');
        $connection = $res->getConnection('core_write');
        $table = $res->getTableName('customer_entity');
        $i = $connection->addColumn($table, 'unloq_id', array(
            'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable'  => true,
            'length'    => 50,
            'comment'   => 'UNLOQ.io remote id'
        ));
        if($i) {
            $config->saveConfig('unloq_login/status/installed', '1');
        }
    }
}