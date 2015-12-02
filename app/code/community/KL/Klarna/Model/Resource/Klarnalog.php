<?php

/**
 * Class KL_Klarna_Model_Resource_Klarnalog
 */
class KL_Klarna_Model_Resource_Klarnalog extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init("klarna/klarnalog", "id");
    }
}