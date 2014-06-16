<?php

/**
 * Class KL_Klarna_Helper_Data
 */
class KL_Klarna_Helper_Data extends KL_Klarna_Helper_Abstract {

    /**
     * Fetch configuration settings for
     *
     * @param string $key Magento setting key
     * @param string $type "invoice", "spec", "part" or "checkout". Default is "klarna"
     *
     * @return mixed
     */
    public function getConfig($key, $type = 'klarna')
    {
        /**
         * Add prefix if missing (probably will)
         */
        if ( substr($type, 0, 6) !== 'klarna' ) {
            $type = 'klarna_' . $type;
        }

        /**
         * Return configuration value
         */
        return Mage::getStoreConfig('payment/' . $type . '/' . $key);
    }

    public function getConfigFlag($key, $type = 'klarna')
    {
        /**
         * Add prefix if missing (probably will)
         */
        if ( substr($type, 0, 6) !== 'klarna' ) {
            $type = 'klarna_' . $type;
        }

        /**
         * Return configuration value
         */
        return Mage::getStoreConfigFlag('payment/' . $type . '/' . $key);
    }

    /**
     * Log message to our log files
     *
     * @param mixed $message Log message
     * @param boolean $force Flag to activate force logging
     *
     * @return void
     */
    public function log($message, $force = null)
    {
        /**
         * Check if we should do logging or not
         */
        if ( is_null($force) ) {
            if ( $this->getConfig('debug') == '1' ) {
                $force = true;
            } else {
                $force = false;
            }
        }

        /**
         * Fetch message if it's an exception
         */
        if ( $message instanceof Exception ) {
            /**
             * Fetch the message
             */
            $message = "Exception: " . $message->getMessage();

            /**
             * Force logging if it's an exception
             */
            $force = true;
        }

        /**
         * Add remote IP address if it's a string
         */
        if ( is_string($message) ) {
            $message = '[' . $_SERVER['REMOTE_ADDR'] . '] ' . $message;
        }

        /**
         * Log to our Magento log file
         */
        Mage::log($message, null, 'kl_klarna.log', $force);
    }

    /**
     * Get current Klarna country ID
     *
     * @author Erik Eng <erik@karlssonlord.com>
     *
     * @return null|string
     */
    public function getCountryId()
    {
        return Mage::getModel('klarna/klarna')->getCurrentCountry();
    }

    /**
     * Get current Klarna country code
     *
     * @author Erik Eng <erik@karlssonlord.com>
     *
     * @return null|string
     */
    public function getCountryCode()
    {
        return $this->klarnaCountryToMagento($this->getCountryId());
    }

    /**
     * Convert Klarna country id to Magento string (ISO 2 char)
     *
     * @param int $klarnaCountryId Klarna country ID
     *
     * @return null|string
     */
    public function klarnaCountryToMagento($klarnaCountryId)
    {
        return KlarnaCountry::getCode($klarnaCountryId);
    }

    /**
     * Get URL for invoice logo from Klarna CDN
     *
     * @param int $width Width in pixels
     * @param string $country Country code
     *
     * @return string
     */
    public function getInvoiceLogo($width = 250, $country = null)
    {
        if ( $country === null ) {
            $country = $this->getCountryCode();
        }
        return 'https://cdn.klarna.com/public/images/' . $country . '/badges/v1/invoice/' . $country . '_invoice_badge_std_blue.png?width=' . $width . '&eid=' . $this->getConfig(
            'merchant_id'
        );
    }

    /**
     * Get URL for account logo from Klarna CDN
     *
     * @param int $width Width in pixels
     * @param string $country Country code
     *
     * @return string
     */
    public function getPartpaymentLogo($width = 250, $country = null)
    {
        if ( $country === null ) {
            $country = $this->getCountryCode();
        }
        return 'https://cdn.klarna.com/public/images/' . $country . '/badges/v1/account/' . $country . '_account_badge_std_blue.png?width=' . $width . '&eid=' . $this->getConfig(
            'merchant_id'
        );
    }

    /**
     * Get URL for regular logo from Klarna CDN
     *
     * @param int $width
     * @param string $country
     *
     * @return string
     */
    public function getRegularLogo($width = 250, $country = null)
    {
        if ( $country === null ) {
            $country = $this->getCountryCode();
        }
        return 'https://cdn.klarna.com/public/images/' . $country . '/logos/v1/basic/' . $country . '_basic_logo_std_blue-black.png?width=' . $width . '&eid=' . $this->getConfig(
            'merchant_id'
        );
    }

    /**
     * Check if Klarna Checkout is enabled
     *
     * @return bool
     */
    public function isKcoEnabled()
    {
        return (bool)$this->getConfig('active', 'checkout');
    }

    /**
     * Check if the integration is live or using test environment
     *
     * @return bool
     */
    public function isLive()
    {
        return (bool)$this->getConfig('live');
    }

}