<?php

class KL_Klarna_Model_Validation_KlarnaValidators_CurrencyValidator implements KL_Klarna_Model_Validation_KlarnaValidators_RequestValidator
{
    protected $error;

    public function validate(Mage_Sales_Model_Quote $quote, $klarnasValidationRequestObject, $klarnaId)
    {
        /**
         * What about currency, it needs to be the same?
         */
        if ($this->hasCurrencyMisMatch($klarnasValidationRequestObject, $quote)) {
            $this->error = sprintf('Currency mismatch. Given %s but should have been %s',
                strtoupper($klarnasValidationRequestObject->purchaseCurrency),
                strtoupper($quote->getQuoteCurrencyCode())
            );

            Mage::helper('klarna/log')->message(
                $quote,
                $this->error,
                null,
                $klarnaId
            );

            return false;
        }

        return true;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * @param $request
     * @param $quote
     * @return bool
     */
    protected function hasCurrencyMisMatch($request, $quote)
    {
        return strtolower($quote->getQuoteCurrencyCode()) !== strtolower($request->purchaseCurrency);
    }
}