<?php

class KL_Klarna_Helper_Log extends Mage_Core_Helper_Abstract
{
    /**
     *  Log filename
     */
    const LOG_FILE = 'kl_klarna.log';

    /**
     * @var boolean
     */
    protected $forced = false;

    /**
     * Message [verb] the message in the log
     *
     * @param $quote
     * @param $message
     * @param null $orderId
     * @param null $klarnaCheckoutId
     * @param null $force
     */
    public function message($quote, $message, $orderId = null, $klarnaCheckoutId = null, $force = null)
    {
        $message = $this->buildMessageString($quote, $message, $orderId, $klarnaCheckoutId);
        Mage::helper('klarna')->log($message, $force, null, $orderId, $klarnaCheckoutId);
    }

    /**
     * Log message to our log files
     *
     * @deprectaded See KL_Klarna_Helper_Data::log
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
            $quoteId = $quote->getId();
        } else {
            $data = sprintf(
                "Quote ID: [NONE GIVEN] | IP: %s | URL: %s | %s",
                $_SERVER['REMOTE_ADDR'],
                $url,
                $message
            );
            $quoteId = null;
        }

        Mage::helper('klarna')->log($data, $force, $quoteId, null, null);
    }

    /**
     * @deprectaded See KL_Klarna_Helper_Data::log
     *
     * @param $quote
     * @param $orderId
     * @return string
     */
    protected function getOrderId($quote, $orderId)
    {
        /**
         * If an orderId is given then do nothing but return it
         */
        if ( ! is_null($orderId)) {
            return $orderId;
        }

        /**
         * Check if there is reservedOrderId on the quote object.
         * That could be useful
         */
        $reservedOrderId = null;
        if (is_object($quote)) {
            $reservedOrderId = $quote->getReservedOrderId();
        }

        if ($reservedOrderId) {
            return 'Reserved order ID: '.(string) $reservedOrderId;
        }

        return '[NONE GIVEN]';
    }

    /**
     * @param $quote
     * @return bool
     */
    protected function isQuoteObject($quote)
    {
        return $quote instanceof Mage_Sales_Model_Quote;
    }

    /**
     * Build a nice string representation off the provided input
     *
     * @param $quote
     * @param $message
     * @param $orderId
     * @param $klarnaCheckoutId
     * @return string
     */
    protected function buildMessageString($quote, $message, $orderId, $klarnaCheckoutId)
    {
        return sprintf(
            "Quote ID: %d | Reserved order ID: %s | KCO ID: %s | IP: %s | URL: %s | %s",
            $this->getQuoteId($quote),
            $this->getOrderId($quote, $orderId),
            $this->getKlarnaCheckoutId($klarnaCheckoutId),
            $_SERVER['REMOTE_ADDR'],
            Mage::helper('core/url')->getCurrentUrl(),
            $this->getMessageString($message)
        );
    }

    /**
     * @param $force
     * @throws InvalidArgumentException
     * @return bool
     */
    protected function isForced($force)
    {
        /**
         * I $this->forced is true then, it has been set previously by
         * the getMessageString method, and subsequently overrides all else
         */
        if ($this->forced) {
            return $this->forced;
        }

        /**
         * Check debug settings
         */
        if (is_null($force)) {
            if (Mage::helper('klarna')->getConfig('debug') == '1') {
                $force = true;
                return $force;
            } else {
                $force = false;
                return $force;
            }
        }

        /**
         * Argument must have either null or boolean type
         */
        if ( ! is_bool($force) and ! is_null($force)) {
            throw new InvalidArgumentException('5th argument of Log::message must have boolean type or be null. Doh.');
        }

        return $force;
    }

    /**
     * @param $klarnaCheckoutId
     * @return string
     */
    protected function getKlarnaCheckoutId($klarnaCheckoutId)
    {
        if (is_null($klarnaCheckoutId)) {
            return '[NONE GIVEN]';
        }

        return $klarnaCheckoutId;
    }

    /**
     * @param $quote
     * @return string
     */
    protected function getQuoteId($quote)
    {
        if ($this->isQuoteObject($quote)) {
            return $quote->getId();
        }

        return '[NONE GIVEN]';
    }

    /**
     * @param $message
     * @return string
     */
    protected function getMessageString($message)
    {
        if ($message instanceof Exception) {
            $this->forced = true;
            return "Exception: " . $message->getMessage();
        }

        return $message;
    }
}
