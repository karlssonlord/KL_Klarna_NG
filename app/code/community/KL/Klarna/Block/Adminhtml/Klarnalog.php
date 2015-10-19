<?php

/**
 * Class KL_Klarna_Block_Adminhtml_Klarnalog
 */
class KL_Klarna_Block_Adminhtml_Klarnalog extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_controller = "adminhtml_klarnalog";
        $this->_blockGroup = "klarna";
        $this->_headerText = Mage::helper("klarna")->__("Klarnalog Manager");
        $this->_addButtonLabel = Mage::helper("klarna")->__("Add New Item");
        parent::__construct();
        $this->_removeButton('add');
    }
}