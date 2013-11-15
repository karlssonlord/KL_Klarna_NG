<?php

require_once('Klarna/2.4.3/Klarna.php');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');

class KL_Klarna_Model_Invoice_Abstract extends Varien_Object
{
    public function getClient()
    {
        $client = new Klarna();

        $client->config(
            $this->getMerchantId(),
            $this->getSharedSecret(),
            $this->getCountry(),
            $this->getLanguage(),
            $this->getCurrency(),
            $this->getServer(),
            $this->getPclassStorage(),
            $this->getPclassStorageUri(),
            $this->useSsl(),
            $this->useRemoteResponseTimeLogging()
        );

        return $client;
    }

    protected function getMerchantId()
    {
        if ($merchantId = Mage::getStoreConfig('payment/klarna/merchant_id')) {
            return $merchantId;
        }
        else {
            throw new Exception("Missing Merchant ID");
        }
    }

    protected function getSharedSecret()
    {
        if ($sharedSecret = Mage::getStoreConfig('payment/klarna/shared_secret')) {
            return $sharedSecret;
        }
        else {
            throw new Exception("Missing shared secret");
        }
    }

    protected function getCountry()
    {
        return KlarnaCountry::SE;
    }

    protected function getLanguage()
    {
        return KlarnaLanguage::SV;
    }

    protected function getCurrency()
    {
        return KlarnaCurrency::SEK;
    }

    protected function getServer()
    {
        return Klarna::BETA;
    }

    protected function getPclassStorage()
    {
        return 'json';
    }

    protected function getPclassStorageUri()
    {
        return '/srv/pclasses.json';
    }

    protected function useSsl()
    {
        return true;
    }

    protected function useRemoteResponseTimeLogging()
    {
        return true;
    }
}
