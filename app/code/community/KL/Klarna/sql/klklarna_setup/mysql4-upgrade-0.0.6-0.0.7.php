<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

/**
 * Create table for logs
 */

$table = $installer->getConnection()
    ->newTable($installer->getTable('klarna_log'))
    ->addColumn('log_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ),
        'Log Id')
    ->addColumn('klarna_checkout_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable' => true,
        ),
        'Klarna Checkout Id')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'unsigned' => true,
            'nullable' => true,
        ),
        'Store Id')
    ->addColumn('quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'unsigned' => true,
            'nullable' => true,
        ),
        'Quote Id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'unsigned' => true,
            'nullable' => true,
        ),
        'Order Id')
    ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable' => false
        ),
        'Message')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
        array(
            'nullable' => false
        ),
        'Created At')
    ->setComment('Log Messages');

$installer->getConnection()->createTable($table);

$installer->endSetup();