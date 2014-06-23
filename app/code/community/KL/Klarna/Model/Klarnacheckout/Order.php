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
            throw new Exception('No checkout ID exists');
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
                'Comparing amount quote:' . $quoteTotal . ' and Klarna ' . $order['cart']['total_price_including_tax'] .  ' for quote id ' . $quote->getId()
            );

            /**
             * Compare the total amounts
             */
            if ( $quoteTotal == $order['cart']['total_price_including_tax'] ) {

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
                if ($profiles) {
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
            }

        } else {

            // Unable to find a matching quote!!!
            Mage::helper('klarna')->log('Unable to find matching quote when about to create Klarna order for quote id ' . $quote->getId());

            return false;

        }

    }

}
