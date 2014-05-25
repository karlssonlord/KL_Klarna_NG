<?php

/**
 * Include the controller we're extending
 */
require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';

/**
 * Class ShippingController
 */
class KL_Klarna_CouponController extends Mage_Checkout_CartController {

    /**
     * Set back redirect url to response
     *
     * @return Mage_Checkout_CartController
     */
    protected function _goBack()
    {
        $this->_redirect('klarna/checkout');
        return $this;
    }
}