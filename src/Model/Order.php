<?php

namespace Iidev\MetaConversionAPI\Model;

use XCart\Extender\Mapping\Extender;
use Iidev\MetaConversionAPI\Core\Events;

/**
 *
 * @Extender\Mixin
 */
abstract class Order extends \XLite\Model\Order
{
    public function setPaymentStatus($paymentStatus = null)
    {
        parent::setPaymentStatus($paymentStatus);

        if (!$this->getPaymentStatus())
            return;

        $paymentStatus = $this->getPaymentStatus()->getCode();
        $oldStatus = $this->getOldPaymentStatusCode();

        $events = new Events;

        $order = \XLite\Core\Database::getRepo('XLite\Model\Order')->find($this->getOrderId());

        if (!$order instanceof \XLite\Model\Order) {
            return;
        }

        if ($paymentStatus === $oldStatus) {
            return;
        }

        if ($paymentStatus === \XLite\Model\Order\Status\Payment::STATUS_PAID) {
            $events->doPurchase($order);
        }
    }

    public function setShippingStatus($shippingStatus = null)
    {
        parent::setShippingStatus($shippingStatus);

        if ($this->isCompletedOrder()) {
            $events = new Events;

            $order = \XLite\Core\Database::getRepo('XLite\Model\Order')->find($this->getOrderId());

            if (!$order instanceof \XLite\Model\Order) {
                return;
            }

            $events->doPurchase($order);
        }
    }

    protected function isCompletedOrder(): bool
    {
        $paymentStatus = $this->getPaymentStatus()?->getCode();
        $shippingStatus = $this->getShippingStatus()?->getCode();
        $oldShippingStatus = $this->oldShippingStatus?->getCode();

        if (!$oldShippingStatus || $oldShippingStatus == $shippingStatus) {
            return false;
        }

        if (!$shippingStatus || !$paymentStatus) {
            return false;
        }

        if($paymentStatus === \XLite\Model\Order\Status\Payment::STATUS_PAID && $shippingStatus === \XLite\Model\Order\Status\Shipping::STATUS_SHIPPED) {
            return true;
        }

        if($paymentStatus === \XLite\Model\Order\Status\Payment::STATUS_PAID && $shippingStatus === \XLite\Model\Order\Status\Shipping::STATUS_DELIVERED) {
            return true;
        }

        return false;
    }

}
