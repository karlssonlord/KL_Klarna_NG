<?php

class KL_Klarna_Block_Invoice_Info extends KL_Klarna_Block_Info {

    protected function _construct()
    {
        parent::_construct();
        Mage::log( $this->getTemplate());
        $this->setTemplate('klarna/info.phtml');
    }

}