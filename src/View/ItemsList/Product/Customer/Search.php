<?php

namespace Iidev\MetaConversionAPI\View\ItemsList\Product\Customer;

use XCart\Extender\Mapping\Extender;
use Iidev\MetaConversionAPI\Core\Events;

/**
 * @Extender\Mixin
 */
class Search extends \XLite\View\ItemsList\Product\Customer\Search
{
    protected function getData(\XLite\Core\CommonCell $cnd, $countOnly = false)
    {
        $data = parent::getData($cnd, $countOnly);

        $events = new Events();
        
        $events->doSearch($data);

        return $data;
    }
}
