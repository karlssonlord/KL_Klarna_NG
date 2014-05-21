<?php

/**
 * Class KL_Klarna_model_KlarnaCheckout
 */
class KL_Klarna_model_KlarnaCheckout extends KL_Klarna_model_KlarnaCheckout_Abstract {

    /**
     * Create or update order
     *
     * @return mixed
     */
    public function handleOrder()
    {
        /**
         * Collect quote totals
         */
        $this
            ->getQuote()
            ->collectTotals();

        /**
         * Setup the items array
         */
        $items = array();

        /**
         * Add all visible items from quote
         */
        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            $items[] = Mage::getModel('klarna/klarnacheckout_item')->build($item);
        }

        /**
         * Add shipping method and the cost
         */
        $items[] = Mage::getModel('klarna/klarnacheckout_shipping')->build();

        // @todo Add shipping method
        // @todo Add discounts

        /**
         * Setup the create array
         */
        $klarnaData = array(
            'purchase_country' => $this->getCountry(),
            'purchase_currency' => $this->getCurrency(),
            'locale' => $this->getLocale(),
            'merchant' => array(
                'id' => $this->getMerchantId(),
                'terms_uri' => Mage::getUrl('klarna/checkout/terms'),
                'checkout_uri' => Mage::getUrl('klarna/checkout'),
                'confirmation_uri' => Mage::getUrl('klarna/checkout/success'),
                'push_uri' => Mage::getUrl('klarna/checkout/push'),
            ),
            'cart' => array('items' => $items)
        );

        /**
         * Configure Klarna
         */
        Klarna_Checkout_Order::$baseUri = Mage::helper('klarna/checkout')->getKlarnaBaseUri();
        Klarna_Checkout_Order::$contentType = "application/vnd.klarna.checkout.aggregated-order-v2+json";

        /**
         * Create a connector
         */
        $connector = Klarna_Checkout_Connector::create($this->getSharedSecret());

        /**
         * Default is that no order is set
         */
        $order = false;

        /**
         * Try to load existing order
         */
        if ( Mage::helper('klarna/checkout')->getKlarnaCheckoutId() ) {

            try {

                /**
                 * Fetch the checkout
                 */
                $order = new Klarna_Checkout_Order($connector, Mage::helper('klarna/checkout')->getKlarnaCheckoutId());
                $order->fetch();

                /**
                 * Update the data
                 */
                $order->update($klarnaData);

            } catch (Exception $e) {

                /**
                 * Something went wrong, unset the checkout id
                 */
                Mage::helper('klarna/checkout')->setKlarnaCheckoutId(false);

                /**
                 * Remove any order data previously fetched
                 */
                $order = false;

            }

        }

        /**
         * Create a new order if nothing is set
         */
        if ( ! $order ) {

            /**
             * Check if we should use the mobile gui
             * This can only be set when first creating the checkout session
             */
            if ( $this->useMobileGui() ) {
                $klarnaData['gui'] = array('layout' => 'mobile');
            }

            /**
             * Fetch the checkout
             */
            $order = new Klarna_Checkout_Order($connector);

            /**
             * Create the order
             */
            $order->create($klarnaData);

            /**
             * Fetch from Klarna
             */
            $order->fetch();

            /**
             * Store session ID in session
             */
            Mage::helper('klarna/checkout')->setKlarnaCheckoutId($order->getLocation());
        }

        return $order['gui']['snippet'];

    }

}