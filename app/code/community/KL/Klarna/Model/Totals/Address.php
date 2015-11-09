<?php
class KL_Klarna_Model_Totals_Address
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_calculator = null;

    public function __construct()
    {
        $this->setCode('klarna_total');
        $this->_calculator = Mage::getSingleton('tax/calculation');
    }

    /**
     * Calculate your total value
     *
     * @param Mage_Sales_Model_Quote_Address $address Quote address
     *
     * @return Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $helper = Mage::helper('klarna');
        $helper->log('Preparing to collect totals');

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

        if ($quote->getId()) {
            try {

                $payment = $quote->getPayment();

                if (is_object($payment)) {
                    $paymentCode = $payment->getMethodInstance()->getCode();
                } else {
                    $paymentCode = false;
                }

                $helper->log('Quote found with ID ' . $quote->getId() . ' ' . $address->getAddressType());

                /**
                 * Make sure it's a payment that has a fee and it's a shipping
                 * address, otherwise the invoice fee won't be added.
                 */
                if ($address->getAddressType() == 'shipping'
                    && $paymentCode == 'klarna_invoice'
                ) {
                    $helper->log('Updating quote since we\'re using ' . $paymentCode);

                    /**
                     * Fetch payment method invoice fee
                     */
                    $fee           = $helper->getConfig('fee', $paymentCode);
                    $calc          = $this->_calculator;
                    $rate          = Mage::getStoreConfig('payment/klarna_invoice/fee_tax_percent');
                    $taxAmount     = $fee - ($fee * 1 / (1 + ($rate / 100)));
                    $baseTaxAmount = $taxAmount;

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

                    $address->setKlarnaTaxAmount($taxAmount);
                    $address->setBaseKlarnaTaxAmount($taxAmount);
                    $address->setTaxAmount($address->getTaxAmount() + $taxAmount);
                    $address->setBaseTaxAmount($address->getBaseTaxAmount() + $baseTaxAmount);
                    $address->setGrandTotal($address->getGrandTotal() + $fee);
                    $address->setBaseGrandTotal($address->getBaseGrandTotal() + $fee);

                    Mage::helper('klarna')->log('Set fee ' . $fee);
                }

                /**
                 * Remove fee if any other payment option
                 */
                if ($paymentCode !== 'klarna_invoice') {
                    $address->setKlarnaTotal(null);
                    $address->setBaseKlarnaTotal(null);

                    $address->getQuote()
                        ->setKlarnaTotal(null)
                        ->setBaseKlarnaTotal(null);

                    $address->setKlarnaTaxAmount(null);
                    $address->setBaseKlarnaTaxAmount(null);

                    $address->getQuote()
                        ->setKlarnaTaxAmount(null)
                        ->setBaseKlarnaTaxAmount(null);
                }
            } catch (Exception $e) {
                $helper->log('Exception when calling collect: ' . $e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Show in cart and review summary
     *
     * @param Mage_Sales_Model_Quote_Address $address Quote address
     *
     * @return $this|array
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        /**
         * Add to totals summary if Klarna Total is set
         */
        $quote = $address->getQuote();
        $payment = $quote->getPayment();

        if (is_object($payment)) {
            $paymentCode = $payment->getMethodInstance()->getCode();
        } else {
            $paymentCode = false;
        }

        if ( $paymentCode == 'klarna_invoice' && $address->getKlarnaTotal() > 0 ) {
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
