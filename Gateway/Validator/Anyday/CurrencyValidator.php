<?php

namespace PensoPay\Payment\Gateway\Validator\Anyday;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class CurrencyValidator extends AbstractValidator
{
    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $isValid = false;
        $fails = [];

        //Anyday only allows DKK for now
        $currencyCode = $validationSubject['currency'];
        if ($currencyCode === 'DKK') {
            $isValid = true;
        } else {
            $fails[] = __('Anyday can only be used when buying in Danish Krone (DKK)');
        }

        return $this->createResult($isValid, $fails);
    }
}