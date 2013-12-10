<?php

require_once('Klarna/2.4.3/Klarna.php');
require_once('Klarna/2.4.3/Country.php');
require_once('Klarna/2.4.3/Exceptions.php');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');

class KL_Klarna_Model_Api_Abstract extends Varien_Object {

    private $_klarnaApi;

    public function getApi()
    {


        error_reporting(2047);
        ini_set('display_errors', 'on');

        /**
         * Check if we already has an instance of the API
         */
        if ( ! $this->_klarnaApi ) {

            /**
             * Setup new instance of Klarna's library
             */

            $api = new Klarna();

            /**
             * Setup a new instance of our own Klarna model
             */
            $klarna = Mage::getModel('klarna/klarna');

            /**
             * Configure Klarna
             */
            $api->config(
                $klarna->getMerchantId(),
                $klarna->getSharedSecret(),
                $klarna->getCountry(),
                $klarna->getLanguage(),
                $klarna->getCurrency(),
                $klarna->getServer(),
                $klarna->getPclassStorage(),
                $klarna->getPclassStorageUri(),
                $klarna->useSsl(),
                $klarna->useRemoteResponseTimeLogging()
            );

            $this->_klarnaApi = $api;
        }

        return $this->_klarnaApi;
    }

}