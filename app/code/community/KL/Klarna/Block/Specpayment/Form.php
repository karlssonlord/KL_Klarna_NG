<?php

class KL_Klarna_Block_Specpayment_Form extends KL_Klarna_Block_Form {

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('klarna/specpayment/form.phtml');

        // https://developers.klarna.com/en/api-references-v1/invoice-and-account#get_pclasses
        $pclasses = array_merge(
            Mage::helper('klarna/pclass')->getAvailable(0),
            Mage::helper('klarna/pclass')->getAvailable(2),
            Mage::helper('klarna/pclass')->getAvailable(4)
        );

        $this
            ->assign('pclasses', $pclasses)
            ->setTemplate('klarna/specpayment/form.phtml');
    }

}







