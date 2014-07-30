<?php
/**
 * @var Mage_Sales_Model_Resource_Setup $installer
 */
$installer = Mage::getResourceModel('sales/setup', 'default_setup');

$installer->startSetup();

$attributes = array(
    'base_klarna_tax_amount' => array(
        'label' => 'Base Klarna Invoice Fee Tax Amount',
        'type' => 'decimal',
    ),
    'klarna_tax_amount' => array(
        'label' => 'Klarna Invoice Fee Tax Amount',
        'type' => 'decimal',
    )
);

$entities = array(
    'order',
    'quote',
    'invoice',
    'creditmemo',
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