<?php

class KL_Klarna_Block_Invoice_Form extends KL_Klarna_Block_Form {

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('klarna/invoice/form.phtml');
    }

}





