<?php

class KL_Klarna_Block_Form extends Mage_Payment_Block_Form {

    /**
     * @return string
     */
    public function getCurrentCountry()
    {
        return Mage::getSingleton('core/session')->getCountryCode();
    }

    public function getEid()
    {
        return Mage::helper('klarna')->getConfig('merchant_id');
    }

    public function getFetchAddressEndpoint()
    {
        return Mage::getUrl('klarna/address/get');
    }

    public function getUpdateAddressEndpoint()
    {
        return Mage::getUrl('klarna/address/update');
    }

}





