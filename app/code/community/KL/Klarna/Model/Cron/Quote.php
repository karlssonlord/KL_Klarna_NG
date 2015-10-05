<?php
class KL_Klarna_Model_Cron_Quote
{
    protected function _getCollection()
    {
        $from = Mage::getModel('core/date')->gmtDate(null, date('Y-m-d H:i:s', strtotime('-4 hour')));
        $to = Mage::getModel('core/date')->gmtDate(null, date('Y-m-d H:i:s', strtotime('-1 hour')));

        $quotes = Mage::getModel('sales/quote')
            ->getCollection()
            ->addFieldToFilter('klarna_checkout', array('notnull' => true))
            ->addFieldToFilter('customer_email', array('null' => true))
            ->addFieldToFilter('updated_at', array('from' => $from))
            ->addFieldToFilter('updated_at', array('to' => $to))
            ->load();

        return $quotes;
    }

    public function collectEmails()
    {
        foreach ($this->_getCollection() as $quote)
        {
            $checkoutId = $quote->getKlarnaCheckout();

            if ($checkoutId) {
                $checkout = Mage::getModel('klarna/klarnacheckout', array('store_id' => $quote->getStoreId()))->getOrder($checkoutId);

                if (isset($checkout['shipping_address']['email']) && $checkout['shipping_address']['email']) {
                    Mage::log($quote->getId(), null, 'klarnaCollectEmails.log', true);
                    $quote->setCustomerEmail($checkout['shipping_address']['email']);
                    $quote->save();
                }
            }
        }
    }
}
