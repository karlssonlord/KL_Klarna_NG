<?php

class KL_Klarna_Model_Resource_Pushlock_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('klarna/pushlock');
    }
} 