<?php

class KL_Klarna_Block_Adminhtml_Sales_Order_Invoice_Totals extends Mage_Adminhtml_Block_Sales_Order_Invoice_Totals {

    protected function _initTotals()
    {
        parent::_initTotals();

        Mage::helper('klarna/totals')->addFeeToBlock($this);
    }

}