<?php

class KL_Klarna_Block_Info extends Mage_Payment_Block_Info {

    protected $_order;

    public function getOrder()
    {
        if ( ! $this->_order ) {
            $this->_order = $this->getInfo()->getOrder();
        }

        return $this->_order;
    }

    public function getLogo()
    {
        switch ($this->getMethod()->getCode()) {
            case 'klarna_invoice':
                return Mage::helper('klarna')->getInvoiceLogo(200);
                break;
            default:
                return '';
        }

    }

    public function getPayment()
    {
        if ( ! $this->_payment ) {
            $this->_payment = $this->getOrder()->getPayment();
        }

        return $this->_payment;
    }

    public function getTxnId()
    {
        /**
         * Fetch the payment
         */
        if ( is_object($this->getPayment()) ) {

            $authTrans = $this->getPayment()->getAuthorizationTransaction();

            if ( is_object($authTrans) ) {

                return $authTrans->getTxnId();

            }
        }

        return Mage::helper('klarna')->__('Reservation missing');
    }

    protected function getPaymentField($field)
    {
        if ( is_object($this->getPayment()) ) {
            $additional = $this->getPayment()->getAdditionalInformation();
            if ( isset($additional[$field]) ) {
                return $additional[$field];
            }
        }

        return false;
    }

    public function getSocialSecurityNumber()
    {
        if ( $this->getPaymentField('klarna_invoice_ssn') ) {
            return $this->getPaymentField('klarna_invoice_ssn');
        }

        return false;
    }

    public function getInvoiceUrl()
    {
        if ( $this->getPaymentField('klarna_invoice_no') ) {
            return 'https://online.klarna.com/invoices/' . $this->getPaymentField('klarna_invoice_no') . '.pdf';
        }

        return false;
    }

    public function sentByEmail()
    {
        if ($this->getPaymentField('emailed')) {
            if ($this->getPaymentField('emailed') == 'success') {
                return Mage::helper('klarna')->__('Yes');
            } else {
                return Mage::helper('klarna')->__('Failure');
            }
        }

        return Mage::helper('klarna')->__('No');
    }

    public function sentByPostal()
    {
        if ($this->getPaymentField('posted')) {
            if ($this->getPaymentField('posted') == 'success') {
                return Mage::helper('klarna')->__('Yes');
            } else {
                return Mage::helper('klarna')->__('Failure');
            }
        }

        return Mage::helper('klarna')->__('No');
    }

}