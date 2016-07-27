<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('admin/user'), 'unloq_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => 256,
    'nullable' => true,
    'default' => "",
    'comment' => 'UNLOQ.io user id'
));

$installer->endSetup();
