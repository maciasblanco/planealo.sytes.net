<?php

namespace app\components;

use yii\base\Component;
use Detection\MobileDetect;

/**
 * Componente para detección de dispositivos móviles
 */
class MobileDetectComponent extends Component
{
    /**
     * @var MobileDetect
     */
    public $mobileDetect;

    public function init()
    {
        parent::init();
        $this->mobileDetect = new MobileDetect();
    }

    /**
     * Verifica si es un dispositivo móvil
     * @return bool
     */
    public function isMobile()
    {
        return $this->mobileDetect->isMobile();
    }

    /**
     * Verifica si es una tablet
     * @return bool
     */
    public function isTablet()
    {
        return $this->mobileDetect->isTablet();
    }

    /**
     * Verifica si es un dispositivo desktop
     * @return bool
     */
    public function isDesktop()
    {
        return !$this->mobileDetect->isMobile() && !$this->mobileDetect->isTablet();
    }

    /**
     * Obtiene el tipo de dispositivo
     * @return string
     */
    public function getDeviceType()
    {
        if ($this->isMobile()) {
            return 'mobile';
        } elseif ($this->isTablet()) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
}