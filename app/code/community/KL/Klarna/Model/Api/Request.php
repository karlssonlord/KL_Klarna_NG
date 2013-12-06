<?php

/*
 * This class wraps Klarna's PHP library in a more "lazy" interface. Rather
 * than the procedural mess Klarna suggest in their documentation, you can
 * instantiate this class and add information about the transaction in (almost)
 * any order. When you call one of the request methods, this class will handle
 * the procedural mess for you. This prevents most of Klarna's bad design from
 * leaking into your code.
 */

require_once('Klarna/2.4.3/Klarna.php');
require_once('Klarna/2.4.3/Country.php');
require_once('Klarna/2.4.3/Exceptions.php');
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

    public function setCurrencyByCode($code)
    {
        try {
            $this->setData('currency', $code);
        } catch (Exception $ex) {
            throw new Exception("Klarna does not support payments in " . $code);
        }
    }

    public function addProducts($products)
    {
        foreach ($products as $product) {
            $this->addProduct($product);
        }

        return $this;
    }

    public function getProducts()
    {
        if (!parent::getProducts()) {
            $this->setProducts(array());
        }

        return parent::getProducts();
    }

    public function addProduct($product)
    {
        $products = $this->getProducts();

        // Get a decorated object for the current product type
        $klarnaProduct = Mage::getModel("klarna/api_product")->forProduct($product);
        array_push($products, $klarnaProduct);

        $this->setProducts($products);

        return $this;
    }

    public function addShippingFee($amount, $taxAmount, $description)
    {
        $shippingFee = Mage::getModel("klarna/api_product_shippingfee");
        $shippingFee->setAmount($amount);
        $shippingFee->setTaxAmount($taxAmount);
        $shippingFee->setDescription($description);

        $products = $this->getProducts();
        array_push($products, $shippingFee);
        $this->setProducts($products);

        return $this;
    }

    public function setBillingAddress($address)
    {
        $this->setAddress('billing_address', $address);

        /*
         * Use the billing address to set country and language for the
         * transaction, or else we can't instantiate Klarna.
         */
        $klarnaCountry = KlarnaCountry::fromCode($address->getCountry());
        $klarnaLanguage = KlarnaCountry::getLanguage($klarnaCountry);
        $this->setCountry($klarnaCountry);
        $this->setLanguage($klarnaLanguage);

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

    public function setupReservation()
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
                $product->getFlags()
            );
        }
    }

    public function createReservation() {
        $this->setupReservation();

        $result = $this->api()->reserveAmount(
            $this->getNationalId(),
            null,   // Gender
            -1,     // -1 = Calculate amount from items
            KlarnaFlags::NO_FLAG,   // I have no idea
            $this->getPclass()
        );

        return $result;
    }

    public function activateReservation() {
        $this->setupReservation();

        $result = $this->api()->activateReservation(
            $this->getNationalId(),
            $this->getReservationNumber(),
            null,   // Gender
            null,   // OCR (if there is a reserved OCR)
            KlarnaFlags::NO_FLAG,   // I have no idea
            $this->getPclass()
        );

        return $result;
    }

    public function cancelReservation() {
        $result = $this->api()->cancelReservation(
            $this->getReservationNumber()
        );

        return $result;
    }

    public function creditInvoice() {
        $result = $this->api()->creditInvoice(
            $this->getInvoiceId(),
            false
        );

        return $result;
    }

    protected function api(array $params = null)
    {
        if (!$this->getApi()) {
            $api = new Klarna();

            if (!is_null($params)) {
                $country = $params['country'];
                $language = $params['language'];
                $currency = $params['currency'];
            } else {
                $country = $this->getCountry();
                $language = $this->getLanguage();
                $currency = $this->getCurrency();
            }

            $api->config(
                $this->getMerchantId(),
                $this->getSharedSecret(),
                $country,
                $language,
                $currency,
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

    protected function getServer()
    {
        if (Mage::getStoreConfigFlag('payment/klarna/live')) {
            return Klarna::LIVE;
        }
        else {
            return Klarna::BETA;
        }
    }

    protected function getPclassStorage()
    {
        //return 'mysql';
        return 'mysql';
    }

    protected function getPclassStorageUri()
    {
        $config = Mage::getConfig()->getResourceConnectionConfig("default_setup")->asArray();
        return array(
            "user"      => $config["username"],
            "passwd"    => $config["password"],
            "dsn"       => $config["host"],
            "db"        => $config["dbname"],
            "table"     => "klarna_pclass" // Mage::getSingleton('core/resource')->getTableName('catalog/product');
        );
    }

    protected function useSsl()
    {
        return true;
    }

    protected function useRemoteResponseTimeLogging()
    {
        return true;
    }

    /**
     * Fetch PClasses from Klarna if all necessary parameter are set and correct
     *
     * @param array $params -This array must contain correct Klarna specific values
     * @return string - Error messages if any
     */
    public function getPClasses(array $params)
    {
        $errors = false;
        if (!isset($params['country']) || !isset($params['language']) || !isset($params['currency'])) {
            Mage::log(get_class($this) . '::' . __FUNCTION__ . ' Parameters not set');
            return Mage::helper('klarna')->__("Can't get PClasses, parameters missing for Klarna API");
        }

        try {
            $this->api($params)->fetchPClasses($params['country'], $params['language'], $params['currency']);
        } catch (KlarnaException $ex) {
            print $ex->getMessage() . PHP_EOL;
            $this->debug($ex->getMessage());
            $errors = Mage::helper('klarna')->__('Error occurred when fetching Klarna PClasses, se log file for details.');
        }
        return $errors;
    }
}
