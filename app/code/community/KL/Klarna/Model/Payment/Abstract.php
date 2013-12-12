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
         * Check the social security number
         */
        $socialSecurityNumber = null;
        if (isset($additionalInformation[$this->getCode() . '_ssn'])) {
            $socialSecurityNumber = $additionalInformation[$this->getCode() . '_ssn'];
        }

        /**
         * Make sure social security number is set
         */
        if (!$socialSecurityNumber) {
            Mage::throwException($this->__('Social security number not set.'));
        }

        /**
         * Make sure payment option is availibe for their country
         */
        if ( $paymentInfo instanceof Mage_Sales_Model_Order_Payment ) {
            $billingCountry = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
        }

        Mage::log($billingCountry);

        /**
         * @todo Validate country
         * @todo validate address
         * @todo validate ssn
         */


        Mage::log($this->getCode());
        Mage::throwException('Validation done, so far so good. Please stop further actions for now. ' . date("H:i:s", time()));


        /**
         * to validate payment method is allowed for billing country or not
         */


        if ( ! $this->canUseForCountry($billingCountry) ) {

        }
        return $this;
    }

}