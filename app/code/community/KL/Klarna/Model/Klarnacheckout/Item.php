<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Item
 */
class KL_Klarna_Model_Klarnacheckout_Item extends KL_Klarna_Model_Klarnacheckout_Abstract {

    /**
     * Build array
     *
     * @param $quoteItem
     * @return array
     */
    public function build($quoteItem)
    {
        $result = array();

        /**
         * Return the array
         */
        $result[] = array(
            'reference' => $quoteItem->getSku(),
            'name' => $quoteItem->getName(),
            'quantity' => intval($quoteItem->getQty()),
            'unit_price' => $this->fakeFloatToKlarnaInt($quoteItem->getPriceInclTax()),
            'discount_rate' => 0,
            'tax_rate' => ($quoteItem->getTaxPercent() * 100),
            'type' => 'physical'
        );

        if ($quoteItem->getDiscountAmount()) {
            /**
             * If tax is applied after discount
             */
            if (Mage::getStoreConfig('tax/calculation/apply_after_discount') === 1) {
                $taxMultiplier = 1 + ($quoteItem->getTaxPercent() / 100);
                $unitPrice = $quoteItem->getDiscountAmount() * $taxMultiplier;
            } else {
                $unitPrice = $quoteItem->getDiscountAmount();
            }

            $result[] = array(
                'reference' => $quoteItem->getSku() . '-discount',
                'name' => Mage::helper('klarna')->__('Discount') . ': ' . $quoteItem->getName(),
                'quantity' => 1,
                'unit_price' => -1 * $this->fakeFloatToKlarnaInt($unitPrice),
                'discount_rate' => 0,
                'tax_rate' => ($quoteItem->getTaxPercent() * 100),
                'type' => 'discount'
            );
        }

        return $result;
    }

}
