<?php

class KL_Klarna_Model_Payment_Checkout extends KL_Klarna_Model_Payment_Abstract {

    /**
     * @var string
     */
    protected $_code = 'klarna_checkout';

    /**
     * @var string
     */
    #protected $_formBlockType = 'klarna/partpayment_form';

    /**
     * @var string
     */
    protected $_infoBlockType = 'klarna/checkout_info';

    /**
     * Validation
     *
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        return $this;
    }

    /**
     * Check if payment method is available
     *
     * @param string $quote
     *
     * @return bool
     */
    public function isAvailable($quote = '')
    {
        return true;
    }

    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this|Mage_Payment_Model_Abstract
     *
     * @throws Exception
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        /**
         * Fetch the order
         */
        $order = $payment->getOrder();

        /**
         * Store reservation number
         */
        $klarnaCheckoutID = $order->getKlarnaCheckout();

        /**
         * Fetch the order
         */
        $klarnaOrder = Mage::getModel('klarna/klarnacheckout')->getOrder($klarnaCheckoutID);

        /**
         * Fetch the reservation number
         */
        $transactionId = $klarnaOrder['reservation'];

        /**
         * Set Magento payment method transaction
         */
        $payment
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(0);

        Mage::log("Auth: " . $amount . ", " . $transactionId, null, 'klarna.log', true);

        return $this;
    }

    public function l($a)
    {
        Mage::log($a, null, 'klarna.log', true);
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        /**
         * Get authorization transaction
         */
        $authTrans = $payment->getAuthorizationTransaction();

        /**
         * Fetch Klarna API Order model
         */
        $apiModel = Mage::getModel('klarna/api_order');

        /**
         * Activate whole invoice at Klarna
         */
        $apiModel->activateReservation($authTrans->getTxnId());

        return $this;
    }

    public function refund(Varien_Object $payment, $amount)
    {


        throw new Exception('Foo bared');

        die('foo');

        return 0.00;
    }

}