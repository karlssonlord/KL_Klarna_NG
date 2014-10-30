<?php

class KL_Klarna_Model_Cron_Cleanpushlocktable
{
    /**
     * Allowed push lock age expressed in number of hours
     */
    const ALLOWED_AGE = 3;

    /**
     *  Check for records old enough, and try to delete them
     */
    public function clean()
    {
        $collectionOfOldies = Mage::getModel('klarna/pushlock')->getCollection()
            ->addFieldToFilter('created_at', array(
                'to' => $this->getMaximumAllowedAge()
            ));

        if (count($collectionOfOldies)) {
            $this->delete($collectionOfOldies);
        }

        Mage::log("Routine for cleaning of klarna_pushlock table finished.", null, 'kl_klarna.log');
    }

    /**
     * Delete all records in one go and log any troubles
     *
     * @param $collection
     */
    private function delete($collection)
    {
        foreach ($collection as $row) {
            try {
                $row->delete();
            } catch (Exception $e) {
                Mage::log("Unable to remove pushlock record {$row->klarna_id}@{$row->created_at}: {$e->getMessage()}", null, 'kl_klarna.log');
            }
        }
    }

    /**
     * Create timestamp
     *
     * @return bool|string
     */
    private function getMaximumAllowedAge()
    {
        $now = now();
        $timestamp = strtotime("-".self::ALLOWED_AGE." hour", strtotime($now));

        return date('Y-m-d H:i:s', $timestamp);
    }

} 