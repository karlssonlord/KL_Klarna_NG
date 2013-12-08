<?php
/**
 * Class KL_Klarna_Adminhtml_PaymentController
 *
 * Controller class to handle Klarna PClasses, method for retrieve PClasses and to get PClasses stored in database.
 */

class KL_Klarna_Adminhtml_PaymentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Define allowed method calls
     * @var array
     */
    public $_publicActions = array('update', 'get');

    /**
     * Update PClasses from Klarna
     *
     * Set error or success messages depending on result
     */
    public function updateAction()
    {
        $errors = Mage::getModel('klarna/import')->run();
        // Set success or error message
        if (count($errors)) {
            $message = '';
            foreach ($errors as $error) {
                $message .= $error;
            }
            Mage::getSingleton('adminhtml/session')->addError($message);
        } else {
            $message = $this->__('PClasses has been updated successfully!');
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        }

        $this->_redirectReferer();
    }

    /**
     * Get Pclasses stored in database
     */
    public function getAction()
    {
        // Get all pclasses from database
        /* @var $klarnaHelper KL_Klarna_Helper_Klarna */
        $klarnaHelper = Mage::helper('klarna/klarna');
        $pclasses = $klarnaHelper->getPclasses();

        $this->_redirectReferer();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
} 
