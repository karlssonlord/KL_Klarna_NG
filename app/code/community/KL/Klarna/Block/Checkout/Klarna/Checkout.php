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
        try {
            $data = $model->handleOrder();
        } catch (Exception $e) {
            $data = false;
        }

        /**
         * If no data was received, redirect to legacy checkout
         */
        if ( ! $data ) {

            $model = new Mage_Checkout_Block_Cart_Sidebar();
            Mage::app()->getFrontController()
                ->getResponse()
                ->setRedirect($model->getCheckoutUrl());

        }
    }

}