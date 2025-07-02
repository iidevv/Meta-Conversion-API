<?php

namespace Iidev\MetaConversionAPI\Model;

use Doctrine\ORM\Mapping as ORM;
use XCart\Extender\Mapping\Extender;

/**
 * @Extender\Mixin
 */
class Profile extends \XLite\Model\Profile
{
    /**
     * fb user data
     *
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $fbUserData = '';

    /**
     * Get fb user data as an array
     *
     * @return array
     */
    public function getFbUserData(): array
    {
        return json_decode($this->fbUserData, true) ?: [];
    }

    /**
     * Set fb user data from an array
     * @return self
     */
    public function setFbUserData($fbUserData): self
    {
        if (!is_array($fbUserData))
            return $this;

        $this->fbUserData = json_encode($fbUserData);
        return $this;
    }
}
