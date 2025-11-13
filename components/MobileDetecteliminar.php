<?php

namespace app\components;

use Yii;
use yii\base\Component;
use Detection\MobileDetect;

class MobileDetectComponent extends Component
{
    private $mobileDetect;

    public function init()
    {
        parent::init();
        $this->mobileDetect = new MobileDetect();
    }

    public function isMobile()
    {
        return $this->mobileDetect->isMobile();
    }

    public function isTablet()
    {
        return $this->mobileDetect->isTablet();
    }

    public function isDesktop()
    {
        return !$this->mobileDetect->isMobile() && !$this->mobileDetect->isTablet();
    }

    public function getDeviceType()
    {
        if ($this->mobileDetect->isMobile()) {
            return 'mobile';
        } elseif ($this->mobileDetect->isTablet()) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
}