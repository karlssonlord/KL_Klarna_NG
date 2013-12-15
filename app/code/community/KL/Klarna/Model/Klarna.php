<?php

class KL_Klarna_Model_Klarna extends Varien_Object {

    public function __construct()
    {
        /**
         * Configure the Klarna object
         */
        $this
            ->setMerchantId(Mage::helper('klarna')->getConfig('merchant_id'))
            ->setSharedSecret(Mage::helper('klarna')->getConfig('shared_secret'))
            ->setServer(Klarna::BETA) // @todo
            ->setPclassStorage('json')
            ->setPclassStorageUri('/tmp/pclasses.json') // @tidi
            ->setCountry($this->getCurrentCountry())
            ->setLanguage($this->getCurrentLanguage())
            ->setCurrency($this->getCurrentCurrency())
        ;

        /**
         * @todo Implement this with settings from admin or other place
         */

        # $this->getServer(),
        # $this->getPclassStorage(),
        # $this->getPclassStorageUri(),

    }

    /**
     * Fetch the current country and return using Klarnas own ID
     */
    public function getCurrentCountry()
    {
        /**
         * Set default country
         */
        $country = null;

        /**
         * Fetch the quote
         */
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        /**
         * Check the billing address
         */
        $country = $quote->getBillingAddress()->getCountry();

        /**
         * Check the shipping address
         */
        if (!$country) {
            $country = $quote->getShippingAddress()->getCountry();
        }

        /**
         * Use store default as our final destination
         */
        if (!$country) {
            $country = Mage::getStoreConfig('general/country/default');
        }

        return KlarnaCountry::fromCode($country);
    }

    /**
     * Fetch current language from current country
     *
     * @return int
     */
    public function getCurrentLanguage()
    {
        return KlarnaCountry::getLanguage($this->getCurrentCountry());
    }

    /**
     * Fetch current currency from current country
     *
     * @return int|null
     */
    public function getCurrentCurrency()
    {
        return KlarnaCurrency::fromCode('SEK');
    }

    /**
     * @todo Follow up and use admin settings
     *
     * @return bool
     */
    public function useRemoteResponseTimeLogging()
    {
        return true;
    }

    /**
     * @todo Follow up and use admin settings
     *
     * @return bool
     */
    public function useSsl()
    {
        return true;
    }

}