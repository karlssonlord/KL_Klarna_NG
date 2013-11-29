<?php

class KL_Klarna_Model_Invoice extends KL_Klarna_Model_Payment
{
    protected $_code = 'klarna_invoice';

    public function getPclass()
    {
        return -1; // KlarnaPClass::INVOICE
    }
}
