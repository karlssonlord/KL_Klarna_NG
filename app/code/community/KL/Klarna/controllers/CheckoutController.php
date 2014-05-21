<?php

/**
 * Class KL_Klarna_CheckoutController
 */
class KL_Klarna_CheckoutController extends Mage_Core_Controller_Front_Action {

    /**
     * Display the checkout
     *
     * @return void
     */
    public function indexAction()
    {
        /**
         * Render layoyt
         */
        $this
            ->loadLayout()
            ->renderLayout();
    }

    /**
     * Display the success page
     */
    public function successAction()
    {
        /**
         * Reset the Magento quote
         */
        Mage::getSingleton('checkout/session')->setQuoteId(null);

        /**
         * Render layoyt
         */
        $this
            ->loadLayout()
            ->renderLayout();
    }

    public function termsAction()
    {
        echo "Terms";
    }

    public function pushAction()
    {
        echo "Push";
    }

}
