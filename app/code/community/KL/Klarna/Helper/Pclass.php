<?php

class KL_Klarna_Helper_Pclass extends KL_Klarna_Helper_Abstract {

    public function updateDatabase()
    {
        // truncate existing pclasses

        $allPClasses = array();

        foreach (Mage::app()->getStores() as $store) {

            /**
             * Check configuration for countries
             */
            $countries = explode(',', $store->getConfig('payment/klarna/countries'));

            /**
             * Check mode (live or dev)
             */
            if ( $store->getConfig('payment_klarna_live') == '1' ) {
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
                            ->setMerchantId($store->getConfig('payment/klarna/merchant_id'))
                            ->setSharedSecret($store->getConfig('payment/klarna/shared_secret'))
                            ->setServer($mode);

                        /**
                         * Add new pclasses to array
                         */
                        foreach ($api->fetch($klarnaModel) as $pclass) {
                            if ( ! isset($allPClasses[$pclass->getEid() . '-' . $pclass->getId()]) ) {
                                $allPClasses[$pclass->getEid() . '-' . $pclass->getId()] = $pclass;
                            }
                        }

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
                            ->setMerchantId($store->getConfig('payment/klarna/merchant_id'))
                            ->setSharedSecret($store->getConfig('payment/klarna/shared_secret'))
                            ->setServer($mode);

                        /**
                         * Add new pclasses to array
                         */
                        foreach ($api->fetch($klarnaModel) as $pclass) {
                            if ( ! isset($allPClasses[$pclass->getEid() . '-' . $pclass->getId()]) ) {
                                $allPClasses[$pclass->getEid() . '-' . $pclass->getId()] = $pclass;
                            }
                        }

                        break;
                }
            }

            return $allPClasses;
        }

        return array();
    }

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

        $template = new Mage_Core_Block_Template();
        $template
            ->setTemplate('klarna/pclasses.phtml')
            ->assign('pclasses', $this->addCurrencySign($pclasses, $code, $before));

        Mage::getSingleton("core/session")->addNotice(
            $template->renderView()
        );

    }

    public function addCurrencySign($pclasses, $sign, $before = false)
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