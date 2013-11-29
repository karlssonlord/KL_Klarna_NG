<?php

class KL_Klarna_Model_Invoice_Validate extends KL_Klarna_Model_Invoice_Abstract
{

    /**
     * Validation
     *
     * @throws Exception
     */
    public function validate()
    {
        $this->validateCountryCodeAndCurrency();
    }

    /**
     * Checks if the currency and country match, else throw a exception
     */
    protected function validateCountryCodeAndCurrency()
    {
        $paymentInfo    = $this->getPayment();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $order = $paymentInfo->getOrder();
            $currencyCode = $order->getOrderCurrencyCode();
        } else {
            $order = $paymentInfo->getQuote();
            $currencyCode = $order->getQuoteCurrencyCode();
        }

        $billingAddress = $order->getBillingAddress();

        $countryCode = $billingAddress->getCountry();
        $country = Mage::getModel('klarna/api_countries')->getCountry($countryCode);

        if ($country->getCode() != $countryCode || $country->getCurrency() != $currencyCode) {
            Mage::throwException(Mage::helper('klarna')->__('Selected payment type is not allowed for billing country.'));
        }
    }

} 
