<?php

/*
 * This class and its subclasses wrap around a Magento order item and exposes
 * methods to retrieve the data needed to create a reservation.
 */

require_once('Klarna/2.4.3/Flags.php');

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
        return $this->getProduct()->getDiscountPercent();
    }

    public function getFlags()
    {
        return KlarnaFlags::INC_VAT;
    }
}