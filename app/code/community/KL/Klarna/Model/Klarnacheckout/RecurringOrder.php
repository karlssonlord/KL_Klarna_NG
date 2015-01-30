<?php

/**
 * KL Implementation of the Klarna order resource
 *
 * @category  Payment
 * @package   KL_Subscriber
 * @author    David Wickström <david@karlssonlord.com>
 * @copyright 2014 Animail AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://www.karlssonlord.com/
 */
class KL_Klarna_Model_Klarnacheckout_RecurringOrder implements Klarna_Checkout_ResourceInterface, ArrayAccess
{
    /**
     *  Content type header string
     */
    const CONTENT_TYPE = 'application/vnd.klarna.checkout.recurring-order-v1+json';

    /**
     * @var Klarna_Checkout_ConnectorInterface
     */
    private $connector;

    /**
     * @var
     */
    private $subscription;

    /**
     * @var The Object in question
     */
    private $_data;

    /**
     * @var Remote endpoint
     */
    private $_location;

    /**
     * @param Klarna_Checkout_ConnectorInterface $connector
     * @param null $uri
     */
    public function __construct(Klarna_Checkout_ConnectorInterface $connector = null, $uri = null)
    {
        $this->connector = $connector ? : Klarna_Checkout_Connector::create(
            Mage::helper('klarna')->getConfig('shared_secret')
        );

        if ($uri !== null) {
            $this->setLocation($uri);
        }
    }

    /**
     * @param $quote
     * @param $subscription
     */
    public function make(KL_Subscriber_Model_Quote $quote, KL_Subscriber_Model_SubscriptionInterface $subscription)
    {
        $this->subscription = $subscription;

        $this->create($this->buildKlarnaOrderObject($quote));
    }

    /**
     * Spins up a builder object that creates the Klarna order array
     *
     * @param $quote
     * @return array
     */
    private function buildKlarnaOrderObject($quote)
    {
        $builder = new KL_Klarna_Model_Klarnacheckout_BuildRecurringOrder;

        return $builder->build($quote);
    }


    /**
     * Go ahead and shoot this guy on the connector
     *
     * @param array $data
     */
    private function create(array $data)
    {
        $options = array(
            'url' => $this->prepareBaseUri(),
            'data' => $data
        );

        $this->connector->apply('POST', $this, $options);
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->_data);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $key
     * @throws InvalidArgumentException
     * @return mixed Can return all value types.
     */
    public function offsetGet($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("Key must be string");
        }

        return $this->_data[$key];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $key
     * @param mixed $value
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("Key must be string");
        }

        $value = print_r($value, true);

        throw new RuntimeException(
            "Use update function to change values. trying to set $key to $value"
        );
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * @throws RuntimeException
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new RuntimeException(
            "unset of fields not supported. trying to unset $offset"
        );
    }

    /**
     * Get the URL of the resource
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->_location;
    }

    /**
     * Set the URL of the resource
     *
     * @param string $location URL of the resource
     *
     * @return void
     */
    public function setLocation($location)
    {
        $this->_location = strval($location);
    }

    /**
     * Return content type of the resource
     *
     * @return string Content type
     */
    public function getContentType()
    {
        return self::CONTENT_TYPE;
    }

    /**
     * Update resource with the new data
     *
     * @param array $data data
     *
     * @return void
     */
    public function parse(array $data)
    {
        $this->_data = $data;
    }

    /**
     * Basic representation of the object
     *
     * @return array data
     */
    public function marshal()
    {
        return $this->_data;
    }

    /**
     * @return string
     */
    private function prepareBaseUri()
    {
        $recurringToken = $this->subscription->getPaymentToken();

        return "/checkout/recurring/{$recurringToken}/orders";
    }

}