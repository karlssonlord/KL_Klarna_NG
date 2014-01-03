<?php

class KL_Klarna_Model_Api_Order extends KL_Klarna_Model_Api_Abstract {

    protected $_klarnaOrder;

    public function _construct()
    {
        /**
         * Setup Klarna API
         */
        $this->_klarnaOrder = $this->getApi();
    }

    /**
     * Create reservation
     *
     * @param $socialSecurityNumber
     * @param $amount
     *
     * @return mixed
     */
    public function createReservation($socialSecurityNumber, $amount = -1)
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
                KlarnaPClass::INVOICE // -1 notes that this is an invoice purchase, for part payment purchase you will have a pclass object on which you use getId().
            );
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->log(
                '#' . $e->getCode() . ': ' . $e->getMessage()
            );
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }

        return $result;
    }

    public function activateReservation($reservationNumber)
    {
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

        return $result;
    }

    /**
     * Populate Klarna order object with the use of an order
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
                $item->getSku(),
                $item->getName(),
                $item->getPriceInclTax(),
                $item->getTaxPercent(), // TAX rate
                0, // Discount
                KlarnaFlags::INC_VAT
            );
        }

        /**
         * Set billing address
         */
        $billingAddress = Mage::helper('klarna/address')->fromMagentoToKlarna($order->getBillingAddress());
        $this->_klarnaOrder->setAddress(KlarnaFlags::IS_BILLING, $billingAddress);

        /**
         * Set shipping address
         */
        $shippingAddress = Mage::helper('klarna/address')->fromMagentoToKlarna($order->getShippingAddress(), $order->getBillingAddress()->getEmail());
        $this->_klarnaOrder->setAddress(KlarnaFlags::IS_SHIPPING, $shippingAddress);

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