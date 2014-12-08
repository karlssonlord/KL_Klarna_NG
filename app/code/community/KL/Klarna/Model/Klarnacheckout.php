<?php

/**
 * Class KL_Klarna_Model_KlarnaCheckout
 */
class KL_Klarna_Model_Klarnacheckout
    extends KL_Klarna_Model_Klarnacheckout_Abstract {

    /**
     * @var
     */
    protected $_connector;

    public function __construct()
    {
        parent::__construct();

        /**
         * Force the right merchant ID and shared secret
         */
        $this
            ->setMerchantId(Mage::helper('klarna')->getConfig('merchant_id'))
            ->setSharedSecret(Mage::helper('klarna')->getConfig('shared_secret'));
    }

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
        $errorEmailMessages = array();

        /**
         * Make a note in the logs
         */
        Mage::helper('klarna/log')->log(null, '[' . $checkoutId . '] Acknowledge method called for checkout id');

        /**
         * Avoid timeouts in PHP to allow the script to finish
         */
        set_time_limit(0);

        try {
            $order = $this->getOrder($checkoutId);

            if ($order['status'] == 'checkout_complete') {

                /**
                 * Check if the order exists
                 */
                $magentoOrder = Mage::getModel('klarna/klarnacheckout_order')
                    ->loadByCheckoutId($checkoutId);

                /**
                 * What to do if the order exists
                 */
                if ( !$magentoOrder || !$magentoOrder->getId() ) {

                    Mage::helper('klarna/log')->log(null, '[' . $checkoutId . '] No previous order found, trying to create...');

                    /**
                     * Try to create the order if it was not found
                     */
                    $magentoOrder = Mage::getModel('klarna/klarnacheckout_order')
                        ->create($checkoutId);

                } else {

                    Mage::helper('klarna/log')->log(null, '[' . $checkoutId . '] Existing order found for checkout id');

                }

                /**
                 * Make sure the Magento order exists
                 */
                if ( $magentoOrder && $magentoOrder->getId() ) {

                    /**
                     * Load the quote
                     */
                    $quote = $magentoOrder->getQuote();

                    /**
                     * Fetch payment (if any)
                     */
                    $magentoOrderPayment = $magentoOrder
                        ->getPayment();

                    /**
                     * Set the payment information
                     */
                    $magentoOrderPayment
                        ->setMethod('klarna_checkout')
                        ->setAdditionalInformation(array('klarnaCheckoutId' => $checkoutId,
                                                         'orderInfo' => $order->marshal() ))
                        ->setTransactionId($checkoutId)
                        ->setIsTransactionClosed(0)
                        ->save();

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
                     * Fetch order status from config
                     */
                    $orderStatus = Mage::helper('klarna')->getConfig(
                        'acknowledged_order_status',
                        'klarna_checkout'
                    );

                    /**
                     * Log what status and state we're setting
                     */
                    Mage::helper('klarna/log')->log(
                        $quote,
                        'Setting processing/' . $orderStatus . ' on Magento ID ' . $magentoOrder->getIncrementId()
                    );

                    /**
                     * Configure and save the order
                     */
                    $magentoOrder
                        ->setState('processing')
                        ->setStatus($orderStatus);

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
                            'orderid1' => $magentoOrder->getIncrementId(),
                        )
                    );

                    /**
                     * Update order
                     */
                    $order->update($updateData);

                    /**
                     * Send new order e-mail
                     */
                    try {

                        $magentoOrder->sendNewOrderEmail();

                    } catch (Exception $e) {

                        $errorMessage = 'Unable to send new order email (' . $e->getMessage() . '), Magento ID ' .
                            $magentoOrder->getIncrementId();
                        $errorEmailMessages[] = $errorMessage;

                        Mage::helper('klarna/log')->log(
                            $quote,
                            $errorMessage,
                            true
                        );
                    }

                    Mage::helper('klarna/log')->log(
                        $quote,
                        'Order acknowledged, Magento ID ' . $magentoOrder->getIncrementId(),
                        true
                    );

                } else {

                    $errorMessage = 'Unable to acknowledge due to missing order in Magento. (' . $checkoutId . ')';
                    $errorEmailMessages[] = $errorMessage;

                    Mage::helper('klarna/log')->log(
                        null,
                        $errorMessage
                    );

                }

            } else {

                $errorMessage = 'Unable to acknowledge due to order status from Klarna: ' . $order['status'] .
                    ' (' . $checkoutId . ')';
                $errorEmailMessages[] = $errorMessage;

                Mage::helper('klarna/log')->log(
                    null,
                    $errorMessage
                );

            }
            if(!empty($errorEmailMessages)) {
                Mage::helper('klarna')->sendErrorEmail(implode("\n", $errorEmailMessages));
            }

        } catch (Exception $e) {

            /**
             * Remove the order lock: allow Klarna to make subsequent push retries
             */
            Mage::getModel('klarna/pushlock')->unLock($checkoutId);

            $errorMessage =  'CheckoutId = "' . $checkoutId . '"; Cannot acknowledge: ' . $e->getMessage();
            Mage::helper('klarna')->sendErrorEmail($errorMessage);

            /**
             * Log error
             */
            Mage::helper('klarna/log')->log(
                null,
                $errorMessage
            );

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

            /**
             * Log the event
             */
            $quote = $this->getQuote();

            Mage::helper('klarna/log')->log(
                $quote,
                'Trying to fetch existing KCO order from Klarna using '
                    . Mage::helper('klarna/checkout')->getKlarnaCheckoutId()
            );

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
                 * Log the event
                 */
                Mage::helper('klarna')->log('Unable to get existing Klarna Order ID. Error received: ' . $e->getMessage());

                return false;
            }

            return $order;

        }

        /**
         * Log the event
         */
        Mage::helper('klarna')->log('No existing checkout ID when fetching order from Klarna, returning false.');

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
         * Fetch existing Klarna Order
         */
        $order = $this->getExistingKlarnaOrder();

        /**
         * Update or create the order
         */
        if ( $order ) {

            /**
             * Setup the update array
             */
            $klarnaData = array(
                'cart' => array('items' => $items),
                'merchant_reference' => array(
                    'orderid2' => $this->getQuote()->getId()
                )
            );

            Mage::helper('klarna')->log($klarnaData);

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

                Mage::helper('klarna')->log($e->getMessage());

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
                    'push_uri' => Mage::getUrl('klarna/checkout/push') . '?klarna_order={checkout.order.uri}'
                ),
                'cart' => array('items' => $items),
                'merchant_reference' => array(
                    'orderid2' => $this->getQuote()->getId()
                )
            );

            /**
             * Set the validation URL
             */
            $validationUrl = Mage::getUrl('klarna/checkout/validate', array('_forced_secure' => true));

            /**
             * Make sure the link uses https, only add it to Klarna if it is
             */
            if (substr($validationUrl, 0, 5) == 'https') {
                $klarnaData['merchant']['validation_uri'] = $validationUrl;
            }

            Mage::helper('klarna')->log($klarnaData);

            $klarnaData['gui']['options'] = array('disable_autofocus');

            /**
             * Check if we should use the mobile gui
             * This can only be set when first creating the checkout session
             */
            if ( $this->useMobileGui() ) {
                $klarnaData['gui']['layout'] = 'mobile';
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
             * We also make a check for duplicated checkoutID
             */
            if(!Mage::helper('klarna/checkout')->setKlarnaCheckoutId($order->getLocation())) {
                $order = new Klarna_Checkout_Order($this->getKlarnaConnector());
                $order->create($klarnaData);
                $order->fetch();
                Mage::helper('klarna/checkout')->setKlarnaCheckoutId($order->getLocation());
            }

        }

        return $order['gui']['snippet'];

    }

}
