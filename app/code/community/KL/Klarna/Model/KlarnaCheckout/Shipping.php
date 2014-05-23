<?php

/**
 * Class KL_Klarna_model_KlarnaCheckout_Shipping
 */
class KL_Klarna_model_KlarnaCheckout_Shipping extends KL_Klarna_model_KlarnaCheckout_Abstract {

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
            die('@todo No shipping information there. Handle me please.');
        }

        /**
         * Calculate total price
         */
        $shippingPrice = $shipping->getShippingAmount() + $shipping->getShippingTaxAmount();

        /**
         * Calculate shipping tax percent
         */
        if ( $shippingPrice ) {
            $shippingTaxPercent = $shipping->getShippingTaxAmount() / $shipping->getShippingAmount();
        } else {
            $shippingTaxPercent = 0;
        }

        /**
         * Return the array
         */
        return array(
            'reference' => $shipping->getShippingMethod(),
            'name' => $shipping->getShippingDescription(),
            'quantity' => 1,
            'unit_price' => intval($shippingPrice * 100),
            'discount_rate' => 0, // @todo
            'tax_rate' => ($shippingTaxPercent * 100)
        );
    }

}
