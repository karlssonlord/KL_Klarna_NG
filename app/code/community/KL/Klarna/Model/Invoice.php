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

        $authorization = Mage::getModel('klarna/invoice_authorize')
            ->setPayment($payment)
            ->setAmount($amount)
            ->authorize();

        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $this->_debug("capture");

        $capture = Mage::getModel('klarna/invoice_capture')
            ->setPayment($payment)
            ->setAmount($amount)
            ->capture();

        return $this;
    }

    public function cancel(Varien_Object $payment)
    {
        $this->_debug("cancel");

        $cancellation = Mage::getModel('klarna/invoice_cancel')
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

        $refund = Mage::getModel('klarna/invoice_refund')
            ->setPayment($payment)
            ->setAmount($amount)
            ->refund();

        return $this;
    }
}
