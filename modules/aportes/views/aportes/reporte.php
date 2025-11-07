<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $fechaInicio string */
/* @var $fechaFin string */
/* @var $totalRecaudado float */
/* @var $totalPendiente float */
/* @var $totalCancelado float */
/* @var $countPagados int */
/* @var $countPendientes int */
/* @var $countCancelados int */
/* @var $topEscuelas array */
/* @var $evolucionMensual array */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    // ❌ MOSTRAR ERROR Y REDIRECCIÓN
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de ver reportes.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/index'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Reporte General de Aportes Semanales - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Semanales', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="reporte-aportes">
    <div class="row">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-chart-bar text-info"></i>
                <?= Html::encode($this->title) ?>
            </h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver al Listado', ['index'], ['class' => 'btn btn-default']) ?>
            <?= Html::a('<i class="fas fa-print"></i> Imprimir', '#', [
                'class' => 'btn btn-info',
                'onclick' => 'window.print()'
            ]) ?>
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
                <small class="text-muted">Sistema GED - Reportes</small>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros del Reporte</h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['reporte']]); ?>
            
            <!-- ✅ CAMPO OCULTO ESCUELA -->
            <input type="hidden" name="id_escuela" value="<?= $id_escuela ?>">
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $fechaInicio ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= $fechaFin ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" style="margin-top: 25px;">
                        <?= Html::submitButton('<i class="fas fa-search"></i> Filtrar', ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('<i class="fas fa-sync"></i> Limpiar', ['reporte'], ['class' => 'btn btn-default']) ?>
                    </div>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Recaudado</span>
                    <span class="info-box-number">$<?= number_format($totalRecaudado, 2) ?></span>
                    <span class="info-box-detail"><?= $countPagados ?> aportes pagados</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Pendiente</span>
                    <span class="info-box-number">$<?= number_format($totalPendiente, 2) ?></span>
                    <span class="info-box-detail"><?= $countPendientes ?> aportes pendientes</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Cancelado</span>
                    <span class="info-box-number">$<?= number_format($totalCancelado, 2) ?></span>
                    <span class="info-box-detail"><?= $countCancelados ?> aportes cancelados</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Escuelas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 5 Escuelas - Recaudación</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($topEscuelas)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Escuela</th>
                                        <th class="text-center">Aportes</th>
                                        <th class="text-right">Recaudado</th>
                                        <th class="text-right">Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topEscuelas as $escuela): ?>
                                        <tr>
                                            <td><?= Html::encode($escuela['escuela_nombre']) ?></td>
                                            <td class="text-center"><?= $escuela['total_aportes'] ?></td>
                                            <td class="text-right text-success">
                                                <strong>$<?= number_format($escuela['total_recaudado'], 2) ?></strong>
                                            </td>
                                            <td class="text-right text-warning">
                                                $<?= number_format($escuela['total_pendiente'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            No hay datos para mostrar en el período seleccionado.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Evolución Mensual -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Evolución Mensual (Últimos 6 meses)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($evolucionMensual)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mes</th>
                                        <th class="text-center">Total Aportes</th>
                                        <th class="text-right">Recaudado</th>
                                        <th class="text-right">Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($evolucionMensual as $mes): ?>
                                        <tr>
                                            <td><?= date('M Y', strtotime($mes['mes'] . '-01')) ?></td>
                                            <td class="text-center"><?= $mes['total_aportes'] ?></td>
                                            <td class="text-right text-success">
                                                <strong>$<?= number_format($mes['recaudado'], 2) ?></strong>
                                            </td>
                                            <td class="text-right text-warning">
                                                $<?= number_format($mes['pendiente'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            No hay datos de evolución mensual disponibles.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Adicionales -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-percentage"></i> Estadísticas Adicionales</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="text-primary"><?= number_format($countPagados + $countPendientes + $countCancelados) ?></h3>
                                <p class="text-muted">Total Aportes Registrados</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="text-success"><?= $countPagados > 0 ? number_format(($countPagados / ($countPagados + $countPendientes + $countCancelados)) * 100, 1) : 0 ?>%</h3>
                                <p class="text-muted">Tasa de Pago</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="text-warning"><?= $countPendientes > 0 ? number_format(($countPendientes / ($countPagados + $countPendientes + $countCancelados)) * 100, 1) : 0 ?>%</h3>
                                <p class="text-muted">Tasa de Pendientes</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="text-info">$<?= number_format($totalRecaudado + $totalPendiente + $totalCancelado, 2) ?></h3>
                                <p class="text-muted">Monto Total Movimiento</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Período -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-light border">
                <h6><i class="fas fa-info-circle"></i> Información del Reporte</h6>
                <p class="mb-0">
                    <strong>Período analizado:</strong> <?= Yii::$app->formatter->asDate($fechaInicio, 'long') ?> 
                    al <?= Yii::$app->formatter->asDate($fechaFin, 'long') ?> |
                    <strong>Escuela:</strong> <?= Html::encode($nombre_escuela) ?> (ID: <?= $id_escuela ?>) |
                    <strong>Generado el:</strong> <?= Yii::$app->formatter->asDatetime(time(), 'long') ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// CSS para el reporte
$css = <<<CSS
.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: 0.25rem;
    background: #fff;
    display: flex;
    margin-bottom: 1rem;
    min-height: 80px;
    padding: 0.5rem;
    position: relative;
}
.info-box .info-box-icon {
    border-radius: 0.25rem;
    align-items: center;
    display: flex;
    font-size: 1.875rem;
    justify-content: center;
    text-align: center;
    width: 70px;
}
.info-box .info-box-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.2;
    flex: 1;
    padding: 0 10px;
}
.info-box .info-box-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-transform: uppercase;
    font-weight: bold;
    font-size: 0.875rem;
}
.info-box .info-box-number {
    display: block;
    font-weight: bold;
    font-size: 1.5rem;
    margin-bottom: 5px;
}
.info-box .info-box-detail {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
}
.stat-card {
    padding: 20px;
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}
.stat-card h3 {
    margin-bottom: 0.5rem;
    font-weight: 700;
}
.stat-card p {
    margin-bottom: 0;
    color: #6c757d;
}
@media print {
    .btn, .card-header, .alert {
        display: none !important;
    }
    .card {
        border: 1px solid #000 !important;
    }
}
CSS;

$this->registerCss($css);