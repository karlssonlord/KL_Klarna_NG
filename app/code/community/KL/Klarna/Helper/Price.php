<?php 

class KL_Klarna_Helper_Price extends Mage_Core_Helper_Abstract
{
    /**
     * Convert fake float with 4 decimals to int
     *
     * @param $float
     * @return int
     */
    public function fakeFloatToKlarnaInt($float)
    {
        $float = (string) $float;

        /** Assure it has 4 decimals */
        $decimals = explode('.', $float);

        if (count($decimals) == 1) {
            return (int) $float * 100;
        }

        if (strlen($decimals[1]) > 1) {
            $string = $decimals[0] . substr($decimals[1], 0, 2);
        } else {
            $string = $decimals[0] . substr($decimals[1], 0, 1) . 0;
        }

        return (int) $string;
    }
}