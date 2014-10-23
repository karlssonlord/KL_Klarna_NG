<?php

class KL_Klarna_Model_Validation_KlarnaValidateRequest
{
    /**
     * Register new validators in this array please
     *
     * @var array
     */
    protected $validators = array(
        'CurrencyValidator',
        'SkuValidator',
        'ItemCountValidator'
    );

    /**
     * @param $request
     * @throws KL_Klarna_Model_Exception_KlarnaOrderQuoteMismatch
     * @throws KL_Klarna_Model_Exception_InvalidRequest
     */
    public function validate($request)
    {
        /**
         * Someone is calling this URL, but has nothing to say
         */
        if (empty($request)) {
            $errorEmailMessage = 'Empty request thrown at /klarna/checkout/validate/ - $numberOfWtf += 1';
            Mage::helper('klarna/log')->message('', $errorEmailMessage);
            Mage::helper('klarna')->sendErrorEmail($errorEmailMessage);

            throw new KL_Klarna_Model_Exception_InvalidRequest($errorEmailMessage);
        }

        /**
         * Okay so the request is seemingly valid, let's see now what it actually contains...
         * Start by instantiating our own object representation of the request
         */

        $request = KL_Klarna_Model_Validation_KlarnaValidationRequest_KlarnaValidationRequestAdapter::make($request);

        $quote = Mage::getModel('sales/quote')->load($request->orderId);
        $klarnaId = $this->getKlarnaId($request);
        $errorMessages = array();

        foreach ($this->validators as $v) {
            $validator = $this->buildInstance($v);
            $isValid = $validator->validate($quote, $request, $klarnaId);
            if ( ! $isValid) {
                $errorMessages[] = $validator->getError();
            }
        }

        /**
         * Right, now that we have spun through all the validators
         * we shall determine whether they all passed or not. If not messages will be compiled, logged, and e-mailed.
         * If they all passed hallelujah, then nothing happens, whereupon the CheckoutController will fire a 200 OK response
         */
        if (count($errorMessages)) {
            $errorEmailMessage = implode("\n", $errorMessages);

            Mage::helper('klarna')->sendErrorEmail($errorEmailMessage, $quote);

            Mage::helper('klarna/log')->message(
                $quote,
                var_export($request, true),
                null,
                $klarnaId
            );

            $this->logValidationComplete($quote, $klarnaId);

            throw new KL_Klarna_Model_Exception_KlarnaOrderQuoteMismatch($errorEmailMessage);
        }

        $this->logValidationComplete($quote, $klarnaId);
    }


    /**
     * Get the klarna checkout Id from the request object by parsing the push_uri string
     *
     * @param $request
     * @return mixed
     */
    private function getKlarnaId($request)
    {
        $parts = parse_url($request->merchant->push_uri);
        parse_str($parts['query'], $queryString);

        return $queryString['klarna_order'];
    }

    /**
     * @param $quote
     * @param $klarnaId
     */
    protected function logValidationComplete($quote, $klarnaId)
    {
        Mage::helper('klarna/log')->message(
            $quote,
            'Validation routine complete',
            null,
            $klarnaId
        );
    }

    /**
     * Return an instance of the requested validator
     *
     * @param $validator
     * @return string
     */
    private function buildInstance($validator)
    {
        $namespace = 'KL_Klarna_Model_Validation_KlarnaValidators_';

        return new $namespace.$validator;
    }
} 