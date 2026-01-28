<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\AportesSemanales;
use app\models\Asistencia;

$this->title = 'Mi Información de Atleta';
$this->params['breadcrumbs'][] = $this->title;

// Obtener el ID del usuario actual
$user_id = Yii::$app->user->id;

// Buscar al atleta por el user_id
$atleta = \app\models\AtletasRegistro::find()
    ->where(['user_id' => $user_id])
    ->andWhere(['eliminado' => false])
    ->with(['representante', 'escuela', 'categoria'])
    ->one();

// Si no se encuentra el atleta
if (!$atleta) {
    echo '<div class="alert alert-danger">No se encontró información del atleta.</div>';
    return;
}

// Obtener datos de aportes del atleta
$aportes = AportesSemanales::find()
    ->where(['atleta_id' => $atleta->id])
    ->andWhere(['eliminado' => false])
    ->orderBy(['fecha' => SORT_DESC])
    ->all();

// Calcular deuda total
$deudaTotal = 0;
$deudasPendientes = [];
foreach ($aportes as $aporte) {
    if (!$aporte->pagado) {
        $deudaTotal += $aporte->monto;
        $deudasPendientes[] = $aporte;
    }
}

// Obtener asistencias recientes
$asistenciasRecientes = Asistencia::find()
    ->where(['id_atleta' => $atleta->id])
    ->andWhere(['eliminado' => false])
    ->orderBy(['fecha' => SORT_DESC])
    ->limit(10)
    ->all();

// Calcular porcentaje de asistencia del último mes
$fechaInicio = date('Y-m-01');
$fechaFin = date('Y-m-t');

$asistenciasMes = Asistencia::find()
    ->where(['id_atleta' => $atleta->id])
    ->andWhere(['>=', 'fecha', $fechaInicio])
    ->andWhere(['<=', 'fecha', $fechaFin])
    ->andWhere(['eliminado' => false])
    ->all();

$totalAsistenciasMes = count($asistenciasMes);
$asistenciasCount = 0;
foreach ($asistenciasMes as $asistencia) {
    if ($asistencia->asistio) {
        $asistenciasCount++;
    }
}

$porcentajeAsistencia = $totalAsistenciasMes > 0 ? 
    round(($asistenciasCount / $totalAsistenciasMes) * 100, 2) : 0;

// Tasa de cambio aproximada (puedes cambiar esto por una API real)
$tasaCambio = 36.5; // 1 USD = 36.5 Bs
$deudaDolares = $deudaTotal / $tasaCambio;
?>

<div class="reportes-atletas">
    <div class="container-fluid">
        <!-- Título principal -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-user-circle mr-2"></i> 
                            Información Personal del Atleta
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 1: Información del Atleta -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-id-card mr-2"></i> 
                            Datos del Atleta
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-user mr-1"></i> Nombre Completo:</strong><br>
                                <?= Html::encode($atleta->p_nombre . ' ' . 
                                    ($atleta->s_nombre ? $atleta->s_nombre . ' ' : '') . 
                                    $atleta->p_apellido . ' ' . 
                                    ($atleta->s_apellido ? $atleta->s_apellido : '')) ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-fingerprint mr-1"></i> Cédula:</strong><br>
                                <code class="bg-light p-1 rounded"><?= Html::encode($atleta->identificacion) ?></code>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-phone mr-1"></i> Teléfono:</strong><br>
                                <?= Html::encode($atleta->cell ?: 'No registrado') ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-envelope mr-1"></i> Correo:</strong><br>
                                <?= Html::encode($atleta->email ?: 'No registrado') ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-school mr-1"></i> Escuela:</strong><br>
                                <?= $atleta->escuela ? Html::encode($atleta->escuela->nombre) : 'No asignada' ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-tag mr-1"></i> Categoría:</strong><br>
                                <?= $atleta->categoria ? 
                                    Html::encode($atleta->categoria->nombre_venezuela) : 
                                    Html::encode($atleta->categoriaCalculada ?: 'No asignada') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 2: Información del Representante -->
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-tie mr-2"></i> 
                            Datos del Representante
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($atleta->representante): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-user-tie mr-1"></i> Nombre:</strong><br>
                                    <?= Html::encode($atleta->representante->p_nombre . ' ' . 
                                        ($atleta->representante->s_nombre ? $atleta->representante->s_nombre . ' ' : '') . 
                                        $atleta->representante->p_apellido . ' ' . 
                                        ($atleta->representante->s_apellido ? $atleta->representante->s_apellido : '')) ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-fingerprint mr-1"></i> Cédula:</strong><br>
                                    <code class="bg-light p-1 rounded"><?= Html::encode($atleta->representante->identificacion) ?></code>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-phone mr-1"></i> Teléfono:</strong><br>
                                    <?= Html::encode($atleta->representante->cell ?: 'No registrado') ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-envelope mr-1"></i> Correo:</strong><br>
                                    <?= Html::encode($atleta->representante->email ?: 'No registrado') ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-home mr-1"></i> Dirección:</strong><br>
                                    <?= Html::encode($atleta->representante->direccion ?: 'No registrada') ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-calendar-alt mr-1"></i> Relación:</strong><br>
                                    <?= Html::encode($atleta->representante->parentesco ?: 'No especificada') ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Sin Representante Asignado</h5>
                                <p class="text-muted">No tienes un representante registrado en el sistema.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 3: Estado de Pagos/Aportes -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card <?= $deudaTotal > 0 ? 'border-danger' : 'border-success' ?>">
                    <div class="card-header <?= $deudaTotal > 0 ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-money-bill-wave mr-2"></i> 
                            Estado de Pagos y Aportes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Estado General -->
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded <?= $deudaTotal > 0 ? 'bg-danger-light' : 'bg-success-light' ?>">
                                    <h2 class="<?= $deudaTotal > 0 ? 'text-danger' : 'text-success' ?>">
                                        <i class="fas fa-<?= $deudaTotal > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                                    </h2>
                                    <h4 class="<?= $deudaTotal > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= $deudaTotal > 0 ? 'EN MORA' : 'AL DÍA' ?>
                                    </h4>
                                    <p class="text-muted">Estado de Pagos</p>
                                </div>
                            </div>

                            <!-- Montos en Bolívares -->
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3 class="<?= $deudaTotal > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= Yii::$app->formatter->asCurrency($deudaTotal) ?>
                                    </h3>
                                    <h6 class="text-muted">Total Deuda (Bs)</h6>
                                    <?php if ($deudaTotal > 0): ?>
                                        <small class="text-muted">
                                            <?= count($deudasPendientes) ?> aporte(s) pendiente(s)
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Montos en Dólares -->
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3 class="<?= $deudaTotal > 0 ? 'text-danger' : 'text-success' ?>">
                                        $<?= number_format($deudaDolares, 2) ?> USD
                                    </h3>
                                    <h6 class="text-muted">Total Deuda (USD)</h6>
                                    <small class="text-muted">
                                        Tasa: 1 USD = <?= $tasaCambio ?> Bs
                                    </small>
                                </div>
                            </div>

                            <!-- Detalle de deudas pendientes -->
                            <?php if (!empty($deudasPendientes)): ?>
                                <div class="col-md-12 mt-3">
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-list mr-2"></i>Detalle de Aportes Pendientes:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Concepto</th>
                                                        <th>Monto (Bs)</th>
                                                        <th>Monto (USD)</th>
                                                        <th>Vencimiento</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($deudasPendientes as $deuda): ?>
                                                        <tr>
                                                            <td><?= Yii::$app->formatter->asDate($deuda->fecha, 'php:d/m/Y') ?></td>
                                                            <td><?= Html::encode($deuda->concepto ?: 'Aporte Semanal') ?></td>
                                                            <td class="text-danger"><?= Yii::$app->formatter->asCurrency($deuda->monto) ?></td>
                                                            <td>$<?= number_format($deuda->monto / $tasaCambio, 2) ?></td>
                                                            <td><?= Yii::$app->formatter->asDate($deuda->fecha_vencimiento, 'php:d/m/Y') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-md-12 mt-3">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        ¡Felicidades! No tienes aportes pendientes.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 4: Asistencias -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-check mr-2"></i> 
                            Control de Asistencias
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-tools fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Módulo en Desarrollo</h5>
                                    <p class="mb-0">El sistema de control de asistencias se encuentra en desarrollo y será implementado próximamente.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Información preliminar de asistencias -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3><?= $porcentajeAsistencia ?>%</h3>
                                    <h6 class="text-muted">Asistencia Este Mes</h6>
                                    <small class="text-muted">
                                        <?= $asistenciasCount ?> de <?= $totalAsistenciasMes ?> días
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3><?= count($asistenciasRecientes) ?></h3>
                                    <h6 class="text-muted">Registros Recientes</h6>
                                    <small class="text-muted">Últimos 10 registros</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3><?= date('F Y') ?></h3>
                                    <h6 class="text-muted">Mes Actual</h6>
                                    <small class="text-muted">Período de evaluación</small>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje de funcionalidades futuras -->
                        <div class="alert alert-secondary">
                            <h6><i class="fas fa-lightbulb mr-2"></i>Próximas Funcionalidades:</h6>
                            <ul class="mb-0">
                                <li>Gráficos de asistencia mensual y anual</li>
                                <li>Reporte detallado de inasistencias</li>
                                <li>Justificación de faltas en línea</li>
                                <li>Notificaciones de asistencia para representantes</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 5: Estadísticas -->
        <div class="row">
            <div class="col-md-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line mr-2"></i> 
                            Estadísticas y Rendimiento
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-rocket fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Módulo en Construcción</h5>
                                    <p class="mb-0">El sistema de estadísticas y rendimiento está siendo desarrollado y estará disponible próximamente.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Información preliminar de estadísticas -->
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3><?= $atleta->edad ?> años</h3>
                                    <h6 class="text-muted">Edad Actual</h6>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3><?= $atleta->peso ?: 'N/A' ?> kg</h3>
                                    <h6 class="text-muted">Peso Actual</h6>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3><?= $atleta->altura ?: 'N/A' ?> cm</h3>
                                    <h6 class="text-muted">Altura</h6>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 rounded bg-light">
                                    <h3><?= $atleta->sexo == 'M' ? 'Masculino' : ($atleta->sexo == 'F' ? 'Femenino' : 'No especificado') ?></h3>
                                    <h6 class="text-muted">Género</h6>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje de funcionalidades futuras -->
                        <div class="alert alert-light">
                            <h6><i class="fas fa-cogs mr-2"></i>Estadísticas que Pronto Dispondrás:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul>
                                        <li>Progreso de rendimiento físico</li>
                                        <li>Comparativas por categoría</li>
                                        <li>Histórico de competencias</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul>
                                        <li>Gráficos de evolución</li>
                                        <li>Metas y objetivos</li>
                                        <li>Recomendaciones personalizadas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie de página con información adicional -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card border-secondary">
                    <div class="card-body text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Información actualizada al: <?= date('d/m/Y H:i:s') ?> | 
                            Usuario: <?= Html::encode(Yii::$app->user->identity->username ?? 'Atleta') ?> |
                            Sesión iniciada desde: <?= Yii::$app->request->userIP ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
.bg-danger-light {
    background-color: #f8d7da !important;
    border: 1px solid #f5c6cb;
}
.bg-success-light {
    background-color: #d4edda !important;
    border: 1px solid #c3e6cb;
}
.alert h5 {
    margin-bottom: 0.5rem;
}
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}
.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
</style>