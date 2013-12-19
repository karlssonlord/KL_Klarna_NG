<?php

require_once('Klarna/2.4.3/Klarna.php');
require_once('Klarna/2.4.3/Country.php');
require_once('Klarna/2.4.3/Exceptions.php');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
require_once('Klarna/2.4.3/pclasses/mysqlstorage.class.php');

class KL_Klarna_Model_Api_Abstract extends Varien_Object {

    private $_klarnaApi;

    /**
     * Fetch Klarna API
     *
     * @param object $klarnaModel
     *
     * @return Klarna
     */
    public function getApi($klarnaModel = null)
    {
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
            if ( is_null($klarnaModel) ) {
                $klarnaModel = Mage::getModel('klarna/klarna');
            }

            /**
             * Configure Klarna
             */
            $api->config(
                $klarnaModel->getMerchantId(),
                $klarnaModel->getSharedSecret(),
                $klarnaModel->getCountry(),
                $klarnaModel->getLanguage(),
                $klarnaModel->getCurrency(),
                $klarnaModel->getServer(),
                $klarnaModel->getPclassStorage(),
                $klarnaModel->getPclassStorageUri(),
                $klarnaModel->useSsl(),
                $klarnaModel->useRemoteResponseTimeLogging()
            );

            $this->_klarnaApi = $api;
        }

        return $this->_klarnaApi;
    }

}