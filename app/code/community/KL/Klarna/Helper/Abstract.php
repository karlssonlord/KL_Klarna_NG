<?php

require_once('Klarna/2.4.3/Klarna.php');
require_once('Klarna/2.4.3/Country.php');
require_once('Klarna/2.4.3/Exceptions.php');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
require_once('Klarna/2.4.3/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');

class KL_Klarna_Helper_Abstract extends Mage_Core_Helper_Abstract {

    /**
     * Convert string form UTF-8 to ISO-8859-1
     *
     * @param $string
     *
     * @return string
     */
    public function encode($string)
    {
        return utf8_decode($string);
    }

    /**
     * Convert string from ISO-8859-1 to UTF-8
     *
     * @param $string
     *
     * @return string
     */
    public function decode($string)
    {
        return utf8_encode($string);
    }

}