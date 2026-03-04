<?php

use yii\helpers\Html;

$this->title = 'Estadísticas del Atleta';
$this->params['breadcrumbs'][] = $this->title;

$deudaTotal = array_sum(array_column($deudasPendientes, 'monto'));
$deudaDolares = $deudaTotal / $tasaCambio;

$mesActual = date('Y-m');
$estadisticasMes = $estadisticas['mensual'][$mesActual] ?? null;
$asistenciasMes = $estadisticasMes ? $estadisticasMes['asistencias'] : ['total' => 0, 'asistidas' => 0, 'porcentaje' => 0];
$aportesMes = $estadisticasMes ? $estadisticasMes['aportes'] : ['total' => 0, 'pagados' => 0, 'pendientes' => 0];

$totales = $estadisticas['totales'];

// Función auxiliar para obtener nombre completo del representante
function nombreCompletoRepresentante($rep) {
    if (!$rep) return '';
    $nombre = $rep->p_nombre;
    if (!empty($rep->s_nombre)) $nombre .= ' ' . $rep->s_nombre;
    $apellido = $rep->p_apellido;
    if (!empty($rep->s_apellido)) $apellido .= ' ' . $rep->s_apellido;
    return trim($nombre . ' ' . $apellido);
}
?>

<div class="reporte-atletas">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-user-circle mr-2"></i> 
                            Estadísticas de <?= Html::encode($atleta->nombreCompleto) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-info h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-id-card mr-2"></i> Datos del Atleta</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><th>Nombre completo:</th><td><?= Html::encode($atleta->nombreCompleto) ?></td></tr>
                            <tr><th>Cédula:</th><td><?= Html::encode($atleta->identificacion) ?></td></tr>
                            <tr><th>Fecha nacimiento:</th><td><?= Yii::$app->formatter->asDate($atleta->fn) ?> (<?= $atleta->edad ?> años)</td></tr>
                            <tr><th>Teléfono:</th><td><?= Html::encode($atleta->cell ?: 'No registrado') ?></td></tr>
                            <tr><th>Escuela:</th><td><?= $atleta->escuela ? Html::encode($atleta->escuela->nombre) : 'No asignada' ?></td></tr>
                            <tr><th>Categoría:</th><td><?= $atleta->categoria ? Html::encode($atleta->categoria->nombre_venezuela) : ($atleta->categoriaCalculada ?: 'No asignada') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning h-100">
                    <div class="card-header bg-warning text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-user-tie mr-2"></i> 
                            <?= $esPersonalAutorizado ? 'Consultado por' : 'Datos del Representante' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($esPersonalAutorizado): ?>
                            <?php if ($usuarioActual): ?>
                                <table class="table table-sm table-borderless">
                                    <tr><th>Usuario:</th><td><?= Html::encode($usuarioActual->username) ?></td></tr>
                                    <tr><th>Rol(es):</th><td>
                                        <?php
                                        $roles = array_keys(Yii::$app->authManager->getRolesByUser($usuarioActual->id));
                                        echo Html::encode(implode(', ', $roles));
                                        ?>
                                    </td></tr>
                                </table>
                            <?php else: ?>
                                <p class="text-muted text-center">No se pudo identificar el usuario.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($atleta->representante): ?>
                                <table class="table table-sm table-borderless">
                                    <tr><th>Nombre completo:</th><td><?= Html::encode(nombreCompletoRepresentante($atleta->representante)) ?></td></tr>
                                    <tr><th>Cédula:</th><td><?= Html::encode($atleta->representante->identificacion) ?></td></tr>
                                    <tr><th>Teléfono:</th><td><?= Html::encode($atleta->representante->cell ?: 'No registrado') ?></td></tr>
                                </table>
                            <?php else: ?>
                                <p class="text-muted text-center">No hay representante asignado.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card <?= $deudaTotal > 0 ? 'border-danger' : 'border-success' ?>">
                    <div class="card-header <?= $deudaTotal > 0 ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                        <h5 class="card-title mb-0"><i class="fas fa-money-bill-wave mr-2"></i> Estado de Pagos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded <?= $deudaTotal > 0 ? 'bg-danger-light' : 'bg-success-light' ?>">
                                    <h2 class="<?= $deudaTotal > 0 ? 'text-danger' : 'text-success' ?>">
                                        <i class="fas fa-<?= $deudaTotal > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                                    </h2>
                                    <h4><?= $deudaTotal > 0 ? 'EN MORA' : 'AL DÍA' ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3 class="<?= $deudaTotal > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= Yii::$app->formatter->asCurrency($deudaTotal) ?>
                                    </h3>
                                    <h6 class="text-muted">Total Deuda (Bs)</h6>
                                    <?php if ($deudaTotal > 0): ?>
                                        <small><?= count($deudasPendientes) ?> aporte(s) pendiente(s)</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3 class="<?= $deudaTotal > 0 ? 'text-danger' : 'text-success' ?>">
                                        $<?= number_format($deudaDolares, 2) ?>
                                    </h3>
                                    <h6 class="text-muted">Total Deuda (USD)</h6>
                                    <small>Tasa: 1 USD = <?= number_format($tasaCambio, 2) ?> Bs</small>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($deudasPendientes)): ?>
                        <div class="mt-3">
                            <h6>Detalle de aportes pendientes:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Fecha Quincena</th>
                                            <th>N° Quincena</th>
                                            <th>Monto (Bs)</th>
                                            <th>Monto (USD)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deudasPendientes as $deuda): ?>
                                        <tr>
                                            <td><?= Yii::$app->formatter->asDate($deuda->fecha_quincena) ?></td>
                                            <td class="text-center"><?= $deuda->numero_quincena ?></td>
                                            <td class="text-danger"><?= Yii::$app->formatter->asCurrency($deuda->monto) ?></td>
                                            <td>$<?= number_format($deuda->monto / $tasaCambio, 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="fas fa-check-circle mr-2"></i> No hay deudas pendientes.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-check mr-2"></i> Asistencias - Mes actual</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <h3><?= $asistenciasMes['asistidas'] ?></h3>
                                <small>Asistió</small>
                            </div>
                            <div class="col-4">
                                <h3><?= $asistenciasMes['total'] - $asistenciasMes['asistidas'] ?></h3>
                                <small>Faltó</small>
                            </div>
                            <div class="col-4">
                                <h3><?= $asistenciasMes['porcentaje'] ?>%</h3>
                                <small>Asistencia</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line mr-2"></i> Totales históricos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h3><?= $totales['asistencias'] ?></h3>
                                <small>Asistencias totales</small>
                            </div>
                            <div class="col-6">
                                <h3><?= Yii::$app->formatter->asCurrency($totales['aportes']) ?></h3>
                                <small>Aportes totales (Bs)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-history mr-2"></i> Historial últimos 6 meses</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mes</th>
                                        <th>Asistencias</th>
                                        <th>% Asistencia</th>
                                        <th>Aportes (Bs)</th>
                                        <th>Pagado</th>
                                        <th>Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_reverse($estadisticas['mensual'], true) as $mes => $datos): ?>
                                    <tr>
                                        <td><?= Yii::$app->formatter->asDate($mes . '-01', 'MMM yyyy') ?></td>
                                        <td><?= $datos['asistencias']['asistidas'] . '/' . $datos['asistencias']['total'] ?></td>
                                        <td><?= $datos['asistencias']['porcentaje'] ?>%</td>
                                        <td><?= Yii::$app->formatter->asCurrency($datos['aportes']['total']) ?></td>
                                        <td class="text-success"><?= Yii::$app->formatter->asCurrency($datos['aportes']['pagados']) ?></td>
                                        <td class="text-danger"><?= Yii::$app->formatter->asCurrency($datos['aportes']['pendientes']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-danger-light { background-color: #f8d7da; border: 1px solid #f5c6cb; }
.bg-success-light { background-color: #d4edda; border: 1px solid #c3e6cb; }
</style>