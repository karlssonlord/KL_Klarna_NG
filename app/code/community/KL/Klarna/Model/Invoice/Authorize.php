<?php

class KL_Klarna_Model_Invoice_Authorize extends KL_Klarna_Model_Invoice_Abstract
{
    public function authorize()
    {
        $payment    = $this->getPayment();
        $order      = $payment->getOrder();
        $products   = $order->getAllVisibleItems();

        $this->request()->setOrderId($order->getIncrementId());
        $this->request()->setNationalId('4103219202');
        $this->request()->setEmail($order->getCustomerEmail());
        $this->request()->setBillingAddress($order->getBillingAddress());
        $this->request()->setShippingAddress($order->getShippingAddress());
        $this->request()->addProducts($products);

        $result = $this->request()->createReservation();

        $transactionId = $result[0];

        $payment
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(false);

        return $this;
    }
}
