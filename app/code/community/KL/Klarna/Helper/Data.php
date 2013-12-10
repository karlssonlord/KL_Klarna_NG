<?php

class KL_Klarna_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Fetch configuration settings for
     * @param $key
     * @param $type "invoice", "spec" or "part". Default is "klarna"
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

    /**
     * Log message to our log files
     *
     * @param $message mixed
     *
     * @return void
     */
    public function log($message)
    {
        /**
         * Check if we should do logging or not
         */
        if ($this->getConfig('debug') == '1') {
            $force = true;
        } else {
            $force = false;
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
         * Log to our Magento log file
         */
        Mage::log($message, null, 'kl_klarna.log', true);
    }

}