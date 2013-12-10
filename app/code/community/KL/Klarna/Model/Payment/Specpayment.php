<?php

class KL_Klarna_Model_Payment_Specpayment extends KL_Klarna_Model_Payment_Abstract {

    protected $_code = 'klarna_specpayment';
    protected $_formBlockType = 'klarna/specpayment_form';
    protected $_infoBlockType = 'klarna/specpayment_info';

    public function __construct()
    {
        // disable if no pclass exists
        $this->_isGateway = false;
        $this->_canUseCheckout = false;
    }

    public function agetTitle()
    {
        /**
         * @todo Magic for setting the current campaign name here and also have the method removed if no campaign exists
         */
#        $this->_code = null;
        #


        return $this->__('No campaign is currently set');
    }

}