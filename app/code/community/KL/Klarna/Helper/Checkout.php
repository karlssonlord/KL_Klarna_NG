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
         * Fetch shipping address
         */
        $shipping = Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getShippingAddress();

        /**
         * Force given country and save it
         */
        $shipping
            ->setCountryId($klarnaCheckout->getCountry())
            ->save();

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

            $shipping
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
         * Collect shipping rates and assure the right country is set
         */
        Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getShippingAddress()
            ->setCountryId($klarnaCheckout->getCountry())
            ->collectShippingRates()
            ->save();

        /**
         * Fetched grouped shipping rates
         */
        $groupedShippingRates = Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getShippingAddress()
            ->getGroupedAllShippingRates();

        /**
         * Return data
         */
        return $groupedShippingRates;
    }
}