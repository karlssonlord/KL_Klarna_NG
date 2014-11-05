<?php

class KL_Klarna_Model_Validation_KlarnaValidators_AvailabilityValidator implements KL_Klarna_Model_Validation_KlarnaValidators_RequestValidator {

    /**
     * @var
     */
    protected $error;

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param $klarnasValidationRequest
     * @param $klarnaId
     */
    public function validate(Mage_Sales_Model_Quote $quote, $klarnasValidationRequest, $klarnaId)
    {
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($this->productIsComplex($item)) {
                $this->validateProductChildren($item);
            } else {
                $this->validateSimpleProduct($item);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param $item
     */
    protected function validateProductChildren($item)
    {
        foreach ($item->getChildren() as $childProduct) {
            $this->validateChildProduct($childProduct);
        }
    }

    /**
     * @param $item
     * @throws Exception
     */
    protected function validateChildProduct($item)
    {
        if ($this->productIsComplex($item)) {
            $this->validateProductChildren($item); // Recursive action!
        } else {
            $this->validateSimpleProduct($item);
        }
    }

    /**
     * @param $item
     * @throws Exception
     */
    protected function validateSimpleProduct($item)
    {
        if ($this->amountIsNotInStock($item->getQty(), $item->getSku()) and $this->isNotAnOversellable($item)) {
            $this->error = sprintf(
                'Sorry, product with name: %s and with SKU: %s and productID: %s in those amounts is currently not in stock.',
                $item->getName(), $item->getSku(), $item->getProductId()
            );

            throw new KL_Klarna_Model_Exception_InsufficientStockLevel($this->error);
        }
    }

    /**
     * Check if the requested quantity is not in stock
     *
     * @param $quantity
     * @param $sku
     * @return bool
     * @todo account for "manage stock"-setting
     */
    protected function amountIsNotInStock($quantity, $sku)
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        $stockQuantity = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();

        return intval($quantity) > intval($stockQuantity);
    }

    /**
     * @param $item
     * @return bool
     */
    protected function productIsComplex($item)
    {
        return $item->getProductType() !== 'simple';
    }

    /**
     * Check products backorder settings, is overselling allowed?
     *
     * @param $item
     * @return bool
     */
    protected function isNotAnOversellable($item)
    {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProduct());

        return $stockItem->getBackorders() == 0;
    }

}