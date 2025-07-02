<?php

namespace Iidev\MetaConversionAPI\Model;

use XCart\Extender\Mapping\Extender;
use Iidev\MetaConversionAPI\Core\Events;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @Extender\Mixin
 */
abstract class Order extends \XLite\Model\Order
{
    /**
     * fb  data
     *
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $fbData = '';

    /**
     * Get fb data as an array
     *
     * @return array
     */
    public function getFbData(): array
    {
        return json_decode($this->fbData, true) ?: [];
    }

    /**
     * Set fb data from an array
     * @return self
     */
    public function setFbData($fbData): self
    {
        if (!is_array($fbData))
            return $this;

        $this->fbData = json_encode($fbData);
        return $this;
    }

    public function setPaymentStatus($paymentStatus = null)
    {
        parent::setPaymentStatus($paymentStatus);

        if (!$this->getPaymentStatus())
            return;

        $paymentStatus = $this->getPaymentStatus()->getCode();
        $oldStatus = $this->getOldPaymentStatusCode();

        if ($paymentStatus === $oldStatus) {
            return;
        }

        if ($paymentStatus === \XLite\Model\Order\Status\Payment::STATUS_PAID) {
            $events = new Events;

            $events->doPurchase($this);
        }
    }

    public function setShippingStatus($shippingStatus = null)
    {
        parent::setShippingStatus($shippingStatus);

        if ($this->isCompletedOrder()) {
            $events = new Events;

            $events->doPurchase($this);
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

        if ($paymentStatus === \XLite\Model\Order\Status\Payment::STATUS_PAID && $shippingStatus === \XLite\Model\Order\Status\Shipping::STATUS_SHIPPED) {
            return true;
        }

        if ($paymentStatus === \XLite\Model\Order\Status\Payment::STATUS_PAID && $shippingStatus === \XLite\Model\Order\Status\Shipping::STATUS_DELIVERED) {
            return true;
        }

        return false;
    }
}
