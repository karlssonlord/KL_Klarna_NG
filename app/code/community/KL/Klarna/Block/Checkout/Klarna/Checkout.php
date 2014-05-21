<?php

/**
 * Class KL_Klarna_Block_Checkout_Klarna_Checkout
 */
class KL_Klarna_Block_Checkout_Klarna_Checkout extends Mage_Core_Block_Template {

    /**
     * Fetch Klarna HTML
     */
    public function getKlarnaHTML()
    {
        /**
         * Fetch the Klarna model
         */
        $model = Mage::getModel('klarna/klarnacheckout');

        /**
         * Create the order and return the checkout HTML
         */
        return $model->createOrder();
    }

}