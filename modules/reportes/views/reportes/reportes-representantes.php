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
            // Columna de beca activa
            [
                'label' => 'Beca Activa',
                'format' => 'raw',
                'value' => function($model) use ($datosAtletas) {
                    // Buscar en $datosAtletas el índice correspondiente (esto es un hack rápido)
                    // En un caso real, sería mejor agregar un método al modelo o pasar un array indexado
                    foreach ($datosAtletas as $dato) {
                        if ($dato['atleta']->id == $model->id) {
                            $tieneBeca = $dato['becaActiva'];
                            break;
                        }
                    }
                    return $tieneBeca 
                        ? '<span class="badge badge-success">Sí</span>' 
                        : '<span class="badge badge-secondary">No</span>';
                }
            ],
            // Columnas de resumen (opcional, ya existentes)
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
                        return Html::a('<i class="fas fa-chart-bar"></i>', 
                            ['estadisticas-atleta', 'id' => $model->id], 
                            ['title' => 'Estadísticas', 'class' => 'btn btn-info btn-xs']
                        );
                    },
                    'recibo-pago' => function ($url, $model) {
                        return Html::a('<i class="fas fa-receipt"></i>', 
                            ['recibo-pago', 'id' => $model->id], 
                            ['title' => 'Recibo de Pago (Fase 2)', 'class' => 'btn btn-success btn-xs']
                        );
                    },
                    'recibo-cobro' => function ($url, $model) {
                        return Html::a('<i class="fas fa-file-invoice"></i>', 
                            ['recibo-cobro', 'id' => $model->id], 
                            ['title' => 'Recibo de Cobro (Fase 3)', 'class' => 'btn btn-warning btn-xs']
                        );
                    },
                    'gestion-beca' => function ($url, $model) use ($esPersonalAutorizado) {
                        if (!$esPersonalAutorizado) {
                            return '';
                        }
                        // Aquí deberías colocar la URL correcta para gestionar becas
                        // Por ejemplo, si existe un controlador BecaController con actionAsignar
                        return Html::a('<i class="fas fa-medal"></i>', 
                            ['/becas/beca/asignar', 'id_atleta' => $model->id], 
                            ['title' => 'Gestionar Beca', 'class' => 'btn btn-primary btn-xs']
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