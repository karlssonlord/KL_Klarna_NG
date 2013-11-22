<?php

require_once('Klarna/2.4.3/Klarna.php');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');

class KL_Klarna_Model_Api_Request extends Varien_Object
{
    public function setCurrency($currency)
    {
        $klarnaCurrency = KlarnaCurrency::fromCode($currency->getCode());

        if (!is_null($klarnaCurrency)) {
            $this->setData('currency', $klarnaCurrency);
        }
        else {
            throw new Exception("Klarna does not support payments in " . $currency->getCode());
        }

        return $this;
    }

    public function addProducts($products)
    {
        foreach ($products as $product) {
            $this->addProduct($product);
        }

        return $this;
    }

    public function addProduct($product)
    {
        if ($this->getProducts()) {
            $products = $this->getProducts();
        }
        else {
            $products = array();
        }

        $klarnaProduct = Mage::getModel("klarna/api_product")->forProduct($product);
        array_push($products, $klarnaProduct);

        $this->setProducts($products);

        return $this;
    }

    public function setBillingAddress($address)
    {
        $this->setAddress('billing_address', $address);
        $this->setCountry(KlarnaCountry::fromCode($address->getCountry()));

        return $this;
    }

    public function setShippingAddress($address)
    {
        $this->setAddress('shipping_address', $address);
        return $this;
    }

    protected function setAddress($dataKey, $address)
    {
        if (!$this->getEmail()) {
            throw new Exception("You need to setEmail() before adding addresses!");
        }

        $klarnaAddress = new KlarnaAddr(
            $this->getEmail(),             // email
            $address->getTelephone(),        // Telno, only one phone number is needed.
            '',                                     // Cellno
            $address->getFirstname(),        // Firstname
            $address->getLastname(),         // Lastname
            '',                                     // No care of, C/O.
            join(', ', $address->getStreet()),   // Street
            $address->getPostcode(),         // Zip Code
            $address->getCity(),             // City
            KlarnaCountry::fromCode($address->getCountry()),            // Country
            null,                         // HouseNo for German and Dutch customers.
            null                          // House Extension. Dutch customers only.
        );

        $this->setData($dataKey, $klarnaAddress);
    }

    public function createReservation()
    {
        $this->api()->setAddress(KlarnaFlags::IS_BILLING, $this->getBillingAddress());
        $this->api()->setAddress(KlarnaFlags::IS_SHIPPING, $this->getShippingAddress());

        $this->api()->setEstoreInfo(
            $this->getOrderId(),
            false,                  // Secondary order ID (wtf?)
            false                   // Customer ID
        );

        foreach ($this->getProducts() as $product) {
            $this->api()->addArticle(
                $product->getQuantity(),
                $product->getSku(),
                $product->getName(),
                $product->getPriceInclTax(),
                $product->getTaxPercent(),
                $product->getDiscountPercent(),
                KlarnaFlags::INC_VAT
            );
        }

        $result = $this->api()->reserveAmount(
            $this->getNationalId(),
            null,   // Gender
            -1,     // -1 = Calculate amount from items
            KlarnaFlags::NO_FLAG,   // I have no idea
            KlarnaPClass::INVOICE   // Sounds reasonable
        );

        return $result;
    }

    protected function api()
    {
        if (!$this->getApi()) {
            $api = new Klarna();

            $api->config(
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

            $this->setApi($api);
        }

        return $this->getApi();
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

    protected function getLanguage()
    {
        return KlarnaLanguage::SV;
    }

    protected function getServer()
    {
        if (Mage::getStoreConfigFlag('payment/klarna_invoice/live')) {
            return Klarna::LIVE;
        }
        else {
            return Klarna::BETA;
        }
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
