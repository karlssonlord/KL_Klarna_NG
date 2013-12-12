<?php

class KL_Klarna_AddressController extends Mage_Core_Controller_Front_Action {

    /**
     * Helper function for setting correct headers and data when responding
     *
     * @param $jsonData
     *
     * @return void
     */
    protected function jsonReponse($jsonData)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    /**
     * Frontend controller for fetching address data
     *
     * @author Robert Lord, Karlsson & Lord AB <robert@karlssonlord.com>
     *
     * @return void
     */
    public function getAction()
    {
        /**
         * Fetch given social security number
         */
        $ssn = $this->getRequest()->getParam('ssn');

        /**
         * If no social security number was given, exit with an error message
         */
        if ( ! $ssn ) {
            return $this->jsonReponse(Mage::helper('klarna/json')->error($this->__('Missing social security number')));
        }

        /**
         * Fetch the addresses
         */
        $addresses = Mage::getModel('klarna/api_address')
            ->fetch($ssn);

        /**
         * Check the results
         */
        if ( $addresses && is_array($addresses) ) {
            /**
             * Store addresses in the session
             */
            Mage::getSingleton('core/session')->setKlarnaAddresses($addresses);

            /**
             * Return the addresses
             */
            return $this->jsonReponse(Mage::helper('klarna/json')->success($addresses));
        }

        /**
         * Return the json error
         */
        return $this->jsonReponse(Mage::helper('klarna/json')->error($this->__('Unable to fetch address information')));
    }

    /**
     * Frontend controller for updating address data in the quote
     *
     * @author Robert Lord, Karlsson & Lord AB <robert@karlssonlord.com>
     *
     * @return void
     */
    public function updateAction()
    {
        /**
         * Fetch given address hash
         */
        $addressHash = $this->getRequest()->getParam('address_key');

        /**
         * Only if hash were given
         */
        if ( $addressHash ) {

            /**
             * Fetch all addresses in session
             */
            $allAddresses = Mage::getSingleton('core/session')->getKlarnaAddresses();

            /**
             * Look for a matching hash
             */
            foreach ($allAddresses as $address) {
                if ( $address['hash'] == $addressHash ) {

                    /**
                     * Fetch quote
                     */
                    $quote = Mage::getModel('checkout/cart')->getQuote();

                    /**
                     * Update billing address
                     */
                    if ( $quote && $quote->getBillingAddress() ) {

                        $quote->getBillingAddress()
                            ->setFirstname($address['fname'])
                            ->setLastname($address['lname'])
                            ->setCompany($address['company'])
                            ->setStreet($address['street'])
                            ->setPostcode($address['zip'])
                            ->setCity($address['city'])
                            ->setCountry(Mage::helper('klarna')->klarnaCountryToMagento($address['country']))
                            ->save();
                    }

                    /**
                     * Update shipping address
                     */
                    if ( $quote && $quote->getShippingAddress() ) {

                        $quote->getShippingAddress()
                            ->setFirstname($address['fname'])
                            ->setLastname($address['lname'])
                            ->setCompany($address['company'])
                            ->setStreet($address['street'])
                            ->setPostcode($address['zip'])
                            ->setCity($address['city'])
                            ->setCountry(Mage::helper('klarna')->klarnaCountryToMagento($address['country']))
                            ->save();
                    }

                    /**
                     * Return successful response
                     */
                    return $this->jsonReponse(Mage::helper('klarna/json')->success(array('success' => true)));
                }
            }
        }

        /**
         * Return the json error
         */
        return $this->jsonReponse(Mage::helper('klarna/json')->error($this->__('Unable to set address')));
    }

}