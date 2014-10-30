<?php

class KL_Klarna_Model_Validation_KlarnaValidationRequest_KlarnaValidationRequestAdapter
{
    public static function make($request)
    {
        return new KL_Klarna_Model_Validation_KlarnaValidationRequest_KlarnaValidationRequest(
            $request['id'],
            $request['merchant_reference']['orderid2'],
            $request['purchase_country'],
            $request['purchase_currency'],
            $request['locale'],
            $request['status'],
            $request['started_at'],
            $request['last_modified_at'],
            $request['cart'],
            $request['customer'],
            $request['shipping_address'],
            $request['billing_address'],
            $request['gui'],
            $request['merchant']
        );
    }
} 