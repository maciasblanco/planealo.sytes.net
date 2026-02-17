<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap\Alert;
use yii\web\JsExpression;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $atletasConEstadisticas array */
/* @var $totalRecaudado float */
/* @var $pendientes int */
/* @var $deudaTotal float */
/* @var $atletasConDeuda int */
/* @var $topAtletas array */
/* @var $totalAtletas int */
/* @var $erroresProcesamiento array */

$this->title = 'Gestión de Aportes Quincenales';
$this->params['breadcrumbs'][] = $this->title;

// Obtener monto quincenal desde el modelo
$montoQuincenal = app\models\AportesSemanales::MONTO_QUINCENAL_USD;
?>

<div class="aportes-index">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-money"></i> <?= Html::encode($this->title) ?>
                    </h3>
                    <div class="box-tools pull-right">
                        <?= Html::a('<i class="fa fa-plus"></i> Nuevo Aporte', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
                        <?= Html::a('<i class="fa fa-users"></i> Gestión por Atleta', ['gestion-atleta'], ['class' => 'btn btn-primary btn-sm']) ?>
                        <?= Html::a('<i class="fa fa-shopping-cart"></i> Compras', ['compras'], ['class' => 'btn btn-info btn-sm']) ?>
                        <?= Html::a('<i class="fa fa-bar-chart"></i> Reportes', ['reporte-ejecutivo'], ['class' => 'btn btn-warning btn-sm']) ?>
                    </div>
                </div>
                <div class="box-body">
                    <?php if (!empty($erroresProcesamiento)): ?>
                        <div class="alert alert-danger">
                            <h4><i class="icon fa fa-warning"></i> Errores durante el procesamiento</h4>
                            <ul>
                                <?php foreach ($erroresProcesamiento as $error): ?>
                                    <li><?= Html::encode($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Estadísticas Generales -->
                    <div class="row">
                        <div class="col-md-3 col-sm-6 col-xs-12">
                            <div class="info-box bg-green">
                                <span class="info-box-icon"><i class="fa fa-dollar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Recaudado</span>
                                    <span class="info-box-number">$<?= number_format($totalRecaudado, 2) ?></span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">
                                        <?= $totalAtletas ?> atletas registrados
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 col-xs-12">
                            <div class="info-box bg-red">
                                <span class="info-box-icon"><i class="fa fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Deuda Total</span>
                                    <span class="info-box-number">$<?= number_format($deudaTotal, 2) ?></span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">
                                        <?= $atletasConDeuda ?> atletas con deuda
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 col-xs-12">
                            <div class="info-box bg-yellow">
                                <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Quincenas Pendientes</span>
                                    <span class="info-box-number"><?= $pendientes ?></span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">
                                        Sistema quincenal ($<?= $montoQuincenal ?> cada 15 días)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 col-xs-12">
                            <div class="info-box bg-aqua">
                                <span class="info-box-icon"><i class="fa fa-line-chart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Promedio por Atleta</span>
                                    <span class="info-box-number">
                                        $<?= $totalAtletas > 0 ? number_format($totalRecaudado / $totalAtletas, 2) : '0.00' ?>
                                    </span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">
                                        <?= count($topAtletas) ?> top contribuyentes
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Atletas -->
                    <?php if (!empty($topAtletas)): ?>
                        <div class="box box-success">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-trophy"></i> Top Contribuyentes</h3>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <?php foreach ($topAtletas as $index => $top): ?>
                                        <?php
                                        $atletaTop = \app\models\AtletasRegistro::findOne($top['atleta_id']);
                                        if (!$atletaTop) continue;
                                        
                                        $porcentaje = $top['total_pagado'] > 0 ? ($top['total_pagado'] / $totalRecaudado) * 100 : 0;
                                        ?>
                                        <div class="col-md-2 col-sm-4 col-xs-6 text-center">
                                            <div class="small-box bg-<?= $index < 3 ? 'green' : 'aqua' ?>">
                                                <div class="inner">
                                                    <h4>
                                                        <strong><?= $index + 1 ?>.</strong> 
                                                        <?= Html::encode($atletaTop->p_nombre . ' ' . $atletaTop->p_apellido) ?>
                                                    </h4>
                                                    <p>
                                                        <strong>$<?= number_format($top['total_pagado'], 2) ?></strong><br>
                                                        <small><?= $top['total_aportes'] ?> quincenas</small>
                                                    </p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fa fa-<?= $index == 0 ? 'trophy' : ($index == 1 ? 'star' : 'star-o') ?>"></i>
                                                </div>
                                                <?= Html::a(
                                                    'Ver Detalles <i class="fa fa-arrow-circle-right"></i>',
                                                    ['gestion-atleta', 'atleta_id' => $atletaTop->id],
                                                    ['class' => 'small-box-footer']
                                                ) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tabla de Atletas -->
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-list"></i> Listado de Atletas</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="box-body table-responsive">
                            <?php if (empty($atletasConEstadisticas)): ?>
                                <div class="callout callout-warning">
                                    <h4>No hay atletas registrados</h4>
                                    <p>No se encontraron atletas en esta escuela o no tiene permisos para verlos.</p>
                                </div>
                            <?php else: ?>
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th>Atleta</th>
                                            <th class="text-center">Quincenas</th>
                                            <th class="text-center">Pagadas</th>
                                            <th class="text-center">Pendientes</th>
                                            <th class="text-center">Adelantadas</th>
                                            <th class="text-right">Monto Pagado (USD)</th>
                                            <th class="text-right">Deuda (USD)</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($atletasConEstadisticas as $index => $item): ?>
                                            <?php 
                                            $atleta = $item['atleta'];
                                            $montoPagado = $item['montoPagado'];
                                            $montoDeuda = $item['montoDeuda'];
                                            $montoAdelantado = $item['montoAdelantado'];
                                            $quincenasAdelantadas = $item['quincenasAdelantadas'];

                                            // Calcular quincenas pagadas y totales
                                            $quincenasPagadas = $montoPagado / $montoQuincenal;
                                            $quincenasDeuda = $montoDeuda / $montoQuincenal;
                                            $totalQuincenas = $item['totalQuincenas'];
                                            
                                            // Determinar estado
                                            if ($quincenasDeuda == 0 && $quincenasPagadas > 0) {
                                                $estadoLabel = 'Al día';
                                                $estadoClass = 'label-success';
                                            } elseif ($quincenasDeuda > 0 && $quincenasDeuda <= 2) {
                                                $estadoLabel = 'Moroso leve';
                                                $estadoClass = 'label-warning';
                                            } elseif ($quincenasDeuda > 2) {
                                                $estadoLabel = 'Moroso grave';
                                                $estadoClass = 'label-danger';
                                            } else {
                                                $estadoLabel = 'Sin historial';
                                                $estadoClass = 'label-default';
                                            }
                                            ?>
                                            <tr>
                                                <td class="text-center"><?= $index + 1 ?></td>
                                                <td>
                                                    <strong><?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= Html::encode($atleta->cedula ?? 'Sin cédula') ?>
                                                        <?php if ($item['error']): ?>
                                                            <span class="label label-danger">Error en procesamiento</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-blue"><?= $totalQuincenas ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-green"><?= round($quincenasPagadas, 1) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($quincenasDeuda > 0): ?>
                                                        <span class="badge bg-red"><?= round($quincenasDeuda, 1) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-green">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($quincenasAdelantadas > 0): ?>
                                                        <span class="badge bg-purple"><?= round($quincenasAdelantadas, 1) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-gray">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <strong class="text-green">$<?= number_format($montoPagado, 2) ?></strong>
                                                    <?php if ($montoAdelantado > 0): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            (+$<?= number_format($montoAdelantado, 2) ?> adelantado)
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php if ($montoDeuda > 0): ?>
                                                        <strong class="text-red">$<?= number_format($montoDeuda, 2) ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= round($quincenasDeuda) ?> quincena(s)
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-green">$0.00</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="label <?= $estadoClass ?>"><?= $estadoLabel ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <?= Html::a(
                                                            '<i class="fa fa-eye"></i>',
                                                            ['gestion-atleta', 'atleta_id' => $atleta->id],
                                                            [
                                                                'class' => 'btn btn-info btn-xs',
                                                                'title' => 'Gestionar aportes',
                                                                'data-toggle' => 'tooltip'
                                                            ]
                                                        ) ?>
                                                        <?= Html::a(
                                                            '<i class="fa fa-money"></i>',
                                                            ['create', 'AportesSemanales[atleta_id]' => $atleta->id],
                                                            [
                                                                'class' => 'btn btn-success btn-xs',
                                                                'title' => 'Registrar pago',
                                                                'data-toggle' => 'tooltip'
                                                            ]
                                                        ) ?>
                                                        <?= Html::a(
                                                            '<i class="fa fa-history"></i>',
                                                            ['pago-multiple', 'atleta_id' => $atleta->id],
                                                            [
                                                                'class' => 'btn btn-warning btn-xs',
                                                                'title' => 'Pago múltiple',
                                                                'data-toggle' => 'tooltip'
                                                            ]
                                                        ) ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray">
                                            <th colspan="6" class="text-right">TOTALES:</th>
                                            <th class="text-right">
                                                <strong class="text-green">$<?= number_format($totalRecaudado, 2) ?></strong>
                                            </th>
                                            <th class="text-right">
                                                <?php if ($deudaTotal > 0): ?>
                                                    <strong class="text-red">$<?= number_format($deudaTotal, 2) ?></strong>
                                                <?php else: ?>
                                                    <span class="text-green">$0.00</span>
                                                <?php endif; ?>
                                            </th>
                                            <th class="text-center">
                                                <span class="label label-info">
                                                    <?= $totalAtletas ?> atletas
                                                </span>
                                            </th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            <?php endif; ?>
                        </div>
                        <div class="box-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn-group">
                                        <?= Html::a('<i class="fa fa-file-excel-o"></i> Exportar Excel', ['exportar-excel'], [
                                            'class' => 'btn btn-success',
                                            'target' => '_blank'
                                        ]) ?>
                                        <?= Html::a('<i class="fa fa-file-pdf-o"></i> Exportar PDF', ['exportar-pdf'], [
                                            'class' => 'btn btn-danger',
                                            'target' => '_blank'
                                        ]) ?>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <div class="btn-group">
                                        <?= Html::a('<i class="fa fa-calendar-check-o"></i> Pago Múltiple', ['pago-multiple'], [
                                            'class' => 'btn btn-primary'
                                        ]) ?>
                                        <?= Html::a('<i class="fa fa-calendar-plus-o"></i> Pago Adelantado', ['pago-adelantado'], [
                                            'class' => 'btn btn-warning'
                                        ]) ?>
                                        <?= Html::a('<i class="fa fa-users"></i> Registro Masivo', ['registro-masivo'], [
                                            'class' => 'btn btn-info'
                                        ]) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Sistema -->
                    <div class="callout callout-info">
                        <h4><i class="fa fa-info-circle"></i> Información del Sistema Quincenal</h4>
                        <p>
                            <strong>Sistema actualizado:</strong> Aportes quincenales de $<?= $montoQuincenal ?> cada 15 días.<br>
                            <strong>Fechas de pago:</strong> Días 15 y último día de cada mes.<br>
                            <strong>Fecha de inicio:</strong> 15 de enero de 2026.<br>
                            <strong>Manejo de moneda:</strong> Sistema dual USD/BS con tasa de cambio histórica.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Script para tooltips
$this->registerJs("
    $(function () {
        $('[data-toggle=\"tooltip\"]').tooltip();
        
        // Actualizar automáticamente cada 5 minutos
        setInterval(function() {
            $.pjax.reload({container: '#pjax-container', timeout: 5000});
        }, 300000); // 5 minutos
    });
");
?>