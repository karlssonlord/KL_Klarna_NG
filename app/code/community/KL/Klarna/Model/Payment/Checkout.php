<?php

class KL_Klarna_Model_Payment_Checkout extends KL_Klarna_Model_Payment_Abstract {

    /**
     * @var string
     */
    protected $_code = 'klarna_checkout';

    /**
     * @var string
     */
    protected $_formBlockType = 'klarna/partpayment_form';

    /**
     * @var string
     */
    protected $_infoBlockType = 'klarna/checkout_info';

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
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal = true;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout = true;

    /**
     * Can refund online?
     */
    protected $_canRefund = true;

    /**
     * Can refund partial
     */
    protected $_canRefundInvoicePartial = true;

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
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {


        throw new Exception('Foo bared');

        die('foo');

        return 0.00;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        // validate order status

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
        return $this;
    }

}