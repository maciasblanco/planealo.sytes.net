<?php

namespace app\modules\escuela_club\controllers;

use yii\web\Controller;

/**
 * Default controller for the `escuela_club` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
