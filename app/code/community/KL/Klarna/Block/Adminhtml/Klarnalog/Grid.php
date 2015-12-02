<?php

/**
 * Class KL_Klarna_Block_Adminhtml_Klarnalog_Grid
 */
class KL_Klarna_Block_Adminhtml_Klarnalog_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId("klarnalogGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel("klarna/klarnalog")->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            "id",
            array(
                "header" => Mage::helper("klarna")->__("ID"),
                "align" => "right",
                "width" => "50px",
                "type" => "number",
                "index" => "id",
            )
        );

        $this->addColumn(
            "quote_id",
            array(
                "header" => Mage::helper("klarna")->__("Quote ID"),
                "index" => "quote_id",
            )
        );
        $this->addColumn(
            "klarna_checkout_id",
            array(
                "header" => Mage::helper("klarna")->__("KCO ID"),
                "index" => "klarna_checkout_id",
            )
        );
        $this->addColumn(
            "order_id",
            array(
                "header" => Mage::helper("klarna")->__("Order ID"),
                "index" => "order_id",
            )
        );
        $this->addColumn(
            "message",
            array(
                "header" => Mage::helper("klarna")->__("Message"),
                "index" => "message",
            )
        );
        $this->addColumn(
            "ip",
            array(
                "header" => Mage::helper("klarna")->__("IP Address"),
                "index" => "ip",
            )
        );

        $this->addColumn(
            "level",
            array(
                "header" => Mage::helper("klarna")->__("Error level"),
                "index" => "level",
            )
        );
        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('klarna')->__('Created at'),
                'index' => 'created_at',
                'type' => 'datetime',
            )
        );
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return '#';
    }


}