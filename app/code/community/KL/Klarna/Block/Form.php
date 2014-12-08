<?php
class KL_Klarna_Block_Form
    extends Mage_Payment_Block_Form
{
    /**
     * Get current country
     *
     * @return mixed
     */
    public function getCurrentCountry()
    {
        $address = $this->getBillingAddress();

        if (!$address) {
            return false;
        }

        $country = $this->getBillingAddress()->getCountry();

        if (!$country) {
            return false;
        }

        return $country;
    }

    /**
     * Get Eid
     *
     * @return string
     */
    public function getEid()
    {
        return Mage::helper('klarna')->getConfig('merchant_id_legacy');
    }

    /**
     * Get endpoint to fetch address from
     *
     * @return string
     */
    public function getFetchAddressEndpoint()
    {
        return Mage::getUrl('klarna/address/get');
    }

    /**
     * Get endpoint to update address with
     *
     * @return string
     */
    public function getUpdateAddressEndpoint()
    {
        return Mage::getUrl('klarna/address/update');
    }

    /**
     * Is payment method selected
     *
     * @return boolean
     */
    public function isSelected()
    {
        if (Mage::getSingleton('checkout/session')->getData('selected_payment') == $this->getMethodCode()) {
            return true;
        }

        return false;
    }

    /**
     * Get quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        return $quote;
    }

    /**
     * Get billing address
     *
     * @return mixed
     */
    public function getBillingAddress()
    {
        $quote = $this->getQuote();

        if ($quote) {
            $address = $quote->getBillingAddress();

            return $address;
        }

        return false;
    }
}
