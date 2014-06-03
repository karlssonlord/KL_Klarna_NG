<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Order
 */
class KL_Klarna_Model_Klarnacheckout_Order extends KL_Klarna_model_KlarnaCheckout_Abstract {

    /**
     * @var
     */
    protected $_model;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_klarnacheckout = Mage::getModel('klarna/klarnacheckout');
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
        if (!$checkoutId) {
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
                'Comparing amount quote:' . $quoteTotal . ' and Klarna ' . $order['cart']['total_price_including_tax']
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

                $quote
                    ->collectTotals()
                    ->save();

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
                    ->setState('pending')
                    ->setStatus('pending');

                /**
                 * Save the order
                 */
                $magentoOrder->save();

                /**
                 * Reset the Magento quote
                 */
                Mage::getSingleton('checkout/session')->setQuoteId(null);

                /**
                 * Reset the Magento quote of the current user
                 */
                Mage::getSingleton('checkout/session')->setQuoteId(null);

                return $magentoOrder;
            }

        } else {

            // Unable to find a matching quote!!!
            throw new Exception('Unable to find matching quote when about to create Klarna order');

        }

    }

}
