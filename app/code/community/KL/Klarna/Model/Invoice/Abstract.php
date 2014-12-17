<?php

/*
 * This class holds shared behavior for method objects used by
 * KL_Klarna_Model_Invoice.
 */

class KL_Klarna_Model_Invoice_Abstract extends Varien_Object
{
    protected function request()
    {
        if (!$this->getRequest()) {
            $request = Mage::getModel('klarna/api_request');
            $request->setPclass($this->getPaymentMethodInstance()->getPclass());
            $this->setRequest($request);
        }

        return $this->getRequest();
    }
}