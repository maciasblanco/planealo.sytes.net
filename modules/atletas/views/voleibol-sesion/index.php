<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\VoleibolSesionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Sesiones de Voleibol';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="voleibol-sesion-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Nueva Sesión', ['create'], ['class' => 'btn btn-success']) ?>
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
                'attribute' => 'nombreEscuela',
                'label' => 'Escuela',
                'value' => 'escuela.nombre',
                'filter' => Html::activeTextInput($searchModel, 'nombreEscuela', ['class' => 'form-control']),
            ],
            [
                'attribute' => 'nombreCategoria',
                'label' => 'Categoría',
                'value' => 'categoria.nombre',
                'filter' => Html::activeTextInput($searchModel, 'nombreCategoria', ['class' => 'form-control']),
            ],
            'nombre',
            'fecha:date',
            [
                'attribute' => 'estado',
                'value' => function($model) {
                    return $model->estado == 'A' ? 'Activa' : 'Finalizada';
                },
                'filter' => Html::activeDropDownList($searchModel, 'estado', ['A' => 'Activa', 'F' => 'Finalizada'], ['class' => 'form-control', 'prompt' => 'Todos']),
            ],
            //'created_at',
            //'updated_at',
            //'created_by',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'visibleButtons' => [
                    'update' => function($model) {
                        return $model->estado == 'A'; // solo editable si está activa
                    },
                    'delete' => function($model) {
                        return $model->estado == 'A';
                    }
                ]
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>
</div>