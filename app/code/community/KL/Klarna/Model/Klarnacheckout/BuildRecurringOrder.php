<?php 

class KL_Klarna_Model_Klarnacheckout_BuildRecurringOrder extends KL_Klarna_Model_Klarnacheckout_Abstract
{
    /**
     * @var KL_Subscriber_Model_Quote
     */
    private $quote;

    /**
     * @param KL_Subscriber_Model_Quote $quote
     * @return array
     */
    public function build(KL_Subscriber_Model_Quote $quote)
    {
        $this->setQuote($quote);

        $items = $this->prepareOrderItems();

        return $this->createNewOrder($items);
    }

    /**
     * @param $items
     * @return array
     */
    private function createNewOrder($items)
    {
        // Setup the create array
        $klarnaData = $this->prepareKlarnaDataObject($items);

        return $this->setCustomerData($klarnaData);
    }

    /**
     * @param $items
     * @return array
     */
    private function prepareKlarnaDataObject($items)
    {
        $klarnaData = array(
            'purchase_country' => $this->getCountry(),
            'purchase_currency' => $this->getCurrency(),
            'locale' => $this->getLocale(),
            'merchant' => array(
                'id' => $this->getMerchantId(),
            ),
            'cart' => array('items' => $items),
            'merchant_reference' => array(
                'orderid2' => $this->getQuote()->getId()
            )
        );

        return $klarnaData;
    }

    /**
     * @return array
     */
    private function prepareOrderItems()
    {
        $items = $this->addQuoteItems();
        $items = $this->addShippingDetails($items);
        $items = $this->handleDiscounts($items);

        return $items;
    }

    /**
     * @return array
     */
    private function addQuoteItems()
    {
        $items = array();

        // Add all visible items from quote
        foreach ($this->quote->getAllVisibleItems() as $item) {
            $items[] = Mage::getModel('klarna/klarnacheckout_item')->build($item);
        }

        return $items;
    }

    /**
     *  Add shipping method and the cost
     *
     * @param $items
     * @return array
     */
    private function addShippingDetails($items)
    {
        $shipping = $this->buildShippingDetails();

        if ($shipping) {
            $items[] = $shipping;

            return $items;
        }

        return $items;
    }

    /**
     * @param $items
     * @return array
     */
    private function handleDiscounts($items)
    {
        $discounts = $this->buildDiscountDetails($this->getQuote());

        if ($discounts) {
            $items[] = $discounts;
        }

        return $items;
    }

    /**
     * TODO: create a valid implementation of this guy
     *
     * @param $klarnaData
     * @return mixed
     */
    private function setCustomerData($klarnaData)
    {
        // Make sure the variable in the array is defined
        if (!isset($klarnaData['shipping_address'])) {
            $klarnaData['shipping_address'] = array();
        }

        // Make sure the variable in the array is defined
        if (!isset($klarnaData['billing_address'])) {
            $klarnaData['billing_address'] = array();
        }

        $klarnaData['shipping_address']['email'] = $this->getQuote()->getCustomerEmail();
        $klarnaData['billing_address']['email'] = $this->getQuote()->getCustomerEmail();

        $klarnaData['shipping_address']['given_name'] = $this->getQuote()->getShippingAddress()->getFirstname();
        $klarnaData['billing_address']['given_name'] = $this->getQuote()->getBillingAddress()->getFirstname();

        $klarnaData['shipping_address']['family_name'] = $this->getQuote()->getShippingAddress()->getLastname();
        $klarnaData['billing_address']['family_name'] = $this->getQuote()->getBillingAddress()->getLastname();

        $klarnaData['shipping_address']['street_address'] = $this->buildStreetAddress($this->getQuote()->getShippingAddress()->getStreet());
        $klarnaData['billing_address']['street_address'] = $this->buildStreetAddress($this->getQuote()->getBillingAddress()->getStreet());

        $klarnaData['shipping_address']['city'] = $this->getQuote()->getShippingAddress()->getCity();
        $klarnaData['billing_address']['city'] = $this->getQuote()->getBillingAddress()->getCity();

        $klarnaData['shipping_address']['postal_code'] = $this->getQuote()->getShippingAddress()->getPostcode();
        $klarnaData['billing_address']['postal_code'] = $this->getQuote()->getBillingAddress()->getPostcode();

        $klarnaData['shipping_address']['phone'] = $this->getQuote()->getShippingAddress()->getTelephone();
        $klarnaData['billing_address']['phone'] = $this->getQuote()->getBillingAddress()->getTelephone();

        $klarnaData['shipping_address']['country'] = $this->getQuote()->getShippingAddress()->getCountryId();
        $klarnaData['billing_address']['country'] = $this->getQuote()->getBillingAddress()->getCountryId();

        return $klarnaData;
    }

    /**
     *
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param KL_Subscriber_Model_Quote $quote
     */
    private function setQuote(KL_Subscriber_Model_Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * TODO: Move to new collaborator
     *
     * Build array with shipping information
     *
     * @return array
     */
    public function buildShippingDetails()
    {
        if (!$this->getQuote()->getShippingAddress()->getShippingMethod() ) {
            // TODO: Set default shipping method if none is set
//            Mage::helper('klarna/checkout')->setDefaultShippingMethodIfNotSet();
        }

        // If we're still failing with no shipping method
        if (!$this->getQuote()->getShippingAddress()->getShippingMethod() ) {
            return Mage::helper('klarna')->log('Missing shipping method when trying to create Klarna recurring order!');
        }

        // Calculate total price
        $shippingPrice = (float)$this->getQuote()->getShippingAddress()->getShippingAmount();

        // Calculate shipping tax percent
        if ($shippingPrice) {
            $shippingTaxAmount = (float)$this->getQuote()->getShippingAddress()->getShippingTaxAmount();
            $shippingTaxPercent = ($shippingTaxAmount / ($shippingPrice - $shippingTaxAmount)) * 100;
        } else {
            $shippingTaxPercent = 0;
        }

        // Set the shipping name
        $shippingName = $this->getQuote()->getShippingAddress()->getShippingDescription();
        // If the shipping name wasn't loaded by some reason, just add a standard name
        if (!$shippingName) {
            $shippingName = Mage::helper('klarna')->__('Shipping');
        }

        return array(
            'reference' => $this->getQuote()->getShippingAddress()->getShippingMethod(),
            'name' => $shippingName,
            'quantity' => 1,
            'unit_price' => intval($shippingPrice * 100),
            'discount_rate' => 0, // Not needed since Magento gives us the actual price
            'tax_rate' => ceil(($shippingTaxPercent * 100)),
            'type' => 'shipping_fee'
        );
    }

    /**
     * Build array
     *
     * @param $quoteItem
     * @return array
     */
    public function buildDiscountDetails($quoteItem)
    {
        // Collect quote totals
        $quoteTotals = $quoteItem->getTotals();

        // Make sure any discount is set
        if ( isset($quoteTotals['discount']) && $quoteTotals['discount']->getValue() ) {
            return array(
                'type' => 'discount',
                'reference' => Mage::helper('klarna')->__('Discount'),
                'name' => Mage::helper('klarna')->__('Discount'),
                'quantity' => 1,
                'unit_price' => intval($quoteTotals['discount']->getValue() * 100),
                'tax_rate' => 0
            );
        }

        return false;
    }

    private function buildStreetAddress(array $address)
    {
        return implode("\n", $address);
    }
} 