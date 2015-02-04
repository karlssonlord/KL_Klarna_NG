<?php 

class KL_Klarna_Model_Klarnacheckout_BuildRecurringOrder
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
            'activate' => false,
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
        // TODO: Fetch current user
        // Load the user by taking the customer_id off the subscription object
        $currentUser = $this->quote->getCustomer();

        // Make sure the variable in the array is defined
        if (!isset($klarnaData['shipping_address'])) {
            $klarnaData['shipping_address'] = array();
        }

        // Set the e-mail
        // TODO: maybe fallback to getting the email off the customer object if the one on the quote is empty?
        $klarnaData['shipping_address']['email'] = $this->quote->getCustomerEmail();

        // TODO: Think about where we should get the address from. Template quote or just quote?
        // TODO: What if the address returns invalid from Klarna, do we try another address and notify the customer?
        // I suppose we could fetch the shipping address from the original order.
        // Or we could get the "default shipping address" and try that, and if that fails too,
        // just iterate over the remaining ones, if any

        // 1. try with quote address (notify customer if fails)
        // 2. try with default address (notify customer)
        // 3. fail and notify customer service

        $klarnaData['shipping_address']['postal_code'] = $this->getQuote()->getShippingAddress()->getPostcode();


        return $klarnaData;
    }

    /**
     *
     */
    private function getCountry()
    {
        // TODO:
    }

    /**
     *
     */
    private function getCurrency()
    {
        // TODO:
    }

    /**
     *
     */
    private function getLocale()
    {
        // TODO:
    }

    /**
     *
     */
    private function getMerchantId()
    {
        // TODO:
    }

    /**
     *
     */
    private function getQuote()
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
        $shippingAddress = $this->getQuote()->getShippingAddress();

        if (!$shippingAddress->getShippingMethod() ) {
            // TODO: Set default shipping method if none is set
//            Mage::helper('klarna/checkout')->setDefaultShippingMethodIfNotSet();

            $shippingAddress = $this->getQuote->getShippingAddress();
        }

        // If we're still failing with no shipping method
        if (!$shippingAddress->getShippingMethod() ) {
            return Mage::helper('klarna')->log('Missing shipping method when trying to create Klarna recurring order!');
        }

        $shippingAddress
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->save();

        // Calculate total price
        $shippingPrice = $shippingAddress->getShippingAmount();

        // Calculate shipping tax percent
        if ($shippingPrice) {
            $shippingTaxPercent = ($shippingAddress->getShippingTaxAmount() / ($shippingPrice - $shippingAddress->getShippingTaxAmount())) * 100;
        } else {
            $shippingTaxPercent = 0;
        }

        // Set the shipping name
        $shippingName = $shippingAddress->getShippingDescription();

        // If the shipping name wasn't loaded by some reason, just add a standard name
        if (!$shippingName) {
            $shippingName = Mage::helper('klarna')->__('Shipping');
        }

        return array(
            'reference' => $shippingAddress->getShippingMethod(),
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
} 