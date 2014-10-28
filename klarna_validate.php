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
        $this->_data = json_decode(file_get_contents('php://input'), true);
    }

    /**
     * Perform the validation
     */
    public function run()
    {
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
                'SELECT *, css.qty as available_qty, sfqi.qty as ordered_qty FROM `sales_flat_quote_item` sfqi
                 LEFT JOIN sales_flat_quote sfq ON (sfqi.`quote_id` = sfq.`entity_id`)
                 LEFT JOIN core_store cs ON (sfq.`store_id` = cs.`store_id`)
                 LEFT JOIN cataloginventory_stock_status css
                    ON (css.`website_id` = cs.`website_id` AND css.`product_id` = sfqi.`product_id`)
                WHERE sfqi.`quote_id` = \'' . $this->getQuoteId() . '\' AND css.`stock_id` = 1'
            );

            /**
             * Loop each item
             */
            while ($quoteItem = mysql_fetch_object($quoteItems)) {

                if(empty($quoteItem->parent_item_id) && empty($klarnaItems[$quoteItem->sku])) {
                    /*
                     * Check if quote product exists in Klarna order
                     */
                    $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Item with SKU ' . $quoteItem->sku
                        . ' doesn\'t exist in the Klarna cart';
                    $errorMessages[] = $errorMessage;
                } else {
                    if(!(int)$quoteItem->stock_status) {
                        /*
                         * Check if product has stock_status = 1
                         */
                        $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Item with SKU ' . $quoteItem->sku
                            . ' is not salable.';
                        $errorMessages[] = $errorMessage;
                        $isStock = '?is_stock=1';
                    } elseif(
                        /*
                         * Check if available quantity is > than ordered
                         */
                        $quoteItem->product_type == 'simple' &&
                        (int)$quoteItem->available_qty < (int)$quoteItem->ordered_qty
                    ) {
                        $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Item with SKU ' . $quoteItem->sku
                            . ' has available qty = "' . (int)$quoteItem->available_qty . '" but ordered = "' .
                            (int)$quoteItem->ordered_qty . '".';
                        $errorMessages[] = $errorMessage;
                        $isStock = '?is_stock=1';
                    } else {
                        /*
                         * Check if quote product is enabled
                         */
                        $statusAttrbutes = $this->query(
                            'SELECT *, catalog_product_entity_int.value as statusValue FROM eav_attribute
                                LEFT JOIN eav_entity_type ON (
                                    eav_attribute.entity_type_id = eav_entity_type.entity_type_id
                                )
                                LEFT JOIN catalog_product_entity_int ON (
                                    catalog_product_entity_int.entity_type_id = eav_entity_type.entity_type_id
                                    AND catalog_product_entity_int.attribute_id = eav_attribute.attribute_id
                                )
                            WHERE
                                eav_attribute.attribute_code = "status"
                                AND  eav_entity_type.entity_type_code = "catalog_product"
                                AND catalog_product_entity_int.entity_id = ' . $quoteItem->product_id . '
                            ORDER BY catalog_product_entity_int.store_id ASC'
                        );

                        $disabledProduct = false;
                        while ($statusAttrbute = mysql_fetch_object($statusAttrbutes)) {
                            /*
                             * Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                             * Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                             */
                            if($statusAttrbute->store_id == 0 && $statusAttrbute->statusValue == 2) {
                                $disabledProduct = true;
                            } elseif($statusAttrbute->store_id == $quoteItem->store_id && $statusAttrbute->statusValue == 2) {
                                $disabledProduct = true;
                            }
                        }
                        if($disabledProduct) {
                            $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Item with SKU ' . $quoteItem->sku
                                . ' is disabled.';
                            $errorMessages[] = $errorMessage;
                        }
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
                $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Currency mismatch. Given ' .
                    strtolower($this->_data['purchase_currency']) . ' but should be ' . strtolower($currencyCode);
                $errorMessages[] = $errorMessage;
            }

            /*
             * Check if carts match
             */
            if(count($klarnaItems) > 0) {
                $errorMessage = '[QuoteID:' . $this->getQuoteId() . '] Klarna cart and Magento quote do not match. ' .
                    'Klarna cart contains more products than Magento quote';
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
