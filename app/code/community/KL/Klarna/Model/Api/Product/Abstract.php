<?php

class KL_Klarna_Model_Api_Product_Abstract extends Varien_Object
{
    public function getQuantity()
    {
        return $this->getProduct()->getQtyOrdered();
    }

    public function getSku()
    {
        return $this->getProduct()->getSku();
    }

    public function getName()
    {
        return $this->getProduct()->getName();
    }

    public function getPriceInclTax()
    {
        return $this->getProduct()->getPriceInclTax();
    }

    public function getTaxPercent()
    {
        return $this->getProduct()->getTaxPercent();
    }

    public function getDiscountPercent()
    {
        return 0;
    }
}
