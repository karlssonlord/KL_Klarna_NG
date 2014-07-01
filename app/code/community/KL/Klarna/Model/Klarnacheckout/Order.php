<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Order
 */
class KL_Klarna_Model_Klarnacheckout_Order extends KL_Klarna_Model_Klarnacheckout_Abstract {

    /**
     * @var
     */
    protected $_model;

    /**
     * @var Mage_Customer_Model_Session
     */
    protected $_customerSession;

    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $_checkoutSession;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_klarnacheckout = Mage::getModel('klarna/klarnacheckout');
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
        $this->_customerSession = Mage::getSingleton('customer/session');
    }

    public function create()
    {
        /**
         * Fetch checkout ID from session
         */
        $checkoutId = Mage::helper('klarna/checkout')->getKlarnaCheckoutId();

        /**
         * Make sure it was found
         */
        if ( ! $checkoutId ) {
            throw new Exception('No checkout ID exists (at create method)');
        }

        /**
         * Look for orders with the same checkout id
         */
        $magentoOrderSearch = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('klarna_checkout', $checkoutId)
            ->getFirstItem();

        /**
         * Make sure nothing was found
         */
        if ( $magentoOrderSearch->getId() ) {
            throw new Exception('Order with checkout ID "' . $checkoutId . '" already exists (at create method)');
        }

        /**
         * Fetch the order from Klarna
         */
        $order = $this->_klarnacheckout->getOrder($checkoutId);

        /**
         * Fetch quote
         */
        $quote = Mage::getModel('sales/quote')
            ->getCollection()
            ->addFieldToFilter('klarna_checkout', $checkoutId)
            ->addFieldToFilter('is_active', 1)
            ->getFirstItem();

        /**
         * Make sure quote was found
         */
        if ( $quote->getId() ) {

            /**
             * Make a notice in the log
             */
            Mage::helper('klarna')->log('Creating order at success page from quote id ' . $quote->getId());

            /**
             * Convert our total amount the Klarna way
             */
            $quoteTotal = intval($quote->getGrandTotal() * 100);

            /**
             * Make a note about the amounts
             */
            Mage::helper('klarna')->log(
                'Comparing amount quote:' . $quoteTotal . ' and Klarna ' . $order['cart']['total_price_including_tax'] . ' for quote id ' . $quote->getId(
                )
            );

            /**
             * Amount matches, update the quote
             */
            Mage::helper('klarna')->log('Amount matches. Configuring quote with data from Klarna');

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
             * Fetch delivery instructions and door code
             */
            $doorCode = $quote->getShippingAddress()->getData('door_code');
            $deliveryInstructions = $quote->getShippingAddress()->getData('delivery_instructions');

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
                ->setData('door_code', $doorCode)
                ->setData('delivery_instructions', $deliveryInstructions)
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
                ->setData('door_code', $doorCode)
                ->setData('delivery_instructions', $deliveryInstructions)
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

            /**
             * Assign customer object
             */
            $quote
                ->setCustomerFirstname($order['shipping_address']['given_name'])
                ->setCustomerLastname($order['shipping_address']['family_name'])
                ->setCustomerEmail($order['shipping_address']['email'])
                ->save();

            /**
             * Collect totals once more
             */
            $quote
                ->collectTotals()
                ->setIsActive(0)
                ->save();

            /**
             * Feed quote object into sales model
             */
            $service = Mage::getModel('sales/service_quote', $quote);

            /**
             * Submit the quote and generate order
             */
            $service->submitAll();

            $this->_checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();

            /**
             * Fetch the Magento Order
             */
            $magentoOrder = $service->getOrder();

            if ( $magentoOrder ) {

                Mage::dispatchEvent(
                    'checkout_type_onepage_save_order_after',
                    array('order' => $magentoOrder, 'quote' => $quote)
                );

                /**
                 * Make sure delivery instructions and door code is set
                 */
                $magentoOrder->getBillingAddress()
                    ->setData('door_code', $doorCode)
                    ->setData('delivery_instructions', $deliveryInstructions)
                    ->save();

                $magentoOrder->getShippingAddress()
                    ->setData('door_code', $doorCode)
                    ->setData('delivery_instructions', $deliveryInstructions)
                    ->save();

                /**
                 * Add order information to the session
                 */
                $this->_checkoutSession
                    ->setLastOrderId($magentoOrder->getId())
                    ->setLastRealOrderId($magentoOrder->getIncrementId());

            }

            /**
             * Configure and save the order
             */
            $magentoOrder
                ->setState('pending')
                ->setStatus('pending');

            /**
             * Save the order
             */
            $magentoOrder->save();

            /**
             * Add order information to the session
             */
            Mage::getSingleton('checkout/session')
                ->setLastOrderId($magentoOrder->getId())
                ->setLastRealOrderId($magentoOrder->getIncrementId());

            /**
             * Add recurring profiles information to the session
             */
            $profiles = $service->getRecurringPaymentProfiles();
            if ( $profiles ) {
                $ids = array();
                foreach ($profiles as $profile) {
                    $ids[] = $profile->getId();
                }
                $this->_checkoutSession->setLastRecurringProfileIds($ids);
            }

            Mage::dispatchEvent(
                'checkout_submit_all_after',
                array('order' => $magentoOrder, 'quote' => $quote, 'recurring_profiles' => $profiles)
            );

            return $magentoOrder;

        } else {

            // Unable to find a matching quote!!!
            Mage::helper('klarna')->log('Unable to find matching quote when about to create Klarna order');

            return false;

        }

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
            $order = $this->_klarnacheckout->getOrder($checkoutId);
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

        /**
         * Try to cancel the reservation
         */
        try {

            /**
             * Load the Klarna API library
             */
            $klarnaApi = Mage::getModel('klarna/api_order');

            /**
             * Cancel the reservation
             */
            $result = $klarnaApi->cancelReservation($reservationNumber);

            Mage::helper('klarna')->log(
                'Reservation (' . $checkoutId . ') canceled at abortCreate with result: ' . $result
            );

            return true;

        } catch (Exception $e) {

            /**
             * Log the event
             */
            Mage::helper('klarna')->log(
                'Reservation (' . $checkoutId . ') NOT canceled at abortCreate: ' . $e->getMessage()
            );

            return false;

        }

    }

}
