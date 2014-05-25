<?php

class KL_Klarna_Block_Checkout_Info extends KL_Klarna_Block_Info {

    /**
     * @var object
     */
    protected $_klarnaOrder;

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('klarna/checkout-info.phtml');
    }

    /**
     * Fetch complete checkout ID (URL)
     *
     * @return bool
     */
    protected function getCompleteCheckoutID()
    {
        return $this->getPaymentField('klarnaCheckoutId');
    }

    /**
     * Fetch field from Klarna order object
     *
     * @param $field
     * @return mixed
     */
    protected function getKlarnaOrderField($field)
    {
        /**
         * Fetch order if not fetched
         */
        if ( ! $this->_klarnaOrder ) {

            /**
             * Load our Klarna Checkout model
             */
            $checkout = Mage::getModel('klarna/klarnacheckout');

            /**
             * Fetch the order
             */
            $this->_klarnaOrder = $checkout->getOrder($this->getCompleteCheckoutID());

        }

        /**
         * Return field if set
         */
        return $this->_klarnaOrder->offsetGet($field);
    }

    /**
     * Get checkout ID
     *
     * @return array
     */
    public function getCheckoutID()
    {
        /**
         * Explode it
         */
        $klarnaCheckoutId = explode('/', $this->getCompleteCheckoutID());

        /**
         * Use the last bit in the URL
         */
        $klarnaCheckoutId = $klarnaCheckoutId[(count($klarnaCheckoutId) - 1)];

        return $klarnaCheckoutId;
    }

    /**
     * Get order status
     *
     * @return mixed
     */
    public function getKlarnaStatus()
    {
        return Mage::helper('klarna')->__('status_' . $this->getKlarnaOrderField('status'));
    }

    /**
     * Get order reference
     *
     * @return mixed
     */
    public function getKlarnaReference()
    {
        return $this->getKlarnaOrderField('reference');
    }

    /**
     * Get order reservation
     *
     * @return mixed
     */
    public function getKlarnaReservation()
    {
        return $this->getKlarnaOrderField('reservation');
    }

    /**
     * Get order purchase country
     *
     * @return string
     */
    public function getKlarnaPurchaseCountry()
    {
        return strtoupper($this->getKlarnaOrderField('purchase_country'));
    }

    /**
     * Get order purchase currency
     *
     * @return string
     */
    public function getKlarnaPurchaseCurrency()
    {
        return strtoupper($this->getKlarnaOrderField('purchase_currency'));
    }

}