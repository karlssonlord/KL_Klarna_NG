<?php

class KL_Klarna_Block_Partpayment_Form extends KL_Klarna_Block_Form {

    public function __construct()
    {
        parent::__construct();

        $pclasses = Mage::helper('klarna/pclass')->getAvailable(1);

        $this
            ->assign('pclasses', $pclasses)
            ->setTemplate('klarna/partpayment/form.phtml');
    }

}





