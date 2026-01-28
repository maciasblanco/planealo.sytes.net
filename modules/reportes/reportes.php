<?php

namespace app\modules\reportes;

/**
 * reportes module definition class
 */
class Reportes extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\reportes\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // SOLUCIÓN: Elimina completamente esta configuración
        // O corrígela si realmente necesitas formatters PDF
    }
}