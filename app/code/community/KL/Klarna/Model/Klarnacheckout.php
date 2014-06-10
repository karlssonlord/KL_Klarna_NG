<?php
/**
 * Class KL_Klarna_Model_KlarnaCheckout
 */
class KL_Klarna_Model_Klarnacheckout
    extends KL_Klarna_Model_Klarnacheckout_Abstract
{

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
        Mage::helper('klarna')->log('Request to acknowledge ' . $checkoutId);

        /**
         * Load the order
         */
        try {

            /**
             * Fetch order
             */
            $order = $this->getOrder($checkoutId);

            Mage::helper('klarna')->log('Acknowledge: Order status: ' . $order['status']);

            /**
             * Make sure the order status is correct
             */
            if ( $order['status'] == 'checkout_complete' ) {

                /**
                 * Load Magento order
                 */
                $magentoOrder = Mage::getModel('sales/order')
                    ->getCollection()
                    ->addFieldToFilter('klarna_checkout', $checkoutId)
                    ->getFirstItem();

                /**
                 * Make sure order was found
                 */
                if ( $magentoOrder->getId() ) {

                    /**
                     * Set the payment information
                     */
                    $magentoOrder
                        ->getPayment()
                        ->setMethod('klarna_checkout')
                        ->setAdditionalInformation(array('klarnaCheckoutId' => $checkoutId))
                        ->setTransactionId($checkoutId)
                        ->setIsTransactionClosed(0)
                        ->save();

                    /**
                     * Fetch order status from config
                     */
                    $orderStatus = Mage::helper('klarna')->getConfig(
                        'acknowledged_order_status',
                        'klarna_checkout'
                    );

                    /**
                     * Configure and save the order
                     */
                    $magentoOrder
                        ->setState('processing')
                        ->setStatus($orderStatus);

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

                    Mage::helper('klarna')->log(
                        'Order acknowledged, Magento ID ' . $magentoOrder->getIncrementId()
                    );

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

    /**
     * Prepare totals
     *
     * @return KL_Klarna_Model_Klarnacheckout
     */
    public function prepareTotals()
    {
        /**
         * Fetch the quote
         */
        $quote = $this->getQuote();

        /**
         * Collect totals
         */
        $quote
            ->setTotalsCollectedFlag(false)
            ->collectTotals()
            ->save();

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
         * Collect shipping rates, quote totals
         * and save the quote
         */
        $shippingAddress
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->save();

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
                'terms_uri' => Mage::getUrl(Mage::getStoreConfig('payment/klarna_checkout/terms_url')),
                'checkout_uri' => Mage::getUrl('klarna/checkout'),
                'confirmation_uri' => Mage::getUrl('klarna/checkout/success'),
                'push_uri' => Mage::getUrl('klarna/checkout/push'),
            ),
            'cart' => array('items' => $items)
        );

        Mage::helper('klarna')->log($klarnaData);

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
             * Prefill information from current user
             */
            if ( Mage::getSingleton('customer/session')->isLoggedIn() ) {

                /**
                 * Fetch current user
                 */
                $currentUser = Mage::getSingleton('customer/session')->getCustomer();

                /**
                 * Make sure the variable in the array is defined
                 */
                if ( ! isset($klarnaData['shipping_address']) ) {
                    $klarnaData['shipping_address'] = array();
                }

                /**
                 * Set the e-mail
                 */
                $klarnaData['shipping_address']['email'] = $currentUser->getEmail();

                /**
                 * Fetch the default shipping address
                 */
                $defaultShippingAddressId = $currentUser->getDefaultShipping();
                if ( $defaultShippingAddressId ) {

                    /**
                     * Load the address
                     */
                    $defaultShippingAddress = $address = Mage::getModel('customer/address')->load(
                        $defaultShippingAddressId
                    );

                    /**
                     * Prefill postcode
                     */
                    if ( $defaultShippingAddress->getPostcode() ) {
                        $klarnaData['shipping_address']['postal_code'] = $defaultShippingAddress->getPostcode();
                    }

                }

                /**
                 * Prefill using test credentials if it's a test environment
                 */
                if ( ! Mage::helper('klarna')->isLive() ) {
                    $klarnaData['shipping_address']['email'] = 'checkout-se@testdrive.klarna.com';
                    $klarnaData['shipping_address']['postal_code'] = '12345';
                }

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