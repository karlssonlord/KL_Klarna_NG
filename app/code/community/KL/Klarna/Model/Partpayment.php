<?php

class KL_Klarna_Model_Partpayment extends KL_Klarna_Model_Payment
{
    protected $_code = 'klarna_partpayment';

    public function getPclass()
    {
        /*
         * This is hardcoded for now, it should be changed to pick up a
         * user selected part payment plan from checkout.
         */
        return 100; // 1/24 part payment per month
    }
}
