<?php

namespace Iidev\MetaConversionAPI\Controller\Customer;

use XCart\Extender\Mapping\Extender as Extender;
use Iidev\MetaConversionAPI\Core\Events;

/**
 * @Extender\Mixin
 */
class Cart extends \XLite\Controller\Customer\Cart
{
    protected function processAddItemSuccess($item)
    {
        $events = new Events();
        $events->doAddToCart($item);

        parent::processAddItemSuccess($item);
    }
}
