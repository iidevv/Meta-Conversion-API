<?php

namespace Iidev\MetaConversionAPI\Core;

use Iidev\MetaConversionAPI\Core\API;
use XLite\Core\Config;

class Events
{
    public function doPurchase(\XLite\Model\Order $order)
    {
        if (!$this->isEnabled())
            return;

        if (!$order->getProfile() || !$this->isIncludedRefererList($order->getProfile())) {
            return;
        }

        $data = $this->getOrderEventData($order, 'Purchase');

        if (!$data)
            return;

        $api = new API();

        $api->event($data);
    }

    private function getOrderEventData(\XLite\Model\Order $order, $eventName = '')
    {
        $items = $order->getItems();
        $currencyCode = \XLite::getInstance()->getCurrency()->getCode();

        $data = [
            "data" => [
                [
                    "event_name" => $eventName,
                    "event_time" => time(),
                    "event_id" => $order->getOrderId(),
                    "event_source_url" => \XLite::getInstance()->getShopURL(),
                    "action_source" => "website",
                    "user_data" => [
                        "em" => [
                            $order->getProfile()->getLogin() ? hash('sha256', $order->getProfile()->getLogin()) : null,
                        ],
                        "ph" => [
                            $order->getProfile()?->getBillingAddress()?->getPhone() ? hash('sha256', $order->getProfile()->getBillingAddress()->getPhone()) : null,
                        ]
                    ],
                    "custom_data" => [
                        "value" => $order->getTotal(),
                        "currency" => $currencyCode,
                        "content_ids" => $this->getContentIds($items),
                        "content_type" => "product"
                    ],
                    "opt_out" => false
                ]
            ],
        ];

        return $data;
    }

    private function isIncludedRefererList($profile)
    {
        $refererList = Config::getInstance()->Iidev->MetaConversionAPI->included_referer_list;

        if (empty($refererList)) {
            return true;
        }

        $referers = explode(',', $refererList);
        $currentReferer = $profile->getReferer() . ':' . json_encode($profile->getRefererParams());

        foreach ($referers as $referer) {
            if (stripos($currentReferer, trim($referer)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function getContentIds($items)
    {
        $contentIds = [];

        foreach ($items as $item) {
            $contentIds[] = $item->getSku();
        }

        return $contentIds;
    }

    private function isEnabled()
    {
        return (boolean) Config::getInstance()->Iidev->MetaConversionAPI->enabled;
    }
}