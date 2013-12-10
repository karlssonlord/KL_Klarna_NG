<?php

class KL_Klarna_Block_Partpayment_Info extends KL_Klarna_Block_Info {

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('klarna/partpayment/info.phtml');
    }

}