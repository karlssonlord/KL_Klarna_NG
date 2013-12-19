<?php

class KL_Klarna_Model_Observer extends Mage_Core_Model_Abstract {

    public function adminSystemConfigChangedSectionPayment($observer)
    {
        /**
         * Fetch the action
         */
        $action = Mage::app()->getRequest()->getParam(
            'klarna_pclasses_buttons'
        );

        /**
         * Find out what to do
         */
        switch ($action) {
            /**
             * View PClasses
             */
            case 'view':
                Mage::helper('klarna/pclass')->adminView();
                break;

            /**
             * Update PClasses
             */
            case 'update':
                Mage::helper('klarna/pclass')->updateDatabase();
                break;
        }

        return $observer;
    }

}