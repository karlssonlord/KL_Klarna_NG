<?php

/**
 * Class KL_Klarna_Block_Checkout_Klarna_Checkout
 */
class KL_Klarna_Block_Checkout_Klarna_Checkout extends Mage_Core_Block_Template {


    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('customer')->__('Checkout'));
        return parent::_prepareLayout();
    }
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

        return $data;
    }

    /** Show veterinar popup? This is Animail-specific code. */

    public function getVeterinarProducts(){
        $veterinaryProducts = array();
        $vererinaryHtml = array();

        // Fetch instance of products and if it's not set
        $products = Mage::getModel('catalog/product');

        // Fetch the cart
        $cart = Mage::helper('checkout/cart')->getCart();

        // Loop through the cart
        foreach($cart->getItems() as $_item) {
            // Load the product
            $_product = $products->load($_item->getProductId());

            // Check the product
            if ($_product->getStandardvet() == 1 || $_product->getVeterinar() == 1) {
                $veterinaryProducts[] = $_product->getName();
            }
        }

        if(count($veterinaryProducts)){
            Mage::getSingleton('core/session')->setShowVetDialog(true);
            return $veterinaryProducts;
        }
        Mage::getSingleton('core/session')->setShowVetDialog(false);
        return false;
    }

}