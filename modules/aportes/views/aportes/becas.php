<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\models\Beca[] $becas */
/** @var app\models\TipoBeca[] $tiposBeca */
/** @var int $totalBecas */
/** @var int $becasMerito */
/** @var int $becasEntrenador */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de gestionar becas.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/select-escuela'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Gestión de Becas - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Quincenales', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="becas-index">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-medal text-warning"></i> <?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-plus"></i> Asignar Nueva Beca', ['asignar-beca'], ['class' => 'btn btn-success']) ?>
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <!-- Información de la Escuela -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-6">
                <strong><i class="fas fa-school"></i> Escuela Activa:</strong> <?= Html::encode($nombre_escuela) ?>
                <span class="badge bg-primary ms-2">ID: <?= $id_escuela ?></span>
            </div>
            <div class="col-md-6 text-right">
                <small class="text-muted">Sistema GED - Becas</small>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-tag"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Becas</span>
                    <span class="info-box-number"><?= $totalBecas ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-star"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Becas Mérito</span>
                    <span class="info-box-number"><?= $becasMerito ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Becas Entrenador</span>
                    <span class="info-box-number"><?= $becasEntrenador ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Próximas a vencer</span>
                    <span class="info-box-number"><?= $proximasAVencer ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => new \yii\data\ArrayDataProvider([
            'allModels' => $becas,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'attributes' => ['fecha_asignacion', 'estado', 'atleta.p_nombre', 'tipoBeca.nombre'],
            ],
        ]),
        'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
        'columns' => [
            [
                'attribute' => 'id_beca',
                'label' => 'ID',
                'headerOptions' => ['style' => 'width:80px'],
            ],
            [
                'attribute' => 'atleta',
                'label' => 'Atleta',
                'value' => function($model) {
                    return $model->atleta ? $model->atleta->p_nombre . ' ' . $model->atleta->p_apellido : 'N/A';
                },
            ],
            [
                'attribute' => 'tipoBeca.nombre',
                'label' => 'Tipo de Beca',
            ],
            [
                'attribute' => 'fecha_asignacion',
                'format' => ['date', 'php:d/m/Y'],
                'headerOptions' => ['style' => 'width:120px'],
            ],
            [
                'attribute' => 'fecha_vencimiento',
                'format' => ['date', 'php:d/m/Y'],
                'headerOptions' => ['style' => 'width:120px'],
                'contentOptions' => function($model) {
                    $hoy = time();
                    $venc = strtotime($model->fecha_vencimiento);
                    if ($model->estado == 'ACTIVA' && $venc < $hoy) {
                        return ['class' => 'bg-danger text-white'];
                    }
                    return [];
                },
            ],
            [
                'attribute' => 'estado',
                'value' => function($model) {
                    $estados = [
                        'ACTIVA' => 'Activa',
                        'VENCIDA' => 'Vencida',
                        'REVOCADA' => 'Revocada',
                    ];
                    return $estados[$model->estado] ?? $model->estado;
                },
                'contentOptions' => function($model) {
                    $class = '';
                    if ($model->estado == 'ACTIVA') $class = 'badge bg-success';
                    if ($model->estado == 'VENCIDA') $class = 'badge bg-danger';
                    if ($model->estado == 'REVOCADA') $class = 'badge bg-secondary';
                    return ['class' => $class];
                },
            ],
            [
                'attribute' => 'autorizacion_excepcion',
                'label' => 'Excepción',
                'format' => 'boolean',
                'headerOptions' => ['style' => 'width:80px'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {revocar} {historial}',
                'headerOptions' => ['style' => 'width:120px'],
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', ['view-beca', 'id' => $model->id_beca], [
                            'title' => 'Ver detalles',
                            'class' => 'btn btn-info btn-xs',
                        ]);
                    },
                    'revocar' => function ($url, $model) {
                        if ($model->estado == 'ACTIVA') {
                            return Html::a('<i class="fas fa-ban"></i>', ['revocar-beca', 'id_beca' => $model->id_beca], [
                                'title' => 'Revocar beca',
                                'class' => 'btn btn-danger btn-xs',
                                'data' => [
                                    'confirm' => '¿Está seguro de revocar esta beca?',
                                    'method' => 'post',
                                ],
                            ]);
                        }
                        return '';
                    },
                    'historial' => function ($url, $model) {
                        return Html::a('<i class="fas fa-history"></i>', ['historial-beca', 'id' => $model->id_beca], [
                            'title' => 'Ver historial',
                            'class' => 'btn btn-default btn-xs',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

    <!-- Información adicional -->
    <div class="alert alert-light border mt-4">
        <h6><i class="fas fa-info-circle text-info"></i> Reglas de negocio para becas</h6>
        <ul class="mb-0">
            <li>Máximo 3 becas activas por familia.</li>
            <li>Máximo 1 beca de tipo "Entrenador" por familia.</li>
            <li>Cada atleta puede tener solo una beca activa.</li>
            <li>Al menos un atleta por familia debe estar sin beca (excepto con autorización de excepción).</li>
            <li>Las becas se renuevan automáticamente cada año si cumplen las condiciones.</li>
        </ul>
    </div>
</div>

<?php
// CSS adicional para los badges
$css = <<<CSS
.badge {
    padding: 0.4em 0.8em;
    font-size: 0.85em;
    border-radius: 0.25rem;
}
.badge.bg-success { background-color: #28a745; color: white; }
.badge.bg-danger { background-color: #dc3545; color: white; }
.badge.bg-secondary { background-color: #6c757d; color: white; }
.bg-danger.text-white td { background-color: #f8d7da !important; }
CSS;
$this->registerCss($css);
?>