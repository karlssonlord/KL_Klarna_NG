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
     *
     * @return void
     */
    public function successAction()
    {
        $quote = $this->_getQuote();

        Mage::helper('klarna/log')->log($quote, "successAction");

        $quoteId = $quote->getId();

        /**
         * Clear the session
         *
         * @see Mage_Checkout_OnepageController successAction
         */
        $this->getOnepage()->getCheckout()->clear();

        Mage::getSingleton('checkout/session')->setLastQuoteId($quoteId);

        $this->loadLayout();
        $layout = $this->getLayout();
        $block  = $layout->getBlock('klarna_success');
        $this->renderLayout();
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
        Mage::getModel('klarna/klarnacheckout')->acknowledge($_REQUEST['klarna_order']);
    }

    public function convertAction()
    {
        $quoteId = Mage::app()->getRequest()->getParam('qid');
        $orderId = Mage::helper('klarna/checkout')->createOrderFromQuote($quoteId);
        echo $orderId;
        die();
    }
}
