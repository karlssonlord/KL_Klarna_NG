<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Shipping
 */
class KL_Klarna_Model_Klarnacheckout_Shipping extends KL_Klarna_Model_Klarnacheckout_Abstract {

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
            'tax_rate' => $this->calculateShippingTaxPercentage($quote) * 100,
            'type' => 'shipping_fee'
        );
    }

    /**
     * @param $shipping
     * @return string
     */
    protected function getShippingName($shipping)
    {
        // Get the name from the quote, or if unavailable the just set a default name
        return $shipping->getShippingDescription() ? : Mage::helper('klarna')->__('Shipping');
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return float
     */
    protected function calculateShippingTaxPercentage(Mage_Sales_Model_Quote $quote)
    {
        $taxCalculation = Mage::getModel('tax/calculation');

        $request = $taxCalculation->getRateRequest(null, null, null, $quote->getStore());

        $taxRateId = Mage::getStoreConfig('tax/classes/shipping_tax_class', $quote->getStore());

        return $taxCalculation->getRate($request->setProductClassId($taxRateId));
    }

    /**
     * @param $quote
     * @return mixed
     */
    protected function getShippingAddress($quote)
    {
        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress->getShippingMethod()) {

            Mage::helper('klarna/checkout')->setDefaultShippingMethodIfNotSet();

            return $quote->getShippingAddress();
        }

        return $shippingAddress;
    }

}
