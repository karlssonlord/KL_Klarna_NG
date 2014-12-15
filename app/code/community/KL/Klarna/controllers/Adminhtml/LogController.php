<?php
class KL_Klarna_Adminhtml_LogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     *
     * @return KL_Slideshow_CategoryController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('report/kl_log')
            ->_addBreadcrumb(
                Mage::helper('klarna')->__('Reports'),
                Mage::helper('klarna')->__('Reports')
            )
            ->_addBreadcrumb(
                Mage::helper('klarna')->__('Klarna Logs'),
                Mage::helper('klarna')->__('Klarna Logs')
            );

        return $this;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_title($this->__('CMS'))->_title($this->__('Klarna Logs'));
        $this->_initAction();

        $this->renderLayout();
    }

    /**
     * New action
     *
     * @return void
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Edit action
     *
     * @return void
     */
    public function editAction()
    {
        $this->_title($this->__('CMS'))->_title($this->__('Categories'));

        $id    = $this->getRequest()->getParam('category_id');
        $model = Mage::getModel('slideshow/category');

        if ($id) {
            $model->load($id);

            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('slideshow')->__("This category doesn't exist anymore.")
                );

                $this->_redirect('*/*/');

                return;
            }

            $title      = $model->getName();
            $breadcrumb = Mage::helper('slideshow')->__('Edit category');
        } else {
            $title      = $this->__('New slide');
            $breadcrumb = Mage::helper('slideshow')->__('New category');
        }

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);

        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('slideshow_category', $model);

        $this->_title($title);

        $this->_initAction()
            ->_addBreadcrumb($breadcrumb, $breadcrumb)
            ->renderLayout();
    }

    /**
     * Save action
     *
     * @return void
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost();

        if ($data) {
            $id    = $this->getRequest()->getParam('category_id');
            $model = Mage::getModel('slideshow/category')->load($id);

            if (!$model->getId() && $id) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('slideshow')->__("This slide doesn't exist anymore.")
                );

                $this->_redirect('*/*/');

                return;
            }

            try {

                $model->setData($data);
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('slideshow')->__('The category was successfully saved.')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('category_id' => $model->getId()));

                    return;
                }
            } catch (Exception $e) {
                $error_msg = $e->getMessage();

                Mage::getSingleton('adminhtml/session')->addError($error_msg);
                Mage::getSingleton('adminhtml/session')->setFormData($data);

                $this->_redirect(
                    '*/*/edit',
                    array('category_id' => $this->getRequest()->getParam('category_id'))
                );

                return;
            }
        }

        $this->_redirect('*/*/');
    }

    /**
     * Delete action
     *
     * @return void
     */
    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('category_id');

        if ($id) {
            try {
                $model = Mage::getModel('slideshow/category');

                $model->load($id);
                $model->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('slideshow')->__('The category was successfully deleted.')
                );

                $this->_redirect('*/*/');

                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                $this->_redirect('*/*/edit', array('category_id' => $id));

                return;
            }
        }

        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('slideshow')->__('Could not find the category.')
        );

        $this->_redirect('*/*/');
    }

    /**
     * Is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('cms/slideshow');
    }
}