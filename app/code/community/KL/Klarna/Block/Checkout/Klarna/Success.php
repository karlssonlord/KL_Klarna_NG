<?php

/**
 * Class KL_Klarna_Block_Checkout_Klarna_Shipping
 */
class KL_Klarna_Block_Checkout_Klarna_Success extends Mage_Checkout_Block_Onepage_Abstract {

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
        $order->fetch();

        /**
         * Reset the Klarna Checkout ID
         */
        Mage::helper('klarna/checkout')->setKlarnaCheckoutId(false);

        /**
         * Return HTML snippet
         */
        return $order['gui']['snippet'];
    }

}