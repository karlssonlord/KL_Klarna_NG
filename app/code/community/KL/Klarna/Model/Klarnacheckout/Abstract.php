<?php
/**
 * Include Klarna Checkout library
 */
require_once('Klarna/Checkout.php');

/**
 * Class KL_Klarna_Model_Klarnacheckout_Abstract
 */
class KL_Klarna_Model_Klarnacheckout_Abstract
    extends KL_Klarna_Model_Klarna
{

    /**
     * Convert fake float with 4 decimals to int
     *
     * @throws Exception
     * @param $float
     * @return int
     */
    public function fakeFloatToKlarnaInt($float)
    {
        /**
         * Assure it's a string
         */
        if (!is_string($float)) {
            throw new Exception('Incorrect input data, value is not a string');
        }

        /**
         * Assure it has 4 decimals
         */
        $decimals = explode('.', $float);
        if (strlen($decimals[1]) != 4) {
            throw new Exception('Incorrect input data, expected 4 decimals');
        }

        /**
         * Store the original value
         */
        $originalValue = $float;

        /**
         * Replace the "." for the decimals
         */
        $float = str_replace('.', '', $float);

        /**
         * Force int
         */
        $float = intval($float);

        /**
         * Remove the two extra decimals
         */
        $float = $float / 100;

        /**
         * Log the result
         */
        Mage::helper('klarna')->log('floatToKlarnaInt: ' . $originalValue . ' vs ' . $float);

        return $float;
    }

    /**
     * Fetch country model selected
     *
     * @return mixed
     */
    protected function getCountryModel()
    {
        /**
         * Fetch model that handles the countries
         */
        $model = Mage::getModel('klarna/klarnacheckout_countries');

        /**
         * Fetch the country code from admin settings
         */
        $countryCode = Mage::helper('klarna')->getConfig('country', 'checkout');

        /**
         * Return the country object
         */
        return $model->getCountry($countryCode);
    }

    /**
     * Get country
     *
     * @return mixed
     */
    public function getCountry()
    {
        return $this->getCountryModel()->getCode();
    }

    /**
     * Get currency
     *
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->getCountryModel()->getCurrency();
    }

    /**
     * Get locale
     *
     * @return mixed
     */
    public function getLocale()
    {
        return $this->getCountryModel()->getLanguage();
    }

    /**
     * Return the current quote
     *
     * @return mixed
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Check user agent if it's a mobile request
     *
     * @return bool
     */
    public function useMobileGui()
    {
        /**
         * Browser user agent
         */
        $_userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

        /**
         * Check user agent string
         */
        if ( stripos($_userAgent, 'mobile') || stripos($_userAgent, 'android') ) {
            return true;
        }

        return false;
    }

}