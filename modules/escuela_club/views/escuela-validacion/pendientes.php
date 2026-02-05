<?php
// [file name]: modules/escuela_club/views/escuela-validacion/pendientes.php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\Escuela;

/** @var yii\web\View $this */
/** @var app\modules\escuela_club\models\EscuelaRegistroSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Escuelas Pendientes de Aprobación - Fase 3: Validación';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="escuela-validacion-pendientes">

    <div class="card card-custom">
        <div class="card-header bg-warning text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-clipboard-check"></i> <?= Html::encode($this->title) ?>
            </h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-list"></i> Todas las Escuelas', ['/escuela-club/escuela-registro/index'], [
                    'class' => 'btn btn-sm btn-light'
                ]) ?>
                <span class="badge badge-light badge-pill ml-2">
                    <?= $dataProvider->getTotalCount() ?> pendientes
                </span>
            </div>
        </div>
        <div class="card-body">
            <?php Pjax::begin(['id' => 'pendientes-pjax']); ?>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => "{items}\n{summary}\n{pager}",
                'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn', 'header' => '#'],
                    
                    [
                        'attribute' => 'nombre',
                        'format' => 'raw',
                        'value' => function($model) {
                            return Html::a(Html::encode($model->nombre), ['/escuela-club/escuela-registro/view', 'id' => $model->id], [
                                'class' => 'font-weight-bold text-primary',
                                'title' => 'Ver detalles completos',
                                'target' => '_blank',
                            ]);
                        },
                    ],
                    
                    [
                        'attribute' => 'tipo_entidad',
                        'label' => 'Tipo',
                        'value' => function($model) {
                            return $model->tipo_entidad ? 
                                '<span class="badge badge-success">Escuela</span>' : 
                                '<span class="badge badge-info">Club</span>';
                        },
                        'format' => 'raw',
                        'filter' => ['1' => 'Escuela', '0' => 'Club'],
                        'contentOptions' => ['style' => 'width: 100px; text-align: center;'],
                    ],
                    
                    [
                        'attribute' => 'telefono',
                        'contentOptions' => ['style' => 'width: 120px;'],
                    ],
                    
                    [
                        'attribute' => 'email',
                        'format' => 'email',
                    ],
                    
                    [
                        'attribute' => 'd_creacion',
                        'label' => 'Fecha Solicitud',
                        'format' => 'datetime',
                        'contentOptions' => ['style' => 'width: 140px;'],
                    ],
                    
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '<div class="btn-group">{view} {aprobar} {rechazar}</div>',
                        'header' => 'Acciones',
                        'headerOptions' => ['style' => 'width: 200px;'],
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 200px;'],
                        'buttons' => [
                            'view' => function($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i>', 
                                    ['/escuela-club/escuela-registro/view', 'id' => $model->id], 
                                    [
                                        'class' => 'btn btn-sm btn-info',
                                        'title' => 'Ver detalles completos',
                                        'target' => '_blank',
                                    ]
                                );
                            },
                            'aprobar' => function($url, $model) {
                                return Html::a('<i class="fas fa-check"></i> Aprobar', 
                                    ['aprobar', 'id' => $model->id], 
                                    [
                                        'class' => 'btn btn-sm btn-success',
                                        'title' => 'Aprobar escuela',
                                        'data' => [
                                            'confirm' => '¿Está seguro de APROBAR esta escuela?\n\nEscuela: ' . $model->nombre,
                                            'method' => 'post',
                                        ],
                                    ]
                                );
                            },
                            'rechazar' => function($url, $model) {
                                return Html::a('<i class="fas fa-times"></i> Rechazar', 
                                    ['rechazar', 'id' => $model->id], 
                                    [
                                        'class' => 'btn btn-sm btn-danger',
                                        'title' => 'Rechazar escuela',
                                    ]
                                );
                            },
                        ],
                    ],
                ],
                'rowOptions' => function($model) {
                    return ['class' => 'table-warning'];
                },
            ]); ?>

            <?php if ($dataProvider->getTotalCount() == 0): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h4>¡No hay escuelas pendientes!</h4>
                    <p class="mb-0">Todas las solicitudes han sido procesadas.</p>
                </div>
            <?php endif; ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
</div>