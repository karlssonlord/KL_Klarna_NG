<?php

class KL_Klarna_Helper_Pclass extends KL_Klarna_Helper_Abstract {

    /**
     * Fetch all available pclasses
     *
     * @param null $typeId
     * @param null $quote
     *
     * @return array
     */
    public function getAvailable($typeId = null, $quote = null)
    {
        /**
         * Setup the return array
         */
        $return = array();

        /**
         * Fetch quote if missing
         */
        if ( is_null($quote) ) {
            /**
             * Fetch quote from session
             */
            $quote = Mage::getSingleton('checkout/cart')->getQuote();
        }

        /**
         * Make sure it's a valid Magento quote
         */
        if ( $quote instanceof Mage_Sales_Model_Quote ) {
            /**
             * Fetch grand total
             */
            $grandTotal = floatval($quote->getGrandTotal());
        } else {
            $grandTotal = 0;
        }

        /**
         * Fetch our Klarna model
         */
        $klarna = Mage::getModel('klarna/klarna');

        /**
         * Setup the collection
         */
        $collection = Mage::getModel('klarna/pclass')
            ->getCollection()
            ->addFieldToFilter('country', $klarna->getCurrentCountry())
            ->addFieldToFilter('eid', $klarna->getMerchantId());

        /**
         * Add type id if set
         */
        if ( ! is_null($typeId) ) {
            $collection->addFieldToFilter('type', $typeId);
        }

        /**
         * Add sorting
         */
        $collection->addOrder('id', 'ASC');

        /**
         * Add pclasses if the minimum amount is fulfilled
         */
        foreach ($collection as $row) {
            if ( $grandTotal >= floatval($row->getData('minamount')) ) {

                $rowData = $row->getData();
                try {
                    $pclass = new KlarnaPClass($rowData);
                    $montlyCost = KlarnaCalc::calc_monthly_cost($grandTotal, $pclass, KlarnaFlags::CHECKOUT_PAGE);
                    $montlyCost = round($montlyCost, 0);
                    $rowData['perMonthRaw'] = $montlyCost;
                    $montlyCost = Mage::helper('core')->currency($montlyCost, true, false);
                    $rowData['perMonth'] = $this->__(sprintf('%s/month', $montlyCost));

                } catch (Exception $e) {
                    Mage::helper('klarna')->log($e->getMessage());
                    $rowData['perMonth'] = '';
                    $rowData['perMonthRaw'] = '';
                }

                $return[] = $rowData;
            }
        }

        /**
         * Return the result
         */
        return $return;
    }

    public function getCheapestOption($typeId = 0, $quote = null)
    {
        if ( is_array($typeId) ) {
            foreach ($typeId as $subId) {
                foreach ($this->getAvailable($subId, $quote) as $option) {
                    if ( ! isset($cheapest) ) {
                        $cheapest = $option['perMonth'];
                        $amount = $option['perMonthRaw'];
                    } else {
                        if ( $option['perMonthRaw'] < $amount ) {
                            $cheapest = $option['perMonth'];
                            $amount = $option['perMonthRaw'];
                        }
                    }
                }
            }

        } else {
            foreach ($this->getAvailable($typeId, $quote) as $option) {
                if ( ! isset($cheapest) ) {
                    $cheapest = $option['perMonth'];
                    $amount = $option['perMonthRaw'];
                } else {
                    if ( $option['perMonthRaw'] < $amount ) {
                        $cheapest = $option['perMonth'];
                        $amount = $option['perMonthRaw'];
                    }
                }
            }
        }

        return $cheapest;
    }

    /**
     * Fetch new pclasses from Klarna and store in database
     *
     * @return array
     */
    public function updateDatabase()
    {
        /**
         * Default is that we haven't cleared the old pclasses
         */
        $clearedPclasses = false;

        /**
         * Loop all stores to figure out what to do
         */
        foreach (Mage::app()->getStores() as $_eachStoreId => $val) {


            /**
             * Check if invoice or account is enabled
             */
            $eidStoreSpecific = Mage::getStoreConfig('payment/klarna/merchant_id', $_eachStoreId);
            $invoiceEnabled = Mage::getStoreConfig('payment/klarna_invoice/active', $_eachStoreId);
            $accountEnabled = Mage::getStoreConfig('payment/klarna_partpayment/active', $_eachStoreId);

            if ( $eidStoreSpecific && ($invoiceEnabled || $accountEnabled) ) {

                /**
                 * Check configuration for countries
                 */
                $countries = explode(',', Mage::getStoreConfig('payment/klarna/countries', $_eachStoreId));

                /**
                 * Check mode (live or dev)
                 */
                if ( Mage::getStoreConfig('payment/klarna/live', $_eachStoreId) == '1' ) {
                    $mode = Klarna::LIVE;
                } else {
                    $mode = Klarna::BETA;
                }

                /**
                 * Handle all countries
                 */
                foreach ($countries as $country) {
                    switch ($country) {
                        case 'SE':
                            /**
                             * Setup custom configuration
                             */
                            $api = Mage::getModel('klarna/api_pclass');
                            $klarnaModel = Mage::getModel('klarna/klarna');

                            $klarnaModel
                                ->setCountry(KlarnaCountry::SE)
                                ->setLanguage(KlarnaLanguage::SV)
                                ->setCurrency(KlarnaCurrency::SEK)
                                ->setMerchantId(Mage::getStoreConfig('payment/klarna/merchant_id', $_eachStoreId))
                                ->setSharedSecret(
                                    Mage::getStoreConfig('payment/klarna/shared_secret', $_eachStoreId)
                                )
                                ->setServer($mode);


                            /**
                             * Clear old pclasses
                             */
                            if ( ! $clearedPclasses ) {
                                $clearedPclasses = true;
                                $api->clearPClasses($klarnaModel);
                            }

                            $api->fetch($klarnaModel);
                            break;
                        case 'DK':
                            /**
                             * Setup custom configuration
                             */
                            $api = Mage::getModel('klarna/api_pclass');
                            $klarnaModel = Mage::getModel('klarna/klarna');

                            $klarnaModel
                                ->setCountry(KlarnaCountry::DK)
                                ->setLanguage(KlarnaLanguage::DA)
                                ->setCurrency(KlarnaCurrency::DKK)
                                ->setMerchantId(Mage::getStoreConfig('payment/klarna/merchant_id', $_eachStoreId))
                                ->setSharedSecret(
                                    Mage::getStoreConfig('payment/klarna/shared_secret', $_eachStoreId)
                                )
                                ->setServer($mode);

                            /**
                             * Clear old pclasses
                             */
                            if ( ! $clearedPclasses ) {
                                $clearedPclasses = true;
                                $api->clearPClasses($klarnaModel);
                            }

                            $api->fetch($klarnaModel);
                            break;
                    }
                }
            }
        }

        return array();
    }

    /**
     * View stored pclasses in admin
     */
    public function adminView()
    {
        /**
         * Fetch MySQL storage from Klarna lib
         */
        $storage = new MySQLStorage();

        /**
         * Get our Klarna model
         */
        $klarna = Mage::getModel('klarna/klarna');

        try {
            $storage->load($klarna->getDbUri());
        } catch (KlarnaException $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            return;
        }

        /**
         * Setup array with PClasses found
         */
        $pclasses = array();

        /**
         * Fetch all PClasses from database
         */
        foreach ($storage->getAllPClasses() as $pclass) {

            $expiryDate = '-';
            if ( $pclass->getExpire() ) {
                $expiryDate = date('Y-m-d', $pclass->getExpire());
            }

            $country = KlarnaCountry::getCode($pclass->getCountry());

            $code = "";
            $before = false;

            // Check if we should set the currency symbol
            switch (strtolower($country)) {
                case "de":
                case "nl":
                case "fi":
                    $before = true;
                    $code = "&euro;";
                    break;
                case "se":
                case "no":
                case "dk":
                    $before = false;
                    $code = "kr";
                    break;
            }

            $pclasses[] = array(
                'eid' => $pclass->getEid(),
                'country' => $country,
                'id' => $pclass->getId(),
                'description' => $pclass->getDescription(),
                'expiryDate' => $expiryDate,
                'months' => $pclass->getMonths(),
                'startFee' => $pclass->getStartFee(),
                'invoiceFee' => $pclass->getInvoiceFee(),
                'interestRate' => $pclass->getInterestRate() . '%',
                'minAmount' => $pclass->getMinAmount()
            );
        }

        /**
         * Setup template
         */
        $template = new Mage_Core_Block_Template();
        $template
            ->setTemplate('klarna/pclasses.phtml')
            ->assign('pclasses', $this->addCurrencySign($pclasses, $code, $before));

        /**
         * Add HTML to session message
         */
        Mage::getSingleton("core/session")->addNotice(
            $template->renderView()
        );

    }

    /**
     * Add currency signs to amounts
     *
     * @param $pclasses
     * @param $sign
     * @param bool $before
     *
     * @return mixed
     */
    private function addCurrencySign($pclasses, $sign, $before = false)
    {
        foreach ($pclasses as $key => $pclass) {
            if ( $before ) {
                $pclass['startFee'] = $sign . $pclass['startFee'];
                $pclass['invoiceFee'] = $sign . $pclass['invoiceFee'];
                $pclass['minAmount'] = $sign . $pclass['minAmount'];
            } else {
                $pclass['startFee'] = $pclass['startFee'] . ' ' . $sign;
                $pclass['invoiceFee'] = $pclass['invoiceFee'] . ' ' . $sign;
                $pclass['minAmount'] = $pclass['minAmount'] . ' ' . $sign;
            }
            $pclasses[$key] = $pclass;
        }
        return $pclasses;
    }

}