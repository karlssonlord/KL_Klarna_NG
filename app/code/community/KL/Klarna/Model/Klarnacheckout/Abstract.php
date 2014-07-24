<?php
/**
 * Include Klarna Checkout library
 */
require_once('Klarna/kco_php/src/Klarna/Checkout.php');

/**
 * Class KL_Klarna_Model_Klarnacheckout_Abstract
 */
class KL_Klarna_Model_Klarnacheckout_Abstract
    extends KL_Klarna_Model_Klarna
{

    /**
     * Convert fake float with 4 decimals to int
     *
     * @param $float
     *
     * @return int
     */
    public function fakeFloatToKlarnaInt($float)
    {
        $float = (string) $float;

        /**
         * Assure it has 4 decimals
         */
        $decimals = explode('.', $float);

        if (count($decimals) == 1) {
            return (int) $float * 100;
        }

        if (strlen($decimals[1]) > 1) {
            $string = $decimals[0] . substr($decimals[1], 0, 2);
        } else {
            $string = $decimals[0] . substr($decimals[1], 0, 1) . 0;
        }

        return (int) $string;
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