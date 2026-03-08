<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Propuestas de Becas Pendientes';
$this->params['breadcrumbs'][] = ['label' => 'Becas', 'url' => ['becas']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="propuestas-pendientes">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => new \yii\data\ArrayDataProvider([
            'allModels' => $pendientes,
            'pagination' => ['pageSize' => 20],
        ]),
        'columns' => [
            'id_beca',
            [
                'attribute' => 'atleta',
                'value' => function($model) {
                    return $model->atleta ? $model->atleta->p_nombre . ' ' . $model->atleta->p_apellido : 'N/A';
                }
            ],
            [
                'attribute' => 'tipoBeca.nombre',
                'label' => 'Tipo'
            ],
            'fecha_propuesta:datetime',
            [
                'attribute' => 'propuesto_por',
                'value' => function($model) {
                    return $model->propuestoPor ? $model->propuestoPor->username : 'N/A';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {aprobar} {rechazar}',
                'buttons' => [
                    'aprobar' => function ($url, $model) {
                        return Html::a('Aprobar', ['aprobar-beca', 'id' => $model->id_beca], [
                            'class' => 'btn btn-success btn-xs',
                            'data' => [
                                'confirm' => '¿Aprobar esta beca?',
                                'method' => 'post',
                            ]
                        ]);
                    },
                    'rechazar' => function ($url, $model) {
                        return Html::a('Rechazar', ['rechazar-beca', 'id' => $model->id_beca], [
                            'class' => 'btn btn-danger btn-xs',
                        ]);
                    },
                    'view' => function ($url, $model) {
                        return Html::a('Ver', ['view-beca', 'id' => $model->id_beca], [
                            'class' => 'btn btn-info btn-xs',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>