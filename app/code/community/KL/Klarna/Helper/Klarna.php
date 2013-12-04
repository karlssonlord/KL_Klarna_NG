<?php

class KL_Klarna_Helper_Klarna extends Mage_Core_Helper_Abstract {

    /**
     * Get all countries that are defined for invoicing and part payments. Populate and return a array with
     * corresponding Klarna specific codes for country, language and currency.
     *
     * @return array
     */
    public function getDefinedCountriesCredentials()
    {
        $params = array();
        $errors = array();
        // Get all countries defined for Klarna Invoice
        $invoicedCountries = explode(',', Mage::getStoreConfig('payment/klarna_invoice/countries'));
        // Get all countries defined for Klarna Part Payments
        $partPaymentCountries = explode(',', Mage::getStoreConfig('payment/klarna_partpayment/countries'));
        // @todo Need to get all countries for "Special deals" as well
        // i.e. $partPaymentCountries = explode(',', Mage::getStoreConfig('payment/klarna_specialpayment/countries'));

        // Compile all countries to a nice array without any duplicates
        $countries = array_unique(array_merge($invoicedCountries, $partPaymentCountries));

        // Get country data from config.xml file
        foreach ($countries as $country) {
            $enabledCountries[] = Mage::getModel("klarna/api_countries")->getCountry($country)->getData();
        }

        // Translate country data to Klarna specific codes
        foreach ($enabledCountries as $country) {
            $klarnaCountry = $this->_getKlarnaCountryForCode($country['code'], $errors);
            $klarnaLanguage = $this->_getKlarnaLanguageForCode($country['language'], $errors);
            $klarnaCurrency = $this->_getKlarnaCurrencyForCode($country['currency'], $errors);

            /**
             * Get PClasses only for countries that got valid Klarna constants, if one or more codes raises an
             * exception, get data for the rest of the countries anyway
             */
            if (!is_null($klarnaCountry) && !is_null($klarnaLanguage) && !is_null($klarnaCurrency)) {
                $params[] = array(
                    'country' => $klarnaCountry,
                    'language' => $klarnaLanguage,
                    'currency' => $klarnaCurrency,
                );
            }
        }

        // If something went pear shaped, log error messages to system log
        $this->_logKlarnaApiErrors($errors);

        return $params;
    }

    /**
     * Wrapper function. Get Klarna country code
     *
     * @param int $code Two letter code, e.g. "se", "no", etc.
     * @param array $errors Error messages
     * @return int|null
     */
    private function _getKlarnaCountryForCode($code, &$errors)
    {
        $klarnaCountry = null;
        try {
            $klarnaCountry = Klarna::getCountryForCode($code);
        } catch (KlarnaException $ex) {
            $errors[] = $ex->getMessage();
        }
        return $klarnaCountry;
    }

    /**
     * Wrapper function. Get Klarna language code
     *
     * @param $language
     * @param string $language Two letter code, e.g. "da", "de", etc.
     * @param array $errors Error messages
     * @return int|null
     */
    private function _getKlarnaLanguageForCode($language, &$errors)
    {
        $klarnaLanguage = null;
        try {
            $klarnaLanguage = Klarna::getLanguageForCode($language);
        } catch (KlarnaException $ex) {
            $errors[] = $ex->getMessage();
        }
        return $klarnaLanguage;
    }

    /**
     * Wrapper function. Get Klarna Currency code
     *
     * @param string $currency Two letter code, e.g. "dkk", "eur", etc.
     * @param array $errors Error messages
     * @return int|null
     */
    private function _getKlarnaCurrencyForCode($currency, &$errors)
    {
        $klarnaCurrency = null;
        try {
            $klarnaCurrency = Klarna::getCurrencyForCode($currency);
        } catch (KlarnaException $ex) {
            $errors[] = $ex->getMessage();
        }
        return $klarnaCurrency;
    }

    /**
     * Log any errors from Klarna
     *
     * @param array $errors
     */
    private function _logKlarnaApiErrors(array $errors)
    {
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Mage::log('Error getting PClasses: '. $error);
            }
        }
    }
} 
