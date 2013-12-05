<?php

class KL_Klarna_Model_Observer
{
    public function updateDatabase(Varien_Event_Observer $observer)
    {
        /**
         * Only run this observer code if we are in the payment section in the admin interface
         */
        if ($observer->getData('section') == 'payment') {
            /**
             * Update PClasses in database
             *
             * @var $klarnaHelper KL_Klarna_Helper_Klarna
             */
            Mage::helper('klarna/klarna')->updatePClassesDatabaseAfterSave();
        }
    }
}

