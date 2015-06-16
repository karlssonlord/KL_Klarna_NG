<?php

class KL_Klarna_Model_Klarnacheckout_Order
{
    const ORDER_STATUS = 'pending';

    const ORDER_STATE = 'pending';

    protected $checkoutId;

    /**
     * @var KL_Klarna_Model_Klarnacheckout
     */
    protected $klarnaCheckout;

    /**
     * @var Mage_Customer_Model_Session
     */
    protected $customerSession;

    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $checkoutSession;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->klarnaCheckout = Mage::getModel('klarna/klarnacheckout');
        $this->checkoutSession = Mage::getSingleton('checkout/session');
        $this->customerSession = Mage::getSingleton('customer/session');
    }

    /**
     * @param $checkoutId
     * @return mixed
     */
    public function loadByCheckoutId($checkoutId)
    {
        return Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('klarna_checkout', $checkoutId)
            ->getFirstItem();
    }

    /**
     * @param null $checkoutId
     * @return bool
     * @throws Exception
     */
    public function create($checkoutId = null)
    {
        $this
            ->validateCheckoutId($checkoutId)
            ->validateOrderNotExists()
        ;

        /** Fetch the order from Klarna */
        $klarnaOrder = $this->klarnaCheckout->getOrder($this->checkoutId);

        /** Get the corresponding Magento quote */
        $quote = $this->loadQuoteByKlarnaOrder($klarnaOrder);

        if (!$this->quoteWasFound($quote)) {
            return $this->handleMissingQuote();
        }

        $this->updateQuoteWithKlarnaDetails($quote, $klarnaOrder);

        return $this->convertToOrder($quote);
    }

    /**
     * Abort the creation of the order and cancel the reservation
     *
     * @return bool
     */
    public function abortCreate()
    {
        /**
         * Fetch checkout ID from session
         */
        $checkoutId = Mage::helper('klarna/checkout')->getKlarnaCheckoutId();

        /**
         * Make sure it was found
         */
        if ( ! $checkoutId ) {
            Mage::helper('klarna')->log('Unable to cancel reservation at abortCreate due to missing checkout id');
            return false;
        }

        /**
         * Fetch the order from Klarna
         */
        try {
            $order = $this->klarnaCheckout->getOrder($checkoutId);
        } catch (Exception $e) {
            Mage::helper('klarna')->log(
                'Unable to cancel reservation at abortCreate due to missing reservation number. Exception: ' . $e->getMessage(
                )
            );
            return false;
        }

        /**
         * Check for reservation number
         */
        $reservationNumber = $order['reservation'];

        /**
         * Make sure it was found
         */
        if ( ! $reservationNumber ) {
            Mage::helper('klarna')->log(
                'Unable to cancel reservation at abortCreate due to missing reservation number'
            );
            return false;
        }

        /** Try to cancel the reservation */
        try {
            /** Load the Klarna API library */
            $klarnaApi = Mage::getModel('klarna/api_order');

            /** Cancel the reservation */
            $result = $klarnaApi->cancelReservation($reservationNumber);

            Mage::helper('klarna')->log('Reservation (' . $checkoutId . ') canceled at abortCreate with result: ' . $result);

            /** Reset the checkout ID to prevent any other problems */
            Mage::helper('klarna/checkout')->setKlarnaCheckoutId('');

            return true;

        } catch (Exception $e) {

            Mage::getSingleton('checkout/session')->addError(Mage::helper('klarna')->__('Unable to checkout order. Please try again or contact customer services.'));

            Mage::helper('klarna')->log(
                'Reservation (' . $checkoutId . ') NOT canceled at abortCreate: ' . $e->getMessage()
            );

            /** Reset the checkout ID to prevent any other problems */
            Mage::helper('klarna/checkout')->setKlarnaCheckoutId('');

            return false;
        }

    }

    /**
     * @param $quote
     * @param $klarnaOrder
     */
    protected function updateQuoteWithKlarnaDetails(Mage_Sales_Model_Quote $quote, $klarnaOrder)
    {
        /** Build shipping address */
        $shippingAddress = array();
        if (isset($klarnaOrder['shipping_address']['care_of'])) {
            $shippingAddress[] = $klarnaOrder['shipping_address']['care_of'];
        }
        $shippingAddress[] = $klarnaOrder['shipping_address']['street_address'];
        $shippingAddress = implode("\n", $shippingAddress);

        /** Reconfigure the shipping address */
        $quote->getShippingAddress()
            ->setFirstname($klarnaOrder['shipping_address']['given_name'])
            ->setLastname($klarnaOrder['shipping_address']['family_name'])
            ->setStreet($shippingAddress)
            ->setPostcode($klarnaOrder['shipping_address']['postal_code'])
            ->setCity($klarnaOrder['shipping_address']['city'])
            ->setCountryId(strtoupper($klarnaOrder['shipping_address']['country']))
            ->setEmail($klarnaOrder['shipping_address']['email'])
            ->setTelephone($klarnaOrder['shipping_address']['phone'])
            ->setSameAsBilling(0)
            ->setData('door_code', $quote->getShippingAddress()->getData('door_code'))
            ->setData('delivery_instructions', $quote->getShippingAddress()->getData('delivery_instructions'))
            ->save();

        /** Build billing address */
        $billingAddress = array();
        if (isset($klarnaOrder['billing_address']['care_of'])) {
            $billingAddress[] = $klarnaOrder['billing_address']['care_of'];
        }
        $billingAddress[] = $klarnaOrder['billing_address']['street_address'];
        $billingAddress = implode("\n", $billingAddress);

        /** Reconfigure the billing address */
        $quote->getBillingAddress()
            ->setFirstname($klarnaOrder['shipping_address']['given_name'])
            ->setLastname($klarnaOrder['shipping_address']['family_name'])
            ->setStreet($billingAddress)
            ->setPostcode($klarnaOrder['shipping_address']['postal_code'])
            ->setCity($klarnaOrder['shipping_address']['city'])
            ->setCountryId(strtoupper($klarnaOrder['shipping_address']['country']))
            ->setEmail($klarnaOrder['shipping_address']['email'])
            ->setTelephone($klarnaOrder['shipping_address']['phone'])
            ->setSameAsBilling(0)
            ->setData('door_code', $quote->getShippingAddress()->getData('door_code'))
            ->setData('delivery_instructions', $quote->getShippingAddress()->getData('delivery_instructions'))
            ->save();

        /** Set payment information */
        $quote
            ->getPayment()
            ->setMethod('klarna_checkout')
            ->setAdditionalInformation(array('klarnaCheckoutId' => $this->checkoutId))
            ->setTransactionId($this->checkoutId)
            ->setIsTransactionClosed(0)
            ->save();

        /** Assign customer object */
        $quote
            ->setCustomerFirstname($klarnaOrder['shipping_address']['given_name'])
            ->setCustomerLastname($klarnaOrder['shipping_address']['family_name'])
            ->setCustomerEmail($klarnaOrder['shipping_address']['email'])
            ->save();

        /** Collect totals once more */
        $quote
            ->collectTotals()
            ->setIsActive(0)
            ->save();
    }

    /**
     * @param $order
     * @return mixed
     */
    protected function loadQuoteByKlarnaOrder($order)
    {
        /** Fetch quote */
        $quote = Mage::getModel('sales/quote')
            ->getCollection()
            ->addFieldToFilter('klarna_checkout', $this->checkoutId)
            ->getFirstItem();


        /** Make a notice in the log */
        Mage::helper('klarna/log')->log($quote, 'Create order', true);

        /** Convert our total amount the Klarna way */
        $quoteTotal = intval($quote->getGrandTotal() * 100);

        /** Make a note about the amounts */
        Mage::helper('klarna/log')->log(
            $quote,
            'Comparing amount quote:' . $quoteTotal . ' and Klarna ' . $order['cart']['total_price_including_tax']
        );

        return $quote;
    }

    /**
     * @throws Exception
     */
    protected function validateOrderNotExists()
    {
        /** Look for orders with the same checkout id */
        $magentoOrderSearch = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('klarna_checkout', $this->checkoutId)
            ->getFirstItem();

        /** Make sure nothing was found */
        if ($magentoOrderSearch->getId()) {
            throw new Exception('Order with checkout ID "' . $this->checkoutId . '" already exists (at create method)');
        }
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return mixed
     */
    protected function convertToOrder(Mage_Sales_Model_Quote $quote)
    {
        /**
         * Collect totalts
         */
        $quote
            ->collectTotals()
            ->setIsActive(0)
            ->save();

        /** Convert quote to order */
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();

        $this->checkoutSession
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();

        /** Fetch the Magento Order */
        $magentoOrder = $service->getOrder();

        if ($magentoOrder) {

            Mage::dispatchEvent(
                'checkout_type_onepage_save_order_after',
                array('order' => $magentoOrder, 'quote' => $quote)
            );

            /** Make sure delivery instructions and door code is set */
            $magentoOrder->getBillingAddress()
                ->setData('door_code', $quote->getShippingAddress()->getData('door_code'))
                ->setData('delivery_instructions', $quote->getShippingAddress()->getData('delivery_instructions'))
                ->save();

            $magentoOrder->getShippingAddress()
                ->setData('door_code', $quote->getShippingAddress()->getData('door_code'))
                ->setData('delivery_instructions', $quote->getShippingAddress()->getData('delivery_instructions'))
                ->save();

            /** Add order information to the session */
            $this->checkoutSession
                ->setLastOrderId($magentoOrder->getId())
                ->setLastRealOrderId($magentoOrder->getIncrementId());
        }

        $magentoOrder
            ->setState(self::ORDER_STATE)
            ->setStatus(self::ORDER_STATUS);

        $magentoOrder->save();

        /** Add order information to the session */
        Mage::getSingleton('checkout/session')
            ->setLastOrderId($magentoOrder->getId())
            ->setLastRealOrderId($magentoOrder->getIncrementId());

        /** Add recurring profiles information to the session */
        $profiles = $service->getRecurringPaymentProfiles();
        if ($profiles) {
            $ids = array();
            foreach ($profiles as $profile) {
                $ids[] = $profile->getId();
            }
            $this->checkoutSession->setLastRecurringProfileIds($ids);
        }

        Mage::dispatchEvent(
            'checkout_submit_all_after',
            array('order' => $magentoOrder, 'quote' => $quote, 'recurring_profiles' => $profiles)
        );

        return $magentoOrder;
    }

    /**
     * @param $checkoutId
     * @return $this
     * @throws Exception
     */
    protected function validateCheckoutId($checkoutId)
    {
        $this->checkoutId = $checkoutId;
        if (!$this->checkoutId) {
            $this->checkoutId = Mage::helper('klarna/checkout')->getKlarnaCheckoutId();
        }

        if (!$this->checkoutId) {
            throw new Exception('No checkout ID exists (at create method)');
        }

        Mage::helper('klarna')->log($this->checkoutId, true);
        return $this;
    }

    /**
     * @return bool
     */
    protected function handleMissingQuote()
    {
        Mage::helper('klarna')->log('Unable to find matching quote when about to create Klarna order');
        return false;
    }

    /**
     * @param $quote
     * @return bool
     */
    protected function quoteWasFound($quote)
    {
        return $quote && $quote->getId();
    }

}
