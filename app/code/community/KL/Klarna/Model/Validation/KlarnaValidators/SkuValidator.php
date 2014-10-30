<?php

class KL_Klarna_Model_Validation_KlarnaValidators_SkuValidator implements KL_Klarna_Model_Validation_KlarnaValidators_RequestValidator
{
    /**
     *  Error messages
     *
     * @var mixed
     */
    protected $error = false;

    /**
     *  Unsalable error messages
     *
     * @var mixed
     */
    protected $unsalable = false;

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param $klarnasValidationRequestObject
     * @param $klarnaId
     * @throws KL_Klarna_Model_Exception_UnsalableProduct
     * @return bool
     */
    public function validate(Mage_Sales_Model_Quote $quote, $klarnasValidationRequestObject, $klarnaId)
    {
        $klarnaItems = $this->extractCartItems($klarnasValidationRequestObject);

        foreach($quote->getAllVisibleItems() as $quoteItem) {

            //First make sure that the SKU matches on both sides
            if ($this->thisItemIsNotInKlarnasOrder($klarnaItems, $quoteItem)) {
                $this->error = sprintf('Item with SKU %s is not found in the Klarna order, where Grand Total = %f',
                    $quoteItem->getSku(),
                    (float) $quote->getGrandTotal()*100
                );

                Mage::helper('klarna/log')->message(
                    $quote,
                    $this->error,
                    null,
                    $klarnaId
                );

            } else {
                // If SKU matches on both sides, check if it is "saleable"
                if( ! Mage::getModel('catalog/product')->load($quoteItem->getProductId())->isSalable()) {
                    $this->unsalable = sprintf('Item with SKU %s is not salable. Grand total: %f',
                        $quoteItem->getSku(),
                        (float)$quote->getGrandTotal()*100
                    );

                    Mage::helper('klarna/log')->message(
                        $quote,
                        $this->unsalable,
                        null,
                        $klarnaId
                    );
                } else {
                    unset($klarnaItems[$quoteItem->getSku()]);
                }
            }
        }

        /**
         * The most important check to make, is whether the product is in stock
         * So if that isn't the case, we need to throw an exception so that the
         * responding controller can dispatch a 303 redirect header ASAP.
         */
        if ($this->unsalable) {
            throw new KL_Klarna_Model_Exception_UnsalableProduct($this->unsalable);
        }

        // If there is any error recorded...
        if ($this->error) {
            return false;
        }
        // No error recorded - validation passed
        return true;
    }

    /**
     * @return bool
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param $request
     * @return array
     */
    protected function extractCartItems($request)
    {
        $klarnaItems = array();
        // Reorganising the array for easier iteration
        foreach ($request->cart['items'] as $klarnaItem) {
            if ($klarnaItem['type'] === 'physical') {
                $klarnaItems[$klarnaItem['reference']] = $klarnaItem;
            }
        }
        return $klarnaItems;
    }

    /**
     * @param $klarnaItems
     * @param $quoteItem
     * @return bool
     */
    protected function thisItemIsNotInKlarnasOrder($klarnaItems, $quoteItem)
    {
        return empty($klarnaItems[$quoteItem->getSku()]);
    }
}