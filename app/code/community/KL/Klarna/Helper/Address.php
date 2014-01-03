<?php

class KL_Klarna_Helper_Address extends KL_Klarna_Helper_Abstract {

    public function fromMagentoToKlarna($address, $emailAddress = null)
    {

        /**
         * Build street address
         */
        $street = implode(" ", $address->getStreet());

        // HouseNo for German and Dutch customers.
        $houseNo = '';

        // House Extension. Dutch customers only.
        $houseExt = '';

        if ( is_null($emailAddress) ) {
            $emailAddress = $address->getEmail();
        }

        $klarnaAddress = new KlarnaAddr(
            $emailAddress,
            "",
            $this->encode($address->getTelephone()),
            $this->encode($address->getFirstname()),
            $this->encode($address->getLastname()),
            "",
            $this->encode($street),
            $this->encode($address->getPostcode()),
            $this->encode($address->getCity()),
            KlarnaCountry::fromCode($address->getCountry()),
            $this->encode($houseNo),
            $this->encode($houseExt)
        );

        Mage::helper('klarna')->log($klarnaAddress);

        return $klarnaAddress;
    }

}