<?php

class KL_Klarna_AddressController extends Mage_Core_Controller_Front_Action {

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
            Mage::helper('klarna/json')->error($this->__('Missing social security number'));
        }

        /**
         * Fetch the addresses
         */
        $addresses = Mage::getModel('klarna/api_address')
            ->fetch($ssn);

        if ( $addresses && is_array($addresses) ) {
            Mage::helper('klarna/json')->success($addresses);
        }

        Mage::helper('klarna/json')->error( $this->__('Unable to fetch address information') );
    }

}