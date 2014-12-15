<?php
class KL_Klarna_Block_Adminhtml_Log
    extends KL_Klarna_Block_Adminhtml_Log_Grid_Container
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->_controller     = 'adminhtml_log';
        $this->_headerText     = Mage::helper('klarna')->__('Logs');

        parent::__construct();
    }
}
