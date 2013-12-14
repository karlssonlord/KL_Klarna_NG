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
    protected $_canCapturePartial = false;

    /**
     * Can refund online?
     */
    protected $_canRefund = false;

    /**
     * Can void transactions online?
     */
    protected $_canVoid = true;

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

    protected $data;

    /**
     * Store data from frontend in the database
     *
     * @param mixed $data
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
        foreach ($additionalInformation as $key => $value) {
            $info
                ->unsAdditionalInformation($key)
                ->setAdditionalInformation($key, $value);
        }

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
        if ( ! $socialSecurityNumber ) {
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
         * Fetch countries where the payment module is enabled in
         */
        $enabledCountries = Mage::helper('klarna')->getConfig('countries', $this->getCode());
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
     * @return Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        error_reporting(2047);
        ini_set('display_errors', 'on');


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
         * Fetch payment method invoice fee
         */
        $fee = Mage::helper('klarna')->getConfig('fee', $this->getCode());

        /**
         * Fetch the quote
         */
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        /**
         * Add invoice fee to quote
         */
        $quote->setData('klarna_fee', $fee);

        /**
         * Update "grand_total"
         */
        $quote->setData('grand_total', ($quote->getData('grand_total') + $fee));

        /**
         * Save quote
         */
        $quote->save();

        /**
         * Fetch the order
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
        $return = $klarnaOrderApi->createReservation($socialSecurityNumber);

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

        Mage::log('Sparade trans');

        return $this;
    }

}