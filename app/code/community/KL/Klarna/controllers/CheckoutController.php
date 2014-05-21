<?php

/**
 * Class KL_Klarna_CheckoutController
 */
class KL_Klarna_CheckoutController extends Mage_Core_Controller_Front_Action {

    /**
     * Display the checkout
     *
     * @return void
     */
    public function indexAction()
    {
        $this
            ->loadLayout()
            ->renderLayout();
    }

    public function termsAction()
    {
        echo "Terms";
    }

    public function successAction()
    {
        echo "Success";
    }

    public function pushAction()
    {
        echo "Push";
    }

}
