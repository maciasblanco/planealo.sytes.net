<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\Escuela;

/** @var yii\web\View $this */
/** @var app\modules\escuela_club\models\EscuelaRegistroSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Escuelas/Clubes Registrados';
$this->params['breadcrumbs'][] = $this->title;

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');
?>
<div class="escuela-index">
    
    <!-- Información del Sistema -->
    <?php if (!empty($nombre_escuela)): ?>
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-8">
                <strong><i class="fas fa-school"></i> Sistema GED - Gestión de Escuelas</strong>
                <br><small>Escuela activa en sesión: <?= Html::encode($nombre_escuela) ?> (ID: <?= $id_escuela ?>)</small>
            </div>
            <div class="col-md-4 text-right">
                <?= Html::a('<i class="fas fa-times"></i> Cambiar Escuela', ['clear-escuela'], [
                    'class' => 'btn btn-sm btn-outline-warning'
                ]) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card card-custom">
        <div class="card-header bg-primary text-white">
            <div class="card-title">
                <h3 class="card-label mb-0">
                    <i class="fas fa-school me-2"></i>
                    <?= Html::encode($this->title) ?>
                </h3>
            </div>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-plus-circle me-1"></i> Nueva Escuela', ['create'], [
                    'class' => 'btn btn-sm btn-light'
                ]) ?>
                <?= Html::a('<i class="fas fa-clock me-1"></i> Pendientes', ['/escuela/pendientes'], [
                    'class' => 'btn btn-sm btn-warning ms-1'
                ]) ?>
                <?= Html::a('<i class="fas fa-file-alt me-1"></i> Pre-Registro', ['/escuela/pre-registro'], [
                    'class' => 'btn btn-sm btn-info ms-1'
                ]) ?>
            </div>
        </div>
        <div class="card-body">
            <?php Pjax::begin(['id' => 'escuelas-pjax', 'timeout' => 10000]); ?>

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
                        'value' => function($model) use ($id_escuela) {
                            $link = Html::a(Html::encode($model->nombre), ['view', 'id' => $model->id], [
                                'data-pjax' => 0,
                                'class' => 'font-weight-bold text-primary',
                                'title' => 'Ver detalles'
                            ]);
                            
                            // Mostrar badge si es la escuela activa
                            if ($id_escuela == $model->id) {
                                $link .= ' <span class="badge badge-success ml-2"><i class="fas fa-check"></i> Activa</span>';
                            }
                            
                            return $link;
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
                        'filter' => [
                            '1' => 'Escuela',
                            '0' => 'Club'
                        ],
                        'contentOptions' => ['style' => 'width: 100px; text-align: center;'],
                    ],
                    
                    [
                        'attribute' => 'telefono',
                        'contentOptions' => ['style' => 'width: 120px;'],
                    ],
                    
                    [
                        'attribute' => 'email',
                        'format' => 'email',
                        'contentOptions' => ['style' => 'max-width: 150px;'],
                    ],
                    
                    [
                        'attribute' => 'estado_registro',
                        'value' => function($model) {
                            return $model->getEstadoRegistroLabel();
                        },
                        'filter' => Escuela::getEstadoRegistroOptions(),
                        'contentOptions' => ['style' => 'width: 150px;'],
                    ],
                    
                    [
                        'attribute' => 'estadoNombre',
                        'label' => 'Estado',
                        'value' => 'estado.estado',
                        'filter' => \yii\helpers\ArrayHelper::map(
                            \app\models\Estado::find()->where(['eliminado' => false])->orderBy('estado')->all(), 
                            'estado', 'estado'
                        ),
                        'contentOptions' => ['style' => 'width: 120px;'],
                    ],
                    
                    [
                        'attribute' => 'd_creacion',
                        'label' => 'Fecha Registro',
                        'format' => 'datetime',
                        'contentOptions' => ['style' => 'width: 140px;'],
                    ],
                    
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{select} {view} {update} {delete}',
                        'header' => 'Acciones',
                        'headerOptions' => ['style' => 'width: 180px;'],
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 180px;'],
                        'buttons' => [
                            'select' => function($url, $model) use ($id_escuela) {
                                if ($model->isAprobado() && $id_escuela != $model->id) {
                                    return Html::a('<i class="fas fa-check"></i>', ['select-escuela', 'id' => $model->id], [
                                        'class' => 'btn btn-sm btn-success',
                                        'title' => 'Seleccionar esta escuela',
                                        'data' => [
                                            'confirm' => '¿Seleccionar ' . $model->nombre . ' como escuela activa?',
                                            'method' => 'post',
                                        ],
                                    ]);
                                }
                                return '';
                            },
                            'view' => function($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i>', $url, [
                                    'class' => 'btn btn-sm btn-info',
                                    'title' => 'Ver detalles',
                                    'data-pjax' => 0,
                                ]);
                            },
                            'update' => function($url, $model) {
                                return Html::a('<i class="fas fa-edit"></i>', $url, [
                                    'class' => 'btn btn-sm btn-primary',
                                    'title' => 'Editar',
                                    'data-pjax' => 0,
                                ]);
                            },
                            'delete' => function($url, $model) {
                                return Html::a('<i class="fas fa-trash"></i>', $url, [
                                    'class' => 'btn btn-sm btn-danger',
                                    'title' => 'Eliminar',
                                    'data' => [
                                        'confirm' => '¿Está seguro de eliminar esta escuela?',
                                        'method' => 'post',
                                    ],
                                ]);
                            },
                        ],
                    ],
                ],
                'rowOptions' => function($model) use ($id_escuela) {
                    $classes = [];
                    if ($id_escuela == $model->id) {
                        $classes[] = 'table-success';
                    } elseif ($model->estado_registro === Escuela::ESTADO_PENDIENTE) {
                        $classes[] = 'table-warning';
                    } elseif ($model->estado_registro === Escuela::ESTADO_APROBADO) {
                        $classes[] = '';
                    } elseif ($model->estado_registro === Escuela::ESTADO_RECHAZADO) {
                        $classes[] = 'table-danger';
                    }
                    return ['class' => implode(' ', $classes)];
                },
            ]); ?>

            <?php if ($dataProvider->getTotalCount() == 0): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>No hay escuelas registradas</h4>
                    <p class="mb-0">Comience registrando una nueva escuela o club.</p>
                    <div class="mt-3">
                        <?= Html::a('<i class="fas fa-plus-circle"></i> Registrar Nueva Escuela', 
                            ['create'], 
                            ['class' => 'btn btn-primary me-2']
                        ) ?>
                        <?= Html::a('<i class="fas fa-clipboard-list"></i> Pre-Registro', 
                            ['/escuela/pre-registro'], 
                            ['class' => 'btn btn-outline-primary']
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php Pjax::end(); ?>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <strong>Total registros:</strong> 
                    <span class="badge badge-primary"><?= $dataProvider->getTotalCount() ?></span>
                    <?php if ($id_escuela): ?>
                        | <strong>Escuela activa:</strong> 
                        <span class="badge badge-success"><?= Html::encode($nombre_escuela) ?></span>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-right">
                    <?= Html::a('<i class="fas fa-sync"></i> Actualizar', ['index'], [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'data-pjax' => 1,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerCss(<<<CSS
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.1) !important;
}
.badge {
    font-size: 0.75em;
}
CSS
);
?>