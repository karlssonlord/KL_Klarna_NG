<?php

class KL_Klarna_Model_Invoice_Refund extends KL_Klarna_Model_Invoice_Abstract
{
    public function refund()
    {
        $payment    = $this->getPayment();
        $order      = $payment->getOrder();
        $invoiceId  = $payment->getParentTransactionId();

        $this->request()->setCurrency($order->getOrderCurrency());
        $this->request()->setEmail($order->getCustomerEmail());
        $this->request()->setBillingAddress($order->getBillingAddress());
        $this->request()->setInvoiceId($invoiceId);

        $result = $this->request()->creditInvoice();

        $payment->setTransactionId($invoiceId . "-refund");

        return $this;
    }
}
