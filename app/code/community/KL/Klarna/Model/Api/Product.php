<?php

class KL_Klarna_Model_Api_Product
{
    public function forProduct($product)
    {
        return Mage::getModel("klarna/api_product_" . $product->getProductType())
            ->setProduct($product);
    }
}
