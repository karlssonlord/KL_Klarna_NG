<?php

interface KL_Klarna_Model_Validation_KlarnaValidators_RequestValidator
{
    public function validate(Mage_Sales_Model_Quote $quote, $klarnasValidationRequestObject, $klarnaId);

    public function getError();
} 