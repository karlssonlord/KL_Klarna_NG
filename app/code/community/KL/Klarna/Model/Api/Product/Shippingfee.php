<?php

class KL_Klarna_Model_Api_Product_Shippingfee extends Kl_Klarna_Model_Api_Product_Abstract
{
    public function getPriceInclTax()
    {
        return $this->getAmount() + $this->getTaxAmount();
    }

    public function getTaxPercent()
    {
        if ($this->getTaxAmount() > 0 && $this->getPriceInclTax() > 0) {
            $taxAmount = $this->getTaxAmount();
            $taxPercentage = $taxAmount / ($this->getPriceInclTax() - $taxAmount) * 100;
        }
        else {
            $taxPercentage = 0;
        }

        return $taxPercentage;
    }

    public function getQuantity()
    {
        return 1;
    }

    public function getSku()
    {
        return "";
    }

    public function getName()
    {
        return Mage::helper('klarna')->__("Shipping fee") . ": " . $this->getDescription();
    }

    public function getDiscountPercent()
    {
        return 0;
    }

    public function getFlags()
    {
        return KlarnaFlags::INC_VAT | KlarnaFlags::IS_SHIPMENT;
    }
}
