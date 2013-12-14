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
     *
     * @return mixed
     */
    public function createReservation($socialSecurityNumber)
    {
        /**
         * Reserv the amount
         */
        try {
            $result = $this->_klarnaOrder->reserveAmount(
                $socialSecurityNumber, // PNO (Date of birth for DE and NL).
                null, // Gender.
                // Amount. -1 specifies that calculation should calculate the amount
                // using the goods list
                - 1,
                KlarnaFlags::NO_FLAG, // Flags to affect behavior.
                // -1 notes that this is an invoice purchase, for part payment purchase
                // you will have a pclass object on which you use getId().
                KlarnaPClass::INVOICE
            );
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->log(
                Mage::log($e->getCode() . ': ' . $e->getMessage())
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

            // @todo fetch class tax rate

            /**
             * Add the product
             */
            $this->_klarnaOrder->addArticle(
                $qty,
                $item->getSku(),
                $item->getName(),
                $item->getPriceInclTax(),
                25, // TAX rate
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
        $shippingAddress = Mage::helper('klarna/address')->fromMagentoToKlarna($order->getBillingAddress());
        $this->_klarnaOrder->setAddress(KlarnaFlags::IS_SHIPPING, $shippingAddress);

        return $this;
    }

}