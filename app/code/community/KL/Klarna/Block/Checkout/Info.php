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

    protected function getCompleteCheckoutID()
    {
        return $this->getPaymentField('klarnaCheckoutId');
    }

    protected function getKlarnaOrderField($field)
    {
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


    public function getKlarnaStatus()
    {
        return $this->getKlarnaOrderField('status');
    }

    public function getKlarnaReference()
    {
        return $this->getKlarnaOrderField('reference');
    }

    public function getKlarnaReservation()
    {
        return $this->getKlarnaOrderField('reservation');
    }

    public function getKlarnaPurchaseCountry()
    {
        return $this->getKlarnaOrderField('purchase_country');
    }

    public function getKlarnaPurchaseCurrency()
    {
        return $this->getKlarnaOrderField('purchase_currency');
    }

}