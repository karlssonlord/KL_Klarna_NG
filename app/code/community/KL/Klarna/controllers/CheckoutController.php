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
         * Render layout
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
         * Create the order
         */
        try {
            /**
             * Create the order
             */
            $orderObject = Mage::getModel('klarna/klarnacheckout_order')->create();

            /**
             * Render layout
             */
            $this->loadLayout();
            $layout = $this->getLayout();
            $block = $layout->getBlock('klarna_success');
            $block->setOrder($orderObject);

            $this->renderLayout();

        } catch (Exception $e) {

            /**
             * Log the exception
             */
            Mage::helper('klarna')->log('Exception when trying to create order: ' . $e->getMessage());

            /**
             * Throw error to frontend
             */
            throw new Exception($e->getMessage());

        }

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
         * Render layout
         */
        $this
            ->loadLayout()
            ->renderLayout();
    }

}
