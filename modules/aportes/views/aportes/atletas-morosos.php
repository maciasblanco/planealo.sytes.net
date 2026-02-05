<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use app\models\Escuela;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel app\models\AportesSemanalesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Reporte de Atletas Morosos';
$this->params['breadcrumbs'][] = $this->title;

// Calcular totales
$totalDeuda = 0;
$totalAtletas = 0;
$promedioDeuda = 0;

if ($dataProvider->models) {
    $totalAtletas = count($dataProvider->models);
    foreach ($dataProvider->models as $model) {
        $totalDeuda += $model->getMontoDeuda();
    }
    $promedioDeuda = $totalAtletas > 0 ? $totalDeuda / $totalAtletas : 0;
}
?>

<div class="aportes-semanales-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'action' => ['atletas-morosos'],
        'method' => 'get',
        'options' => ['class' => 'form-inline'],
    ]); ?>

    <div class="row">
        <div class="col-md-3">
            <?= $form->field($searchModel, 'escuela_id')->dropDownList(
                ArrayHelper::map(Escuela::find()->orderBy('nombre')->all(), 'id', 'nombre'),
                ['prompt' => 'Todas las Escuelas', 'class' => 'form-control']
            )->label(false) ?>
        </div>
        
        <div class="col-md-2">
            <?= $form->field($searchModel, 'minDeuda')->dropDownList([
                1 => '1+ quincenas',
                2 => '2+ quincenas', 
                3 => '3+ quincenas',
                4 => '4+ quincenas',
                5 => '5+ quincenas'
            ], ['prompt' => 'Mín. deuda', 'class' => 'form-control'])->label(false) ?>
        </div>
        
        <div class="col-md-2">
            <?= $form->field($searchModel, 'searchGlobal')->textInput([
                'placeholder' => 'Buscar atleta...',
                'class' => 'form-control'
            ])->label(false) ?>
        </div>
        
        <div class="col-md-2">
            <?= Html::submitButton('Filtrar', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Limpiar', ['atletas-morosos'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

    <hr>

    <!-- Resumen Estadístico -->
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">Resumen de Morosidad</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <h4><?= $totalAtletas ?></h4>
                    <p class="text-muted">Atletas Morosos</p>
                </div>
                <div class="col-md-3 text-center">
                    <h4>$<?= number_format($totalDeuda, 2) ?></h4>
                    <p class="text-muted">Deuda Total</p>
                </div>
                <div class="col-md-3 text-center">
                    <h4>$<?= number_format($promedioDeuda, 2) ?></h4>
                    <p class="text-muted">Deuda Promedio</p>
                </div>
                <div class="col-md-3 text-center">
                    <h4><?= $totalAtletas > 0 ? number_format(($totalAtletas / $totalAtletasGeneral) * 100, 1) : 0 ?>%</h4>
                    <p class="text-muted">Porcentaje Morosos</p>
                </div>
            </div>
            <div class="alert alert-warning" style="margin-top: 15px; margin-bottom: 0;">
                <strong>Nota:</strong> El sistema quincenal opera con pagos de <strong>$4.00 por quincena</strong>. 
                La morosidad se calcula desde la fecha de inscripción del atleta.
            </div>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'atleta.nombreCompleto',
                'label' => 'Atleta',
                'value' => function($model) {
                    return $model->atleta->nombreCompleto;
                }
            ],
            
            [
                'attribute' => 'atleta.escuela.nombre',
                'label' => 'Escuela',
                'filter' => ArrayHelper::map(Escuela::find()->orderBy('nombre')->all(), 'nombre', 'nombre'),
                'value' => function($model) {
                    return $model->atleta->escuela->nombre;
                }
            ],
            
            [
                'attribute' => 'atleta.fecha_inscripcion',
                'label' => 'Fecha Inscripción',
                'format' => ['date', 'php:d/m/Y'],
                'contentOptions' => ['class' => 'text-center']
            ],
            
            [
                'attribute' => 'quincenas_deuda',
                'label' => 'Quincenas en Deuda',
                'value' => function($model) {
                    $deuda = $model->getQuincenasDeuda();
                    $clase = $deuda >= 4 ? 'danger' : ($deuda >= 2 ? 'warning' : 'info');
                    return Html::tag('span', $deuda, ['class' => 'label label-' . $clase]);
                },
                'format' => 'raw',
                'contentOptions' => ['class' => 'text-center']
            ],
            
            [
                'attribute' => 'monto_deuda',
                'label' => 'Monto Deuda',
                'value' => function($model) {
                    $monto = $model->getMontoDeuda();
                    $clase = $monto >= 16.00 ? 'danger' : ($monto >= 8.00 ? 'warning' : 'info');
                    return Html::tag('span', '$' . number_format($monto, 2), 
                        ['class' => 'label label-' . $clase]);
                },
                'format' => 'raw',
                'contentOptions' => ['class' => 'text-center']
            ],
            
            [
                'attribute' => 'ultimo_pago',
                'label' => 'Último Pago',
                'value' => function($model) {
                    return $model->getFechaUltimoPagoFormatted();
                },
                'contentOptions' => ['class' => 'text-center']
            ],
            
            [
                'attribute' => 'dias_sin_pagar',
                'label' => 'Días sin Pagar',
                'value' => function($model) {
                    $dias = $model->getDiasSinPagar();
                    $clase = $dias > 45 ? 'danger' : ($dias > 30 ? 'warning' : 'info');
                    return Html::tag('span', $dias . ' días', ['class' => 'label label-' . $clase]);
                },
                'format' => 'raw',
                'contentOptions' => ['class' => 'text-center']
            ],
            
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{gestion}',
                'buttons' => [
                    'gestion' => function($url, $model) {
                        return Html::a(
                            '<span class="glyphicon glyphicon-usd"></span> Gestionar',
                            ['gestion-atleta', 'id' => $model->atleta_id],
                            ['class' => 'btn btn-xs btn-primary']
                        );
                    }
                ],
                'contentOptions' => ['class' => 'text-center']
            ],
        ],
        'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
        'summary' => 'Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> atletas morosos.',
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">Información Importante</h4>
                </div>
                <div class="panel-body">
                    <ul>
                        <li>El sistema quincenal requiere pagos de <strong>$4.00 cada 15 días</strong></li>
                        <li>Las fechas de corte son los días <strong>15 y 30 de cada mes</strong></li>
                        <li>Se considera moroso después de <strong>2 quincenas sin pagar</strong></li>
                        <li>Para atletas nuevos, si se inscriben con menos de 8 días para la próxima quincena, 
                            pagan solo <strong>$2.00 (mitad de quincena)</strong></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">Acciones Disponibles</h4>
                </div>
                <div class="panel-body">
                    <div class="btn-group-vertical" style="width: 100%;">
                        <?= Html::a('<i class="glyphicon glyphicon-print"></i> Imprimir Reporte', 
                            ['reporte', 'tipo' => 'morosos'], 
                            ['class' => 'btn btn-default', 'target' => '_blank']) ?>
                            
                        <?= Html::a('<i class="glyphicon glyphicon-envelope"></i> Enviar Recordatorios', 
                            ['enviar-recordatorios'], 
                            ['class' => 'btn btn-info', 
                             'data' => [
                                 'confirm' => '¿Enviar recordatorios a todos los atletas morosos?',
                                 'method' => 'post'
                             ]]) ?>
                             
                        <?= Html::a('<i class="glyphicon glyphicon-refresh"></i> Actualizar Cálculos', 
                            ['actualizar-deudas'], 
                            ['class' => 'btn btn-warning',
                             'data' => [
                                 'confirm' => '¿Recalcular todas las deudas quincenales?',
                                 'method' => 'post'
                             ]]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>