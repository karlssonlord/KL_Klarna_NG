<?php

/**
 * Class KL_Klarna_Adminhtml_KlarnalogController
 */
class KL_Klarna_Adminhtml_KlarnalogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu("klarna/klarnalog");

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__("Klarna"));
        $this->_title($this->__("Manager Klarnalog"));

        $this->_initAction();
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->_title($this->__("Klarna"));
        $this->_title($this->__("Klarnalog"));
        $this->_title($this->__("Edit Item"));

        $id = $this->getRequest()->getParam("id");
        $model = Mage::getModel("klarna/klarnalog")->load($id);
        if ($model->getId()) {
            Mage::register("klarnalog_data", $model);
            $this->loadLayout();
            $this->_setActiveMenu("klarna/klarnalog");
            $this->_addBreadcrumb(
                Mage::helper("adminhtml")->__("Klarnalog Manager"),
                Mage::helper("adminhtml")->__("Klarnalog Manager")
            );
            $this->_addBreadcrumb(
                Mage::helper("adminhtml")->__("Klarnalog Description"),
                Mage::helper("adminhtml")->__("Klarnalog Description")
            );
            $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock("klarna/adminhtml_klarnalog_edit"))->_addLeft(
                $this->getLayout()->createBlock("klarna/adminhtml_klarnalog_edit_tabs")
            );
            $this->renderLayout();
        } else {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("klarna")->__("Item does not exist."));
            $this->_redirect("*/*/");
        }
    }

    public function newAction()
    {

        $this->_title($this->__("Klarna"));
        $this->_title($this->__("Klarnalog"));
        $this->_title($this->__("New Item"));

        $id = $this->getRequest()->getParam("id");
        $model = Mage::getModel("klarna/klarnalog")->load($id);

        $data = Mage::getSingleton("adminhtml/session")->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register("klarnalog_data", $model);

        $this->loadLayout();
        $this->_setActiveMenu("klarna/klarnalog");

        $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

        $this->_addBreadcrumb(
            Mage::helper("adminhtml")->__("Klarnalog Manager"),
            Mage::helper("adminhtml")->__("Klarnalog Manager")
        );
        $this->_addBreadcrumb(
            Mage::helper("adminhtml")->__("Klarnalog Description"),
            Mage::helper("adminhtml")->__("Klarnalog Description")
        );


        $this->_addContent($this->getLayout()->createBlock("klarna/adminhtml_klarnalog_edit"))->_addLeft(
            $this->getLayout()->createBlock("klarna/adminhtml_klarnalog_edit_tabs")
        );

        $this->renderLayout();

    }

    public function saveAction()
    {

        $post_data = $this->getRequest()->getPost();


        if ($post_data) {

            try {


                $model = Mage::getModel("klarna/klarnalog")
                    ->addData($post_data)
                    ->setId($this->getRequest()->getParam("id"))
                    ->save();

                Mage::getSingleton("adminhtml/session")->addSuccess(
                    Mage::helper("adminhtml")->__("Klarnalog was successfully saved")
                );
                Mage::getSingleton("adminhtml/session")->setKlarnalogData(false);

                if ($this->getRequest()->getParam("back")) {
                    $this->_redirect("*/*/edit", array("id" => $model->getId()));
                    return;
                }
                $this->_redirect("*/*/");
                return;
            } catch (Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                Mage::getSingleton("adminhtml/session")->setKlarnalogData($this->getRequest()->getPost());
                $this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
                return;
            }

        }
        $this->_redirect("*/*/");
    }


    public function deleteAction()
    {
        if ($this->getRequest()->getParam("id") > 0) {
            try {
                $model = Mage::getModel("klarna/klarnalog");
                $model->setId($this->getRequest()->getParam("id"))->delete();
                Mage::getSingleton("adminhtml/session")->addSuccess(
                    Mage::helper("adminhtml")->__("Item was successfully deleted")
                );
                $this->_redirect("*/*/");
            } catch (Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                $this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
            }
        }
        $this->_redirect("*/*/");
    }


    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName = 'klarnalog.csv';
        $grid = $this->getLayout()->createBlock('klarna/adminhtml_klarnalog_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction()
    {
        $fileName = 'klarnalog.xml';
        $grid = $this->getLayout()->createBlock('klarna/adminhtml_klarnalog_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
}
