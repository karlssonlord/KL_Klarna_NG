<?php

class KL_Klarna_Model_Payment_Checkout extends KL_Klarna_Model_Payment_Abstract {

    protected $_code = 'klarna_checkout';

    protected $_formBlockType = 'klarna/partpayment_form';

    protected $_infoBlockType = 'klarna/checkout_info';

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway = false;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize = false;

    /**
     * Can capture funds online?
     */
    protected $_canCapture = false;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal = true;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout = true;

    public function validate()
    {
        return $this;
    }

    public function isAvailable($quote = '')
    {
        return true;
    }

}