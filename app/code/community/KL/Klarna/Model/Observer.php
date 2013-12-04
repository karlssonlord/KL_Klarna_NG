<?php

class KL_Klarna_Model_Observer
{
    public function updateDatabase(Varien_Event_Observer $observer)
    {
        if ($observer->getData('section') == 'payment') {
            /* @var $klarnaHelper KL_Klarna_Helper_Klarna */
            $klarnaHelper = Mage::helper('klarna/klarna');

            $definedCountries = $klarnaHelper->getDefinedCountriesCredentials();

        }
    }
}

