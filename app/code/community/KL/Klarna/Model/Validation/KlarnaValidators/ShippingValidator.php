<?php

/**
 * Class KL_Klarna_Model_Validation_KlarnaValidators_ShippingValidator
 */
class KL_Klarna_Model_Validation_KlarnaValidators_ShippingValidator implements KL_Klarna_Model_Validation_KlarnaValidators_RequestValidator
{
    protected $error;

    /**
     * Validate
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param                        $klarnasValidationRequestObject
     * @param                        $klarnaId
     *
     * @return bool
     */
    public function validate(Mage_Sales_Model_Quote $quote, $klarnasValidationRequestObject, $klarnaId)
    {
        /**
         * Assure a shipping method is set on the quote
         */
        if ($this->hasNoShippingMethod($quote)) {
            $this->error = 'Quote is missing shipping method.';

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

    /**
     * Get the error
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Check quote for shipping method
     *
     * @param $quote
     *
     * @return bool
     */
    protected function hasNoShippingMethod($quote)
    {
        $shippingMethod = null;
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && is_object($shippingAddress)) {
            $shippingMethod = trim($shippingAddress->getShippingMethod());
        }

        if (!$shippingMethod) {
            return true;
        }

        return false;
    }
}