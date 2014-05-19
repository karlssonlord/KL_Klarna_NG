<?php

/**
 * Class KL_Klarna_Block_Checkout_Cart_Sidebar
 */
class KL_Klarna_Block_Checkout_Cart_Sidebar extends Mage_Checkout_Block_Cart_Sidebar {

    /**
     * Get one page checkout page url
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        if (Mage::helper('klarna')->isKcoEnabled()) {
            return $this->getUrl('klarna/checkout');
        } else {
            return parent::getCheckoutUrl();
        }
    }

}

