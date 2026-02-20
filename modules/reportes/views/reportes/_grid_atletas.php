<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\reportes\models\ReporteAtletasSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $datosAtletas array */

?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        [
            'label' => 'Nombre',
            'value' => 'nombreCompleto',
        ],
        'identificacion',
        [
            'label' => 'Categoría',
            'value' => function($model) {
                return $model->categoria ? $model->categoria->nombre_venezuela : ($model->categoriaCalculada ?: 'N/A');
            },
        ],
        [
            'label' => 'Deuda actual',
            'value' => function($model) use ($datosAtletas) {
                foreach ($datosAtletas as $dato) {
                    if ($dato['atleta']->id == $model->id) {
                        return Yii::$app->formatter->asCurrency($dato['deudaPendiente']);
                    }
                }
                return Yii::$app->formatter->asCurrency(0);
            },
            'contentOptions' => ['class' => 'text-danger'],
        ],
        [
            'label' => '% Asistencia mes',
            'value' => function($model) use ($datosAtletas) {
                foreach ($datosAtletas as $dato) {
                    if ($dato['atleta']->id == $model->id) {
                        return $dato['porcentajeAsistencia'] . '%';
                    }
                }
                return '0%';
            },
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view}',
            'buttons' => [
                'view' => function ($url, $model) {
                    return Html::a('<i class="fas fa-eye"></i> Ver detalle', 
                        ['reportes/estadisticas-atleta', 'id' => $model->id], 
                        ['class' => 'btn btn-sm btn-primary']);
                },
            ],
        ],
    ],
]); ?>