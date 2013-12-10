<?php

class KL_Klarna_Block_Invoice_Info extends KL_Klarna_Block_Info {

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('klarna/invoice/info.phtml');
    }

}