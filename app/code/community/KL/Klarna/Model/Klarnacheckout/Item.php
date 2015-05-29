<?php

class KL_Klarna_Model_Klarnacheckout_Item
{

    /**
     * Build item array
     *
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     * @return array
     */
    public function build(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        return array(
            'reference' => $quoteItem->getSku(),
            'name' => $quoteItem->getName(),
            'quantity' => intval($quoteItem->getQty()),
            'unit_price' => Mage::helper('klarna/price')->fakeFloatToKlarnaInt($quoteItem->getPriceInclTax()),
            'discount_rate' => 0, // Not needed since Magento gives us the actual price
            'tax_rate' => ($quoteItem->getTaxPercent() * 100),
            'type' => 'physical'
        );
    }

}
