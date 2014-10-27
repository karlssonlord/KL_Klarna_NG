<?php

/**
 * Class KlarnaValidate
 *
 * Validation class for Klarna. Magento is not
 * used to save performance.
 *
 * @author Robert Lord, Tykhon Dziuban Karlsson & Lord AB
 */
class KlarnaValidate
{

    protected $_data;

    protected $_connection;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $json = '{"id":"149428B7A280A0CF337726C0000","merchant_reference":{"orderid2":"648336"},"purchase_country":"se","purchase_currency":"sek","locale":"sv-se","status":"checkout_incomplete","started_at":"2014-10-24T16:24:37+02:00","last_modified_at":"2014-10-24T16:24:37+02:00","cart":{"total_price_excluding_tax":6000,"total_tax_amount":1500,"total_price_including_tax":7500,"items":[{"reference":"209996813","name":"Renske Kalkon, Anka & Ris 395g","quantity":1,"unit_price":2600,"tax_rate":2500,"discount_rate":0,"type":"physical","total_price_including_tax":2600,"total_price_excluding_tax":2080,"total_tax_amount":520},{"reference":"matrixrate_matrixrate_220","name":"Frakts\\u00e4tt - 1 Hempaket utan kvittens","quantity":1,"unit_price":4900,"tax_rate":2500,"discount_rate":0,"type":"shipping_fee","total_price_including_tax":4900,"total_price_excluding_tax":3920,"total_tax_amount":980}]},"customer":{"type":"person","date_of_birth":"1941-03-21","gender":"female"},"shipping_address":{"given_name":"Testperson-se","family_name":"Approved","street_address":"St\\u00e5rgatan 1","postal_code":"12345","city":"Ankeborg","country":"se","email":"checkout-se@testdrive.klarna.com","phone":"070 111 11 11"},"billing_address":{"given_name":"Testperson-se","family_name":"Approved","street_address":"St\\u00e5rgatan 1","postal_code":"12345","city":"Ankeborg","country":"se","email":"checkout-se@testdrive.klarna.com","phone":"070 111 11 11"},"gui":{"layout":"desktop","options":["disable_autofocus"],"snippet":"<div id=\\"klarna-checkout-container\\" style=\\"overflow-x: hidden;\\"><script type=\\"text/javascript\\">/* <![CDATA[ */(function(w,k,i,d,u,n,c){w[k]=w[k]||function(){(w[k].q=w[k].q||[]).push(arguments)};w[k].config={container:w.document.getElementById(i),TESTDRIVE:true,ORDER_URL:\'https://checkout.testdrive.klarna.com/checkout/orders/149428B7A280A0CF337726C0000\',AUTH_HEADER:\'KlarnaCheckout K0YCI5s7kx5XbOoZjnoS\',LAYOUT:\'desktop\',LOCALE:\'sv-se\',ORDER_STATUS:\'checkout_incomplete\',MERCHANT_TAC_URI:\'http://local.animail.se/\',MERCHANT_TAC_TITLE:\'Karlsson & Lord\',MERCHANT_NAME:\'Karlsson & Lord\',GUI_OPTIONS:[\'disable_autofocus\'],ALLOW_SEPARATE_SHIPPING_ADDRESS:false,NATIONAL_IDENTIFICATION_NUMBER_MANDATORY:false,ANALYTICS:\'UA-36053137-1\',PHONE_MANDATORY:false,PACKSTATION_ENABLED:false,PURCHASE_COUNTRY:\'swe\',PURCHASE_CURRENCY:\'sek\',BOOTSTRAP_SRC:u};n=d.createElement(\'script\');c=d.getElementById(i);n.async=!0;n.src=u;c.insertBefore(n,c.firstChild);})(this,\'_klarnaCheckout\',\'klarna-checkout-container\',document,\'https://checkout.testdrive.klarna.com/141010-5465f7b/checkout.bootstrap.js\');/* ]]> */</script><noscript>Please <a href=\\"http://enable-javascript.com\\">enable JavaScript</a>.</noscript></div>"},"merchant":{"id":"862","terms_uri":"http://local.animail.se/","checkout_uri":"http://local.animail.se/klarna/checkout/","confirmation_uri":"http://local.animail.se/klarna/checkout/success/","push_uri":"http://local.animail.se/klarna/checkout/push/?klarna_order=https%3a%2f%2fcheckout.testdrive.klarna.com%2fcheckout%2forders%2f149428B7A280A0CF337726C0000","validation_uri":"https://tykhon2.pagekite.me/klarna/checkout/validate"}}';
        $this->_data = json_decode($json, true);
//        $this->_data = json_decode(file_get_contents('php://input'), true);
    }

    /**
     * Perform the validation
     */
    public function run()
    {
        $error = false;
        /**
         * Make sure quote id exists
         */
        if ($this->getQuoteId()) {
            $isStock = '';
            $errorMessages = array();

            $klarnaItems = array();
            //Reorginizing the array for the following easy search
            foreach($this->_data['cart']['items'] as $klarnaItem) {
                if($klarnaItem['type'] === 'physical') {
                    $klarnaItems[$klarnaItem['reference']] = $klarnaItem;
                }
            }

            /**
             * Fetch quote items from database
             *
             * Default values taken from here:
             * Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID
             * Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK
             */
            $quoteItems = $this->query(
                'SELECT * FROM `sales_flat_quote_item` sfqi
                 LEFT JOIN sales_flat_quote sfq ON (sfqi.`quote_id` = sfq.`entity_id`)
                 LEFT JOIN  core_store cs ON (sfq.`store_id` = cs.`store_id`)
                 LEFT JOIN cataloginventory_stock_status css
                    ON (css.`website_id` = cs.`website_id` AND css.`product_id` = sfqi.`product_id`)
                WHERE sfqi.`quote_id` = \'' . $this->getQuoteId() . '\' AND css.`stock_id` = 1 AND ISNULL(sfqi.`parent_item_id`)'
            );

            /**
             * Loop each item
             */
            while ($quoteItem = mysql_fetch_object($quoteItems)) {

                if(empty($klarnaItems[$quoteItem->sku])) {
                    $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Item with SKU ' . $quoteItem->sku
                        . ' doesn\'t exist in the Klarna cart';
                    $errorMessages[] = $errorMessage;
                } else {
                    if(!(int)$quoteItem->stock_status) {
                        $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Item with SKU ' . $quoteItem->sku
                            . ' is not salable.';
                        $errorMessages[] = $errorMessage;
                        $isStock = '?is_stock=1';
                    }
                    unset($klarnaItems[$quoteItem->sku]);
                }
                $currencyCode = $quoteItem->quote_currency_code;
                $websiteId = $quoteItem->website_id;
            }
            if(!isset($websiteId)) {
                $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Quote has no items. Quote is empty.';
                $errorMessages[] = $errorMessage;
            }

            /*
             * Currency code check
             */
            if(strtolower($currencyCode) !== strtolower($this->_data['purchase_currency'])) {
                $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Currency mismatch. Given ' . strtolower($this->_data['purchase_currency']) .
                    ' but should be ' . strtolower($currencyCode);
                $errorMessages[] = $errorMessage;
            }

            /*
             * Check if carts match
             */
            if(count($klarnaItems) > 0) {
                $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Klarna cart and Magento quote do not match. Klarna cart contains more products' .
                    ' than Magento quote';
                $errorMessages[] = $errorMessage;
            }


        } else {
            $errorMessages[] = 'Missing quote ID';
        }

        if(!empty($errorMessages)) {

            $configItems = $this->query(
                'SELECT * FROM core_config_data WHERE path = \'web/secure/base_url\''
            );

            $defaultUrl = '';
            $foundUrl = '';
            while ($configItem = mysql_fetch_object($configItems)) {
                if((int)$configItem->scope_id == 0) {
                    $defaultUrl = $configItem->value;
                }
                if((int)$configItem->scope_id == (int)$websiteId) {
                    $foundUrl = $configItem->value;
                }
            }
            $theUrl = '';
            if(empty($foundUrl)) {
                $theUrl = $defaultUrl;
            } else {
                $theUrl = $foundUrl;
            }

            $this->error($errorMessages, $websiteId);
            
            header('HTTP/1.1 303 See Other');
            header('Location: ' . $theUrl . 'klarna/checkout/failure' . $isStock);
        } else {
            header('HTTP/1.1 200 OK');
        }

    }

    /**
     * Handle errors
     *
     * @param $message array|string
     * @param $websiteId int
     */
    protected function error($message, $websiteId = 0)
    {
        if(is_array($message)) {
            $message = implode("\n", $message);
        }

        file_put_contents('./var/log/klarna_validation.log', date("Y-m-d H:i:s") . ':->' .
            $message . "\n", FILE_APPEND | LOCK_EX);

        $configItems = $this->query(
            'SELECT * FROM core_config_data WHERE path = \'payment/klarna_checkout/validation_email\' AND scope_id = \''
            . $websiteId . '\''
        );
        while ($configItem = mysql_fetch_object($configItems)) {
            $name = 'Magento'; //senders name
            $email = 'notifications@karlssonlord.com';
            $recipient = $configItem->value;
            $mail_body = $message;
            $subject = 'Klarna validation error notification';
            $header = "From: ". $name . " <" . $email . ">\r\n";
            try {
                mail($recipient, $subject, $mail_body, $header);
            } catch (Exception $e) {
                file_put_contents('./var/log/klarna_validation.log', date("Y-m-d H:i:s") . ':->' .
                    $e->getMessage(), FILE_APPEND | LOCK_EX);
            }
        }
    }

    /**
     * Fetch quote ID
     *
     * @return mixed|bool
     */
    protected function getQuoteId()
    {
        if (isset($this->_data['merchant_reference']['orderid2'])) {
            return $this->_data['merchant_reference']['orderid2'];
        }

        return false;
    }

    /**
     * Perform MySQL query
     *
     * @param $sql
     * @return resource
     */
    protected function query($sql)
    {
        if (!$this->_connection) {
            $this->connect();
        }

        return mysql_query($sql, $this->_connection);
    }

    /**
     * Connect to the database
     */
    protected function connect()
    {
        /**
         * Use the default database connection
         */
        $database = $this->parseMagentoXml()
            ->global
            ->resources
            ->default_setup
            ->connection;

        /**
         * Setup variables
         */
        $host = (string)$database->host;
        $user = (string)$database->username;
        $pass = (string)$database->password;
        $name = (string)$database->dbname;

        $this->_connection = mysql_connect($host, $user, $pass) or $this->error('Unable to connect to server');
        mysql_select_db($name, $this->_connection) or $this->error('Unable to connect to database');

    }

    /**
     * Parse Magento local.xml
     *
     * @return SimpleXMLElement
     */
    protected function parseMagentoXml()
    {
        return simplexml_load_file('app/etc/local.xml');
    }

}

$validate = new KlarnaValidate();
$validate->run();
