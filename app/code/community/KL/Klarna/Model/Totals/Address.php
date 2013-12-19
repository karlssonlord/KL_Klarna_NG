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
        Mage::helper('klarna')->log('Preparing to collect totals');

        /**
         * Let the parent function do it's magic
         */
        parent::collect($address);

        /**
         * Reset previous values
         */
        $this->_setAmount(0);
        $this->_setBaseAmount(0);

        /**
         * Fetch the quote
         */
        $quote = $address->getQuote();

        /**
         * Fetch the payment if quote exists
         */
        if ( $quote->getId() ) {

            try {

                Mage::helper('klarna')->log('Quote found with ID ' . $quote->getId());

                $payment = $quote->getPayment()->getMethodInstance();

                /**
                 * Make sure it's a payment that has a fee and it's a shipping address, otherwise
                 * the invoice fee won't be added.
                 */
                if ( $address->getAddressType() == 'shipping' && is_object($payment) && $payment->getCode(
                    ) == 'klarna_invoice'
                ) {

                    Mage::helper('klarna')->log('Updating quote since we\'re using ' . $payment->getCode());

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
                    $address->getQuote()
                        ->setKlarnaTotal($fee)
                        ->setBaseKlarnaTotal($fee);

                    Mage::helper('klarna')->log('Set fee ' . $fee);
                }

                /**
                 * Remove fee if any other payment option
                 */
                if ( $payment->getCode() !== 'klarna_invoice' ) {
                    /**
                     * Remove the fee to the quote object
                     */
                    $address->getQuote()
                        ->setKlarnaTotal(null)
                        ->setBaseKlarnaTotal(null);

                    /**
                     * @todo Recollect totals?
                     */

                }
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Show in cart and review summary
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