<?php

// ISO-8859-1

require_once('Klarna/2.4.3/Klarna.php');
require_once('Klarna/2.4.3/Country.php');
require_once('Klarna/2.4.3/Language.php');
require_once('Klarna/2.4.3/Currency.php');

class KL_Klarna_Model_Import extends KL_Klarna_Model_Api_Request {

    public function run()
    {
        // Get all defined countries and get all pclasses
        $definedCountries = $this->_getDefinedCountriesCredentials();

/*
        $params = array(
            'country' => KlarnaCountry::SE,     // 209
            'language' => KlarnaLanguage::SV,   // 138
            'currency' => KlarnaCurrency::SEK,  // 0
        );
*/
        print_r($definedCountries);

        foreach ($definedCountries as $definedCountry) {
            //$this->getPClasses($definedCountry);
        }
    }

    /**
     * Get all countries that are defined for invoicing and part payments. Populate and return a array with
     * corresponding Klarna specific codes for country, language and currency.
     *
     * @return array
     */
    private function _getDefinedCountriesCredentials()
    {
        $params = array();
        $invoicedCountries = explode(',', Mage::getStoreConfig('payment/klarna_invoice/countries'));
        $partPaymentCountries = explode(',', Mage::getStoreConfig('payment/klarna_partpayment/countries'));

        $countries = array_unique(array_merge($invoicedCountries, $partPaymentCountries));

        foreach ($countries as $country) {
            $params[] = Mage::getModel("klarna/api_countries")->getCountry($country)->getData();
        }

        /*
        foreach ($countries as $country) {
            print ':: ' .$country . PHP_EOL;
            $params[] = array(
                'country' => Klarna::getCountryForCode($country),
                'language' => Klarna::getLanguageForCode($country),
                'currency' => Klarna::getCurrencyForCountry(Klarna::getCountryForCode($country)),
            );
        }
        */
        return $params;
    }
} 
