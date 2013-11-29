<?php

class KL_Klarna_Model_Api_Countries
{
    public function getCountries()
    {
        $countriesXml = Mage::getConfig()->getNode('klarna/countries');
        $countries = array();

        foreach ($countriesXml->children() as $countryData) {
            $countries[] = Mage::getModel('klarna/api_country')->setData($countryData->asArray());
        }

        return $countries;
    }

    public function getCountry($code)
    {
        foreach ($this->getCountries() as $country) {
            if (strtoupper($country->getCode()) == strtoupper($code)) {
                return $country;
            }
        }
        // Returns a empty country if no country found by $code
        return Mage::getModel('klarna/api_country');
    }

    public function toOptionArray()
    {
        foreach ($this->getCountries() as $country) {
            $options[] = array(
                'value' => $country->getCode(),
                'label' => $country->getName()
            );
        }

        return $options;
    }
}
