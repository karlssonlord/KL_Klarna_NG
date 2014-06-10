<?php
/**
 * Observer
 */
class KL_Klarna_Model_Observer extends Mage_Core_Model_Abstract {

    /**
     * @param $observer
     * @return mixed
     */
    public function adminSystemConfigChangedSectionPayment($observer)
    {
        /**
         * Fetch the action
         */
        $action = Mage::app()->getRequest()->getParam(
            'klarna_pclasses_buttons'
        );

        /**
         * Find out what to do
         */
        switch ($action) {
            /**
             * View PClasses
             */
            case 'view':
                Mage::helper('klarna/pclass')->adminView();
                break;

            /**
             * Update PClasses
             */
            case 'update':
                Mage::helper('klarna/pclass')->updateDatabase();
                break;
        }

        return $observer;
    }

    /**
     * Observer method for handling Klarna Checkout orders
     *
     * @param Varien_Event_Observer @observer
     *
     * @return Varien_Event_Observer
     */
    public function handleOrder($observer)
    {
        Mage::getModel('klarna/klarnacheckout')
            ->handleOrder();

        return $observer;
    }

    public function preDispatchCheckout($observer)
    {

        $shippingAddress = Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getShippingAddress();

        $shippingAddress
            ->setCountryId(Mage::getModel('klarna/klarnacheckout')->getCountry())
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->save();

        $groups = $shippingAddress->getGroupedAllShippingRates();

        if (!empty($groups)) {
            foreach ($groups as $code => $groupItems) {
                foreach ($groupItems as $item) {
                    if (!isset($shippingMethod)
                        || $shippingMethod->getPrice() > $item->getPrice()
                    ) {
                        $shippingMethod = $item;
                    }
                }
            }

            if (!$shippingAddress->getShippingMethod()) {
                $shippingAddress->setShippingMethod(
                    $shippingMethod->getCode()
                )->save();
            }
        }

        return $observer;
    }
}
