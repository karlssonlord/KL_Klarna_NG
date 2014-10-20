<?php

$installer = $this;

$installer->startSetup();
/*
 * Create product customer group index table
 */
$tableName = $installer->getTable('klarna/pushlock');

$table = $installer->getConnection()->newTable($tableName)
    ->addColumn('pushlock_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
        'identity' => true,
    ), 'ID')
    ->addColumn('klarna_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Klarna ID')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'default' => null
    ), 'Created at');

$installer->getConnection()->createTable($table);

$installer->endSetup();