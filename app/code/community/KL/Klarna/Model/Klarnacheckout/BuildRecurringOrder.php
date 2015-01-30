<?php 

class KL_Klarna_Model_Klarnacheckout_BuildRecurringOrder
{
    public function build($quote)
    {
        $items = $this->prepareOrderItems($quote);

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
            'activate' => true,
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

    private function prepareOrderItems($quote)
    {
        $items = $this->addQuoteItems($quote);
        $items = $this->addShippingDetails($items);
        $items = $this->handleDiscounts($items);

        return $items;
    }

    private function addQuoteItems($quote)
    {
        $items = array();

        // Add all visible items from quote
        foreach ($quote->getAllVisibleItems() as $item) {
            $items[] = Mage::getModel('klarna/klarnacheckout_item')->build($item);
        }

        return $items;
    }

    /**
     * @param $items
     * @return array
     */
    private function addShippingDetails($items)
    {
        /**
         * Add shipping method and the cost
         */
        $shipping = Mage::getModel('klarna/klarnacheckout_shipping')->build();
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
        /**
         * Handle discounts
         */
        $discounts = Mage::getModel('klarna/klarnacheckout_discount')->build($this->getQuote());
        if ($discounts) {
            $items[] = $discounts;
            return $items;
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
        $currentUser = '';

        // Make sure the variable in the array is defined
        if (!isset($klarnaData['shipping_address'])) {
            $klarnaData['shipping_address'] = array();
        }

        // Set the e-mail
        $klarnaData['shipping_address']['email'] = $currentUser->getEmail();

        // Fetch the default shipping address
        $defaultShippingAddressId = $currentUser->getDefaultShipping();
        if ($defaultShippingAddressId) {

            // Load the address
            $defaultShippingAddress = $address = Mage::getModel('customer/address')->load(
                $defaultShippingAddressId
            );

            // Prefill postcode
            if ($defaultShippingAddress->getPostcode()) {
                $klarnaData['shipping_address']['postal_code'] = $defaultShippingAddress->getPostcode();
            }
        }

        return $klarnaData;
    }

    private function getCountry()
    {
        // TODO:
    }

    private function getCurrency()
    {
        // TODO:
    }

    private function getLocale()
    {
        // TODO:
    }

    private function getMerchantId()
    {
        // TODO:
    }

    private function getQuote()
    {
        // TODO:
    }
} 