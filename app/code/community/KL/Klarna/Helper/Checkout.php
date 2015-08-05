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
     * @return boolean|$this
     */
    public function setKlarnaCheckoutId($checkoutId)
    {
        /*
         * Make a check for duplicated $checkoutId
         */
        if(!empty($checkoutId)) {
            $quoteId = Mage::getModel('sales/quote')->load($checkoutId, 'klarna_checkout')->getId();
            if($quoteId) {
                return false;
            }
        }

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
                ->setShippingMethod($cheapestRate->getCode());

            /**
             * Assure correct payment method
             */
            $shippingAddress
                ->setPaymentMethod('klarna_checkout');

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

    public function selectShippingMethod($shippingMethodCode)
    {
        /**
         * Fetch Klarna Checkout model
         */
        $klarnaCheckout = Mage::getModel('klarna/klarnacheckout');

        /**
         * Update the quote
         */
        $quote = Mage::getModel('checkout/cart')->getQuote();

        $shippingAddress = $quote->getShippingAddress();

        $quote
            ->getShippingAddress()
            ->setCountryId($klarnaCheckout->getCountry())
            ->setShippingMethod($shippingMethodCode)
            ->save();

        $quote->collectTotals()
            ->save();

        $shippingAddress
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->save();

        return true;
    }

    public function createOrderFromQuote($quoteId)
    {
        $quote = Mage::getModel('sales/quote')->load($quoteId);

        if ($quote->getId() && $quote->getKlarnaCheckout()) {
            $checkout    = Mage::getModel('klarna/klarnacheckout');
            $checkoutId  = $quote->getKlarnaCheckout();
            $reservation = $checkout->getOrder($checkoutId);

            $shippingAddress = array();
            if ( isset($reservation['shipping_address']['care_of']) ) {
                $shippingAddress[] = $reservation['shipping_address']['care_of'];
            }
            if ( isset($reservation['shipping_address']['street_address']) ) {
                $shippingAddress[] = $reservation['shipping_address']['street_address'];
                $shippingAddress = implode("\n", $shippingAddress);                
            }
            if ( isset($reservation['shipping_address']['street_name']) ) {
                $street = $reservation['shipping_address']['street_name'];

                if ( isset ($reservation['shipping_address']['street_number']) ) {
                    $street .= ' ' . $reservation['shipping_address']['street_number'];
                }

                $shippingAddress[] = $street;
                $shippingAddress = implode("\n", $shippingAddress);                
            }

            $billingAddress = array();
            if ( isset($reservation['shipping_address']['care_of']) ) {
                $billingAddress[] = $reservation['shipping_address']['care_of'];
            }
            if ( isset($reservation['shipping_address']['street_address']) ) {
                $billingAddress[] = $reservation['shipping_address']['street_address'];
                $billingAddress = implode("\n", $shippingAddress);                
            }
            if ( isset($reservation['shipping_address']['street_name']) ) {
                $street = $reservation['shipping_address']['street_name'];

                if ( isset ($reservation['shipping_address']['street_number']) ) {
                    $street .= ' ' . $reservation['shipping_address']['street_number'];
                }

                $billingAddress[] = $street;
                $billingAddress = implode("\n", $billingAddress);                
            }

            $quote->getShippingAddress()
                ->setFirstname($reservation['shipping_address']['given_name'])
                ->setLastname($reservation['shipping_address']['family_name'])
                ->setStreet($shippingAddress)
                ->setPostcode($reservation['shipping_address']['postal_code'])
                ->setCity($reservation['shipping_address']['city'])
                ->setCountryId(strtoupper($reservation['shipping_address']['country']))
                ->setEmail($reservation['shipping_address']['email'])
                ->setTelephone($reservation['shipping_address']['phone'])
                ->setSameAsBilling(0)
                ->save();

            $quote->getBillingAddress()
                ->setFirstname($reservation['shipping_address']['given_name'])
                ->setLastname($reservation['shipping_address']['family_name'])
                ->setStreet($billingAddress)
                ->setPostcode($reservation['shipping_address']['postal_code'])
                ->setCity($reservation['shipping_address']['city'])
                ->setCountryId(strtoupper($reservation['shipping_address']['country']))
                ->setEmail($reservation['shipping_address']['email'])
                ->setTelephone($reservation['shipping_address']['phone'])
                ->save();

            $quote
                ->getPayment()
                ->setMethod('klarna_checkout')
                ->setAdditionalInformation(array('klarnaCheckoutId' => $checkoutId))
                ->setTransactionId($checkoutId)
                ->setIsTransactionClosed(0)
                ->save();

            $quote
                ->setCustomerFirstname($reservation['shipping_address']['given_name'])
                ->setCustomerLastname($reservation['shipping_address']['family_name'])
                ->setCustomerEmail($reservation['shipping_address']['email'])
                ->save();

            $quote
                ->collectTotals()
                ->setIsActive(0)
                ->save();

            $service = Mage::getModel('sales/service_quote', $quote);

            $service->submitAll();
            $order = $service->getOrder();

            if ($order) {
                Mage::dispatchEvent(
                    'checkout_type_onepage_save_order_after',
                    array('order' => $order, 'quote' => $quote)
                );

                $order
                    ->setState('pending')
                    ->setStatus('pending');

                $order->save();

                $profiles = $service->getRecurringPaymentProfiles();

                Mage::dispatchEvent(
                    'checkout_submit_all_after',
                    array('order' => $order, 'quote' => $quote, 'recurring_profiles' => $profiles)
                );

                return $order->getIncrementId();
            }
        }
        return 0;
    }
}