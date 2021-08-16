<?php

namespace PensoPay\Payment\Plugin\Framework\App\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use PensoPay\Payment\Controller\Payment\Callback;

class CsrfValidator
{
    public function aroundValidate(
        $subject,
        callable $proceed,
        RequestInterface $request,
        ActionInterface $action
    ) {
        if ($action instanceof Callback) {
            return true;
        }
        return $proceed($request, $action);
    }
}
