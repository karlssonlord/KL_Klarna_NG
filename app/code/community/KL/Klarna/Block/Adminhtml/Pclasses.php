<?php

class KL_Klarna_Block_Adminhtml_Pclasses extends Mage_Adminhtml_Block_System_Config_Form_Field {

    /**
     * Render a Magento Template for taking care of PClasses in admin
     *
     * @param Varien_Data_Form_Element_Abstract $element element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this
            ->setTemplate('klarna/pclasses-buttons.phtml')
            ->assign('field', "klarna_pclasses_buttons");

        return $this->toHtml();
    }
}