<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = 'Mis Atletas';
$this->params['breadcrumbs'][] = $this->title;

// Calcular deuda total consolidada
$deudaTotalConsolidada = array_sum(array_column($datosAtletas, 'deudaPendiente'));
?>

<div class="reportes-representantes">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-users mr-2"></i> 
                            Atletas a mi cargo
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de resumen -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h2><?= count($datosAtletas) ?></h2>
                        <h6 class="text-muted">Total atletas</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h2 class="text-warning"><?= Yii::$app->formatter->asCurrency($deudaTotalConsolidada) ?></h2>
                        <h6 class="text-muted">Deuda total consolidada</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h2 class="text-success"><?= Yii::$app->formatter->asCurrency($deudaTotalConsolidada / 36.5) ?> USD*</h2>
                        <h6 class="text-muted">Aprox. en dólares</h6>
                        <small>*Tasa referencial</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de atletas -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Listado de atletas</h5>
                    </div>
                    <div class="card-body">
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'columns' => [
                                [
                                    'label' => 'Nombre',
                                    'value' => function($model) {
                                        return $model->nombreCompleto;
                                    },
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>