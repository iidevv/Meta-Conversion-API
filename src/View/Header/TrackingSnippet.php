<?php

namespace Iidev\MetaConversionAPI\View\Header;

use XLite\View\AView;
use XLite\Core\Config;
use XCart\Extender\Mapping\ListChild;
use Iidev\MetaConversionAPI\Core\Events;

/**
 * @ListChild (list="head", zone="customer")
 */
class TrackingSnippet extends AView
{
    public function doViewContentEvent()
    {
        $events = new Events();
        return $events->doViewContent($this->getProduct());
    }

    public function doInitiateCheckoutEvent()
    {
        $events = new Events();
        return $events->doInitiateCheckout(\XLite::getController()->getCart());
    }

    public function getPixelId()
    {
        return Config::getInstance()->Iidev->MetaConversionAPI->pixel_id;
    }

    public function isEnabled()
    {
        return Config::getInstance()->Iidev->MetaConversionAPI->enabled;
    }

    public function isProductPage()
    {
        if ($this->getTarget() == 'product' && $this->getProduct()) {
            return true;
        }

        return false;
    }

    public function isCheckoutPage()
    {
        if ($this->getTarget() == 'checkout' && $this->getCart() && $this->getCart()->getItems()) {
            return true;
        }

        return false;
    }

    public function getJSFiles()
    {
        $list = parent::getJSFiles();
        $list[] = 'modules/Iidev/MetaConversionAPI/tracking_snippet.js';

        return $list;
    }

    protected function getDefaultTemplate()
    {
        return 'modules/Iidev/MetaConversionAPI/header/tracking_snippet.twig';
    }
}
