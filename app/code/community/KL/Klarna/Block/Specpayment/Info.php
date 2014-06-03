<?php

class KL_Klarna_Block_Specpayment_Info extends KL_Klarna_Block_Info {

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('klarna/info.phtml');
    }

}