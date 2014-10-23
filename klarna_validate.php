<?php

/**
 * Class KlarnaValidate
 *
 * Validation class for Klarna. Magento is not
 * used to save performance.
 *
 * @author Robert Lord, Karlsson & Lord AB
 */
class KlarnaValidate
{

    protected $data;

    protected $connection;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->data = json_decode(file_get_contents('php://input'), true);
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

            /**
             * Fetch quote items from database
             */
            $quoteItems = $this->query(
                "SELECT * FROM `sales_flat_quote_item` WHERE `quote_id` = '" . $this->getQuoteId() . "'"
            );

            /**
             * Loop each item
             */
            while ($row = mysql_fetch_object($quoteItems)) {

                // @todo Validation of stock qty and availability

            }


        } else {
            $this->error('Missing quote ID');
        }

        $this->error('Unknown error');
    }

    /**
     * Handle errors
     *
     * @param $message
     */
    protected function error($message)
    {
        // @todo Do a correct error handling
        die("ERROR: " . $message . "\n");
    }

    /**
     * Fetch quote ID
     *
     * @return mixed|bool
     */
    protected function getQuoteId()
    {
        return 1023009;

        if (isset($this->data['merchant_reference']['orderid2'])) {
            return $this->data['merchant_reference']['orderid2'];
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
        if (!$this->connection) {
            $this->connect();
        }

        return mysql_query($sql, $this->connection);
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

        $this->connection = mysql_connect($host, $user, $pass) or $this->error('Unable to connect to server');
        mysql_select_db($name, $this->connection) or $this->error('Unable to connect to database');

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
