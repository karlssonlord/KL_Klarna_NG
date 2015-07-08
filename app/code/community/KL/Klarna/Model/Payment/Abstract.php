<?php

class KL_Klarna_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract {

    /**
     * Unique internal payment method identifier
     *
     * @var string [a-z0-9_]
     */
    protected $_code = null;

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway = true;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize = true;

    /**
     * Can capture funds online?
     */
    protected $_canCapture = true;

    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial = true;

    /**
     * Can refund online?
     */
    protected $_canRefund = true;

    /**
     * Can refund partial
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Can void transactions online?
     */
    protected $_canVoid = false;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal = false;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout = true;

    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping = false;

    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;

    /**
     * @var
     */
    protected $data;

    /**
     * Store data from frontend in the database
     *
     * @param mixed $data
     *
     * @return Mage_Payment_Model_Info|void
     */
    public function assignData($data)
    {
        $this->data = $data;

        /**
         * Make sure it's an Varien_Object
         */
        if ( ! ($this->data instanceof Varien_Object) ) {
            $this->data = new Varien_Object($this->data);
        }

        /**
         * Fetch the info instance
         */
        $info = $this->getInfoInstance();

        /**
         * Convert data to be stored as an array
         */
        $additionalInformation = $this->data->getData();

        /**
         * Sanitize the data
         */
        $additionalInformation = Mage::helper('klarna/sanitize')->arr($additionalInformation);

        /**
         * Store in database
         */
        $info
            ->setAdditionalInformation($additionalInformation);
    }

    /**
     * Validate payment method information object
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        /**
         * Fetch information about the payment data
         */
        $paymentInfo = $this->getInfoInstance();

        /**
         * Fetch additional information
         */
        $additionalInformation = $paymentInfo->getAdditionalInformation();

        /**
         * Get the social security number
         */
        $socialSecurityNumber = null;
        if ( isset($additionalInformation[$this->getCode() . '_ssn']) ) {
            $socialSecurityNumber = $additionalInformation[$this->getCode() . '_ssn'];
        }

        /**
         * Make sure social security number is set
         */
        if ( ! $socialSecurityNumber && ! Mage::app()->getRequest()->getParam('klarna_invoice_ssn', false) === false ) {
            Mage::helper('klarna')->log('Social security number not set.');
            Mage::throwException(Mage::helper('klarna')->__('Social security number not set.'));
        }

        /**
         * Make sure payment option is availibe for their country
         */
        if ( $paymentInfo instanceof Mage_Sales_Model_Order_Payment ) {
            $billingCountry = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
        }

        /**
         * Fetch countries where the Klarna is enabled in
         */
        $enabledCountries = Mage::helper('klarna')->getConfig('countries', 'klarna');
        $enabledCountries = explode(',', $enabledCountries);

        /**
         * Make sure payment option is enabled for this country
         */
        if ( ! in_array($billingCountry, $enabledCountries) ) {
            Mage::throwException(Mage::helper('klarna')->__('This payment option is not available for this country.'));
        }

        return $this;
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
         * Fetch information about the payment data
         */
        $paymentInfo = $this->getInfoInstance();

        /**
         * Fetch additional information
         */
        $additionalInformation = $paymentInfo->getAdditionalInformation();

        /**
         * Get the social security number
         */
        $socialSecurityNumber = null;
        if ( isset($additionalInformation[$this->getCode() . '_ssn']) ) {
            $socialSecurityNumber = $additionalInformation[$this->getCode() . '_ssn'];
        }

        /**
         * Make sure it's still there
         */
        if ( ! $socialSecurityNumber ) {
            Mage::throwException(
                Mage::helper('klarna')->__('Social security number not set')
            );
        }

        /**
         * Set the right pclass
         */
        $pclass = KlarnaPClass::INVOICE;
        if ( isset($additionalInformation[$this->getCode() . '_pclass']) ) {
            $pclass = $additionalInformation[$this->getCode() . '_pclass'];
        }

        /**
         * Get the Magento order
         */
        $order = $payment->getOrder();

        /**
         * Get a new Klarna instance
         */
        $klarnaOrderApi = Mage::getModel('klarna/api_order');

        /**
         * Populare Klarna order object
         */
        $klarnaOrderApi->populateFromOrder($order);

        /**
         * Create reservation
         */
        $return = $klarnaOrderApi->createReservation($socialSecurityNumber, $order->getBaseTotalDue(), $pclass);

        /**
         * Since we're here everything went just fine!
         */
        $transactionId = $return[0];

        /**
         * Set Magento payment method transaction
         */
        $payment
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(0);

        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $authTrans = $payment->getAuthorizationTransaction();

        /**
         * Load up the order object, to retrieve the store scope
         */
        $order = $payment->getOrder();
        $order->load($order->getId());

        /**
         * Get a new Klarna instance
         */
        $klarnaOrderApi = Mage::getModel('klarna/api_order', array('store_id' => $order->getStoreId()));

        /**
         * Make sure the state is correct
         */
        $orderStatus = $klarnaOrderApi->checkOrderStatus($authTrans->getTxnId());
        if ( $orderStatus != KlarnaFlags::ACCEPTED ) {

            Mage::helper('klarna')->log(
                'Not activated. Tried to activate reservation "' . $authTrans->getTxnId() . '" but it had order status ' . $orderStatus
            );

            throw new Exception('Order not accepted at Klarna yet');
        }

        /**
         * Activate invoice
         */
        $result = $klarnaOrderApi->activateReservation($authTrans->getTxnId());
        $invoiceNumber = $result[1];

        /**
         * Update payment
         */
        $payment
            ->setAdditionalInformation('klarna_invoice_no', $invoiceNumber);

        /**
         * Send by e-mail
         */
        if ( Mage::helper('klarna')->getConfig('emailinvoice') ) {
            $result = $klarnaOrderApi
                ->emailInvoice($invoiceNumber);

            if ( $result ) {
                $payment->setAdditionalInformation('emailed', 'success');
            } else {
                $payment->setAdditionalInformation('emailed', 'failure');
            }
        }

        /**
         * Send by postal
         */
        if ( Mage::helper('klarna')->getConfig('postalinvoice') ) {
            $result = $klarnaOrderApi
                ->postalInvoice($invoiceNumber);

            if ( $result ) {
                $payment->setAdditionalInformation('posted', 'success');
            } else {
                $payment->setAdditionalInformation('posted', 'failure');
            }
        }

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /**
         * Make sure we can refund
         */
        parent::refund($payment, $amount);

        /**
         * Get a new Klarna instance
         */
        $klarnaOrderApi = Mage::getModel('klarna/api_order');

        /**
         * Fetch Klarna Invoice No from additional information field
         */
        $klarnaInvoiceNumber = $payment->getAdditionalInformation('klarna_invoice_no');

        /**
         * Perform the refund
         */
        $refundInvoiceId = $klarnaOrderApi->createRefund($amount, $klarnaInvoiceNumber);

        /**
         * Add comment to the order
         */
        $payment->getOrder()
            ->addStatusHistoryComment('Klarna refund successful, Klarna refund invoice id: ' . $refundInvoiceId)
            ->save();

        return $this;
    }


    /**
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        /**
         * Get authorization transaction
         */
        $authTrans = $payment->getAuthorizationTransaction();

        /**
         * Get a new Klarna instance
         */
        $klarnaOrderApi = Mage::getModel('klarna/api_order');

        /**
         * Cancel reservation
         */
        $result = $klarnaOrderApi->cancelReservation($authTrans->getTxnId());

        return $this;
    }

}