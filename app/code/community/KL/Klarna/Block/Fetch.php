<?php
class KL_Klarna_Block_Fetch
    extends Mage_Core_Block_Template
{
    var $supportedCountries = array('SE');

    /**
     * Get URL for fetching address from SSN
     *
     * @return string
     */
    public function getFetchAddressEndpoint()
    {
        return Mage::getUrl('klarna/address/get');
    }

    /**
     * Get URL for updating address object
     *
     * @return string
     */
    public function getUpdateAddressEndpoint()
    {
        return Mage::getUrl('klarna/address/update');
    }

    /**
     * Can fetch
     *
     * @return boolean
     */
    public function canFetch()
    {
        $address = Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getShippingAddress();

        if (!$address) {
            return false;
        }

        $country = $address->getCountry();

        if (!$country) {
            return false;
        }

        if (in_array($country, $this->supportedCountries)) {
            return true;
        } else {
            return false;
        }
    }
}
