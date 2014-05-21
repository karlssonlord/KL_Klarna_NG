<?php

/**
 * Class KL_Klarna_model_KlarnaCheckout
 */
class KL_Klarna_model_KlarnaCheckout extends KL_Klarna_model_KlarnaCheckout_Abstract {

    /**
     * @var
     */
    protected $_connector;

    /**
     * Get the Klarna Connector
     *
     * @return Klarna_Checkout_ConnectorInterface|mixed
     */
    protected function getKlarnaConnector()
    {
        if ( ! $this->_connector ) {
            $this->_connector = Klarna_Checkout_Connector::create($this->getSharedSecret());
        }
        return $this->_connector;
    }

    /**
     * Fetch existing Klarna Order
     *
     * @return bool|Klarna_Checkout_Order
     */
    public function getExistingKlarnaOrder()
    {
        /**
         * Configure Klarna
         */
        Klarna_Checkout_Order::$baseUri = Mage::helper('klarna/checkout')->getKlarnaBaseUri();
        Klarna_Checkout_Order::$contentType = "application/vnd.klarna.checkout.aggregated-order-v2+json";

        /**
         * Try to load existing order
         */
        if ( Mage::helper('klarna/checkout')->getKlarnaCheckoutId() ) {

            try {

                /**
                 * Fetch checkout ID from session
                 */
                $checkoutId = Mage::helper('klarna/checkout')->getKlarnaCheckoutId();

                /**
                 * Fetch the checkout
                 */
                $order = new Klarna_Checkout_Order($this->getKlarnaConnector(), $checkoutId);

                /**
                 * Fetch the order
                 */
                $order->fetch();

            } catch (Exception $e) {

                /**
                 * Something went wrong, unset the checkout id
                 */
                Mage::helper('klarna/checkout')->setKlarnaCheckoutId(false);

                return false;
            }
        }

        return $order;
    }

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
         * Fetch existing Klarna Order
         */
        $order = $this->getExistingKlarnaOrder();

        /**
         * Update or create the order
         */
        if ( $order ) {

            /**
             * Update the data
             */
            try {

                $order->update($klarnaData);

            } catch (Exception $e) {

                /**
                 * Terminate the object, this will make us create a new order
                 */
                $order = false;
            }

        }

        /**
         * Create order if nothing is set
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
             * Fetch empty Klarna order
             */
            $order = new Klarna_Checkout_Order($this->getKlarnaConnector());

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