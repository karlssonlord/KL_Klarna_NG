<?php
class KL_Klarna_Block_Adminhtml_Log_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->setId('logGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('ASC');
    }

    /**
     * Prepare collection
     *
     * @return
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('klarna/log')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return
     */
    protected function _prepareColumns()
    {
        $this->addColumn('log_id', array(
            'header'    => Mage::helper('klarna')->__('Log Id'),
            'align'     => 'left',
            'index'     => 'log_id',
        ));

        $this->addColumn('klarna_checkout_id', array(
            'header'    => Mage::helper('klarna')->__('Klarna Checkout Id'),
            'align'     => 'left',
            'index'     => 'klarna_checkout_id',
            'sortable'  => false,
        ));

        $this->addColumn('store_id', array(
            'header'    => Mage::helper('klarna')->__('Store Id'),
            'align'     => 'left',
            'index'     => 'store_id',
        ));

        $this->addColumn('quote_id', array(
            'header'    => Mage::helper('klarna')->__('Klarna Checkout Id'),
            'align'     => 'left',
            'index'     => 'quote_id',
        ));

        $this->addColumn('order_id', array(
            'header'    => Mage::helper('klarna')->__('Order Id'),
            'align'     => 'left',
            'index'     => 'order_id',
        ));

        $this->addColumn('message', array(
            'header'    => Mage::helper('klarna')->__('Message'),
            'align'     => 'left',
            'index'     => 'message',
            'sortable'  => false,
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('klarna')->__('Created At'),
            'align'     => 'left',
            'index'     => 'created_at',
        ));

        return parent::_prepareColumns();
    }

    /**
     * After load collection
     *
     * @return void
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');

        parent::_afterLoadCollection();
    }

    /**
     * After load collection
     *
     * @return void
     */
    protected function _filterStoreCondition($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (!$value) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }
}
