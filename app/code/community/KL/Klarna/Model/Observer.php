<?php

class KL_Klarna_Model_Observer extends Mage_Core_Model_Abstract {

    public function adminSystemConfigChangedSectionKlarna($observer)
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
            case 'view':
                Mage::helper('klarna/pclass')->adminView();
                break;
            case 'update':
                Mage::helper('klarna/pclass')->updateDatabase();
                break;
        }

        return $observer;
    }

}