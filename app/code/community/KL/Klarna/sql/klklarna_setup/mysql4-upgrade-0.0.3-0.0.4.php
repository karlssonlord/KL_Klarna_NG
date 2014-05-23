<?php

$installer = $this;

$installer->startSetup();

/**
 * Creating table klarna_pclass
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('klarna/pclass'));

$table->addColumn('eid', Varien_Db_Ddl_Table::TYPE_INTEGER, array(10), array(
        'nullable' => false
    ), 'E-store ID which refers to your store in Klarna database')
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, array(10), array(
            'nullable' => false
        ), 'PClass Id')
    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_INTEGER, array(4), array(
            'nullable' => false
        ), 'Type')
    ->addColumn('description', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable' => false,
        ), 'Description')
    ->addColumn('months', Varien_Db_Ddl_Table::TYPE_INTEGER, array(11), array(
            'nullable' => false,
        ), 'Months')
    ->addColumn('interestrate', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(11, 2), array(
            'nullable' => false,
        ), 'Interest rate')
    ->addColumn('invoicefee', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(11, 2), array(
            'nullable' => false,
        ), 'Invoice fee')
    ->addColumn('startfee', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(11, 2), array(
            'nullable' => false,
        ), 'Start fee')
    ->addColumn('minamount', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(11, 2), array(
            'nullable' => false,
        ), 'Min amount')
    ->addColumn('country', Varien_Db_Ddl_Table::TYPE_INTEGER, array(11), array(
            'nullable' => false,
        ), 'Country')
    ->addColumn('expire', Varien_Db_Ddl_Table::TYPE_INTEGER, array(11), array(
            'nullable' => false,
        ), 'Expire date')
    ->setComment('Klarna PClasses');

// Set old school charset and collate
$table->setOption('charset', 'latin1');
$table->setOption('collate', 'latin1_swedish_ci');

$installer->getConnection()->createTable($table);

$installer->endSetup();