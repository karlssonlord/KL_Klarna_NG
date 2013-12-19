<?php

class KL_Klarna_Helper_Totals extends KL_Klarna_Helper_Abstract {

    public function addFeeToBlock($block)
    {
        $fee = false;

        switch ($block->getOrder()->getPayment()->getMethod()) {
            case 'klarna_invoice':
                /**
                 * Setup fee object
                 */
                $fee = new Varien_Object();
                $fee->setCode('invoice_fee_excl');
                $fee->setLabel(Mage::helper('klarna')->__('Invoice fee'));
                $fee->setBaseValue($block->getOrder()->getData('klarna_base_total')); // $baseInvoiceFeeExVat
                $fee->setValue($block->getOrder()->getData('klarna_total')); // $invoiceFeeExVat
                break;
            default:
                break;
        }

        /**
         * Add to block
         */
        if ( $fee ) {
            $block->addTotalBefore($fee, 'shipping');
        }

        return $block;
    }

}
