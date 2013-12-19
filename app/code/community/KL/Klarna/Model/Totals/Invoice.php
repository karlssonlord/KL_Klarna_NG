<?php

class KL_Klarna_Model_Totals_Invoice extends Mage_Sales_Model_Order_Invoice_Total_Abstract {

    // Collect the totals for the invoice
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {

        $order = $invoice->getOrder();

        $myTotal = $order->getKlarnaTotal();

        $baseMyTotal = $order->getBaseKlarnaTotal();

        Mage::log($myTotal . ' xx ' . $baseMyTotal);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $myTotal);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseMyTotal);

        Mage::log('invoice collected');
        return $this;
    }

}