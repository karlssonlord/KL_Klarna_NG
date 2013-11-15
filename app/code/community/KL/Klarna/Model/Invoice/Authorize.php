<?php

class KL_Klarna_Model_Invoice_Authorize extends KL_Klarna_Model_Invoice_Abstract
{
    public function authorize()
    {
        $client = $this->getClient();

        $payment = $this->getPayment();
        $order = $payment->getOrder();
        $items = $order->getAllVisibleItems();

        foreach ($items as $item) {
            $client->addArticle(
                $item->getQtyOrdered(),    // Quantity
                $item->getSku(),    // SKU
                $item->getName(),
                $item->getPriceInclTax(),
                $item->getTaxPercent(),
                0,                  // Discount in percent
                32                  // KlarnaFlags::INC_VAT...
            );
        }

        // Billing address
        $billingAddress = $order->getBillingAddress();

        $klarnaBilling = new KlarnaAddr(
            $order->getCustomerEmail(),             // email
            $billingAddress->getTelephone(),        // Telno, only one phone number is needed.
            '',                                     // Cellno
            $billingAddress->getFirstname(),        // Firstname
            $billingAddress->getLastname(),         // Lastname
            '',                                     // No care of, C/O.
            join(', ', $billingAddress->getStreet()),   // Street
            $billingAddress->getPostcode(),         // Zip Code
            $billingAddress->getCity(),             // City
            KlarnaCountry::SE,            // Country
            null,                         // HouseNo for German and Dutch customers.
            null                          // House Extension. Dutch customers only.
        );

        $client->setAddress(KlarnaFlags::IS_BILLING, $klarnaBilling);

        // Shipping address
        $shippingAddress = $order->getShippingAddress();

        $klarnaShipping = new KlarnaAddr(
            $order->getCustomerEmail(),             // email
            $shippingAddress->getTelephone(),        // Telno, only one phone number is needed.
            '',                                     // Cellno
            $shippingAddress->getFirstname(),        // Firstname
            $shippingAddress->getLastname(),         // Lastname
            '',                                     // No care of, C/O.
            join(', ', $shippingAddress->getStreet()),  // Street
            $shippingAddress->getPostcode(),         // Zip Code
            $shippingAddress->getCity(),             // City
            KlarnaCountry::SE,            // Country
            null,                         // HouseNo for German and Dutch customers.
            null                          // House Extension. Dutch customers only.
        );

        $client->setAddress(KlarnaFlags::IS_SHIPPING, $klarnaShipping);

        $orderId = $order->getIncrementId();
        if ($customer = $order->getCustomer()) {
            $customerId = $customer->getId();
        }
        else {
            $customerId = '';
        }

        $client->setEstoreInfo(
            $orderId,      // Order ID
            '',         // Secondary order ID (wtf?)
            $customerId      // Customer ID
        );

        //try {  
            // Transmit all the specified data, from the steps above, to Klarna.  
            $result = $client->reserveAmount(  
                '4103219202', // PNO (Date of birth for DE and NL).  
                null,       // Gender.  
                // Amount. -1 specifies that calculation should calculate the amount  
                // using the goods list  
                -1,  
                KlarnaFlags::NO_FLAG,   // Flags to affect behavior.  
                // -1 notes that this is an invoice purchase, for part payment purchase  
                // you will have a pclass object on which you use getId().  
                KlarnaPClass::INVOICE  
            );  

            //Check the order status  
            if ($result[1] == KlarnaFlags::PENDING) {  
        /* The order is under manual review and will be accepted or denied at a 
           later stage. Use cronjob with checkOrderStatus() or visit Klarna 
           Online to check to see if the status has changed. You should still 
           show it to the customer as it was accepted, to avoid further attempts 
           to fraud. 
         */  
            }  

            // Here we get the reservation number  
            $rno = $result[0];  

            //echo "status: {$result[1]}\nrno: {$result[0]}\n";  

            $transactionId = $result[0];
            // Order is complete, store it in a database.  
        /*
        } catch(Exception $e) {  
            // The purchase was denied or something went wrong, print the message:  
            echo "{$e->getMessage()} (#{$e->getCode()})\n";  
        }  
        */

        $payment
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(false);

        return $this;
    }
}
