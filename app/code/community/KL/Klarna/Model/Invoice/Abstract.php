<?php

class KL_Klarna_Model_Invoice_Abstract extends Varien_Object
{
    protected function request()
    {
        if (!$this->getRequest()) {
            $this->setRequest(Mage::getModel('klarna/api_request'));
        }

        return $this->getRequest();
    }
}
