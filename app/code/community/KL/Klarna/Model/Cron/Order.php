<?php

class KL_Klarna_Model_Cron_Order
{
    /**
     * Age(in hours) of orders to be checked
     */
    const ORDER_AGE = 1;

    /**
     *  Check if Magento orders exist in Klarna
     */
    public function check()
    {
        Mage::log('Start running KL_Klarna_Model_Cron_Order::check()', null, 'kl_klarna_order_check.log');

        $lastOrderId = (int)Mage::getStoreConfig('payment/klarna/last_checked_order_id');
        /*
         * Fetch order which are not younger than 1 hour and go after the last checked order the last
         * and have klarna_checkout values
         */
        $ordersCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('created_at', array(
                'to' => $this->getMaximumAllowedAge()
            ))
            ->addFieldToFilter('entity_id', array(
                'gt' => $lastOrderId
            ))
            ->addFieldToFilter('klarna_checkout', array(
                'notnull' => true
            ))
            ->setOrder('entity_id','ASC')
            ->setPageSize(20)
            ->setCurPage(1);

        $newLastOrderId = '';
        $errorMessages = array();

        foreach($ordersCollection as $order) {
            try {
                $klarnaOrder = Mage::getModel('klarna/klarnacheckout')->getOrder($order->getKlarnaCheckout());
                /*
                 * Possible values are: `checkout_incomplete` by default, alternatively `checkout_complete`, `created`
                 * We are interested in all except `created`
                 */
                if($klarnaOrder['status'] != 'created') {
                    $errorMessages[] = '[MAGENTO ORDER: ' . $order->getIncrementId() . ']' .
                        '[REFERENCE: ' . $klarnaOrder['reference'] . '][RESERVATION: ' . $klarnaOrder['reservation'] . ']' .
                        ' was NOT created/finished in Klarna';
                }
                $newLastOrderId = $order->getId();
            } catch(Exception $e) {
                $errorMessages[] = 'Caught exception: ' .  $e->getMessage();
            }
        }

        if($newLastOrderId) {
            $modelConfig = new Mage_Core_Model_Config();
            $modelConfig->saveConfig('payment/klarna/last_checked_order_id', $newLastOrderId);
        }

        if(!empty($errorMessages)) {
            $messageFinal = implode("\n", $errorMessages);
            Mage::helper('klarna')->sendErrorEmail($messageFinal);
            Mage::log($messageFinal, null, 'kl_klarna_order_check.log');
        }
        Mage::log('Finished running KL_Klarna_Model_Cron_Order::check()', null, 'kl_klarna_order_check.log');
    }

    /**
     * Create timestamp
     *
     * @return bool|string
     */
    private function getMaximumAllowedAge()
    {
        $now = now();
        $timestamp = strtotime("-".self::ORDER_AGE." hour", strtotime($now));

        return date('Y-m-d H:i:s', $timestamp);
    }
}