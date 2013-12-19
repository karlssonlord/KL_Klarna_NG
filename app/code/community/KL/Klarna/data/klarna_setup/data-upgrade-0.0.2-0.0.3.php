<?php
/**
 * @var Mage_Sales_Model_Resource_Setup $installer
 */
$installer = Mage::getResourceModel('sales/setup', 'default_setup');

$installer->startSetup();

$attributes = array(
    'base_klarna_total' => array(
        'label' => 'Base Klarna Total',
        'type' => 'decimal',
    ),
    'klarna_total' => array(
        'label' => 'Klarna Total',
        'type' => 'decimal',
    )
);

$entities = array(
    'quote_address',
    'order_address'
);

foreach ($entities as $entity) {
    foreach ($attributes as $attributeCode => $data) {
        $installer->addAttribute(
            $entity,
            $attributeCode,
            $data
        );
    }
}

$installer->endSetup();