<?php

class KL_Klarna_Model_Resource_Pushlock extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('klarna/pushlock', 'pushlock_id');
    }
}