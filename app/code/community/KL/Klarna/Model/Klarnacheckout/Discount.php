<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Discount
 */
class KL_Klarna_Model_Klarnacheckout_Discount
{

    /**
     * @var
     */
    protected $discounts;

    /**
     * Build array
     *
     * @param $quote
     * @return array
     */
    public function build(Mage_Sales_Model_Quote $quote)
    {
        foreach ($quote->getAllVisibleItems() as $item) {
            $this->handleDiscount($item);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDiscounts()
    {
        return $this->discounts;
    }

    /**
     * @param $quoteItem
     * @return bool
     */
    protected function quoteIsDiscounted(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        return (float)$quoteItem->getDiscountAmount() > 0;
    }

    /**
     * @param $item
     */
    protected function handleDiscount(Mage_Sales_Model_Quote_Item $item)
    {
        if ($this->quoteIsDiscounted($item)) {
            $this->buildDiscountObject($item);
        }
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $item
     */
    protected function buildDiscountObject(Mage_Sales_Model_Quote_Item $item)
    {
        $this->discounts[] = array(
            'type' => 'discount',
            'reference' => Mage::helper('klarna')->__('Discount'),
            'name' => Mage::helper('klarna')->__('Discount'),
            'quantity' => 1,
            'unit_price' => - ($item->getDiscountAmount() * 100),
            'tax_rate' => $item->getTaxPercent() * 100
        );
    }

}
