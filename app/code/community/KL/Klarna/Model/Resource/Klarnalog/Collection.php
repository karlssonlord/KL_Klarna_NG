<?php

/**
 * Class KL_Klarna_Model_Resource_Klarnalog_Collection
 */
class KL_Klarna_Model_Resource_Klarnalog_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('klarna/klarnalog');
    }
}