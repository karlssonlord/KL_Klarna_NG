<?php

/**
 * Class KL_Klarna_Model_KlarnaCheckout
 */
class KL_Klarna_Model_Klarnacheckout extends KL_Klarna_Model_Klarnacheckout_Abstract
{
    private $subscription;

    private $quoteId;

    private $errorEmailMessages = array();

    /**
     * @var
     */
    protected $_connector;

    protected function _construct()
    {
        $this->subscription = Mage::getModel('subscriber/subscription');
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
        if (!$this->_connector) {
            // Configure Klarna Connector
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
        $order = new Klarna_Checkout_Order($this->getKlarnaConnector(), $checkoutId);

        $order->fetch();

        if (isset($order['merchant_reference']['orderid2'])) {
            $this->quoteId = $order['merchant_reference']['orderid2'];
        }

        return $order;
    }

    /**
     * Fetch existing Klarna Order
     *
     * @return bool|Klarna_Checkout_Order
     */
    public function getExistingKlarnaOrder()
    {
        // Try to get the current klarna checkout id
        $checkoutId = Mage::helper('klarna/checkout')->getKlarnaCheckoutId();

        // If not found then log and return false
        if (!$checkoutId) {
            Mage::helper('klarna')->log('No matching checkout ID found when fetching order from Klarna.');

            return false;
        }

        try {
            Mage::helper('klarna/log')->log(
                $this->getQuote(),
                'Trying to fetch existing KCO order from Klarna using '
                . Mage::helper('klarna/checkout')->getKlarnaCheckoutId()
            );

            // Fetch the order
            $order = new Klarna_Checkout_Order($this->getKlarnaConnector(), $checkoutId);
            $order->fetch();

        } catch (Exception $e) {
            // Log the event
            Mage::helper('klarna')->log('Unable to get existing Klarna Order ID. Error received: ' . $e->getMessage());

            return false;
        }

        return $order;
    }

    /**
     * Prepare totals
     *
     * @return KL_Klarna_Model_Klarnacheckout
     */
    public function prepareTotals()
    {
        $quote = $this->getQuote();

        $quote->setTotalsCollectedFlag(false)
            ->collectTotals()
            ->save();

        // Fetch shipping address
        $shippingAddress = $quote->getShippingAddress();

        // Force country to quote if not set
        if (!$quote->getShippingAddress()->getCountryId()) {
            // Add country ID
            $shippingAddress->setCountryId($this->getCountry());
        }

        // Collect shipping rates, quote totals and save the quote
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
        // The reason we are here is because either cart has been edited so the Klarna order needs update,
        // or we haven't yet created a KCO session. In any case we need to prepare the updated cart first.
        $items = $this->prepareOrderItems();

        // Okay so now we look for an existing order over at Klarna
        $klarnaOrder = $this->getExistingKlarnaOrder();


        if ($klarnaOrder) {

            // Right, we have an order already, so I need to update this order to reflect cart changes
            $updatedOrder = $this->updateExistingOrder($klarnaOrder, $items, $this->handleRecurringOrders());

            return $this->getKlarnaHtml($updatedOrder);
        }

        // Okay we have a clean slate so start a new session over at Klarna
        $newKlarnaOrder = $this->createNewOrder($items);

        return $this->getKlarnaHtml($newKlarnaOrder);
    }

    /**
     * Acknowledge order and create it in our system
     *
     * @param $checkoutId
     * @return void
     */
    public function acknowledge($checkoutId)
    {
        $this->prepareForAcknowledgement($checkoutId);

        try {
            // Get Klarna order object
            $klarnaOrder = $this->getOrder($checkoutId);

            if ($this->orderStatusIsComplete($klarnaOrder)) {

                // Here comes the heavy lifting
                $this->handlePayment($checkoutId, $klarnaOrder);

            } else {

                $this->errorEmailMessages[] = $invalidOrderStatusMessage =
                    'Unable to acknowledge due to order status from Klarna: ' . $klarnaOrder['status'] . ' (' . $checkoutId . ')';

                Mage::helper('klarna/log')->log(null, $invalidOrderStatusMessage);

            }

        } catch (Exception $e) {

            // Remove the order lock: allow Klarna to make subsequent push retries
            Mage::getModel('klarna/pushlock')->unLock($checkoutId);

            $errorMessage = 'CheckoutId = "' . $checkoutId . '"; Cannot acknowledge: ' . $e->getMessage();
            Mage::helper('klarna')->sendErrorEmail($errorMessage);

            // Log error
            Mage::helper('klarna/log')->log(null, $errorMessage);
        }

        if ($this->containsErrorMessage()) {
            Mage::helper('klarna')->sendErrorEmail(implode("\n", $this->errorEmailMessages));
        }
    }

    /**
     * @param $items
     * @return Klarna_Checkout_Order
     */
    private function createNewOrder($items)
    {
        // Setup the create array
        $klarnaData = $this->prepareKlarnaDataObject($items);

        // Set the validation URL
        $validationUrl = Mage::getUrl('klarna/checkout/validate', array('_forced_secure' => true));

        // Make sure the link uses https, only add it to Klarna if it is
        $klarnaData = $this->addValidationCallbackUrl($validationUrl, $klarnaData);

        Mage::helper('klarna')->log($klarnaData);

        $klarnaData['gui']['options'] = array('disable_autofocus');

        // Check if we should use the mobile gui. This can only be
        // set when first creating the checkout session
        $klarnaData = $this->handleMobileGui($klarnaData);

        // Prefill information from current user
        $klarnaData = $this->prefillUserData($klarnaData);

        // Fetch empty Klarna order
        $order = $this->fetchNewKlarnaOrderInstance($klarnaData);

        // Store session ID in session
        // We also make a check for duplicated checkoutID
        if (!Mage::helper('klarna/checkout')->setKlarnaCheckoutId($order->getLocation())) {
            $order = new Klarna_Checkout_Order($this->getKlarnaConnector());
            $order->create($klarnaData);
            $order->fetch();
            Mage::helper('klarna/checkout')->setKlarnaCheckoutId($order->getLocation());
        }

        return $order;
    }

    /**
     * @param $order
     * @param $items
     * @param $recurring
     * @return bool
     */
    private function updateExistingOrder($order, $items, $recurring)
    {
        // Setup the update array
        $klarnaData = array(
            'cart' => array('items' => $items),
            'merchant_reference' => array(
                'orderid2' => $this->getQuote()->getId()
            )
        );

        if ($recurring) {
            $klarnaData['recurring'] = true;
        }

        Mage::helper('klarna')->log($klarnaData);

        try {

            $order->update($klarnaData);

            // Store session ID in session (again)
            Mage::helper('klarna/checkout')->setKlarnaCheckoutId($order->getLocation());

        } catch (Exception $e) {

            Mage::helper('klarna')->log($e->getMessage());

            // Terminate the object, this will make us create a new order
            return false;
        }

        return $order;
    }

    /**
     * @return array
     */
    private function prepareOrderItems()
    {
        $items = $this->addQuoteItems();
        $items = $this->addShippingDetails($items);
        $items = $this->handleDiscounts($items);

        return $items;
    }

    /**
     * @param $order
     * @return mixed
     */
    private function getKlarnaHtml($order)
    {
        return $order['gui']['snippet'];
    }

    /**
     * @param $items
     * @return array
     */
    private function handleDiscounts($items)
    {
        /**
         * Handle discounts
         */
        $discounts = Mage::getModel('klarna/klarnacheckout_discount')->build($this->getQuote());
        if ($discounts) {
            $items[] = $discounts;
            return $items;
        }
        return $items;
    }

    /**
     * @param $items
     * @return array
     */
    private function addShippingDetails($items)
    {
        /**
         * Add shipping method and the cost
         */
        $shipping = Mage::getModel('klarna/klarnacheckout_shipping')->build($this->getQuote());
        if ($shipping) {
            $items[] = $shipping;
            return $items;
        }
        return $items;
    }

    /**
     * @return array
     */
    private function addQuoteItems()
    {
        $items = array();

        // Add all visible items from quote
        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            $items[] = Mage::getModel('klarna/klarnacheckout_item')->build($item);
        }

        return $items;
    }

    /**
     * @param $klarnaData
     * @return mixed
     */
    private function prefillUserData($klarnaData)
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {

            //Fetch current user
            $currentUser = Mage::getSingleton('customer/session')->getCustomer();

            // Make sure the variable in the array is defined
            if (!isset($klarnaData['shipping_address'])) {
                $klarnaData['shipping_address'] = array();
            }

            // Set the e-mail
            $klarnaData['shipping_address']['email'] = $currentUser->getEmail();

            // Fetch the default shipping address
            $defaultShippingAddressId = $currentUser->getDefaultShipping();
            if ($defaultShippingAddressId) {

                // Load the address
                $defaultShippingAddress = $address = Mage::getModel('customer/address')->load(
                    $defaultShippingAddressId
                );

                // Prefill postcode
                if ($defaultShippingAddress->getPostcode()) {
                    $klarnaData['shipping_address']['postal_code'] = $defaultShippingAddress->getPostcode();
                }
            }

            // Prefill using test credentials if it's a test environment
            if (!Mage::helper('klarna')->isLive()) {
                $klarnaData['shipping_address']['email'] = 'checkout-se@testdrive.klarna.com';
                $klarnaData['shipping_address']['postal_code'] = '12345';
            }
        }

        return $klarnaData;
    }

    /**
     * @param $klarnaData
     * @return mixed
     */
    private function handleMobileGui($klarnaData)
    {
        if ($this->useMobileGui()) {
            $klarnaData['gui']['layout'] = 'mobile';
        }

        return $klarnaData;
    }

    /**
     * @param $klarnaData
     * @return Klarna_Checkout_Order
     */
    private function fetchNewKlarnaOrderInstance($klarnaData)
    {
        $order = new Klarna_Checkout_Order($this->getKlarnaConnector());

        // Create the order
        $order->create($klarnaData);

        // Fetch from Klarna
        $order->fetch();

        return $order;
    }

    /**
     * @param $items
     * @return array
     */
    private function prepareKlarnaDataObject($items)
    {
        $klarnaData = array(
            'recurring' => (boolean)$this->getQuote()->getIsSubscription(),
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
Mage::log($klarnaData, null, 'kl_klarna.log', true);
        return $klarnaData;
    }

    /**
     * @param $validationUrl
     * @param $klarnaData
     * @return mixed
     */
    private function addValidationCallbackUrl($validationUrl, $klarnaData)
    {
        if (substr($validationUrl, 0, 5) == 'https') {

            $klarnaData['merchant']['validation_uri'] = $validationUrl;
        }

        return $klarnaData;
    }

    /**
     * @param $order
     * @return bool
     */
    private function orderStatusIsComplete($order)
    {
        Mage::log(var_export($order, true), null, 'kl_klarna.log', true);

        return $order['status'] == 'checkout_complete';
    }

    /**
     * @param $magentoOrder
     * @return bool
     */
    private function orderNotFound($magentoOrder)
    {
        return !$magentoOrder || !$magentoOrder->getId();
    }

    /**
     * @param $magentoOrder
     * @return bool
     */
    private function orderIsLoaded($magentoOrder)
    {
        return $magentoOrder && $magentoOrder->getId();
    }

    /**
     * @param $order
     * @return float
     */
    private function getTotalAmountFrom($order)
    {
        return $order['cart']['total_price_including_tax'] / 100;
    }

    /**
     * @param $magentoOrder
     * @return Exception
     */
    private function sendOrderEmail($magentoOrder)
    {
        try {
            // Send new order e-mail
            $magentoOrder->sendNewOrderEmail();

        } catch (Exception $e) {

            $failedMessage = 'Unable to send new order email (' . $e->getMessage() . '), Magento ID ' .
                $magentoOrder->getIncrementId();
            $this->errorEmailMessages[] = $failedMessage;

            Mage::helper('klarna/log')->log($magentoOrder->getQuote(), $failedMessage, true);
        }
    }

    /**
     * @param $magentoOrder
     * @param $klarnaOrder
     */
    private function exportMagentoOrderIncrementToKlarnaOrder($magentoOrder, $klarnaOrder)
    {
        $updateData = array(
            'status' => 'created',
            'merchant_reference' => array(
                'orderid1' => $magentoOrder->getIncrementId(),
            )
        );

        $klarnaOrder->update($updateData);
    }

    /**
     * @param $magentoOrder
     * @param $orderStatus
     */
    private function setOrderStatusOnMagentoOrder($magentoOrder, $orderStatus)
    {
        $magentoOrder
            ->setState('processing')
            ->setStatus($orderStatus);

        $magentoOrder->save();
    }

    /**
     * @param $checkoutId
     * @return mixed
     */
    private function getMagentoOrderByKlarnaId($checkoutId)
    {
        // Precaution: Check if the order exists
        $magentoOrder = Mage::getModel('klarna/klarnacheckout_order')
            ->loadByCheckoutId($checkoutId);

        // What to do if the order exists
        if (!$this->orderNotFound($magentoOrder)) {
            Mage::helper('klarna/log')->log(null, '[' . $checkoutId . '] Existing order found for checkout id');
            return $magentoOrder;
        }

        Mage::helper('klarna/log')->log(
            null,
            '[' . $checkoutId . '] No previous order found, trying to create...'
        );

        // Try to create the order if it was not found
        $magentoOrder = Mage::getModel('klarna/klarnacheckout_order')->create($checkoutId);

        return $magentoOrder;
    }

    /**
     * @param $magentoOrder
     */
    private function setMagentoOrderStatus($magentoOrder)
    {
        // Fetch order status from config
        $orderStatus = Mage::helper('klarna')->getConfig(
            'acknowledged_order_status',
            'klarna_checkout'
        );

        // Log what status and state we're setting
        Mage::helper('klarna/log')->log(
            $magentoOrder->getQuote(),
            'Setting processing/' . $orderStatus . ' on Magento ID ' . $magentoOrder->getIncrementId()
        );

        // Configure and save the order
        $this->setOrderStatusOnMagentoOrder($magentoOrder, $orderStatus);
    }

    /**
     * @param $checkoutId
     * @param $klarnaOrder
     * @return string
     */
    private function handlePayment($checkoutId, $klarnaOrder)
    {
        // Fetch magento order
        $magentoOrder = $this->getMagentoOrderByKlarnaId($checkoutId);

        // Is there a matching Magento order?
        if (!$this->orderIsLoaded($magentoOrder)) {
            return $this->logMissingOrderMessage($checkoutId);
        }

        // Set the payment information
        $magentoOrder
            ->getPayment()
            ->setMethod('klarna_checkout')
            ->setAdditionalInformation(
                array(
                    'klarnaCheckoutId' => $checkoutId,
                    'orderInfo' => $klarnaOrder->marshal()
                )
            )
            ->setTransactionId($checkoutId)
            ->setIsTransactionClosed(0)
            ->save();

        // Handle Magento payment procedure
        $amountAuthorized = $this->getTotalAmountFrom($klarnaOrder);

        $this->makeMagentoPayment($klarnaOrder, $magentoOrder, $amountAuthorized);

        if ($this->orderIsRecurring($klarnaOrder->marshal())) {
            // Someone wants to become a subscriber. Tell the world!
            Mage::dispatchEvent('recurring_order_was_created', array(
                    'checkout_id' => $checkoutId,
                    'recurring_token' => $klarnaOrder['recurring_token'],
                    'order_id' => $magentoOrder->getId(),
                    'quote_id' => $this->quoteId,
                    'klarna_data' => $klarnaOrder->marshal()
                )
            );
        }

        // Send order email
        // TODO: fire event and move this logic elsewhere
        $this->sendOrderEmail($magentoOrder);

        Mage::helper('klarna/log')->log(
            json_encode($magentoOrder->getQuote()),
            'Order acknowledged, Magento ID ' . $magentoOrder->getIncrementId(),
            true
        );
    }

    /**
     * @param $checkoutId
     */
    private function prepareForAcknowledgement($checkoutId)
    {
        // Make a note in the logs
        Mage::helper('klarna/log')->log(null, '[' . $checkoutId . '] Acknowledge method called for checkout id');

        // Avoid timeouts in PHP to allow the script to finish
        set_time_limit(0);
    }

    /**
     * @return bool
     */
    private function containsErrorMessage()
    {
        return !empty($this->errorEmailMessages);
    }

    /**
     * @param $klarnaOrder
     * @param $magentoOrder
     * @param $amountAuthorized
     */
    private function makeMagentoPayment($klarnaOrder, $magentoOrder, $amountAuthorized)
    {
        // Authorize
        $magentoOrder->getPayment()->authorize($magentoOrder->getPayment(), $amountAuthorized);

        // Update Magento order status
        $this->setMagentoOrderStatus($magentoOrder);

        // Set order increment on Klarna order
        $this->exportMagentoOrderIncrementToKlarnaOrder($magentoOrder, $klarnaOrder);
    }

    /**
     * @param $checkoutId
     */
    private function logMissingOrderMessage($checkoutId)
    {
        $missingOrderMessage = 'Unable to acknowledge due to missing order in Magento. (' . $checkoutId . ')';

        $this->errorEmailMessages[] = $missingOrderMessage;

        Mage::helper('klarna/log')->log(
            null,
            $missingOrderMessage
        );
    }

    /**
     * @return bool
     */
    private function handleRecurringOrders()
    {
        if ($this->getQuote()->getIsSubscription()) {
            return true;
        }

        return false;
    }

    /**
     * @param $klarnaOrder
     * @return bool
     */
    private function orderIsRecurring($klarnaOrder)
    {
        Mage::log('This token is set here: '.$klarnaOrder['recurring_token'], null, 'subscriber.log', true);

        return isset($klarnaOrder['recurring_token']);
    }

}