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
         * Prepare totals
         */
        Mage::getModel('klarna/klarnacheckout')->prepareTotals();

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

    public function pushAction()
    {
        /**
         * Acknowledge order
         */
        Mage::getModel('klarna/klarnacheckout')->acknowledge($_REQUEST['klarna_order']);
    }

    public function termsAction()
    {
        /**
         * Render layoyt
         */
        $this
            ->loadLayout()
            ->renderLayout();
    }

}
