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
         * Fetch the quote
         */
        $quote = $observer->getQuote();

        /**
         * Check the payment code
         */
        if (substr($quote->getPayment()->getCode(), 0, 6) !== 'klarna') {
            $quote->setData('klarna_fee', 0);
        }

        /**
         * Flat quote to make Magento recalculate prices to make sure
         * Klarna fees doesn't apply
         */
        $quote
            ->setTotalsCollectedFlag(false)
            ->collectTotals();

        return $observer;
    }

}