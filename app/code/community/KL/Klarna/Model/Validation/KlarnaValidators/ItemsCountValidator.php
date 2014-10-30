<?php

class KL_Klarna_Model_Validation_KlarnaValidators_ItemsCountValidator implements KL_Klarna_Model_Validation_KlarnaValidators_RequestValidator
{
    /**
     * @var
     */
    protected $error;

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param $klarnasValidationRequestObject
     * @param $klarnaId
     * @return bool
     */
    public function validate(Mage_Sales_Model_Quote $quote, $klarnasValidationRequestObject, $klarnaId)
    {
        /**
         * Let's see if the number of items match... should be fun
         * TODO: this could never have return valid before..? $numberOfWtf +=1
         */
        if ($this->numberOfItemsDoNotMatch($quote, $klarnasValidationRequestObject)) {
            $this->error = 'Klarna cart and Magento quote do not match. Klarna cart contains more products than Magento quote';

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
     * @return string
     */
    public function getError()
    {
        return (string)$this->error;
    }

    /**
     * Reorganising the array for easier iteration
     *
     * @param $request
     * @return array
     */
    protected function extractCartItems($request)
    {
        $klarnaItems = array();

        /**
         * Extract only the 'physical' items, ie not shipping and things like that.
         */
        foreach ($request->cart['items'] as $klarnaItem) {
            if ($klarnaItem['type'] === 'physical') {
                $klarnaItems[$klarnaItem['reference']] = $klarnaItem;
            }
        }

        return $klarnaItems;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param $klarnasValidationRequestObject
     * @return bool
     */
    protected function numberOfItemsDoNotMatch(Mage_Sales_Model_Quote $quote, $klarnasValidationRequestObject)
    {
        return count($this->extractCartItems($klarnasValidationRequestObject)) !== count($quote->getAllVisibleItems());
    }
}