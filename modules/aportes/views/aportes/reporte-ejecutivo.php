<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var string $fechaInicio */
/** @var string $fechaFin */
/** @var float $totalRecaudado */
/** @var float $totalPendiente */
/** @var int $cantidadPagados */
/** @var int $cantidadPendientes */
/** @var array $topAtletas */
/** @var array $evolucion */
/** @var array $porEscuela */
/** @var int|null $id_escuela */
/** @var string|null $nombre_escuela */

$this->title = 'Reporte Ejecutivo de Aportes';
if ($nombre_escuela) {
    $this->title .= ' - ' . Html::encode($nombre_escuela);
}
$this->params['breadcrumbs'][] = ['label' => 'Aportes Quincenales', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Reporte Ejecutivo';

// Calcular porcentaje de cumplimiento
$totalGeneral = $totalRecaudado + $totalPendiente;
$porcentajeCumplimiento = $totalGeneral > 0 ? round(($totalRecaudado / $totalGeneral) * 100, 1) : 0;
?>

<div class="reporte-ejecutivo">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-chart-pie text-info"></i> <?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-default']) ?>
            <?= Html::a('<i class="fas fa-print"></i> Imprimir', '#', [
                'class' => 'btn btn-info',
                'onclick' => 'window.print()'
            ]) ?>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros del Reporte</h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['reporte-ejecutivo']]); ?>
            <div class="row">
                <div class="col-md-4">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= $fechaInicio ?>">
                </div>
                <div class="col-md-4">
                    <label>Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= $fechaFin ?>">
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <div>
                        <?= Html::submitButton('<i class="fas fa-sync"></i> Actualizar', ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('<i class="fas fa-undo"></i> Reiniciar', ['reporte-ejecutivo'], ['class' => 'btn btn-default']) ?>
                    </div>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Recaudado</span>
                    <span class="info-box-number">$<?= number_format($totalRecaudado, 2) ?></span>
                    <span class="info-box-detail"><?= $cantidadPagados ?> aportes pagados</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Pendiente</span>
                    <span class="info-box-number">$<?= number_format($totalPendiente, 2) ?></span>
                    <span class="info-box-detail"><?= $cantidadPendientes ?> aportes pendientes</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-aqua">
                <span class="info-box-icon"><i class="fas fa-percent"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Cumplimiento</span>
                    <span class="info-box-number"><?= $porcentajeCumplimiento ?>%</span>
                    <span class="info-box-detail">Período seleccionado</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-purple">
                <span class="info-box-icon"><i class="fas fa-calendar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Período</span>
                    <span class="info-box-number" style="font-size: 1.2rem;">
                        <?= Yii::$app->formatter->asDate($fechaInicio, 'short') ?> - <?= Yii::$app->formatter->asDate($fechaFin, 'short') ?>
                    </span>
                    <span class="info-box-detail"><?= (strtotime($fechaFin) - strtotime($fechaInicio)) / (60*60*24) ?> días</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de evolución mensual (simulado con Chart.js) -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Evolución Mensual (USD)</h5>
                </div>
                <div class="card-body">
                    <canvas id="evolucionChart" style="height: 300px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Atletas y Estadísticas por Escuela -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 Atletas (por aportes)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topAtletas)): ?>
                        <p class="text-muted">No hay datos en el período seleccionado.</p>
                    <?php else: ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Atleta</th>
                                    <th class="text-right">Total Aportado</th>
                                    <th class="text-center">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topAtletas as $i => $item): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td>
                                        <strong><?= Html::encode($item['nombre']) ?></strong>
                                        <?php if ($item['escuela_id']): ?>
                                            <br><small class="text-muted">Escuela ID: <?= $item['escuela_id'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right text-success">$<?= number_format($item['total_pagado'], 2) ?></td>
                                    <td class="text-center"><?= $item['total_aportes'] ?> quincenas</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-school"></i> Resumen por Escuela</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($porEscuela)): ?>
                        <p class="text-muted">No hay datos por escuela o hay una escuela seleccionada.</p>
                        <?php if ($id_escuela): ?>
                            <p class="text-info">Mostrando datos solo de la escuela activa.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Escuela</th>
                                    <th class="text-right">Recaudado</th>
                                    <th class="text-right">Pendiente</th>
                                    <th class="text-center">Cumplimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($porEscuela as $esc): 
                                    $totalEsc = $esc['pagado'] + $esc['pendiente'];
                                    $porc = $totalEsc > 0 ? round(($esc['pagado'] / $totalEsc)*100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?= Html::encode($esc['nombre']) ?></td>
                                    <td class="text-right text-success">$<?= number_format($esc['pagado'], 2) ?></td>
                                    <td class="text-right text-warning">$<?= number_format($esc['pendiente'], 2) ?></td>
                                    <td class="text-center"><?= $porc ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Datos adicionales (export, etc.) -->
    <div class="row mt-4">
        <div class="col-md-12 text-center">
            <div class="btn-group">
                <?= Html::a('<i class="fas fa-file-excel"></i> Exportar a Excel', '#', [
                    'class' => 'btn btn-success',
                    'onclick' => 'alert("Funcionalidad en desarrollo"); return false;'
                ]) ?>
                <?= Html::a('<i class="fas fa-file-pdf"></i> Exportar a PDF', '#', [
                    'class' => 'btn btn-danger',
                    'onclick' => 'alert("Funcionalidad en desarrollo"); return false;'
                ]) ?>
            </div>
        </div>
    </div>
</div>

<!-- Script para el gráfico con Chart.js -->
<?php
// Preparar datos para el gráfico
$mesesLabels = [];
$pagadosData = [];
$pendientesData = [];
foreach ($evolucion as $e) {
    $mesesLabels[] = Yii::$app->formatter->asDate($e['mes'] . '-01', 'MMM yyyy');
    $pagadosData[] = $e['pagado'];
    $pendientesData[] = $e['pendiente'];
}
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$js = <<<JS
$(document).ready(function() {
    var ctx = document.getElementById('evolucionChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['" . implode("','", $mesesLabels) . "'],
            datasets: [
                {
                    label: 'Recaudado (USD)',
                    data: [" . implode(',', $pagadosData) . "],
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    tension: 0.3
                },
                {
                    label: 'Pendiente (USD)',
                    data: [" . implode(',', $pendientesData) . "],
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });
});
JS;
$this->registerJs($js);
?>