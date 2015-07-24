<?php

require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'OnepageController.php';

/**
 * Class KL_Klarna_CheckoutController
 */
class KL_Klarna_CheckoutController extends Mage_Checkout_OnepageController {

    protected $validateRequestValidator;

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

        Mage::dispatchEvent('klarna_checkout_controller_success_before', array('quote' => $quote));

        $quoteId = $quote->getId();

        /**
         *
         * (un)subscribe to newsletter
         */
        $email           = $quote->getShippingAddress()->getEmail();

        if (Mage::getSingleton('checkout/session')->getWantsNewsletter()){
            Mage::getModel('newsletter/subscriber')->subscribe($email);
        } else {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
            if ($subscriber->getSubscriberStatus()){
                $subscriber->unsubscribe();
            }
        }

        /**
         * Clear the session
         *
         * @see Mage_Checkout_OnepageController successAction
         */
        $this->getOnepage()->getCheckout()->clear();

        Mage::getSingleton('checkout/session')->setLastQuoteId($quoteId);

        $this->loadLayout();
        $layout = $this->getLayout();
        $layout->getBlock('klarna_success');

        /*
         * We need to deactivate the current quote manually here since the quote will be converted into an order only
         * upon push call from Klarna to the store
         */
        $quote->setIsActive(false);
        $quote->save();

        /*
         * We create a new empty quote here for the future use. Should be considered as a precaution to avoid using
         * old active quotes from the past.
         */
        Mage::getModel('sales/quote')
            ->assignCustomer(
                Mage::getSingleton('customer/session')->getCustomer()
            )
            ->setIsActive(true)
            ->setStoreId(
                Mage::app()->getStore()->getStoreId()
            )
            ->save();

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
        $klarnaId = $_REQUEST['klarna_order'];

        /**
         * Workaround: Avoid duplicate orders through Klarna being impatient with Magento order creation
         */
        if (Mage::getModel('klarna/pushlock')->isLocked($klarnaId)) return;

        Mage::getModel('klarna/klarnacheckout')->acknowledge($_REQUEST['klarna_order']);
    }

    public function convertAction()
    {
        $quoteId = Mage::app()->getRequest()->getParam('qid');
        $orderId = Mage::helper('klarna/checkout')->createOrderFromQuote($quoteId);
        echo $orderId;
        die();
    }

    /**
     * Validates the Klarna order to make sure its still available for purchase
     *
     * @return void
     */
    public function validateAction()
    {
        $this->validateRequestValidator = new KL_Klarna_Model_Validation_KlarnaValidateRequest;
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            $this->validateRequestValidator->validate($input);

        } catch (KL_Klarna_Model_Exception_InsufficientStockLevel $e) {
            Mage::helper('klarna')->sendErrorEmail($e->getMessage(), null);
            $location = Mage::getUrl('klarna/checkout/failure');
            $this->send303($location);

        }  catch (KL_Klarna_Model_Exception_UnsalableProduct $e) {
            $location = Mage::getUrl('klarna/checkout/failure').'?is_stock=1';
            $this->send303($location);

        }  catch (Exception $e) {
            $location = Mage::getUrl('klarna/checkout/failure');
            $this->send303($location);
        }

        $this->getResponse()->setHttpResponseCode(200);
    }

    /**
     * Provides error message if necessary and redirects to the checkout page showing the error message.
     *
     * @return void
     */
    public function failureAction()
    {
        if(!$this->getRequest()->getParam('is_stock')) {
            Mage::getSingleton("core/session")->addError('We could  not fulfil your order. Please try again or contact our support.');
        }
        $this->getResponse()->setRedirect(Mage::getUrl('klarna/checkout'));
    }

    public function send303($location)
    {
        header("HTTP/1.1 303 See Other");
        header("Location: {$location}");
        exit;
    }
}