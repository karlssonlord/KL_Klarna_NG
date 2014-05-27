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
        /**
         * Setup the connector if not set
         */
        if ( ! $this->_connector ) {

            /**
             * Configure Klarna
             */
            Klarna_Checkout_Order::$baseUri = Mage::helper('klarna/checkout')->getKlarnaBaseUri();
            Klarna_Checkout_Order::$contentType = "application/vnd.klarna.checkout.aggregated-order-v2+json";

            $this->_connector = Klarna_Checkout_Connector::create($this->getSharedSecret());

        }
        return $this->_connector;
    }

    /**
     * Fetch order from Klarna
     *
     * @param $checkoutId
     * @return Klarna_Checkout_Order
     */
    public function getOrder($checkoutId)
    {
        /**
         * Fetch order from Klarna
         */
        $order = new Klarna_Checkout_Order($this->getKlarnaConnector(), $checkoutId);
        $order->fetch();

        return $order;
    }

    /**
     * Acknowledge order and create it in our system
     *
     * @param $checkoutId
     * @return void
     */
    public function acknowledge($checkoutId)
    {
        /**
         * Load the order
         */
        try {

            /**
             * Fetch order
             */
            $order = $this->getOrder($checkoutId);

            /**
             * Make sure the order status is correct
             */
            if ( $order['status'] == 'checkout_complete' ) {

                /**
                 * Fetch quote
                 */
                $quote = Mage::getModel('sales/quote')
                    ->getCollection()
                    ->addFieldToFilter('klarna_checkout', $checkoutId)
                    ->getFirstItem();

                /**
                 * Make sure quote was found
                 */
                if ( $quote->getId() ) {

                    /**
                     * Convert our total amount the Klarna way
                     */
                    $quoteTotal = intval($quote->getGrandTotal() * 100);

                    /**
                     * Compare the total amounts
                     */
                    if ( $quoteTotal == $order['cart']['total_price_including_tax'] ) {

                        /**
                         * Build shipping address
                         */
                        $shippingAddress = array();
                        if ( isset($order['shipping_address']['care_of']) ) {
                            $shippingAddress[] = $order['shipping_address']['care_of'];
                        }
                        $shippingAddress[] = $order['shipping_address']['street_address'];
                        $shippingAddress = implode("\n", $shippingAddress);

                        /**
                         * Build billing address
                         */
                        $billingAddress = array();
                        if ( isset($order['billing_address']['care_of']) ) {
                            $billingAddress[] = $order['billing_address']['care_of'];
                        }
                        $billingAddress[] = $order['billing_address']['street_address'];
                        $billingAddress = implode("\n", $billingAddress);

                        /**
                         * Reconfigure the shipping address
                         */
                        $quote->getShippingAddress()
                            ->setFirstname($order['shipping_address']['given_name'])
                            ->setLastname($order['shipping_address']['family_name'])
                            ->setStreet($shippingAddress)
                            ->setPostcode($order['shipping_address']['postal_code'])
                            ->setCity($order['shipping_address']['city'])
                            ->setCountryId(strtoupper($order['shipping_address']['country']))
                            ->setEmail($order['shipping_address']['email'])
                            ->setTelephone($order['shipping_address']['phone'])
                            ->setSameAsBilling(0)
                            ->save();

                        /**
                         * Reconfigure the billing address
                         */
                        $quote->getBillingAddress()
                            ->setFirstname($order['shipping_address']['given_name'])
                            ->setLastname($order['shipping_address']['family_name'])
                            ->setStreet($billingAddress)
                            ->setPostcode($order['shipping_address']['postal_code'])
                            ->setCity($order['shipping_address']['city'])
                            ->setCountryId(strtoupper($order['shipping_address']['country']))
                            ->setEmail($order['shipping_address']['email'])
                            ->setTelephone($order['shipping_address']['phone'])
                            ->setSameAsBilling(0)
                            ->save();

                        /**
                         * Set payment information
                         */
                        $quote
                            ->getPayment()
                            ->setMethod('klarna_checkout')
                            ->setAdditionalInformation(array('klarnaCheckoutId' => $checkoutId))
                            ->setTransactionId($checkoutId)
                            ->setIsTransactionClosed(0)
                            ->save();

                        $quote
                            ->collectTotals()
                            ->save();

                        /**
                         * Fetch order status from config
                         */
                        $orderStatus = Mage::helper('klarna')->getConfig(
                            'acknowledged_order_status',
                            'klarna_checkout'
                        );

                        /**
                         * Feed quote object into sales model
                         */
                        $service = Mage::getModel('sales/service_quote', $quote);

                        /**
                         * Submit the quote and generate order
                         */
                        $service->submitAll();

                        /**
                         * Fetch the Magento Order
                         */
                        $magentoOrder = $service->getOrder();

                        /**
                         * Configure and save the order
                         */
                        $magentoOrder
                            ->setState('processing')
                            ->setStatus($orderStatus);

                        /**
                         * Send confirmation e-mail
                         */
                        try {
                            $magentoOrder->sendNewOrderEmail();
                        } catch (Exception $e) {
                            // Do nothing
                        }

                        /**
                         * Save the order
                         */
                        $magentoOrder->save();

                        /**
                         * Fetch the total amount reserved
                         */
                        $amountAuthorized = $order['cart']['total_price_including_tax'] / 100;

                        /**
                         * Set payment information on order object
                         */
                        $payment = $magentoOrder->getPayment();

                        /**
                         * Authorize
                         */
                        $payment->authorize($magentoOrder->getPayment(), $amountAuthorized);

                        /**
                         * Save order again
                         */
                        $magentoOrder->save();

                        /**
                         * Inactivate quote
                         */
                        $quote
                            ->setIsActive(0)
                            ->save();

                        /**
                         * Setup update data
                         */
                        $updateData = array(
                            'status' => 'created',
                            'merchant_reference' => array(
                                'orderid1' => $magentoOrder->getIncrementId()
                            )
                        );

                        /**
                         * Update order
                         */
                        $order->update($updateData);

                    }

                }

            }

        } catch (Exception $e) {
            // Do nothing for now
            die('e: ' . $e->getMessage());
        }

    }

    /**
     * Fetch existing Klarna Order
     *
     * @return bool|Klarna_Checkout_Order
     */
    public function getExistingKlarnaOrder()
    {
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

            return $order;

        }

        return false;

    }

    public function prepareTotals()
    {
        /**
         * Fetch the quote
         */
        $quote = $this->getQuote();

        /**
         * Fetch shipping address
         */
        $shippingAddress = $quote->getShippingAddress();

        /**
         * Force country to quote if not set
         */
        if ( ! $quote->getShippingAddress()->getCountryId() ) {

            /**
             * Add country ID
             */
            $shippingAddress
                ->setCountryId($this->getCountry());

        }

        /**
         * Collect quote totals
         */
        $shippingAddress
            ->setTotalsCollectedFlag(false)
            ->setCollectShippingRates(true)
            ->collectTotals();

        /**
         * Save quote
         */
        $quote->save();

        return $this;
    }

    /**
     * Create or update order
     *
     * @return mixed
     */
    public function handleOrder()
    {
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
        $shipping = Mage::getModel('klarna/klarnacheckout_shipping')->build();
        if ( $shipping ) {
            $items[] = $shipping;
        }

        /**
         * Handle discounts
         */
        $discounts = Mage::getModel('klarna/klarnacheckout_discount')->build($this->getQuote());
        if ( $discounts ) {
            $items[] = $discounts;
        }

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

                /**
                 * Update Klarna
                 */
                $order->update($klarnaData);

                /**
                 * Store session ID in session (again)
                 */
                Mage::helper('klarna/checkout')->setKlarnaCheckoutId($order->getLocation());

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