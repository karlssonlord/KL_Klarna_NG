<?php

class KL_Klarna_Helper_Log
    extends Mage_Core_Helper_Abstract
{
    const LOG_FILE = 'kl_klarna.log';

    /**
     * Log message to our log files
     *
     * @param Mage_Sales_Model_Quote $quote   Quote object
     * @param mixed                  $message Log message
     * @param boolean                $force   Flag to activate force logging
     *
     * @return void
     */
    public function log($quote, $message, $force = null)
    {
        /**
         * Get the textual message if it's an exception
         * and force logging. Otherwise look at the
         * parameter and fallback to system configuration
         * it it's not set.
         */
        if ($message instanceof Exception) {
            $message = "Exception: " . $message->getMessage();
            $force   = true;
        } else {
            if (is_null($force)) {
                if (Mage::helper('klarna')->getConfig('debug') == '1') {
                    $force = true;
                } else {
                    $force = false;
                }
            }
        }

        $url = Mage::helper('core/url')->getCurrentUrl();

        /**
         * Make sure it's an object
         */
        if (is_object($quote) && $quote instanceof Mage_Sales_Model_Quote) {
            $data = sprintf(
                "Quote ID: %d | IP: %s | URL: %s | %s",
                $quote->getId(),
                $_SERVER['REMOTE_ADDR'],
                $url,
                $message
            );
        } else {
            $data = sprintf(
                "Quote ID: [NONE GIVEN] | IP: %s | URL: %s | %s",
                $_SERVER['REMOTE_ADDR'],
                $url,
                $message
            );
        }

        Mage::log($data, null, self::LOG_FILE, $force);
    }
}
