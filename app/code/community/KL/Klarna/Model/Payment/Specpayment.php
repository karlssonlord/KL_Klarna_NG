<?php
/**
 *
 */

/**
 *
 */
class KL_Klarna_Model_Payment_Specpayment
    extends KL_Klarna_Model_Payment_Abstract
{

    protected $_code          = 'klarna_specpayment';
    protected $_formBlockType = 'klarna/specpayment_form';
    protected $_infoBlockType = 'klarna/specpayment_info';
    protected $_pclassTypeId  = 4;

    public function isAvailable($quote = null)
    {
        /**
         * Setup count of possible pclasses
         */
        $pclasses = array_merge(
            Mage::helper('klarna/pclass')->getAvailable(0, $quote),
            Mage::helper('klarna/pclass')->getAvailable(2, $quote),
            Mage::helper('klarna/pclass')->getAvailable(4, $quote)
        );

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
            Mage::throwException(Mage::helper('klarna')->__('Pclass selection not set: ' . var_export($additionalInformation, true)));
        }

        /**
         * Continue with general Klarna validation
         */
        return parent::validate();
    }
}
