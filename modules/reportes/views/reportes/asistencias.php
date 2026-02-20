<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Reporte de Asistencias';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="asistencias-index">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-calendar-check mr-2"></i>
                            <?= Html::encode($this->title) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php Pjax::begin(); ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        [
                            'attribute' => 'fecha',
                            'format' => 'date',
                            'filterInputOptions' => [
                                'class' => 'form-control',
                                'placeholder' => 'YYYY-MM-DD'
                            ],
                        ],
                        [
                            'attribute' => 'id_atleta',
                            'value' => 'atleta.nombreCompleto',
                            'filter' => \yii\helpers\ArrayHelper::map(
                                \app\models\AtletasRegistro::find()->where(['eliminado' => false])->all(),
                                'id',
                                'nombreCompleto'
                            ),
                        ],
                        [
                            'attribute' => 'asistio',
                            'format' => 'boolean',
                            'filter' => [1 => 'Sí', 0 => 'No'],
                            'filterInputOptions' => [
                                'class' => 'form-control',
                                'prompt' => 'Todos'
                            ],
                        ],
                        [
                            'attribute' => 'justificado',
                            'format' => 'boolean',
                            'filter' => [1 => 'Sí', 0 => 'No'],
                        ],
                        [
                            'label' => 'Representante',
                            'value' => function($model) {
                                return $model->atleta->representante->nombreCompleto ?? 'N/A';
                            },
                        ],
                    ],
                ]); ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>