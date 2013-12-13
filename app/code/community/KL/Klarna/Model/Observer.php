<?php

class KL_Klarna_Model_Observer extends Mage_Core_Model_Abstract {

    /**
     * Check payment method, if not Klarna, remove the Klarna invoice fee to avoid confusion
     * when payment method is changed.
     *
     * @param $observer
     * 
     * @return mixed
     */
    public function sales_convert_quote_to_order($observer)
    {
        Mage::log('Obs: sales_convert_quote_to_order');

        /**
         * Fetch the order
         */
        $order = $observer->getOrder();

        /**
         * Check the payment code
         */
        if (substr($order->getPayment()->getCode(), 0, 6) !== 'klarna') {
            $order->setData('klarna_fee', 0);
        }

        /**
         * Fetch the quote
         */
        $quote = $observer->getQuote();

        /**
         * Check the payment code
         */
        if (substr($quote->getPayment()->getCode(), 0, 6) !== 'klarna') {
            $quote->setData('klarna_fee', 0);
        }

        return $observer;
    }

}