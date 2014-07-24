<?php

/**
 * Display invoice fee on a order
 */
class KL_Klarna_Block_Order_Totals_Fee extends Mage_Sales_Block_Order_Totals {

    protected function initTotals()
    {
        $payment = $this->getOrder()->getPayment();
        if ( $payment->getMethod() != "klarna_invoice" ) {
            return $this;
        }

        $order = $this->getOrder();
        if ( ! $order->getKlarnaTotal() ) {
            return $this;
        }

        $parent = $this->getParentBlock();

        $fee = new Varien_Object();
        $fee->setCode('invoice_fee_excl');
        $fee->setLabel(Mage::helper('klarna')->__('Invoice fee'));
        $fee->setBaseValue($order->getData('klarna_base_total'));
        $fee->setValue($order->getData('klarna_total'));

        $parent->addTotal($fee, 'subtotal');

        return $this;
    }
}