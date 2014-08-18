<?php

/**
 * Class KL_Klarna_Block_Checkout_Klarna_Shipping
 */
class KL_Klarna_Block_Checkout_Klarna_Success
    extends Mage_Checkout_Block_Onepage_Success
{

    /**
     * Show the confirmation div
     *
     * @return string
     */
    public function getConfirmationHtml()
    {
        /**
         * Fetch Klarna Checkout model
         */
        $klarnaCheckout = Mage::getModel('klarna/klarnacheckout');

        /**
         * Fetch existing order
         */
        $order = $klarnaCheckout->getExistingKlarnaOrder();

        if ( is_object($order) ) {
            $order->fetch();

            /**
             * Return HTML snippet
             */
            return $order['gui']['snippet'];
        }
    }

    public function getLastQuote()
    {
        $quote   = false;
        $session = Mage::getSingleton('checkout/session');
        $quoteId = $session->getLastQuoteId();

        if ($quoteId) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
        }

        return $quote;
    }
}
