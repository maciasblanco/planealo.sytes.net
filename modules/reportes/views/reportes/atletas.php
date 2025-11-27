<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = $esRepresentante ? 'Mis Atletas Representados' : 'Mi Información';
$this->params['breadcrumbs'][] = $this->title;

// Registrar el asset del módulo de reportes para cargar CSS y JS
\app\assets\AppAsset::register($this);
?>

<div class="reportes-atletas">
    <div class="container-fluid">
        <!-- Información del Representante -->
        <?php if ($esRepresentante && $representante): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-user-tie mr-2"></i> 
                            Información del Representante
                        </h4>
                        <span class="badge badge-light">
                            <i class="fas fa-users mr-1"></i>
                            <?= count($datosAtletas) ?> atleta(s)
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong><i class="fas fa-id-card mr-1"></i>Nombre:</strong><br>
                                <?= Html::encode($representante->p_nombre . ' ' . 
                                    ($representante->s_nombre ? $representante->s_nombre . ' ' : '') . 
                                    $representante->p_apellido . ' ' . 
                                    ($representante->s_apellido ? $representante->s_apellido : '')) ?>
                            </div>
                            <div class="col-md-2">
                                <strong><i class="fas fa-fingerprint mr-1"></i>Cédula:</strong><br>
                                <?= Html::encode($representante->identificacion) ?>
                            </div>
                            <div class="col-md-2">
                                <strong><i class="fas fa-phone mr-1"></i>Teléfono:</strong><br>
                                <?= Html::encode($representante->cell) ?>
                            </div>
                            <div class="col-md-3">
                                <strong><i class="fas fa-school mr-1"></i>Escuela:</strong><br>
                                <?= $representante->escuela ? Html::encode($representante->escuela->nombre) : 'No asignada' ?>
                            </div>
                            <div class="col-md-2">
                                <strong><i class="fas fa-calendar-day mr-1"></i>Actualizado:</strong><br>
                                <?= date('d/m/Y H:i') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Panel de Estadísticas Rápidas -->
        <?php if (!empty($datosAtletas) && $esRepresentante): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="info-box bg-success" onclick="filtrarPorEstado('al-dia')">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Atletas al Día</span>
                        <span class="info-box-number">
                            <?= count(array_filter($datosAtletas, function($d) { return $d['deudaPendiente'] == 0; })) ?>
                        </span>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= count($datosAtletas) > 0 ? (count(array_filter($datosAtletas, function($d) { return $d['deudaPendiente'] == 0; })) / count($datosAtletas)) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning" onclick="filtrarPorEstado('con-deuda')">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Con Deuda</span>
                        <span class="info-box-number">
                            <?= count(array_filter($datosAtletas, function($d) { return $d['deudaPendiente'] > 0; })) ?>
                        </span>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= count($datosAtletas) > 0 ? (count(array_filter($datosAtletas, function($d) { return $d['deudaPendiente'] > 0; })) / count($datosAtletas)) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-info" onclick="filtrarPorEstado('buena-asistencia')">
                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Buena Asistencia</span>
                        <span class="info-box-number">
                            <?= count(array_filter($datosAtletas, function($d) { return $d['porcentajeAsistencia'] >= 70; })) ?>
                        </span>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= count($datosAtletas) > 0 ? (count(array_filter($datosAtletas, function($d) { return $d['porcentajeAsistencia'] >= 70; })) / count($datosAtletas)) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-danger" onclick="filtrarPorEstado('baja-asistencia')">
                    <span class="info-box-icon"><i class="fas fa-user-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Baja Asistencia</span>
                        <span class="info-box-number">
                            <?= count(array_filter($datosAtletas, function($d) { return $d['porcentajeAsistencia'] < 70; })) ?>
                        </span>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= count($datosAtletas) > 0 ? (count(array_filter($datosAtletas, function($d) { return $d['porcentajeAsistencia'] < 70; })) / count($datosAtletas)) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabla Principal de Atletas -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i> 
                            <?= $esRepresentante ? 'Atletas Representados' : 'Mi Información' ?>
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-primary">
                                <i class="fas fa-users mr-1"></i>
                                <?= count($datosAtletas) ?> registro(s)
                            </span>
                            <?php if ($esRepresentante): ?>
                            <button class="btn btn-sm btn-outline-primary ml-2" onclick="exportarTabla()">
                                <i class="fas fa-download mr-1"></i>Exportar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($datosAtletas)): ?>
                            <div class="text-center p-5">
                                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No hay atletas para mostrar</h4>
                                <p class="text-muted">
                                    <?= $esRepresentante ? 
                                        'No tiene atletas representados actualmente.' : 
                                        'No se encontró su información de atleta.' ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped mb-0" id="tabla-atletas">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="16%">Atleta</th>
                                            <th width="8%" class="text-center">Cédula</th>
                                            <th width="6%" class="text-center">Edad</th>
                                            <th width="12%">Categoría</th>
                                            <th width="14%">Asistencias (Este Mes)</th>
                                            <th width="8%" class="text-center">% Asist.</th>
                                            <th width="12%" class="text-center">Deuda Pendiente</th>
                                            <th width="12%" class="text-center">Última Asistencia</th>
                                            <th width="12%" class="text-center">Estado General</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($datosAtletas as $datos): 
                                            $atleta = $datos['atleta'];
                                            // Determinar clase de la fila según estado
                                            $claseFila = '';
                                            $tooltip = '';
                                            if ($datos['deudaPendiente'] > 0 && $datos['porcentajeAsistencia'] < 70) {
                                                $claseFila = 'table-danger';
                                                $tooltip = 'title="Deuda pendiente y baja asistencia"';
                                            } elseif ($datos['deudaPendiente'] > 0) {
                                                $claseFila = 'table-warning';
                                                $tooltip = 'title="Deuda pendiente"';
                                            } elseif ($datos['porcentajeAsistencia'] < 70) {
                                                $claseFila = 'table-warning';
                                                $tooltip = 'title="Baja asistencia"';
                                            }
                                        ?>
                                            <tr class="<?= $claseFila ?>" <?= $tooltip ?> data-estado="<?= 
                                                $datos['deudaPendiente'] > 0 ? 'con-deuda' : 'al-dia' ?> <?= 
                                                $datos['porcentajeAsistencia'] >= 70 ? 'buena-asistencia' : 'baja-asistencia' ?>">
                                                <!-- Columna Atleta -->
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="mr-3">
                                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-weight-bold text-primary">
                                                                <?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?>
                                                            </div>
                                                            <small class="text-muted">
                                                                <i class="fas fa-school mr-1"></i>
                                                                <?= $atleta->escuela ? Html::encode($atleta->escuela->nombre) : 'Sin escuela' ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                
                                                <!-- Cédula -->
                                                <td class="text-center">
                                                    <code class="bg-light p-1 rounded"><?= Html::encode($atleta->identificacion) ?></code>
                                                </td>
                                                
                                                <!-- Edad -->
                                                <td class="text-center">
                                                    <span class="badge badge-info badge-status">
                                                        <?= $atleta->edad ?> años
                                                    </span>
                                                </td>
                                                
                                                <!-- Categoría -->
                                                <td>
                                                    <span class="badge badge-secondary badge-status">
                                                        <?= $atleta->categoria ? 
                                                            Html::encode($atleta->categoria->nombre_venezuela) : 
                                                            Html::encode($atleta->categoriaCalculada) ?>
                                                    </span>
                                                </td>
                                                
                                                <!-- Asistencias -->
                                                <td>
                                                    <div class="progress-bar-container mb-1">
                                                        <div class="progress" style="height: 24px;">
                                                            <div class="progress-bar bg-success" 
                                                                 role="progressbar" 
                                                                 style="width: <?= $datos['porcentajeAsistencia'] ?>%"
                                                                 aria-valuenow="<?= $datos['porcentajeAsistencia'] ?>">
                                                            </div>
                                                            <div class="progress-bar bg-danger" 
                                                                 role="progressbar" 
                                                                 style="width: <?= 100 - $datos['porcentajeAsistencia'] ?>%"
                                                                 aria-valuenow="<?= 100 - $datos['porcentajeAsistencia'] ?>">
                                                            </div>
                                                        </div>
                                                        <div class="progress-text">
                                                            <?= $datos['asistenciasCount'] ?>/<?= $datos['totalAsistencias'] ?>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted d-block text-center">
                                                        <?= $datos['asistenciasCount'] ?> asist. | <?= $datos['inasistenciasCount'] ?> faltas
                                                    </small>
                                                </td>
                                                
                                                <!-- Porcentaje Asistencia -->
                                                <td class="text-center">
                                                    <span class="badge badge-<?= 
                                                        $datos['porcentajeAsistencia'] >= 90 ? 'success' : 
                                                        ($datos['porcentajeAsistencia'] >= 70 ? 'warning' : 'danger') 
                                                    ?> badge-status">
                                                        <?= $datos['porcentajeAsistencia'] ?>%
                                                    </span>
                                                </td>
                                                
                                                <!-- Deuda Pendiente -->
                                                <td class="text-center">
                                                    <?php if ($datos['deudaPendiente'] > 0): ?>
                                                        <div class="text-danger font-weight-bold">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                                            <?= Yii::$app->formatter->asCurrency($datos['deudaPendiente']) ?>
                                                        </div>
                                                        <?php if ($datos['proximoAporte']): ?>
                                                            <small class="text-muted">
                                                                Vence: <?= Yii::$app->formatter->asDate($datos['proximoAporte']->fecha, 'php:d/m/Y') ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-success">
                                                            <i class="fas fa-check-circle mr-1"></i>
                                                            Al día
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <!-- Última Asistencia -->
                                                <td class="text-center">
                                                    <?php if ($datos['ultimaAsistencia']): ?>
                                                        <div class="<?= $datos['ultimaAsistencia']->asistio ? 'text-success' : 'text-danger' ?>">
                                                            <?= Yii::$app->formatter->asDate($datos['ultimaAsistencia']->fecha, 'php:d/m/Y') ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= $datos['ultimaAsistencia']->asistio ? '✅ Asistió' : '❌ Faltó' ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin registro</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <!-- Estado General -->
                                                <td class="text-center">
                                                    <?php
                                                    $estado = 'success';
                                                    $texto = 'Excelente';
                                                    $icono = 'check-circle';
                                                    $descripcion = 'Al día y buena asistencia';
                                                    
                                                    if ($datos['deudaPendiente'] > 0 && $datos['porcentajeAsistencia'] < 70) {
                                                        $estado = 'danger';
                                                        $texto = 'Crítico';
                                                        $icono = 'exclamation-triangle';
                                                        $descripcion = 'Deuda y baja asistencia';
                                                    } elseif ($datos['deudaPendiente'] > 0) {
                                                        $estado = 'warning';
                                                        $texto = 'Atención';
                                                        $icono = 'exclamation-triangle';
                                                        $descripcion = 'Deuda pendiente';
                                                    } elseif ($datos['porcentajeAsistencia'] < 70) {
                                                        $estado = 'warning';
                                                        $texto = 'Regular';
                                                        $icono = 'exclamation-circle';
                                                        $descripcion = 'Baja asistencia';
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?= $estado ?> badge-status" title="<?= $descripcion ?>">
                                                        <i class="fas fa-<?= $icono ?> mr-1"></i>
                                                        <?= $texto ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Información actualizada al: <?= date('d/m/Y H:i') ?>
                                </small>
                            </div>
                            <div class="col-md-6 text-right">
                                <?php if ($esRepresentante): ?>
                                    <span class="badge badge-primary">
                                        <i class="fas fa-user-tie mr-1"></i>
                                        Modo Representante
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-info">
                                        <i class="fas fa-user mr-1"></i>
                                        Modo Atleta
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen de Totales -->
        <?php if (!empty($datosAtletas) && $esRepresentante): ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white py-2">
                        <h6 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>Resumen General</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <small class="text-muted">Deuda Total</small><br>
                                <strong class="text-danger"><?= Yii::$app->formatter->asCurrency(array_sum(array_column($datosAtletas, 'deudaPendiente'))) ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Promedio Asistencia</small><br>
                                <strong class="text-primary"><?= round(array_sum(array_column($datosAtletas, 'porcentajeAsistencia')) / count($datosAtletas), 1) ?>%</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Total Asistencias Mes</small><br>
                                <strong class="text-success"><?= array_sum(array_column($datosAtletas, 'asistenciasCount')) ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Total Inasistencias</small><br>
                                <strong class="text-warning"><?= array_sum(array_column($datosAtletas, 'inasistenciasCount')) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>