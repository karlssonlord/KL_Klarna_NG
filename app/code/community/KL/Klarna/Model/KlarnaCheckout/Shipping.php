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
