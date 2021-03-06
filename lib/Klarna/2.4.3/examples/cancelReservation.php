<?php

require_once dirname(dirname(__FILE__)) . '/Klarna.php';

// Dependencies from http://phpxmlrpc.sourceforge.net/
require_once dirname(dirname(__FILE__)) .
    '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
require_once dirname(dirname(__FILE__)) .
    '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc';

/**
 * 1. Initialize and setup the Klarna instance.
 */

$k = new Klarna();

$k->config(
    123456,               // Merchant ID
    'sharedSecret',       // Shared Secret
    KlarnaCountry::SE,    // Country
    KlarnaLanguage::SV,   // Language
    KlarnaCurrency::SEK,  // Currency
    Klarna::BETA,         // Server
    'json',               // PClass Storage
    '/srv/pclasses.json', // PClass Storage URI path
    true,                 // SSL
    true                  // Remote logging of response times of xmlrpc calls
);

// OR you can set the config to loads from a file, for example /srv/klarna.json:
// $k->setConfig(new KlarnaConfig('/srv/klarna.json'));

/**
 * 2. Cancelling a reservation
 */

// Here you enter the reservation number you got from reserveAmount():
$rno = '123456';

try {
    // [[cancelReservation]]
    $result = $k->cancelReservation($rno);
    // [[cancelReservation]]


    // [[response]]
    true;
    // [[response]]

    echo "Result: {$result}\n";
    // Reservation cancelled, proceed accordingly.
} catch(Exception $e) {
    // Something went wrong or the reservation doesn't exist.
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
