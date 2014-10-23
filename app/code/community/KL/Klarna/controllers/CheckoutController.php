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
        $error = false;
        $postData = json_decode(file_get_contents('php://input'), true);
        if(empty($postData)) {
            $errorEmailMessage = 'No data has been posted.';

            Mage::helper('klarna/log')->log(
                '',
                $errorEmailMessage
            );
            Mage::helper('klarna')->sendErrorEmail($errorEmailMessage);

            $this->getResponse()->setRedirect(Mage::getUrl('klarna/checkout/failure'), 303);
            return;
        }

        $quoteId = $postData['merchant_reference']['orderid2'];
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $isStockArray = array();

        if(strtolower($quote->getQuoteCurrencyCode()) !== strtolower($postData['purchase_currency'])) {
            $errorMessage = 'Currency mismatch. Given ' . strtolower($postData['purchase_currency']) .
                ' but should be ' . strtolower($quote->getQuoteCurrencyCode());

            $errorMessages[] = $errorMessage;

            Mage::helper('klarna/log')->log(
                $quote,
                $errorMessage
            );
            $error = true;
        }

        /**
         * Disabled since it doesn't work. When cart totals is 75,10 kr error is thrown. Error message says
         * "Totals are different. Given 7510 but should be 7510"
         * Probably some problems with float/int/string stuff
         *
        if(((float)$quote->getGrandTotal()*100) !== (float)$postData['cart']['total_price_including_tax']) {
            $errorMessage = 'Totals are different. Given ' . (float)$postData['cart']['total_price_including_tax']
                . ' but should be ' . ((float)$quote->getGrandTotal()*100);

            $errorMessages[] = $errorMessage;

            Mage::helper('klarna/log')->log(
                $quote,
                $errorMessage
            );
            $error = true;
        }
        */

        $klarnaItems = array();
        //Reorginizing the array for the following easy search
        foreach($postData['cart']['items'] as $klarnaItem) {
            if($klarnaItem['type'] === 'physical') {
                $klarnaItems[$klarnaItem['reference']] = $klarnaItem;
            }
        }

        foreach($quote->getAllVisibleItems() as $quoteItem) {
            if(empty($klarnaItems[$quoteItem->getSku()])) {
                $errorMessage = 'Item with SKU ' . $quoteItem->getSku()
                    . ' doesn\'t exist in the Klarna cart ' . ((float)$quote->getGrandTotal()*100);

                $errorMessages[] = $errorMessage;

                Mage::helper('klarna/log')->log(
                    $quote,
                    $errorMessage
                );
                $error = true;
            } else {
                if(!Mage::getModel('catalog/product')->load($quoteItem->getProductId())->isSalable()) {
                    $errorMessage = 'Item with SKU ' . $quoteItem->getSku()
                        . ' is not salable ' . ((float)$quote->getGrandTotal()*100);

                    $errorMessages[] = $errorMessage;

                    $isStockArray = array('is_stock' => 1);
                    Mage::helper('klarna/log')->log(
                        $quote,
                        $errorMessage
                    );
                    $error = true;
                } else {
                    unset($klarnaItems[$quoteItem->getSku()]);
                }
            }
        }

        if(count($klarnaItems) > 0) {
            $errorMessage = 'Klarna cart and Magento quote do not match. Klarna cart contains more products than Magento
             quote';

            $errorMessages[] = $errorMessage;

            Mage::helper('klarna/log')->log(
                $quote,
                $errorMessage
            );
            $error = true;
        }

        if($error) {
            $errorEmailMessage = implode("\n", $errorMessages);

            Mage::helper('klarna')->sendErrorEmail($errorEmailMessage, $quote);

            $this->getResponse()->setRedirect(Mage::getUrl('klarna/checkout/failure', $isStockArray), 303);
            Mage::helper('klarna/log')->log(
                $quote,
                var_export($postData, true)
            );
        } else {
            $this->getResponse()->setHttpResponseCode(200);
        }

        Mage::helper('klarna/log')->log(
            $quote,
            'Check is finished'
        );

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
}