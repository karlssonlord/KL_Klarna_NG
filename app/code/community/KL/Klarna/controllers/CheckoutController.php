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
         * A chance for listeners to set messages on the quote items
         */
        if ($this->_getQuote()->getIsSubscription()) {
            Mage::dispatchEvent('time_to_set_messages_was_passed',
                array('quote' => $this->_getQuote())
            );
        }


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
        /**
         * The quote we want to use for tracking will be inactive if
         * Klarna has been able to create the order in Magento already.
         * In order to still get hold of the right quote we use the
         * Klarna Checkout ID to look up the right quote object. If we
         * still fail we fallback to the old method just trying to get
         * the quote not caring about if it's active or not (and that's
         * where we might fail).
         */
        $klarnaCheckoutId = Mage::helper('klarna/checkout')->getKlarnaCheckoutId();

        $quote = Mage::getModel('sales/quote')
            ->getCollection()
            ->addFieldToFilter('klarna_checkout', $klarnaCheckoutId)
            ->getFirstItem();

        if ($quote && is_object($quote)) {
            $quoteId = $quote->getId();
        } else {
            $quote   = $this->_getQuote();
            $quoteId = $quote->getId();
        }

        Mage::helper('klarna/log')->log($quote, "successAction");

        Mage::dispatchEvent('klarna_checkout_controller_success_before', array('quote' => $quote));

        /**
         * Subscribe to or unsubscribe from newsletter
         *
         * @todo Move to observer
         */
        $email = $quote->getShippingAddress()->getEmail();

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
         * We need to deactivate the current quote manually here since the
         * quote will be converted into an order only upon push call from
         * Klarna to the store
         */
        if ($quote->getIsActive()) {
            $quote->setIsActive(false);
            $quote->save();
        }

        /*
         * We create a new empty quote here for the future use. Should be
         * considered as a precaution to avoid using old active quotes from
         * the past.
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

        /*
         * We reset checkoutId stored in the session so that we don't fetch finished order from Klarna and don't try to
         * use it
         */
        Mage::helper('klarna/checkout')->setKlarnaCheckoutId('');

        Mage::dispatchEvent('klarna_checkout_controller_success_after', array('quote' => $quote));
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
            Mage::helper('klarna/log')->log(null, "Validation routine threw KL_Klarna_Model_Exception_InsufficientStockLevel: " . $e->getMessage());
            Mage::helper('klarna')->sendErrorEmail($e->getMessage(), null);
            $location = Mage::getUrl('klarna/checkout/failure');
            $this->send303($location);

        }  catch (KL_Klarna_Model_Exception_UnsalableProduct $e) {
            Mage::helper('klarna/log')->log(null, "Validation routine threw KL_Klarna_Model_Exception_UnsalableProduct: " . $e->getMessage());
            $location = Mage::getUrl('klarna/checkout/failure').'?is_stock=1';
            $this->send303($location);

        }  catch (Exception $e) {
            Mage::helper('klarna/log')->log(null, "Validation routine threw Exception: " . $e->getMessage());
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


    /**
     * @param $quote
     */
    private function saveCustomerIdOnQuote($quote)
    {
        $websiteId = Mage::getModel('core/store')->load($quote->getStoreId())->getWebsiteId();

        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($quote->getCustomerEmail());

        $quote->setCustomerId($customer->getId());
        $quote->setGroupId($customer->getGroupId());
        $quote->save();
    }
}