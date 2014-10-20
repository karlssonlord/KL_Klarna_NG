<?php

class KL_Klarna_Model_Pushlock extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('klarna/pushlock');
    }

    public function isLocked($klarnaId)
    {
        $order = $this->getLock($klarnaId);
        if ($order->getKlarnaId()) {
            Mage::log('Klarna retries push: '.$klarnaId, null, 'kl_klarna.log');
            return true;
        } else {
            $this->lock($klarnaId);
            return false;
        }
    }

    public function unLock($klarnaId)
    {
        $lock = $this->getLock($klarnaId);
        $lock->delete();
    }

    private function getLock($klarnaId)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('klarna_id', $klarnaId);

        return $collection->getFirstItem();
    }

    private function lock($klarnaId)
    {
        try {
            $this->klarnaId = $klarnaId;
            $this->createdAt = now();
            $this->save();
        } catch (Exception $e) {
            Mage::log('Unable to create order push lock: '.$klarnaId, null, 'kl_klarna.log');
        }
    }
} 