<?php

class KL_Klarna_Model_Payment_Partpayment extends KL_Klarna_Model_Payment_Abstract {

    protected $_code = 'klarna_partpayment';

    protected $_formBlockType = 'klarna/partpayment_form';
    protected $_infoBlockType = 'klarna/partpayment_info';

    protected $_pclassTypeId = 0;

    public function isAvailable($quote = null)
    {
        /**
         * Setup count of possible pclasses
         */
        $pclasses = Mage::helper('klarna/pclass')->getAvailable(1, $quote);

        /**
         * If atleast one was found, enable the method
         */
        if (count($pclasses) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Custom validation
     *
     * @return bool|Mage_Payment_Model_Abstract
     *
     * @throws Exception
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
         * Make sure pclass is set
         */
        if (!isset($additionalInformation[$this->getCode() . '_pclass'])) {
            Mage::throwException(Mage::helper('klarna')->__('Pclass selection not set'));
        }

        /**
         * Continue with general Klarna validation
         */
        return parent::validate();
    }

}