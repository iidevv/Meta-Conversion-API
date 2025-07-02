<?php

namespace Iidev\MetaConversionAPI\Core;

use Iidev\MetaConversionAPI\Core\API;
use XLite\Core\Config;
use \XLite\Core\Event;

class Events
{
    public function doSearch($data)
    {
        $eventName = 'Search';
        $eventId = $this->getEventId($eventName);
        $userData = $this->getUserData();

        $contentIds = [];

        foreach ($data as $product) {
            if ($product instanceof \XLite\Model\Product) {
                $contentIds[] = $product->getVariants()?->first()
                    ? $product->getVariants()->first()?->getSku()
                    : $product->getSku();
            }
        }

        if (empty($contentIds)) {
            return;
        }

        $parameters = [
            'content_ids' => $contentIds,
            'search_string' => \XLite\Core\Request::getInstance()->substring ?: '',
            'content_type' => 'product',
            'currency' => \XLite::getInstance()->getCurrency()->getCode(),
        ];

        $this->doEvent($eventName, $eventId, $parameters, $userData);
    }

    public function doViewContent($product)
    {
        $eventName = 'ViewContent';
        $eventId = $this->getEventId($eventName);
        $userData = $this->getUserData();

        $parameters = [
            'content_ids' => $this->getContentIds($product->getVariants()) ?: [$product->getSku()],
            'content_name' => $product->getName(),
            'content_type' => 'product'
        ];

        $this->doEvent($eventName, $eventId, $parameters, $userData);

        return $this->getPixelEventData(
            $eventName,
            $eventId,
            $parameters
        );
    }

    public function doAddToCart(\XLite\Model\OrderItem $item)
    {
        $eventName = 'AddToCart';
        $eventId = $this->getEventId($eventName);
        $userData = $this->getUserData();

        $parameters = [
            'content_ids' => [$item->getSku()],
            'contents' => [
                [
                    'id' => $item->getSku(),
                    'quantity' => $item->getAmount(),
                ]
            ],
            'currency' => \XLite::getInstance()->getCurrency()->getCode(),
            'value' => $item->getPrice() * $item->getAmount(),
        ];

        $this->doEvent($eventName, $eventId, $parameters, $userData);

        Event::fbAddedToCart($this->getPixelEventData(
            $eventName,
            $eventId,
            $parameters
        ));
    }

    public function doInitiateCheckout(\XLite\Model\Cart $cart)
    {
        $eventName = 'InitiateCheckout';
        $eventId = $this->getEventId($eventName);
        $userData = $this->getUserData();

        $parameters = [
            'content_ids' => $this->getContentIds($cart->getItems()),
            'currency' => \XLite::getInstance()->getCurrency()->getCode(),
            'value' => $cart->getTotal(),
        ];

        $this->doEvent($eventName, $eventId, $parameters, $userData);

        return $this->getPixelEventData(
            $eventName,
            $eventId,
            $parameters
        );
    }

    public function doPurchase(\XLite\Model\Order $order)
    {
        $eventName = 'Purchase';
        $eventId = $this->getEventId($eventName);
        $fbData = $order->getFbData();
        
        if (!$fbData) {
            return;
        }

        $parameters = [
            "content_ids" => $this->getContentIds($order->getItems()),
            "currency" => \XLite::getInstance()->getCurrency()->getCode(),
            "value" => $order->getTotal(),
        ];

        $this->doEvent($eventName, $eventId, $parameters, $fbData);
    }

    public function updatefbData($orderid)
    {
        $order = \XLite\Core\Database::getRepo(\XLite\Model\Order::class)->find($orderid);

        if (!$this->isEnabled() || !$order) {
            return;
        }

        $userData = $this->getUserData($order->getProfile());

        $order->setFbData($userData);

        \XLite\Core\Database::getEM()->persist($order);
    }

    private function getUserData($profile = null)
    {
        if(!$profile) {
            $profile = \XLite\Core\Auth::getInstance()->getProfile();
        }

        $data = [
            'client_ip_address' => $_SERVER['REMOTE_ADDR'],
            'client_user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'fbc' => $_COOKIE['_fbc'] ?? null,
            'fbp' => $_COOKIE['_fbp'] ?? null,
        ];

        if ($profile) {
            $data['em'] = [
                hash('sha256', $profile->getLogin() ?: '')
            ];
        }

        if ($profile && $profile->getAddresses()?->first()) {
            $data['ph'] = [
                hash('sha256', $profile?->getAddresses()->first()->getPhone() ?: '')
            ];
        }

        return $data;
    }

    private function getPixelEventData($eventName = '', $eventId, $parameters = [])
    {
        return [
            'type' => 'track',
            'eventName' => $eventName,
            'parameters' => $parameters,
            'eventIdObject' => ['eventID' => $eventId]
        ];
    }

    private function doEvent($eventName = '', $eventId, $customData = [], $fbData = [])
    {
        if (!$this->isEnabled())
            return;

        $data = $this->getEventData($eventName, $eventId, $customData, $fbData);

        if (!$data)
            return;

        $api = new API();

        $api->event($data);
    }

    private function getEventData($eventName = '', $eventId, $customData = [], $fbData = [])
    {
        $data = [
            "event_name" => $eventName,
            "event_time" => time(),
            "event_id" => $eventId,
            "event_source_url" => \XLite::getInstance()->getShopURL($_SERVER['REQUEST_URI']),
            "action_source" => "website",
            "user_data" => $fbData,
            "custom_data" => $customData,
            "opt_out" => false
        ];

        return $data;
    }

    private function getEventId($eventName = '')
    {
        return md5(
            $eventName . time() . rand(1000, 9999)
        );
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