<?php

class KL_Klarna_Model_Invoice_Capture extends KL_Klarna_Model_Invoice_Abstract
{
    public function capture()
    {
        $payment    = $this->getPayment();
        $order      = $payment->getOrder();
        $products   = $order->getAllVisibleItems();
        $reservationNumber = $payment->getAuthorizationTransaction()->getTxnId();

        $this->request()->setReservationNumber($reservationNumber);
        $this->request()->setOrderId($order->getIncrementId());
        $this->request()->setCurrency($order->getOrderCurrency());
        $this->request()->setNationalId('4103219202');
        $this->request()->setEmail($order->getCustomerEmail());
        $this->request()->setBillingAddress($order->getBillingAddress());
        $this->request()->setShippingAddress($order->getShippingAddress());
        $this->request()->addProducts($products);
        $this->request()->addShippingfee(
            $order->getShippingAmount(),
            $order->getShippingTaxAmount(),
            $order->getShippingDescription()
        );

        $result = $this->request()->activateReservation();

        $transactionId = $result[1];

        $payment->setTransactionId($transactionId);

        return $this;
    }
}
