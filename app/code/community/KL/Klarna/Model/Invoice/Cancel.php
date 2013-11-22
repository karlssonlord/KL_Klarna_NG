<?php

class KL_Klarna_Model_Invoice_Cancel extends KL_Klarna_Model_Invoice_Abstract
{
    public function cancel()
    {
        $payment    = $this->getPayment();
        $order      = $payment->getOrder();
        $reservationNumber = $payment->getAuthorizationTransaction()->getTxnId();

        $this->request()->setReservationNumber($reservationNumber);
        $this->request()->setCurrency($order->getOrderCurrency());
        $this->request()->setEmail($order->getCustomerEmail());
        $this->request()->setBillingAddress($order->getBillingAddress());

        $result = $this->request()->cancelReservation();

        return $this;
    }
}
