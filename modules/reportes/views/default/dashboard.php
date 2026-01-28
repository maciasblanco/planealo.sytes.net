<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard de Reportes';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="reportes-default-dashboard">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-chart-line mr-2"></i> 
                            Sistema de Reportes - GED
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="lead">Bienvenido al módulo de reportes. Seleccione una opción según su rol:</p>
                        
                        <div class="row mt-4">
                            <?php if ($esAdmin || $esRepresentante): ?>
                            <!-- Opciones para Administradores y Representantes -->
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-users mr-2"></i>Reportes de Atletas
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Visualice y gestione la información de los atletas.</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Información general</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Estadísticas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Asistencias</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Deudas pendientes</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="<?= Url::to(['reportes/atletas']) ?>" 
                                           class="btn btn-success btn-block">
                                            <i class="fas fa-arrow-right mr-2"></i>Acceder
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-calendar-check mr-2"></i>Reportes de Asistencias
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Consulte el registro de asistencias de los atletas.</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-info mr-2"></i>Asistencias por fecha</li>
                                            <li><i class="fas fa-check text-info mr-2"></i>Porcentajes mensuales</li>
                                            <li><i class="fas fa-check text-info mr-2"></i>Filtros avanzados</li>
                                            <li><i class="fas fa-check text-info mr-2"></i>Exportar datos</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="<?= Url::to(['reportes/asistencias']) ?>" 
                                           class="btn btn-info btn-block">
                                            <i class="fas fa-arrow-right mr-2"></i>Acceder
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-warning">
                                    <div class="card-header bg-warning text-white">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-money-bill-wave mr-2"></i>Reportes de Deudas
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Gestione y consulte las deudas pendientes.</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-warning mr-2"></i>Deudas por atleta</li>
                                            <li><i class="fas fa-check text-warning mr-2"></i>Histórico de pagos</li>
                                            <li><i class="fas fa-check text-warning mr-2"></i>Próximos vencimientos</li>
                                            <li><i class="fas fa-check text-warning mr-2"></i>Reportes para cobranza</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="<?= Url::to(['reportes/deudas-pendientes']) ?>" 
                                           class="btn btn-warning btn-block">
                                            <i class="fas fa-arrow-right mr-2"></i>Acceder
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($esAtleta): ?>
                            <!-- Opciones específicas para Atletas -->
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-user mr-2"></i>Mi Información
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Consulte su información personal y estadísticas.</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-primary mr-2"></i>Datos personales</li>
                                            <li><i class="fas fa-check text-primary mr-2"></i>Mis asistencias</li>
                                            <li><i class="fas fa-check text-primary mr-2"></i>Mi historial de aportes</li>
                                            <li><i class="fas fa-check text-primary mr-2"></i>Mis deudas pendientes</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="<?= Url::to(['reportes/estadisticas-atleta']) ?>" 
                                           class="btn btn-primary btn-block">
                                            <i class="fas fa-arrow-right mr-2"></i>Ver mi información
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-chart-bar mr-2"></i>Mis Estadísticas
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Visualice sus estadísticas y progreso deportivo.</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-chart-line text-success mr-2"></i>Gráficos de asistencia</li>
                                            <li><i class="fas fa-chart-pie text-success mr-2"></i>Distribución de pagos</li>
                                            <li><i class="fas fa-calendar-alt text-success mr-2"></i>Histórico mensual</li>
                                            <li><i class="fas fa-trophy text-success mr-2"></i>Logros y metas</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="<?= Url::to(['reportes/estadisticas-atleta']) ?>" 
                                           class="btn btn-success btn-block">
                                            <i class="fas fa-chart-bar mr-2"></i>Ver estadísticas
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Panel de información del usuario -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-info-circle mr-2"></i>Información del Sistema
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6><i class="fas fa-user-tag mr-2"></i>Su Rol:</h6>
                                                <div class="alert alert-info py-2">
                                                    <?php if ($esAdmin): ?>
                                                        <strong>Administrador</strong> - Acceso completo al sistema
                                                    <?php elseif ($esRepresentante): ?>
                                                        <strong>Representante</strong> - Gestión de atletas representados
                                                    <?php elseif ($esAtleta): ?>
                                                        <strong>Atleta</strong> - Consulta de información personal
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <h6><i class="fas fa-calendar-alt mr-2"></i>Período Actual:</h6>
                                                <div class="alert alert-light py-2">
                                                    <strong><?= date('F Y') ?></strong>
                                                    <br>
                                                    <small>Generado el: <?= date('d/m/Y H:i:s') ?></small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <h6><i class="fas fa-cog mr-2"></i>Acciones Rápidas:</h6>
                                                <div class="btn-group-vertical w-100">
                                                    <a href="<?= Url::to(['site/index']) ?>" 
                                                       class="btn btn-outline-primary btn-sm mb-1">
                                                        <i class="fas fa-home mr-2"></i>Inicio
                                                    </a>
                                                    <a href="<?= Url::to(['reportes/exportar-pdf', 'reporte' => 'resumen']) ?>" 
                                                       class="btn btn-outline-danger btn-sm mb-1">
                                                        <i class="fas fa-file-pdf mr-2"></i>Exportar PDF
                                                    </a>
                                                    <button onclick="window.print()" 
                                                            class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-print mr-2"></i>Imprimir
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>