<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\Escuela;

/** @var yii\web\View $this */
/** @var app\modules\escuela_club\models\EscuelaRegistroSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Escuelas Pendientes de Aprobación';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="escuela-pre-registro-pendientes">

    <div class="card">
        <div class="card-header bg-warning text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-clock"></i> <?= Html::encode($this->title) ?>
            </h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-list"></i> Todas las Escuelas', ['/escuela-club/escuela-registro/index'], [
                    'class' => 'btn btn-sm btn-light'
                ]) ?>
                <?= Html::a('<i class="fas fa-plus"></i> Nueva Escuela', ['/escuela-club/escuela-pre-registro/pre-registro'], [
                    'class' => 'btn btn-sm btn-light ms-1'
                ]) ?>
            </div>
        </div>
        <div class="card-body">
            <?php Pjax::begin(['timeout' => 10000, 'id' => 'pendientes-pjax']); ?>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => "{items}\n{summary}\n{pager}",
                'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '#',
                        'contentOptions' => ['style' => 'width: 50px; text-align: center;'],
                    ],
                    
                    [
                        'attribute' => 'nombre',
                        'format' => 'raw',
                        'value' => function($model) {
                            return Html::a(Html::encode($model->nombre), ['/escuela-club/escuela-registro/view', 'id' => $model->id], [
                                'data-pjax' => 0,
                                'class' => 'font-weight-bold text-primary',
                                'title' => 'Ver detalles completos'
                            ]);
                        },
                        'contentOptions' => ['style' => 'max-width: 200px;'],
                    ],
                    
                    [
                        'attribute' => 'telefono',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->telefono ? 
                                Html::a(Html::encode($model->telefono), "tel:{$model->telefono}", ['class' => 'text-nowrap']) : 
                                '<span class="text-muted">No especificado</span>';
                        },
                        'contentOptions' => ['style' => 'width: 120px;'],
                    ],
                    
                    [
                        'attribute' => 'email',
                        'format' => 'email',
                        'contentOptions' => ['style' => 'max-width: 150px;'],
                        'value' => function($model) {
                            return $model->email ? $model->email : '<span class="text-muted">No especificado</span>';
                        },
                    ],
                    
                    [
                        'attribute' => 'estadoNombre',
                        'label' => 'Estado',
                        'value' => function($model) {
                            return $model->estado ? $model->estado->estado : '<span class="text-muted">N/A</span>';
                        },
                        'filter' => \yii\helpers\ArrayHelper::map(
                            \app\models\Estado::find()->where(['eliminado' => false])->orderBy('estado')->all(), 
                            'estado', 'estado'
                        ),
                        'contentOptions' => ['style' => 'width: 120px;'],
                    ],
                    
                    [
                        'attribute' => 'municipioNombre',
                        'label' => 'Municipio',
                        'value' => function($model) {
                            return $model->municipio ? $model->municipio->municipio : '<span class="text-muted">N/A</span>';
                        },
                        'filter' => \yii\helpers\ArrayHelper::map(
                            \app\models\Municipio::find()->where(['eliminado' => false])->orderBy('municipio')->all(), 
                            'municipio', 'municipio'
                        ),
                        'contentOptions' => ['style' => 'width: 150px;'],
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
                        'filter' => [
                            '1' => 'Escuela',
                            '0' => 'Club'
                        ],
                        'contentOptions' => ['style' => 'width: 80px; text-align: center;'],
                    ],
                    
                    [
                        'attribute' => 'd_creacion',
                        'label' => 'Fecha Solicitud',
                        'format' => 'datetime',
                        'filter' => false,
                        'contentOptions' => ['style' => 'width: 140px;'],
                    ],
                    
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '<div class="btn-group">{view} {aprobar} {rechazar}</div>',
                        'header' => 'Acciones',
                        'headerOptions' => ['style' => 'width: 150px;'],
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 150px;'],
                        'buttons' => [
                            'view' => function($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i> Ver', 
                                    ['/escuela-club/escuela-registro/view', 'id' => $model->id], 
                                    [
                                        'class' => 'btn btn-sm btn-info',
                                        'title' => 'Ver detalles completos',
                                        'data-pjax' => 0,
                                    ]
                                );
                            },
                            'aprobar' => function($url, $model) {
                                return Html::a('<i class="fas fa-check"></i> Aprobar', 
                                    ['aprobar', 'id' => $model->id], 
                                    [
                                        'class' => 'btn btn-sm btn-success mt-1',
                                        'title' => 'Aprobar escuela',
                                        'data' => [
                                            'confirm' => '¿Está seguro de APROBAR esta escuela?\n\nEscuela: ' . $model->nombre,
                                            'method' => 'post',
                                            'params' => ['comentarios' => 'Escuela aprobada por el administrador'],
                                        ],
                                    ]
                                );
                            },
                            'rechazar' => function($url, $model) {
                                return Html::a('<i class="fas fa-times"></i> Rechazar', 
                                    ['rechazar', 'id' => $model->id], 
                                    [
                                        'class' => 'btn btn-sm btn-danger mt-1',
                                        'title' => 'Rechazar escuela',
                                        'data' => [
                                            'confirm' => '¿Está seguro de RECHAZAR esta escuela?\n\nEscuela: ' . $model->nombre,
                                            'method' => 'post',
                                            'params' => ['comentarios' => 'Escuela rechazada por el administrador'],
                                        ],
                                    ]
                                );
                            },
                        ],
                    ],
                ],
                'rowOptions' => function($model) {
                    return [
                        'class' => $model->estado_registro == Escuela::ESTADO_PENDIENTE ? 'table-warning' : ''
                    ];
                },
            ]); ?>

            <?php if ($dataProvider->getTotalCount() == 0): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>No hay escuelas pendientes de aprobación</h4>
                    <p class="mb-0">Todas las solicitudes de registro han sido procesadas.</p>
                    <div class="mt-3">
                        <?= Html::a('<i class="fas fa-plus-circle"></i> Registrar Nueva Escuela', 
                            ['/escuela-club/escuela-pre-registro/pre-registro'], 
                            ['class' => 'btn btn-primary']
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php Pjax::end(); ?>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <strong>Total pendientes:</strong> 
                    <span class="badge badge-warning"><?= $dataProvider->getTotalCount() ?></span>
                </div>
                <div class="col-md-6 text-right">
                    <?= Html::a('<i class="fas fa-sync"></i> Actualizar', ['pendientes'], [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'data-pjax' => 1,
                    ]) ?>
                    <?= Html::a('<i class="fas fa-download"></i> Exportar', ['export-pendientes'], [
                        'class' => 'btn btn-sm btn-outline-primary ms-1',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
// JavaScript para mejorar la experiencia de usuario
$this->registerJs(<<<JS
$(document).ready(function() {
    // Auto-enfocar el primer filtro
    $('input[type="search"]').first().focus();
    
    // Confirmación mejorada para acciones
    $('body').on('click', '.btn-success, .btn-danger', function(e) {
        var action = $(this).hasClass('btn-success') ? 'APROBAR' : 'RECHAZAR';
        var escuela = $(this).closest('tr').find('td:first').next().text().trim();
        
        if (!confirm('¿Está seguro de ' + action + ' la escuela:\\n\\n' + escuela + '?')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
    });
    
    // Mostrar loading en acciones Pjax
    $(document).on('pjax:send', function() {
        $('#pendientes-pjax').append('<div class="overlay"><i class="fas fa-2x fa-sync fa-spin"></i></div>');
    });
    
    $(document).on('pjax:complete', function() {
        $('#pendientes-pjax .overlay').remove();
    });
});
JS
);

// CSS para mejoras visuales
$this->registerCss(<<<CSS
.table-hover tbody tr:hover {
    background-color: rgba(255, 193, 7, 0.1) !important;
}
.overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.btn-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.btn-group .btn {
    margin: 0;
}
.badge {
    font-size: 0.75em;
}
CSS
);
?>