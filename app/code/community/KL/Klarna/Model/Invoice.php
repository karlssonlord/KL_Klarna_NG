<?php
class KL_Klarna_Model_Invoice extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = "klarna_invoice";

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
        $this->_debug(array("canUseForCountry", $countryCode));
        return parent::canUseForCountry($countryCode);
    }

    public function canUseForCurrency($currencyCode)
    {
        $this->_debug(array("canUseForCurrency", $currencyCode));
        return parent::canUseForCurrency($currencyCode);
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $this->_debug("authorize");

        $transactionId = uniqid(); // Just a random ID for now
        $payment
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(false);

        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $this->_debug("capture");

        $transactionId = uniqid(); // Just a random ID for now
        $payment->setTransactionId($transactionId);

        return parent::capture($payment, $amount);
    }

    public function cancel(Varien_Object $payment)
    {
        $this->_debug("cancel");
        return parent::cancel($payment);
    }

    public function void(Varien_Object $payment)
    {
        $this->_debug("void");
        return parent::void($payment);
    }

    public function refund(Varien_Object $payment, $amount)
    {
        $this->_debug("refund");

        $transactionId = uniqid(); // Just a random ID for now
        $payment->setTransactionId($transactionId);

        return parent::refund($payment, $amount);
    }
}
