<?php

class KL_Klarna_Helper_Sanitize extends KL_Klarna_Helper_Abstract {

    /**
     * Keys we allow to be stored
     *
     * @var array
     */
    protected $_allowedKeys = array(
        'method',
        'klarna_invoice_ssn',
        'klarna_partpayment_ssn',
        'klarna_partpayment_ssn',
        'klarna_specpayment_ssn',
        'klarna_partpayment_pclass',
        'klarna_specpayment_pclass',
    );

    /**
     * Remove unwanted data from the array
     *
     * @param $array
     * @return mixed
     */
    public function arr($array) {
        /**
         * Loop all elements
         */
        foreach ($array as $key => $value) {
            /**
             * Remove if not in our allowed keys array
             */
            if (!in_array($key, $this->_allowedKeys)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

}