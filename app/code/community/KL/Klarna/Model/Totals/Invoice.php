<?php

class KL_Klarna_Model_Totals_Invoice extends Mage_Sales_Model_Order_Invoice_Total_Abstract {

    // Collect the totals for the invoice
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {

        /**
         * Körs när invoice eller credit memo skapas
         */



        Mage::log('invoice collect');
        return $this;
    }


}