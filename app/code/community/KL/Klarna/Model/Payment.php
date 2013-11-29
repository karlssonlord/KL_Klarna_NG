<?php

/*
 * This class defines a Magento payment method for Klarna Invoice. In order
 * to keep this class reasonably short and concise, most methods are delegated
 * to method objects. This helps keep responsibilities clear and should also
 * help with testing.
 */

class KL_Klarna_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = null;

    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;
    protected $_canFetchTransactionInfo = false;

    public function canUseForCountry($countryCode)
    {
        return Mage::getModel('klarna/api_countries')->getCountry($countryCode);
    }

    public function canUseForCurrency($currencyCode)
    {
        $this->_debug(array("canUseForCurrency", $currencyCode));
        return parent::canUseForCurrency($currencyCode);
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $this->_debug("authorize");

        $authorization = $this->getMethodObject('authorize')
            ->setPayment($payment)
            ->setAmount($amount)
            ->authorize();

        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $this->_debug("capture");

        $capture = $this->getMethodObject('capture')
            ->setPayment($payment)
            ->setAmount($amount)
            ->capture();

        return $this;
    }

    public function cancel(Varien_Object $payment)
    {
        $this->_debug("cancel");

        $cancellation = $this->getMethodObject('cancel')
            ->setPayment($payment)
            ->cancel();

        return $this;
    }

    public function void(Varien_Object $payment)
    {
        $this->_debug("void");
        return parent::void($payment);
    }

    public function refund(Varien_Object $payment, $amount)
    {
        $this->_debug("refund");

        $refund = $this->getMethodObject('refund')
            ->setPayment($payment)
            ->setAmount($amount)
            ->refund();

        return $this;
    }

    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();
        $validation = $this->getMethodObject('validate')
            ->setPayment($paymentInfo)
            ->validate();
        return $this;
    }
    
    protected function getMethodObject($methodName)
    {
        $methodObject = Mage::getModel('klarna/invoice_' . $methodName);
        $methodObject->setPaymentMethodInstance($this);
        
        return $methodObject;
    }
}
