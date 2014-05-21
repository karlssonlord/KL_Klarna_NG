<?php

/**
 * Class KL_Klarna_Block_Checkout_Klarna_Shipping
 */
class KL_Klarna_Block_Checkout_Klarna_Shipping extends Mage_Checkout_Block_Onepage_Abstract {

    /**
     * Return available shipping methods
     *
     * @return array
     */
    public function getAvailableShippingMethods()
    {
        /**
         * Fetch the checkout model
         */
        $klarnaCheckout = Mage::getModel('klarna/klarnacheckout');

        /**
         * Collect shipping rates and assure the right country is set
         */
        $this->getShippingAddress()
            ->setCountryId($klarnaCheckout->getCountry())
            ->collectShippingRates()
            ->save();

        /**
         * Fetched grouped shipping rates
         */
        $groupedShippingRates = $this->getShippingAddress()
            ->getGroupedAllShippingRates();

        /**
         * Return data
         */
        return $groupedShippingRates;
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
            return true;
        }

        return false;
    }

}