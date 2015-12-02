<?php

/**
 * Class KL_Klarna_Model_Klarnalog
 */
class KL_Klarna_Model_Klarnalog extends Mage_Core_Model_Abstract
{
    /**
     * Class constructor
     */
    protected function _construct()
    {
        $this->_init("klarna/klarnalog");

        /**
         * Set datetime if not set
         */
        if (!$this->getCreatedAt()) {
            $this->setCreatedAt(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        }
    }
}