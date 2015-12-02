<?php

$installer = $this;

$installer->startSetup();

/**
 * Creating table klarna_log
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('klarna/klarnalog'));

/**
 * Set the columns
 */
$table
    ->addColumn(
        'id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true,),
        'ID'
    )
    ->addColumn('quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, array(11), array('nullable' => true), 'Quote ID')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, array(11), array('nullable' => true), 'Order ID')
    ->addColumn('klarna_checkout_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => true), 'KCO ID')
    ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => false), 'Error message')
    ->addColumn('level', Varien_Db_Ddl_Table::TYPE_TEXT, 20, array('nullable' => false), 'Error severity')
    ->addColumn('ip', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array('nullable' => false), 'Error severity')
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_DATETIME,
        null,
        array('nullable' => false),
        'Date when created'
    );


/**
 * Create index for error level
 */
$table->addIndex(
    $installer->getIdxName(
        'klarna/klarnalog',
        array(
            'level',
        ),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    ),
    array(
        'level',
    ),
    array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
);

/**
 * Create index for checkout ID
 */
$table->addIndex(
    $installer->getIdxName(
        'klarna/klarnalog',
        array(
            'klarna_checkout_id',
        ),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    ),
    array(
        'klarna_checkout_id',
    ),
    array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
);

$installer->getConnection()->createTable($table);

$installer->endSetup();