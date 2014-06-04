<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Shipping
 */
class KL_Klarna_Model_Klarnacheckout_Shipping extends KL_Klarna_Model_Klarnacheckout_Abstract {

    /**
     * Build array with shipping information
     *
     * @return array
     */
    public function build()
    {
        /**
         * Fetch information from quote
         */
        $shipping = Mage::helper('checkout')->getQuote()
            ->getShippingAddress();

        /**
         * Set first shipping method if none is set
         */
        if ( ! $shipping->getShippingMethod() ) {

            /**
             * Set default shipping method if none is set
             */
            Mage::helper('klarna/checkout')->setDefaultShippingMethodIfNotSet();

            /**
             * Fetch information from quote again
             */
            $shipping = Mage::helper('checkout')->getQuote()
                ->getShippingAddress();

        }

        /**
         * If we're still failing with no shipping method
         */
        if ( ! $shipping->getShippingMethod() ) {
            Mage::helper('klarna')->log('Missing shipping method for Klarna Checkout!');
            return false;
        }

        /**
         * Calculate total price
         */
        $shippingPrice = $shipping->getShippingAmount();

        /**
         * Calculate shipping tax percent
         */
        if ( $shippingPrice ) {
            $shippingTaxPercent = $shipping->getShippingTaxAmount() / ($shipping->getShippingAmount()-$shipping->getShippingTaxAmount());
        } else {
            $shippingTaxPercent = 0;
        }

        /**
         * Set the shipping name
         */
        $shippingName = $shipping->getShippingDescription();

        /**
         * If the shipping name wasn't loaded by some reason, just add a standard name
         */
        if (!$shippingName) {
            $shippingName = Mage::helper('klarna')->__('Shipping');
        }

        /**
         * Return the array
         */
        return array(
            'reference' => $shipping->getShippingMethod(),
            'name' => $shippingName,
            'quantity' => 1,
            'unit_price' => intval($shippingPrice * 100),
            'discount_rate' => 0, // @todo
            'tax_rate' => ceil(($shippingTaxPercent * 100))
        );
    }

}
