<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\EvaluacionResultadoSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Resultados de Estadísticas';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="evaluacion-resultado-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Nuevo Resultado', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'nombreAtleta',
                'label' => 'Atleta',
                'value' => 'atleta.nombreCompleto',
                'filter' => Html::activeTextInput($searchModel, 'nombreAtleta', ['class' => 'form-control']),
            ],
            [
                'attribute' => 'nombreEstadistica',
                'label' => 'Estadística',
                'value' => 'estadistica.nombre',
                'filter' => Html::activeTextInput($searchModel, 'nombreEstadistica', ['class' => 'form-control']),
            ],
            [
                'attribute' => 'fechaSesion',
                'label' => 'Fecha Sesión',
                'value' => 'sesion.fecha',
                'filter' => Html::activeTextInput($searchModel, 'fechaSesion', ['class' => 'form-control', 'placeholder' => 'YYYY-MM-DD']),
            ],
            'valor_numerico',
            'set_numero',
            //'d_creacion',
            //'u_creacion',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>
</div>