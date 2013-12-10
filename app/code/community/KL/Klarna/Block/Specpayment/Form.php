<?php

class KL_Klarna_Block_Specpayment_Form extends KL_Klarna_Block_Form {

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('klarna/specpayment/form.phtml');
    }

}







