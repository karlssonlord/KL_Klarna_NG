<?php
/**
 * @var Mage_Sales_Model_Resource_Setup $installer
 */
$installer = Mage::getResourceModel('sales/setup', 'default_setup');

$installer->startSetup();

$attributes = array(
    'klarna_checkout' => array(
        'label' => 'Klarna Checkout ID',
        'type' => 'varchar',
    ),
);

$entities = array(
    'order',
    'quote',
    'invoice',
    'creditmemo',
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