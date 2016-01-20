<?php
class KL_Klarna_Model_Cron_Newsletter
{
    /**
     * Subscribe customer to newsletter
     *
     * @return void
     */
    public function subscribeCustomerToNewsletter()
    {
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('newsletter_subscription', array('neq' => null))
            ->setOrder('entity_id', 'desc')
            ->setPageSize(5)
            ->setCurPage(1);

        foreach ($orders as $order) {
            $order = Mage::getModel('sales/order')->load($order->getId());

            try {
                if ($order->getNewsletterSubscription()) {
                    $emulation = Mage::getSingleton('core/app_emulation');
                    $environment = $emulation->startEnvironmentEmulation($order->getStoreId());
                    Mage::getModel('newsletter/subscriber')->subscribe($order->getCustomerEmail());
                    $emulation->stopEnvironmentEmulation($environment);
                }
            } catch (Exception $e) {
                $message = sprintf("%s failed subscribing to newsletter", $order->getCustomerEmail());
                Mage::log($message, null, 'klarna_newsletter.log');
            }

            $order->setNewsletterSubscription(null);
            $order->save();
        }
    }
}
