<?php

class KL_Klarna_Model_KlarnaCheckout_Countries extends Varien_Object {

    /**
     * @return array
     */
    public function getCountries()
    {
        $countriesXml = Mage::getConfig()->getNode('klarna_checkout/countries');
        $countries = array();

        foreach ($countriesXml->children() as $countryData) {
            $countries[] = Mage::getModel('klarna/country')
                ->setData($countryData->asArray());
        }

        return $countries;
    }

    /**
     * @param $code
     * @return false|Mage_Core_Model_Abstract
     */
    public function getCountry($code)
    {
        foreach ($this->getCountries() as $country) {
            if ( strtoupper($country->getCode()) == strtoupper($code) ) {
                return $country;
            }
        }
        // Returns a empty country if no country found by $code
        return Mage::getModel('klarna/country');
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        foreach ($this->getCountries() as $country) {
            $options[] = array(
                'value' => $country->getCode(),
                'label' => $country->getName()
            );
        }

        return $options;
    }
}