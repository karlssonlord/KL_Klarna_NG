<?php

/**
 * Class KL_Klarna_Block_Checkout_Onepage_Link
 */
class KL_Klarna_Block_Checkout_Onepage_Link extends Mage_Checkout_Block_Onepage_Link {

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

