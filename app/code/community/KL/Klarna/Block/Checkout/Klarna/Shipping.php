<?php

/**
 * Class KL_Klarna_Block_Checkout_Klarna_Shipping
 */
class KL_Klarna_Block_Checkout_Klarna_Shipping extends Mage_Checkout_Block_Onepage_Abstract {

    protected $_shippingFound = false;

    /**
     * Return available shipping methods using quote
     *
     * @return array
     */
    public function getAvailableShippingMethods()
    {
        return Mage::helper('klarna/checkout')->getAvailableShippingMethods();
    }

    /**
     * Shortcut to quote address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getShippingAddress()
    {
        return $this->getQuote()->getShippingAddress();
    }

    /**
     * Get formatted price for a shipping rate
     *
     * @param $shippingRate
     * @return mixed
     */
    public function getShippingRatePrice($shippingRate)
    {
        return Mage::helper('core')->currency($shippingRate->getPrice(), true, false);
    }

    /**
     * Check if a shipping rate is selected and saved in quote
     *
     * @param $shippingRate
     * @return bool
     */
    public function isSelected($shippingRate)
    {

        if ( $this->getShippingAddress()->getShippingMethod() == $shippingRate->getCode() ) {
            $this->_shippingFound = true;
            return true;
        }

        return false;
    }

    /**
     * Check if a method was found
     *
     * @return bool
     */
    public function isMethodFound()
    {
        return $this->_shippingFound;
    }

}