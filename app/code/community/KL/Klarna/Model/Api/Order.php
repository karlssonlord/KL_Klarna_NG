<?php

class KL_Klarna_Model_Api_Order extends KL_Klarna_Model_Api_Abstract {

    protected $_klarnaOrder;

    public function _construct()
    {
        /**
         * Setup Klarna API
         */
        $storeId = $this->getData('store_id') ? : null;
        $this->_klarnaOrder = $this->getApi(null, $storeId);
    }

    /**
     * Create reservation
     *
     * @param $socialSecurityNumber
     * @param $amount
     * @param int $pclass
     *
     * @return mixed
     */
    public function createReservation($socialSecurityNumber, $amount = -1, $pclass = KlarnaPClass::INVOICE)
    {
        Mage::helper('klarna')->log("Reservera: " . $amount);

        /**
         * Reserve the amount
         */
        try {
            $result = $this->_klarnaOrder->reserveAmount(
                $socialSecurityNumber, // PNO (Date of birth for DE and NL).
                null, // Gender.
                $amount, // Amount. -1 specifies that calculation should calculate the amount using the goods list
                KlarnaFlags::NO_FLAG, // Flags to affect behavior.
                $pclass // -1 notes that this is an invoice purchase, for part payment purchase you will have a pclass object on which you use getId().
            );
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->log(
                '#' . $e->getCode() . ': ' . $e->getMessage()
            );
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }

        return $result;
    }

    /**
     * @param amount
     * @param $invoiceNumber
     * @return mixed
     */
    public function createRefund($amount, $invoiceNumber)
    {
        Mage::helper('klarna')->log(
            'Prepare refund of amount ' . $amount . ' on Klarna invoice number ' . $invoiceNumber
        );

        /**
         * Create the refund
         */
        try {
            $result = $this->_klarnaOrder->returnAmount(
                $invoiceNumber,
                $amount,
                25, // 25% VAT
                KlarnaFlags::INC_VAT, // Amount including VAT.
                Mage::helper('klarna')->__('Refund')
            );
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->log(
                '#' . $e->getCode() . ': ' . $e->getMessage()
            );
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }

        Mage::helper('klarna')->log(
            'Refund complete'
        );

        return $result;
    }

    /**
     * Cancel reservation
     *
     * @param $reservationNumber
     * @return mixed
     */
    public function cancelReservation($reservationNumber)
    {
        Mage::helper('klarna')->log(
            'Prepare to cancel reservation'
        );

        /**
         * Cancel the reservation
         */
        try {
            $result = $this->_klarnaOrder->cancelReservation($reservationNumber);
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->log(
                '#' . $e->getCode() . ': ' . $e->getMessage()
            );
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }

        Mage::helper('klarna')->log(
            'Cancel reservation complete'
        );

        return $result;
    }

    /**
     * Check the reservation status if the order is declined or accepted
     *
     * @param $reservationNumber
     * @return bool
     */
    public function checkOrderStatus($reservationNumber)
    {
        try {
            $result = $this->_klarnaOrder->checkOrderStatus($reservationNumber);
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->log(
                '#' . $e->getCode() . ': ' . $e->getMessage()
            );
            return false;
        }

        return $result;
    }

    /**
     * Activate reservation
     *
     * @param $reservationNumber
     * @param array $items
     * @return mixed
     */
    public function activateReservation($reservationNumber, $items = array())
    {
        Mage::helper('klarna')->log(
            'Prepare to activate amount'
        );

        if ( count($items) ) {

            /**
             * Add each item
             */
            foreach ($items as $sku => $qty) {

                Mage::helper('klarna')->log(
                    'Adding ' . $qty . ' pcs of sku ' . $sku
                );

                $this->_klarnaOrder->addArtNo($qty, $sku);
            }

        }

        /**
         * Activate the amount
         */
        try {
            $result = $this->_klarnaOrder->activate($reservationNumber);
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->log(
                '#' . $e->getCode() . ': ' . $e->getMessage()
            );
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }

        Mage::helper('klarna')->log(
            'Activation complete'
        );

        return $result;

    }

    /**
     * Populate Klarna object with the use of a credit memo
     *
     * @param $creditMemo
     * @return mixed
     */
    public function populateFromCreditMemo($creditMemo)
    {
        /**
         * Log the start
         */
        Mage::helper('klarna')->log('Preparing to populate order from credit memo');

        /**
         * Make sure order object is set
         */
        if ( ! is_object($creditMemo) ) {
            Mage::throwException(Mage::helper('klarna')->__('No items found in credit memo!'));
        }

        /**
         * See what product(s) to refund
         */
        foreach ($creditMemo->getItemsCollection() as $item) {

            /**
             * Fetch the product
             */
            $product = $item->getOrderItem();

            /**
             * Make sure it's simple only
             */
            if ( $product->getParentItem() ) {

                /**
                 * Add product that should be refunded
                 */
                $this->_klarnaOrder->addArtNo($item->getQty(), $item->getSku());

                /**
                 * Add quantity and product for logging purposes
                 */
                Mage::helper('klarna')->log(
                    'Added ' . $item->getQty() . ' pcs of SKU ' . $item->getSku() . ' for refund'
                );
            }

        }

        return $this;
    }

    /**
     * Populate Klarna object with the use of an order
     *
     * @param $order
     *
     * @return $this
     */
    public function populateFromOrder($order)
    {
        /**
         * Make sure order object is set
         */
        if ( ! is_object($order) || ! is_array($order->getAllVisibleItems()) ) {
            Mage::throwException(Mage::helper('klarna')->__('No items found in quote!'));
        }

        /**
         * Loop all products in quote
         */
        foreach ($order->getAllVisibleItems() as $item) {

            /**
             * Fetch product qty
             */
            $qty = $item->getQty();
            if ( ! $qty ) {
                $qty = $item->getQtyOrdered();
            }

            /**
             * Add the product
             */
            $this->_klarnaOrder->addArticle(
                $qty,
                utf8_decode($item->getSku()),
                utf8_decode($item->getName()),
                $item->getPriceInclTax(),
                $item->getTaxPercent(), // TAX rate
                0, // Discount
                KlarnaFlags::INC_VAT
            );
        }

        /**
         * Add shipping cost (if any)
         */
        if ( $order->getShippingAmount() > 0 ) {
            $this->_klarnaOrder->addArticle(
                1,
                'shipping_fee',
                utf8_decode(Mage::helper('klarna')->__('Shipping fee')),
                ($order->getShippingAmount() + $order->getShippingTaxAmount()), //price
                (($order->getShippingTaxAmount() / $order->getShippingAmount()) * 100),
                0, // Discount
                KlarnaFlags::INC_VAT
            );
        }

        /**
         * Add invoice cost (if any)
         */
        if ( $order->getData('base_klarna_total') > 0 ) {
            $this->_klarnaOrder->addArticle(
                1,
                'invoice_fee',
                utf8_decode(Mage::helper('klarna')->__('Invoice fee')),
                $order->getData('base_klarna_total'),
                Mage::helper('klarna')->getConfig('fee_tax_percent', 'invoice'),
                0, // Discount
                KlarnaFlags::INC_VAT
            );
        }

        if ( $order->getDiscountAmount() <> 0 ) {

            $discount = $order->getDiscountAmount();
            $defaultTaxDiscount = Mage::helper('klarna')->getConfig('fee_tax_percent', 'invoice');

            if ( Mage::getStoreConfig('tax/calculation/discount_tax') == '0' ) {
                $defaultTaxDiscountValue = ($defaultTaxDiscount / 100) + 1;
                $discount = $discount * $defaultTaxDiscountValue;
            }

            $this->_klarnaOrder->addArticle(
                1,
                'discount',
                $order->getDiscountDescription(),
                $discount,
                $defaultTaxDiscount,
                0, // Discount
                KlarnaFlags::INC_VAT
            );

        }

        /**
         * Set billing address
         */
        $billingAddress = Mage::helper('klarna/address')->fromMagentoToKlarna(
            $order->getBillingAddress(),
            $order->getCustomerEmail()
        );
        $this->_klarnaOrder->setAddress(KlarnaFlags::IS_BILLING, $billingAddress);

        /**
         * Set shipping address
         */
        $shippingAddress = Mage::helper('klarna/address')->fromMagentoToKlarna(
            $order->getShippingAddress(),
            $order->getCustomerEmail()
        );
        $this->_klarnaOrder->setAddress(KlarnaFlags::IS_SHIPPING, $shippingAddress);

        /**
         * Set Magento order id
         */
        $this->_klarnaOrder->setEstoreInfo($order->getIncrementId());

        return $this;
    }

    public function emailInvoice($invoiceNumber)
    {
        try {
            $this->_klarnaOrder->emailInvoice($invoiceNumber);
        } catch (KlarnaException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function postalInvoice($invoiceNumber)
    {
        try {
            $this->_klarnaOrder->sendInvoice($invoiceNumber);
        } catch (KlarnaException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
