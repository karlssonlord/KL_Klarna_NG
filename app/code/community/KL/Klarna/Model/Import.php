<?php

/**
 * Class KL_Klarna_Model_Import
 *
 * Get all PClasses for defined countries. If any country don't have correct codes for language, country and currency
 * the class will omit this country but proceed with correct ones
 */

class KL_Klarna_Model_Import extends KL_Klarna_Model_Api_Request
{

    /**
     * Run method
     */
    public function run()
    {
        // Get all defined countries and get all pclasses from the Klarna helper class
        /* @var $klarnaHelper KL_Klarna_Helper_Klarna */
        $klarnaHelper = Mage::helper('klarna/klarna');

        $credentials = $klarnaHelper->getDefinedCountriesCredentials();
        $definedCountries = $credentials['countries'];
        $errors[] = $credentials['errors'];

        // Get PClasses for defined countries
        if (!is_null($definedCountries) && is_array($definedCountries)) {
            foreach ($definedCountries as $definedCountry) {
                $errors[] = $this->getPClasses($definedCountry);
            }
        }

        return array_filter($errors);
    }

}