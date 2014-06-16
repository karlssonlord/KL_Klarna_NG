<?php

require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'OnepageController.php';

/**
 * Class KL_Klarna_CheckoutController
 */
class KL_Klarna_CheckoutController extends Mage_Checkout_OnepageController {

    /**
     * Display the checkout
     *
     * @return void
     */
    public function indexAction()
    {
        $quote = $this->_getQuote();

        if ( $quote->getItemsCount() === '0' || $quote->getItemsCount() == null ) {
            $this->_redirectUrl(Mage::helper('core/url')->getHomeUrl());
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
        }

        $this->getOnepage()->initCheckout();

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
             * Clear the session
             * @see Mage_Checkout_OnepageController successAction
             */
            $this->getOnepage()->getCheckout()->clear();

            /**
             * Redirect if order wasn't created
             */
            if ( ! $orderObject ) {
                $this->_redirectUrl(Mage::helper('core/url')->getHomeUrl());
                return $this;
            }

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
             * Redirect to frontpage
             */
            $this->_redirect('/');
        }

    }

    /**
     * Get quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        $quote = $this->getOnepage()->getQuote();

        return $quote;
    }

    public function pushAction()
    {
        /**
         * Acknowledge order
         */
        Mage::getModel('klarna/klarnacheckout')->acknowledge($_REQUEST['klarna_order']);
    }

}
