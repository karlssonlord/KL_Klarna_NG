<?php

/**
 * Class KL_Klarna_Helper_Checkout
 */
class KL_Klarna_Helper_Checkout extends KL_Klarna_Helper_Abstract {

    /**
     * Fetch Klarna Checkout ID from the session
     *
     * @return mixed
     */
    public function getKlarnaCheckoutId()
    {
        return Mage::getSingleton('core/session')->getKlarnaCheckoutId();
    }

    /**
     * Store Klarna Checkout ID in the session
     *
     * @param $checkoutId
     * @return $this
     */
    public function setKlarnaCheckoutId($checkoutId)
    {
        Mage::getSingleton('core/session')->setKlarnaCheckoutId($checkoutId);

        return $this;
    }

    /**
     * Fetch Klarna Checkout URI for the correct environment
     *
     * @return string
     */
    public function getKlarnaBaseUri()
    {
        if ( Mage::helper('klarna')->isLive() ) {
            return 'https://checkout.klarna.com/checkout/orders';
        } else {
            return 'https://checkout.testdrive.klarna.com/checkout/orders';
        }
    }

}