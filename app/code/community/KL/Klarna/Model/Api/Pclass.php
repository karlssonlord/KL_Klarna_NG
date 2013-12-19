<?php

class KL_Klarna_Model_Api_Pclass extends KL_Klarna_Model_Api_Abstract {

    public function fetch($klarnaModel = null)
    {
        /**
         * Fetch the API
         */
        $api = $this->getApi($klarnaModel);

        $api->fetchPClasses();

        return $api->getAllPClasses();
    }

}