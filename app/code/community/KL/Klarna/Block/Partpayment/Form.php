<?php

class KL_Klarna_Block_Partpayment_Form extends KL_Klarna_Block_Form {

    public function __construct()
    {
        parent::__construct();
        $this
            ->assign('pclasses', Mage::helper('klarna/pclass')->getAvailable(0))
            ->setTemplate('klarna/partpayment/form.phtml');
    }

}





