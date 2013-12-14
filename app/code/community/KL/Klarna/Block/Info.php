<?php

class KL_Klarna_Block_Info extends Mage_Payment_Block_Info {

    public function getTxnId()
    {
        /**
         * Fetch the order
         */
        $order = $this->getInfo()->getOrder();

        if (is_object($order)) {
            $payment = $order->getPayment();

            /**
             * Fetch the payment
             */
            if (is_object($payment)) {

                $authTrans = $payment->getAuthorizationTransaction();

                if (is_object($authTrans)) {

                    return $authTrans->getTxnId();

                }

            }
        }

        return Mage::helper('klarna')->__('Reservation missing');
    }

}