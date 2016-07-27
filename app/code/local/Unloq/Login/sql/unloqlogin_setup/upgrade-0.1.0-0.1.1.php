<?php
$installer = $this;

$installer->startSetup();

// add new column to admin_user table in order to keep the UNLOQ.io id for the admin user
$installer->getConnection()->addColumn($installer->getTable('admin/user'), 'unloq_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => 255,
    'nullable' => true,
    'default' => "",
    'comment' => 'UNLOQ.io user id'
));

$installer->endSetup();
