<?php

namespace Iidev\MetaConversionAPI\Controller\Customer;

use Iidev\MetaConversionAPI\Core\Events;
use XCart\Extender\Mapping\Extender;

/**
 * Checkout
 * @Extender\Mixin
 */
class Checkout extends \XLite\Controller\Customer\Checkout
{
    public function processSucceed($fullProcess = true)
    {
        parent::processSucceed($fullProcess);

        $events = new Events();
        $events->updatefbData($this->getCart()->getOrderId());
        
    }
}
