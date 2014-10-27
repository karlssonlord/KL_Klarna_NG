<?php
/**
 * Klarna Checkout payment method
 */
class KL_Klarna_Model_Payment_Checkout
    extends KL_Klarna_Model_Payment_Abstract
{
    /**
     * @var string
     */
    protected $_code = 'klarna_checkout';

    /**
     * @var string
     */
    protected $_infoBlockType = 'klarna/checkout_info';

    public function __construct()
    {
        if (Mage::helper('klarna')->getVersion() === '0.0.6') {
            $this->_canCapturePartial = false;
        }

        parent::__construct();
    }

    /**
     * Validation
     *
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        return $this;
    }

    /**
     * Check if payment method is available
     *
     * @param string $quote
     *
     * @return bool
     */
    public function isAvailable($quote = '')
    {
        return false;
    }

    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this|Mage_Payment_Model_Abstract
     *
     * @throws Exception
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        /**
         * Fetch the order
         */
        $order = $payment->getOrder();

        /**
         * Store reservation number
         */
        $klarnaCheckoutID = $order->getKlarnaCheckout();

        /**
         * Fetch the order
         */
        $klarnaOrder = Mage::getModel('klarna/klarnacheckout')->getOrder($klarnaCheckoutID);

        /**
         * Fetch the reservation number
         */
        $transactionId = $klarnaOrder['reservation'];

        /**
         * Set Magento payment method transaction
         */
        $payment
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(0);

        Mage::log("Auth: " . $amount . ", " . $transactionId, null, 'klarna.log', true);

        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        /**
         * Load the order
         */
        $order = $payment->getOrder();

        /**
         * Setup array of items to activate
         */
        $activate = array();

        /**
         * Setup invoice counter
         */
        $counter = 0;

        /**
         * Loop all invoices
         */
        foreach ($order->getInvoiceCollection() as $invoice) {

            /**
             * Increase counter
             */
            $counter ++;

            /**
             * If invoice ID is missing, then that's the one
             */
            if ( ! $invoice->getId() ) {

                /**
                 * Fetch all items
                 */
                foreach ($invoice->getAllItems() as $item) {

                    /**
                     * Only invoice items that has row total set
                     */
                    if ( $item->getRowTotal() > 0 ) {

                        /**
                         * Add item
                         */
                        $activate[$item->getSku()] = $item->getQty();
                    }
                }
            }
        }

        /**
         * If it's the first invoice, include shipping cost
         */
        if ( $counter == 1 && $order->getShippingMethod() ) {
            $activate[$order->getShippingMethod()] = 1;
        }

        /**
         * Get authorization transaction
         */
        $authTrans = $payment->getAuthorizationTransaction();

        /**
         * Fetch Klarna API Order model
         */
        $apiModel = Mage::getModel('klarna/api_order');

        /**
         * Activate invoice at Klarna
         */
        if ( is_object($authTrans) ) {
            if (Mage::helper('klarna')->getVersion() === '0.0.6') {
                $apiModel->activateReservation($authTrans->getTxnId());
            } else {
                $apiModel->activateReservation($authTrans->getTxnId(), $activate);
            }
        } else {
            throw new Exception('No authorization transaction exists');
        }

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract|void
     *
     * @throws Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        throw new Exception('Refund online not implemented');
        
        /**
         * Fetch the order
         */
        try {
            $klarnaOrder = Mage::getModel('klarna/klarnacheckout')->getOrder(
                $payment->getAdditionalInformation('klarnaCheckoutId')
            );
        } catch (Exception $e) {
            Mage::throwException('No order exists');
        }

        /**
         * Return field if set
         */
        if ($klarnaOrder) {
            $invoiceNumber = $klarnaOrder->offsetGet('reservation');
        } else {
            Mage::throwException('No invoice exists');
        }

        /**
         * Fetch Klarna API Order model
         */
        $apiModel = Mage::getModel('klarna/api_order');
        $return = $apiModel->createRefund($amount, $invoiceNumber);
        if($return) {
            $payment->getOrder()
                ->addStatusHistoryComment('Klarna refund successful, Klarna refund invoice id: ' . $invoiceNumber)
                ->save();
            return $this;
        }
        Mage::throwException('Refund was not created');
    }
}