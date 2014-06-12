<?php

/**
 * Class KL_Klarna_PingController
 */
class KL_Klarna_PingController extends Mage_Core_Controller_Front_Action {


    /**
     * Fetch e-mail address from Klarna Checkout quote
     *
     * @return string
     */
    public function indexAction()
    {
        /**
         * Default is that no e-mail address was received
         */
        $emailAddress = false;

        $_SERVER['HTTP_REFERER'] = 'http://local.animail.se/foo/bar';

        /**
         * Check HTTP referer
         */
        if ( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) ) {

            /**
             * Fetch the Klarna Checkout model
             */
            $klarnaCheckout = Mage::getModel('klarna/klarnacheckout');

            /**
             * Make sure quote has a Klarna checkout session
             */
            $order = $klarnaCheckout->getExistingKlarnaOrder();

            /**
             * Make sure the order was found
             */
            if ( $order ) {

                /**
                 * Set the e-mail if it's stored at Klarna
                 */
                if ( isset($order['shipping_address']) && isset($order['shipping_address']['email']) ) {
                    $emailAddress = $order['shipping_address']['email'];
                }

            }

        } else {

            /**
             * Make a note in the log
             */
            Mage::helper('klarna')->log(
                'Referer for PING failed. "' . $_SERVER['HTTP_REFERER'] . '" vs "' . $_SERVER['SERVER_NAME'] . '"'
            );

        }

        /**
         * Setup the response
         */
        $jsonData = array(
            'shipping_address' => array(
                'email' => $emailAddress
            )
        );

        /**
         * Convert to json string
         */
        $jsonData = json_encode($jsonData);

        /**
         * Setup the response
         */
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);

    }


}