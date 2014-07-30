<?php
class KL_Klarna_Model_Totals_Invoice
    extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    /**
     * Collect invoice totals
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice The invoice
     *
     * @return Mage_Sales_Model_Order_Invoice_Total_Abstract
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order         = $invoice->getOrder();
        $myTotal       = $order->getKlarnaTotal();
        $baseMyTotal   = $order->getBaseKlarnaTotal();
        $taxAmount     = $order->getKlarnaTaxAmount();
        $baseTaxAmount = $order->getBaseKlarnaTaxAmount();

        $invoice->setTaxAmount($invoice->getTaxAmount() + $taxAmount);
        $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $baseTaxAmount);
        $invoice->setGrandTotal($invoice->getGrandTotal() + $myTotal);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseMyTotal);

        return $this;
    }
}
