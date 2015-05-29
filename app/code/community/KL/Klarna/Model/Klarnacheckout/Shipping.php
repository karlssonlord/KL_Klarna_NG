<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Shipping
 */
class KL_Klarna_Model_Klarnacheckout_Shipping {

    /**
     * Build array with shipping information
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public function build(Mage_Sales_Model_Quote $quote)
    {
        $shippingAddress = $this->getShippingAddress($quote);

        /** If we're still failing with no shipping method */
        if (!$shippingAddress->getShippingMethod() ) {
            Mage::helper('klarna')->log('Missing shipping method for Klarna Checkout!');
            return false;
        }

        $shippingAddress
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->save()
        ;

        return array(
            'reference' => $shippingAddress->getShippingMethod(),
            'name' => $this->getShippingName($shippingAddress),
            'quantity' => 1,
            'unit_price' => intval($shippingAddress->getShippingAmount() * 100),
            'discount_rate' => 0, // Not needed since Magento gives us the actual price
            'tax_rate' => $this->getShippingTaxPercentage($quote) * 100,
            'type' => 'shipping_fee'
        );
    }

    /**
     * Get the description from the shipping address object, and if not available revert to a default name
     *
     * @param $shipping
     * @return string
     */
    protected function getShippingName($shipping)
    {
        return $shipping->getShippingDescription() ? : Mage::helper('klarna')->__('Shipping');
    }

    /**
     * Retrieve the tax rate off the quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return float
     */
    protected function getShippingTaxPercentage(Mage_Sales_Model_Quote $quote)
    {
        $taxCalculation = Mage::getModel('tax/calculation');
        $request = $taxCalculation->getRateRequest(null, null, null, $quote->getStore());
        $taxRateId = Mage::getStoreConfig('tax/classes/shipping_tax_class', $quote->getStore());

        return $taxCalculation->getRate($request->setProductClassId($taxRateId));
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return mixed
     */
    protected function getShippingAddress(Mage_Sales_Model_Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getShippingMethod()) {
            Mage::helper('klarna/checkout')->setDefaultShippingMethodIfNotSet();

            return $quote->getShippingAddress();
        }

        return $shippingAddress;
    }

}
