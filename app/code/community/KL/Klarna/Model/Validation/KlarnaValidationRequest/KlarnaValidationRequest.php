<?php

final class KL_Klarna_Model_Validation_KlarnaValidationRequest_KlarnaValidationRequest
{
    public $id;
    public $quoteId;
    public $purchaseCountry;
    public $purchaseCurrency;
    public $locale;
    public $status;
    public $startedAt;
    public $lastModifiedAt;
    public $cart;
    public $customer;
    public $shippingAddress;
    public $billingAddress;
    public $gui;
    public $merchant;

    function __construct($id, $orderId, $purchaseCountry, $purchaseCurrency, $locale, $status, $startedAt, $lastModifiedAt, $cart, $customer, $shippingAddress, $billingAddress, $gui, $merchant)
    {
        $this->id               = $id;
        $this->orderId          = $orderId;
        $this->purchaseCountry  = $purchaseCountry;
        $this->purchaseCurrency = $purchaseCurrency;
        $this->locale           = $locale;
        $this->status           = $status;
        $this->startedAt        = $startedAt;
        $this->lastModifiedAt   = $lastModifiedAt;
        $this->cart             = $cart;
        $this->customer         = $customer;
        $this->shippingAddress  = $shippingAddress;
        $this->billingAddress   = $billingAddress;
        $this->gui              = $gui;
        $this->merchant         = $merchant;
    }
}