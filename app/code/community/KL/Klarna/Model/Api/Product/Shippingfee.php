<?php

class KL_Klarna_Model_Api_Product_Shippingfee extends Kl_Klarna_Model_Api_Product_Abstract
{
    /**
     * Get price including VAT
     *
     * @return mixed
     */
    public function getPriceInclTax()
    {
        return $this->getAmount() + $this->getTaxAmount();
    }

    /**
     * Get VAT percent
     *
     * @return float|int
     */
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

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQuantity()
    {
        return 1;
    }

    /**
     * Get Sku string
     *
     * @return string
     */
    public function getSku()
    {
        return "";
    }

    /**
     * Get fee name and description
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('klarna')->__("Shipping fee") . ": " . $this->getDescription();
    }

    /**
     * Get discount percent
     *
     * @return int
     */
    public function getDiscountPercent()
    {
        return 0;
    }

    /**
     * Get Klarna flags
     *
     * @return int
     */
    public function getFlags()
    {
        return KlarnaFlags::INC_VAT | KlarnaFlags::IS_SHIPMENT;
    }
}
