<?php

class KL_Klarna_Block_Partpayment_Form extends KL_Klarna_Block_Form {

    public function __construct()
    {
        parent::__construct();

        $pclasses = array_merge(
            Mage::helper('klarna/pclass')->getAvailable(0), // Part payment
            Mage::helper('klarna/pclass')->getAvailable(1) // Account
        );

        $this
            ->assign('pclasses', $pclasses)
            ->setTemplate('klarna/partpayment/form.phtml');
    }

}





