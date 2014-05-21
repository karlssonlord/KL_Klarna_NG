<?php

/**
 * Class KL_Klarna_model_KlarnaCheckout_Item
 */
class KL_Klarna_model_KlarnaCheckout_Item extends KL_Klarna_model_KlarnaCheckout_Abstract {

    /**
     * Build array
     *
     * @param $quoteItem
     * @return array
     */
    public function build($quoteItem)
    {
        /**
         * Return the array
         */
        return array(
            'reference' => $quoteItem->getSku(),
            'name' => $quoteItem->getName(),
            'quantity' => $quoteItem->getQty(),
            'unit_price' => intval($quoteItem->getPriceInclTax() * 100),
            'discount_rate' => 0, // @todo
            'tax_rate' => ($quoteItem->getTaxPercent() * 100)
        );
    }

}
