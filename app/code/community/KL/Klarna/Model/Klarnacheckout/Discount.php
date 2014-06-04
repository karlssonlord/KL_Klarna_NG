<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Discount
 */
class KL_Klarna_Model_Klarnacheckout_Discount extends KL_Klarna_Model_Klarnacheckout_Abstract {

    /**
     * Build array
     *
     * @param $quoteItem
     * @return array
     */
    public function build($quoteItem)
    {
        /**
         * Collect quote totals
         */
        $quoteTotals = $quoteItem->getTotals();

        /**
         * Make sure any discount is set
         */
        if ( isset($quoteTotals['discount']) && $quoteTotals['discount']->getValue() ) {


            /**
             * Return the array
             */
            return array(
                'type' => 'discount',
                'reference' => Mage::helper('klarna')->__('Discount'),
                'name' => Mage::helper('klarna')->__('Discount'),
                'quantity' => 1,
                'unit_price' => intval($quoteTotals['discount']->getValue() * 100),
                'tax_rate' => 0
            );

        }

        return false;
    }

}
