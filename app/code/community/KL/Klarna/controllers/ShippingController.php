<?php

/**
 * Class ShippingController
 */
class KL_Klarna_ShippingController extends Mage_Core_Controller_Front_Action {

    /**
     * Update the given shipping method
     */
    public function indexAction()
    {
        /**
         * Fetch the checkout model
         */
        $klarnaCheckout = Mage::getModel('klarna/klarnacheckout');

        /**
         * Fetch shipping method code from POST request
         */
        $shippingMethodCode = Mage::app()->getRequest()->getPost('shippingCode');

        /**
         * Default response is that we failed
         */
        $return = array(
            'success' => false,
        );

        /**
         * Update quote and return new information
         */
        if ( $shippingMethodCode ) {

            /**
             * Update the quote
             */
            Mage::helper('klarna/checkout')->selectShippingMethod($shippingMethodCode);

            /**
             * Update the order at Klarna
             */
            $klarnaCheckout->handleOrder();

            /**
             * Set response as positive
             */
            $return['success'] = true;
        }

        /**
         * Send json response
         */
        $this->getResponse()
            ->setHeader('Content-type', 'application/json')
            ->setBody(json_encode($return));
    }

}