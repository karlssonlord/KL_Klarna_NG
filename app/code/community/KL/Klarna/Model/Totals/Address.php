<?php
class KL_Klarna_Model_Totals_Address extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    /**
     * Calculate your total value
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return $this|Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        /**
         * Let the parent function do it's magic
         */
        parent::collect($address);

        /**
         * Fetch the payment
         */
        $payment = $address->getQuote()->getPayment()->getMethodInstance();

        /**
         * Make sure it's a payment that has a fee and it's a billing address
         */
        if ( $address->getAddressType() == 'billing' && $payment->getCode() == 'klarna_invoice' ) {

            /**
             * Fetch payment method invoice fee
             */
            $fee = Mage::helper('klarna')->getConfig('fee', $payment->getCode());

            /**
             * Add store view currency amount
             */
            $this->_addAmount($fee);

            /**
             * Add base currency amount
             */
            $this->_addBaseAmount($fee);

            /**
             * Also store in address for later reference in fetch()
             */
            $address->setKlarnaTotal($fee);
            $address->setBaseKlarnaTotal($fee);

            /**
             * Add the fee to the quote object
             */
            $address->getQuote()->setData('klarna_fee', $fee);
        }

        /**
         * Remove fee if any other payment option
         */
        if ( $payment->getCode() !== 'klarna_invoice' ) {
            /**
             * Remove the fee to the quote object
             */
            $address->getQuote()->setData('klarna_fee', null);
        }

        return $this;
    }

    /**
     * Show in cart summary
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return $this|array
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        /**
         * Add to totals summary if Klarna Total is set
         */
        if ( $address->getKlarnaTotal() > 0 ) {
            $address->addTotal(
                array(
                    'code' => $this->getCode(),
                    'title' => Mage::helper('klarna')->__('Invoice fee:'),
                    'value' => $address->getKlarnaTotal()
                )
            );
        }

        return $this;
    }

}