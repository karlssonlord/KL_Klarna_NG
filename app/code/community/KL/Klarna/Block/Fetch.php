<?php
class KL_Klarna_Block_Fetch extends Mage_Core_Block_Template
{
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
}
