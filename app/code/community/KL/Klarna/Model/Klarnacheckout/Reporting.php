<?php

/**
 * Class KL_Klarna_Model_Klarnacheckout_Reporting
 */
class KL_Klarna_Model_Klarnacheckout_Reporting extends Varien_Object {
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        $options[] = array(
            'value' => 'email',
            'label' => 'E-mail'
        );

        $options[] = array(
            'value' => 'db',
            'label' => 'DB'
        );

        return $options;
    }
}