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
             * Convert KlarnaAddr objects to array and change encoding
             */
            foreach ($addresses as $address) {

                /**
                 * Convert to array
                 */
                $addressArray = $address->toArray();

                /**
                 * Convert ISO-8859-1 characters to UTF-8 and prepare to store hash
                 */
                $hash = '';
                foreach ($addressArray as $key => $value) {
                    $addressArray[$key] = utf8_encode($value);
                    $hash .= $addressArray[$key];
                }

                /**
                 * Add hash to array
                 */
                $addressArray['hash'] = md5($hash);

                /**
                 * Store in the return array
                 */
                $return[] = $addressArray;
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