<?php

/**
 * Class KL_Klarna_Helper_Checkout
 */
class KL_Klarna_Helper_Checkout extends KL_Klarna_Helper_Abstract {

    /**
     * Fetch Klarna Checkout ID from the session
     *
     * @return mixed
     */
    public function getKlarnaCheckoutId()
    {
        return Mage::getSingleton('core/session')->getKlarnaCheckoutId();
    }

    /**
     * Store Klarna Checkout ID in the session
     *
     * @param $checkoutId
     * @return $this
     */
    public function setKlarnaCheckoutId($checkoutId)
    {
        /**
         * Update the session
         */
        Mage::getSingleton('core/session')->setKlarnaCheckoutId($checkoutId);

        /**
         * Update the quote
         */
        Mage::getSingleton('checkout/session')
            ->getQuote()
            ->setKlarnaCheckout($checkoutId)
            ->save();

        return $this;
    }

    /**
     * Fetch Klarna Checkout URI for the correct environment
     *
     * @return string
     */
    public function getKlarnaBaseUri()
    {
        if ( Mage::helper('klarna')->isLive() ) {
            return 'https://checkout.klarna.com/checkout/orders';
        } else {
            return 'https://checkout.testdrive.klarna.com/checkout/orders';
        }
    }

    /**
     * Set default shipping method if none is set
     *
     * @return bool
     */
    public function setDefaultShippingMethodIfNotSet()
    {
        /**
         * Fetch the checkout model
         */
        $klarnaCheckout = Mage::getModel('klarna/klarnacheckout');

        /**
         * Loop all shipping methods
         */
        foreach ($this->getAvailableShippingMethods() as $shippingCode => $shippingRates) {

            /**
             * Loop all rates
             */
            foreach ($shippingRates as $shippingRate) {

                /**
                 * Find the cheapest one
                 */
                if ( ! isset($cheapestRate) ) {
                    $cheapestRate = $shippingRate;
                } elseif ( $cheapestRate->getPrice() > $shippingRate->getPrice() ) {
                    $cheapestRate = $shippingRate;
                }

            }

        }

        /**
         * Make sure checkout method was found and update the quote
         */
        if ( isset($cheapestRate) ) {

            /**
             * Fetch quote and shipping address
             */
            $quote = Mage::getSingleton('checkout/cart')->getQuote();
            $shippingAddress = $quote->getShippingAddress();

            $shippingAddress
                ->setShippingMethod($cheapestRate->getCode())
                ->save();

            return true;

        }

        return false;

    }

    /**
     * Return available shipping methods
     *
     * @return array
     */
    public function getAvailableShippingMethods()
    {
        /**
         * Fetch the checkout model
         */
        $klarnaCheckout = Mage::getModel('klarna/klarnacheckout');

        /**
         * Fetch quote and shipping address
         */
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        /**
         * Make sure postcode is set
         */
        if ( ! $shippingAddress->setPostcode() ) {
            $shippingAddress->setPostcode(0);
        }

        /**
         * Make sure country and shipping rates trigger is set
         */
        $shippingAddress
            ->setCountryId($klarnaCheckout->getCountry())
            ->setCollectShippingRates(true);

        /**
         * Save quote
         */
        $quote->save();

        /**
         * Fetched grouped shipping rates
         */
        return $shippingAddress
            ->getGroupedAllShippingRates();

    }
}