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
    public function sales_order_save_before($observer)
    {
        Mage::log('Obs: sales_convert_quote_to_order');

        /**
         * Fetch the quote
         *
        $quote = $observer->getQuote();
        $order = $observer->getOrder();

        #Mage::log('Code is : ' . $order->getPayment()->getCode());

        /**
         * Check the payment code
         *
        if (substr($quote->getPayment()->getCode(), 0, 6) !== 'klarna') {
            Mage::log('remove fee');
            $quote->setData('klarna_fee', 0);
        } else {
            Mage::log('Add fee');
        }

/*        Mage::throwException('stoppa pressarna');

        /**
         * Flag quote to make Magento recalculate prices to make sure
         * Klarna fees doesn't apply
         */
       /* $quote
            ->setTotalsCollectedFlag(false)
            ->collectTotals();
*/
        return $observer;
    }

}