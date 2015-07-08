<?php

class KL_Klarna_Model_Klarna extends Varien_Object {

    public function __construct($customData = array())
    {
        $storeId = isset($customData['store_id']) ? $customData['store_id'] : null;
        /**
         * Configure the Klarna object with default data
         */
        $this
            ->setMerchantId(Mage::helper('klarna')->getConfig('merchant_id', 'klarna', $storeId))
            ->setSharedSecret(Mage::helper('klarna')->getConfig('shared_secret', 'klarna', $storeId))
            ->setServer($this->getCurrentServer())
            ->setPclassStorage('mysql')
            ->setPclassStorageUri($this->getDbUri())
            ->setCountry($this->getCurrentCountry())
            ->setLanguage($this->getCurrentLanguage())
            ->setCurrency($this->getCurrentCurrency());

        /**
         * Add custom data
         */
        foreach ($customData as $key => $value) {
            $this->setData($key, $value);
        }
    }

    public function getCurrentServer()
    {
        if ( Mage::helper('klarna')->getConfig('live') == '1' ) {
            return Klarna::LIVE;
        }

        return Klarna::BETA;
    }

    public function getDbUri()
    {
        /**
         * Fetch core resource
         */
        $resource = Mage::getSingleton('core/resource');

        /**
         * Fetch database configuration
         */
        $config = Mage::getConfig()->getResourceConnectionConfig("default_setup");

        /**
         * Return array used by Klarna
         */
        return array(
            'user' => $config->username,
            'passwd' => $config->password,
            'dsn' => $config->host,
            'db' => $config->dbname,
            'table' => $resource->getTableName('klarna/pclass')
        );
    }

    /**
     * Fetch the current country and return using Klarnas own ID
     *
     * @return null|int
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
        $systemCurrency = trim(Mage::app()->getStore()->getCurrentCurrencyCode());

        return KlarnaCurrency::fromCode($systemCurrency);
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