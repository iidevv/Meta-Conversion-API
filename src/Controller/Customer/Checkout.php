<?php

namespace Iidev\MetaConversionAPI\Controller\Customer;

use Iidev\MetaConversionAPI\Core\Events;
use XCart\Extender\Mapping\Extender;
use XLite\Core\Session;
use \XLite\Core\Auth;

/**
 * Checkout
 * @Extender\Mixin
 */
class Checkout extends \XLite\Controller\Customer\Checkout
{
    protected function doActionCheckout()
    {
        $events = new Events();
        $events->updateUserData(Auth::getInstance()->getProfile());
        
        parent::doActionCheckout();
    }
}
