<?php

class KL_Klarna_Model_Api_Address extends KL_Klarna_Model_Api_Abstract {

    /**
     * Fetch addresses using Klarna API
     *
     * @param $socialSecurityNumber
     * @throws Exception
     * @return array|boolean
     */
    public function fetch($socialSecurityNumber)
    {
        /**
         * Make a note in the logs
         */
        Mage::helper('klarna')->log('Fetching addresses for ' . $socialSecurityNumber);

        /**
         * Fetch the API
         */
        $api = $this->getApi();

        /**
         * Fetch addresses
         */
        try {

            /**
             * Fetch the addresses
             */
            $addresses = $api->getAddresses($socialSecurityNumber);

            /**
             * Make a note in the logs about the result
             */
            Mage::helper('klarna')->log($addresses);

            /**
             * Setup the return array
             */
            $return = array();

            /**
             * Convert KlarnaAddr objects to array
             */
            foreach ($addresses as $address) {
                $return[] = $address->toArray();
            }

            /**
             * Return the result
             */
            return $return;

        } catch (KlarnaException $e) {

            /**
             * Log the error
             */
            Mage::helper('klarna')->log($e);

            /**
             * Return false
             */
            return false;

        }

    }

}