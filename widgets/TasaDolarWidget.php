<?php

namespace app\widgets;

use yii\base\Widget;
use app\models\TasaDolar;

class TasaDolarWidget extends Widget
{
    public $showCalculator = true;
    public $compact = false;

    public function run()
    {
        $tasaActual = TasaDolar::getTasaActual();

        return $this->render('tasaDolarWidget', [
            'tasaActual' => $tasaActual,
            'showCalculator' => $this->showCalculator,
            'compact' => $this->compact,
        ]);
    }
}