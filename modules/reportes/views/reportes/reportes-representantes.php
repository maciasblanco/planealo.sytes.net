<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\reportes\models\ReporteAtletasSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $representante app\models\RegistroRepresentantes|null */
/* @var $datosAtletas array */
/* @var $esRepresentante bool */
/* @var $esAtleta bool */
/* @var $esPersonalAutorizado bool */
/* @var $tasaCambio float */

$this->title = 'Reporte de Atletas';
$this->params['breadcrumbs'][] = $this->title;

// Determinar si el usuario actual es contador o superusuario (ID 1)
$esContador = Yii::$app->user->can('contador');
$esSuperusuario = Yii::$app->user->id == 1;
$puedeGestionarBecas = $esContador || $esSuperusuario;
?>

<div class="reporte-atletas-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($esPersonalAutorizado && empty($datosAtletas)): ?>
        <div class="alert alert-warning">
            No hay atletas registrados en la escuela activa.
        </div>
    <?php endif; ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'p_nombre',
                'label' => 'Nombre',
                'value' => function($model) {
                    return $model->p_nombre . ' ' . $model->p_apellido;
                }
            ],
            'identificacion',
            [
                'attribute' => 'id_escuela',
                'label' => 'Escuela',
                'value' => function($model) {
                    return $model->escuela ? $model->escuela->nombre : 'N/A';
                }
            ],
            // Categoría
            [
                'attribute' => 'id_categoria',
                'label' => 'Categoría',
                'value' => function($model) {
                    return $model->categoria ? $model->categoria->nombre : 'Sin categoría';
                },
                'filter' => \yii\helpers\ArrayHelper::map(
                    \app\models\CategoriaAtletas::find()->orderBy('nombre')->all(),
                    'id',
                    'nombre'
                ),
            ],
            // Beca activa (muestra el nombre del tipo de beca si existe)
            [
                'label' => 'Beca Activa',
                'format' => 'raw',
                'value' => function($model) use ($datosAtletas) {
                    $becaNombre = null;
                    foreach ($datosAtletas as $dato) {
                        if ($dato['atleta']->id == $model->id) {
                            $becaNombre = $dato['becaNombre'] ?? null;
                            break;
                        }
                    }
                    if ($becaNombre) {
                        return Html::tag('span', Html::encode($becaNombre), [
                            'class' => 'badge',
                            'style' => 'background-color: #e2e3e5; color: #000; font-weight: normal;'
                        ]);
                    } else {
                        return Html::tag('span', 'No', [
                            'class' => 'badge',
                            'style' => 'background-color: #f8f9fa; color: #6c757d; border: 1px solid #ced4da; font-weight: normal;'
                        ]);
                    }
                }
            ],
            // Deuda pendiente
            [
                'label' => 'Deuda Pendiente',
                'value' => function($model) use ($datosAtletas) {
                    foreach ($datosAtletas as $dato) {
                        if ($dato['atleta']->id == $model->id) {
                            return '$' . number_format($dato['deudaPendiente'], 2);
                        }
                    }
                    return '$0.00';
                }
            ],
            // % Asistencia del mes
            [
                'label' => '% Asistencia (mes)',
                'value' => function($model) use ($datosAtletas) {
                    foreach ($datosAtletas as $dato) {
                        if ($dato['atleta']->id == $model->id) {
                            return $dato['porcentajeAsistencia'] . '%';
                        }
                    }
                    return '0%';
                }
            ],
            // Botones de acción
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {recibo-pago} {recibo-cobro} {gestion-beca}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fas fa-chart-bar"></i> Estadísticas', 
                            ['estadisticas-atleta', 'id' => $model->id], 
                            [
                                'title' => 'Ver estadísticas detalladas del atleta',
                                'class' => 'btn btn-info btn-xs',
                                'style' => 'margin-right: 2px;'
                            ]
                        );
                    },
                    'recibo-pago' => function ($url, $model) {
                        return Html::a('<i class="fas fa-receipt"></i> Pago', 
                            ['recibo-pago', 'id' => $model->id], 
                            [
                                'title' => 'Generar recibo de pago',
                                'class' => 'btn btn-success btn-xs',
                                'style' => 'margin-right: 2px;'
                            ]
                        );
                    },
                    'recibo-cobro' => function ($url, $model) {
                        return Html::a('<i class="fas fa-file-invoice"></i> Cobro', 
                            ['recibo-cobro', 'id' => $model->id], 
                            [
                                'title' => 'Generar notificación de cobro',
                                'class' => 'btn btn-warning btn-xs',
                                'style' => 'margin-right: 2px;'
                            ]
                        );
                    },
                    'gestion-beca' => function ($url, $model) use ($puedeGestionarBecas) {
                        if (!$puedeGestionarBecas) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-medal"></i> Beca', 
                            ['/aportes/aportes/asignar-beca', 'id_atleta' => $model->id], 
                            [
                                'title' => 'Asignar o gestionar beca',
                                'class' => 'btn btn-primary btn-xs',
                                'style' => 'margin-right: 2px;'
                            ]
                        );
                    },
                ],
            ],
        ],
    ]); ?>

    <?php if ($representante): ?>
        <div class="alert alert-info">
            <strong>Representante:</strong> <?= Html::encode($representante->p_nombre . ' ' . $representante->p_apellido) ?>
        </div>
    <?php endif; ?>

    <?php if ($esPersonalAutorizado): ?>
        <div class="alert alert-info">
            <strong>Escuela activa:</strong> <?= Html::encode(Yii::$app->session->get('nombre_escuela')) ?> (ID: <?= Yii::$app->session->get('id_escuela') ?>)
        </div>
    <?php endif; ?>
</div>