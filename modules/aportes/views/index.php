<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\aportes\models\AportesSemanalesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $totalRecaudado float */
/* @var $pendientes int */
/* @var $deudaTotal float */
/* @var $atletasConDeuda int */

$this->title = 'Gestión de Aportes Semanales - Viernes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aportes-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <!-- PANEL DE ESTADÍSTICAS MEJORADO -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-success">
                <div class="info-box-content">
                    <span class="info-box-text">Total Recaudado</span>
                    <span class="info-box-number">$<?= number_format($totalRecaudado, 2) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <div class="info-box-content">
                    <span class="info-box-text">Aportes Pendientes</span>
                    <span class="info-box-number"><?= $pendientes ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-danger">
                <div class="info-box-content">
                    <span class="info-box-text">Deuda Total</span>
                    <span class="info-box-number">$<?= number_format($deudaTotal, 2) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-info">
                <div class="info-box-content">
                    <span class="info-box-text">Atletas con Deuda</span>
                    <span class="info-box-number"><?= $atletasConDeuda ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTONES DE ACCIÓN MEJORADOS -->
    <div class="row mb-3">
        <div class="col-md-12">
            <?= Html::a('Registrar Aporte Individual', ['create'], ['class' => 'btn btn-success']) ?>
            <?= Html::a('Registro Masivo', ['registro-masivo'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Atletas Morosos', ['atletas-morosos'], ['class' => 'btn btn-danger']) ?>
            <?= Html::a('Deudas por Escuela', ['deudas-escuelas'], ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Generar Reporte', ['reporte'], ['class' => 'btn btn-info', 'target' => '_blank']) ?>
        </div>
    </div>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'atleta_id',
                'value' => function($model) {
                    return $model->atleta->nombre_completo ?? 'N/A';
                },
                'filter' => \yii\helpers\ArrayHelper::map(
                    \app\models\AtletasRegistro::find()->all(), 'id', 'nombre_completo'
                )
            ],
            [
                'attribute' => 'escuela_id',
                'value' => function($model) {
                    return $model->escuela->nombre ?? 'N/A';
                },
                'filter' => \yii\helpers\ArrayHelper::map(
                    \app\models\Escuela::find()->all(), 'id', 'nombre'
                )
            ],
            'fecha_viernes',
            'numero_semana',
            [
                'attribute' => 'monto',
                'value' => function($model) {
                    return '$' . number_format($model->monto, 2);
                }
            ],
            [
                'attribute' => 'estado',
                'value' => function($model) {
                    $badge = $model->estado == 'pagado' ? 'success' : 
                            ($model->estado == 'pendiente' ? 'warning' : 'danger');
                    return '<span class="badge badge-'.$badge.'">'.ucfirst($model->estado).'</span>';
                },
                'format' => 'raw',
                'filter' => [
                    'pendiente' => 'Pendiente',
                    'pagado' => 'Pagado',
                    'cancelado' => 'Cancelado'
                ]
            ],
            'fecha_pago',
            'metodo_pago',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete} {pagar}',
                'buttons' => [
                    'pagar' => function($url, $model, $key) {
                        if ($model->estado == 'pendiente') {
                            return Html::a('<span class="fa fa-money-bill"></span>', 
                                ['marcar-pagado', 'id' => $model->id], 
                                [
                                    'title' => 'Marcar como pagado',
                                    'data' => [
                                        'confirm' => '¿Está seguro de marcar este aporte como PAGADO?',
                                        'method' => 'post',
                                    ]
                                ]);
                        }
                        return '';
                    }
                ]
            ],
        ],
    ]); ?>
    <?php Pjax::end(); ?>
</div>