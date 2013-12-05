<?php

require_once('Klarna/2.4.3/Klarna.php');
require_once('Klarna/2.4.3/Country.php');
require_once('Klarna/2.4.3/Language.php');
require_once('Klarna/2.4.3/Currency.php');
require_once('Klarna/2.4.3/Exceptions.php');

class KL_Klarna_Helper_Klarna extends Mage_Core_Helper_Abstract
{
    /**
     * @var array|null Klarnas payment types
     */
    private $definedPaymentTypes = null;

    /**
     * Constructor
     *
     * Set default payment types,
     */
    public function __construct()
    {
        $this->definedPaymentTypes = array('invoice', 'partpayment');
    }

    /**
     * Get all countries that are defined for the payment types. Populate and return a array with
     * corresponding Klarna specific codes for country, language and currency.
     *
     * @return array $params Array with Klarna specific data for defined countries
     */
    public function getDefinedCountriesCredentials()
    {
        if (empty($this->definedPaymentTypes)) {
            return array();
        }

        $params = array();
        $errors = array();

        // Get all defined countries and load data
        $enabledCountries = $this->_getDefinedCountries();

        // Nothing to process, gtfo
        if (empty($enabledCountries[0])) {
            return array();
        }

        // Right, let's get some klarna specific values
        foreach ($enabledCountries as $country) {

            /**
             * Translate country data to Klarna specific codes
             *
             * These calls may throw exceptions, but we fail gracefully and write these
             * exceptions to the $errors array and finally log them
             */
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

        // If something went pear shaped, silently log error messages to system log
        $this->_logKlarnaApiErrors($errors);

        return $params;
    }


    /**
     * Get all countries defined in database for all defined payment types. Remove duplicates and empty
     * entities and finally load all data from config.xml for respective country
     *
     * @return array
     */
    private function _getDefinedCountries()
    {
        $countries = array();
        foreach ($this->definedPaymentTypes as $paymentType) {
            $paymentTypeCountries = $this->_getKlarnaInvoiceDefinedCountries($paymentType);
            $countries = array_filter(array_unique(array_merge($countries, $paymentTypeCountries)));
        }

        // Get country data from config.xml file
        foreach ($countries as $country) {
            $enabledCountries[] = Mage::getModel("klarna/api_countries")->getCountry($country)->getData();
        }
        return $enabledCountries;
    }

    /**
     * Update database after save in admin. Purges PClasses for countries that are not present
     * in current configuration
     *
     * Note: There must be a more generic Magento way to handle these sort of db-operations, but since i don't have
     * a clue at the moment I just go for the most obvious solutions a can come up with.
     *
     */
    public function updatePClassesDatabaseAfterSave()
    {
        $countryIds = array();
        $definedCountries = $this->getDefinedCountriesCredentials();

        // If there's no country pclasses to delete, just return
        if (empty($definedCountries)) {
            $sql = 'DELETE FROM klarna_pclass';
        } else {
            // Get all current country id's
            foreach ($definedCountries as $definedCountry) {
                $countryIds[] = $definedCountry['country'];
            }

            // Prepare country ids for sql query
            $countryIdStr = implode(',', $countryIds);
            $sql = sprintf('DELETE FROM klarna_pclass WHERE country NOT IN (%s)', $countryIdStr);
        }

        // Get database connection
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        // Execute query
        $connection->query($sql);
    }


    /**
     * Check if Klarna Payment type is active
     *
     * @param $paymentType
     * @return bool
     */
    private function _isKlarnaInvoicePaymentTypeActive($paymentType = false)
    {
        if (!$paymentType) {
            return false;
        }
        $coreConfigPath = sprintf('payment/klarna_%s/active', $paymentType);
        return Mage::getStoreConfig($coreConfigPath);
    }

    /**
     * Get country codes from database. If the payment type is set to inactive set
     * config to an empty string.
     *
     * @param bool $paymentType
     * @return array
     */
    private function _getKlarnaInvoiceDefinedCountries($paymentType = false)
    {
        if (!$paymentType) {
            return '';
        }

        $coreConfigPath = sprintf('payment/klarna_%s/countries', $paymentType);

        if (!$this->_isKlarnaInvoicePaymentTypeActive($paymentType)) {
            // This payment type is inactive, set countries as empty string
            Mage::getConfig()->saveConfig($coreConfigPath, '');
            Mage::getConfig()->reinit();
            Mage::app()->reinitStores();
        }

        // Return a comma separated string with all country codes
        return explode(',', Mage::getStoreConfig($coreConfigPath));
    }

    /**
     * Wrapper function. Get Klarna country code. If an exception is thrown fail silently and assign
     * exception message to the $errors array reference
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
     * Wrapper function. Get Klarna language code. If an exception is thrown fail silently and assign
     * exception message to the $errors array reference
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
     * Wrapper function. Get Klarna Currency code. If an exception is thrown fail silently and assign
     * exception message to the $errors array reference
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
