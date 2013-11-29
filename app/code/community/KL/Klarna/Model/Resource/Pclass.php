<?php


class KL_Klarna_Model_Resource_Pclass extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('klarna/pclass', 'id');
    }
}
