<?php

namespace app\modules\reportes\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

/**
 * Default controller for the `reportes` module
 */
class DefaultController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['atleta', 'representante', 'admin'],
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Renders the index view for the module
     * Redirige a la página correcta según el rol del usuario
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        $user = Yii::$app->user;
        
        if ($user->can('admin')) {
            // Admin ve todos los reportes
            return $this->redirect(['reportes/atletas']);
        } elseif ($user->can('representante')) {
            // Representante ve reportes de sus atletas
            return $this->redirect(['reportes/atletas']);
        } elseif ($user->can('atleta')) {
            // Atleta ve su propia información
            return $this->redirect(['reportes/estadisticas-atleta']);
        } else {
            // Usuario sin rol específico
            return $this->redirect(['site/index']);
        }
    }
    
    /**
     * Dashboard principal con opciones según rol
     */
    public function actionDashboard()
    {
        $user = Yii::$app->user;
        $esAdmin = $user->can('admin');
        $esRepresentante = $user->can('representante');
        $esAtleta = $user->can('atleta');
        
        return $this->render('dashboard', [
            'esAdmin' => $esAdmin,
            'esRepresentante' => $esRepresentante,
            'esAtleta' => $esAtleta,
        ]);
    }
}