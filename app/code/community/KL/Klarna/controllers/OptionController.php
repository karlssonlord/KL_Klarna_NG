<?php
class KL_Klarna_OptionController
    extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $newsletter = $this->getRequest()->getPost('newsletter');
        $quote = Mage::getModel('checkout/cart')->getQuote();

        if ($quote->getId()) {
            /**
             * Fix the newsletter status
             */
            if ($newsletter !== false) {
                if ($newsletter == 'true') {
                    $newsletter = 1;
                } else {
                    $newsletter = 0;
                }

                Mage::getSingleton('checkout/session')->setCustomerIsSubscribed($newsletter);
                $quote->setData('newsletter_subscription', $newsletter);
            }
            $quote->save();
        }

        print json_encode(array('success' => true));
        exit;
    }
}
