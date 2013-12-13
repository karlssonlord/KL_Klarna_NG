<?php
/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = new Mage_Sales_Model_Resource_Setup('core_setup');

/**
 * Add provision attribute for entities
 */
$entities = array(
    'quote',
    'order',
);

$options = array(
    'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'visible'  => false,
    'required' => false
);

foreach ($entities as $entity) {
    $installer->addAttribute($entity, 'klarna_fee', $options);
}

$installer->endSetup();