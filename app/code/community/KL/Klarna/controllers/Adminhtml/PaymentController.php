<?php

class KL_Klarna_Adminhtml_PaymentController extends Mage_Adminhtml_Controller_Action
{

    public $_publicActions = array('index');

    public function indexAction()
    {
        Mage::log('Index action..', null, 'kalle.log', true);

        $errors = Mage::getModel('klarna/import')->run();

        if (!$errors) {
            // Set success message
        }

       /*
        $this->loadLayout();
        $this->renderLayout();
       */
        $this->_redirectReferer();
    }

    protected function _isAllowed()
    {
        return true;
    }
} 
