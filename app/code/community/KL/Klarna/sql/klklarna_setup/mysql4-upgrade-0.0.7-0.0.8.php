<?php
/**
 * @var Mage_Sales_Model_Resource_Setup $installer
 */
$installer = Mage::getResourceModel('sales/setup', 'default_setup');

$installer->startSetup();

$attributes = array(
    'newsletter_subscription' => array(
        'label' => 'Add for newsletter subscription',
        'type' => 'boolean',
    ),
);

$entities = array(
    'order',
    'quote'
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
