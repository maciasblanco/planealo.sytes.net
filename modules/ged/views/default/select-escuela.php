<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use app\assets\AppAsset;

AppAsset::register($this);

/** @var yii\web\View $this */
/** @var app\models\Escuela[] $escuelas */
/** @var int $totalEscuelas */
/** @var int $totalPreRegistro */
/** @var int $totalAprobadas */
/** @var int $totalPendientes */
/** @var string $cedula */

$this->title = 'Seleccionar Escuela - Sistema GED';
$this->params['breadcrumbs'][] = $this->title;

// Obtener información de sesión
$session = Yii::$app->session;
$escuela_actual = $session->get('nombre_escuela');
$id_escuela_actual = $session->get('id_escuela');
?>

<div class="ged-default-select-escuela">
    <!-- Header del Sistema -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="text-center">
                <h1 class="ged-text-white">
                    <i class="fas fa-school"></i> Sistema GED
                </h1>
                <p class="lead text-light">Gestión Escolar Deportiva - Plataforma Integral</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna Principal - Selección de Escuela -->
        <div class="col-md-8">
            <div class="card ged-card ged-animated-card">
                <div class="card-header ged-card-header-primary">
                    <h3 class="card-title ged-text-white mb-0">
                        <i class="fas fa-building ged-icon"></i> Selección de Escuela
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Estado Actual -->
                    <?php if ($escuela_actual): ?>
                        <div class="current-school-status alert-ged-success">
                            <h5><i class="fas fa-check-circle ged-icon"></i> Escuela Actualmente Seleccionada</h5>
                            <p class="mb-1"><strong><?= Html::encode($escuela_actual) ?></strong></p>
                            <small>ID: <?= $id_escuela_actual ?> | Puede cambiar de escuela en cualquier momento</small>
                        </div>
                    <?php else: ?>
                        <div class="current-school-status alert-ged-warning">
                            <h5><i class="fas fa-exclamation-triangle ged-icon"></i> No Hay Escuela Seleccionada</h5>
                            <p class="mb-0">Seleccione una escuela para comenzar a trabajar</p>
                        </div>
                    <?php endif; ?>

                    <!-- Mensajes Flash -->
                    <?php if (Yii::$app->session->hasFlash('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <i class="fas fa-check-circle ged-icon"></i>
                            <?= Yii::$app->session->getFlash('success') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (Yii::$app->session->hasFlash('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <i class="fas fa-exclamation-circle ged-icon"></i>
                            <?= Yii::$app->session->getFlash('error') ?>
                        </div>
                    <?php endif; ?>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card text-center border-end">
                                <h3 class="text-primary"><?= $totalEscuelas ?></h3>
                                <p class="text-muted mb-0">Total Escuelas</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center border-end">
                                <h3 class="text-warning"><?= $totalPreRegistro ?></h3>
                                <p class="text-muted mb-0">Pre-Registro</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center border-end">
                                <h3 class="text-info"><?= $totalPendientes ?></h3>
                                <p class="text-muted mb-0">Pendientes</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <h3 class="text-success"><?= $totalAprobadas ?></h3>
                                <p class="text-muted mb-0">Aprobadas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de Progreso de Estados -->
                    <div class="mb-4">
                        <div class="progress mb-2" style="height: 12px;">
                            <?php if ($totalEscuelas > 0): ?>
                                <div class="progress-bar bg-success" style="width: <?= ($totalAprobadas / $totalEscuelas * 100) ?>%" 
                                     title="<?= $totalAprobadas ?> Aprobadas (<?= round($totalAprobadas / $totalEscuelas * 100, 1) ?>%)"></div>
                                <div class="progress-bar bg-info" style="width: <?= ($totalPendientes / $totalEscuelas * 100) ?>%" 
                                     title="<?= $totalPendientes ?> Pendientes (<?= round($totalPendientes / $totalEscuelas * 100, 1) ?>%)"></div>
                                <div class="progress-bar bg-warning" style="width: <?= ($totalPreRegistro / $totalEscuelas * 100) ?>%" 
                                     title="<?= $totalPreRegistro ?> Pre-Registro (<?= round($totalPreRegistro / $totalEscuelas * 100, 1) ?>%)"></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Aprobadas: <?= $totalAprobadas ?></span>
                            <span>Pendientes: <?= $totalPendientes ?></span>
                            <span>Pre-Registro: <?= $totalPreRegistro ?></span>
                        </div>
                    </div>

                    <!-- Formulario de Selección -->
                    <?php if (empty($escuelas)): ?>
                        <div class="alert alert-ged-danger text-center">
                            <h4><i class="fas fa-exclamation-triangle ged-icon"></i> No Hay Escuelas Disponibles</h4>
                            <p>No se encontraron escuelas activas en el sistema.</p>
                            <div class="mt-3">
                                <?= Html::a(
                                    '<i class="fas fa-plus-circle ged-icon"></i> Registrar Primera Escuela',
                                    ['/escuela_club/escuela-registro/pre-registro'],
                                    ['class' => 'ged-btn ged-btn-success btn-lg']
                                ) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php $form = ActiveForm::begin([
                            'id' => 'select-escuela-form',
                            'action' => ['/ged/default/select-escuela'],
                        ]); ?>

                        <div class="form-group mb-4">
                            <label class="control-label ged-form-label">
                                <strong>Seleccione una Escuela para Trabajar:</strong>
                            </label>
                            <select name="id_escuela" class="form-control form-select ged-form-control" required 
                                    style="font-size: 16px; padding: 12px; height: auto;">
                                <option value="">-- Seleccionar Escuela --</option>
                                <?php foreach ($escuelas as $escuela): ?>
                                    <option value="<?= $escuela->id ?>" 
                                        <?= $id_escuela_actual == $escuela->id ? 'selected' : '' ?>>
                                        <?= Html::encode($escuela->nombre) ?>
                                        - <?= Html::encode($escuela->estado->estado ?? 'N/A') ?>
                                        <?php if ($escuela->direccion_administrativa): ?>
                                            | <?= Html::encode(substr($escuela->direccion_administrativa, 0, 30)) ?>...
                                        <?php endif; ?>
                                        <!-- Mostrar estado de registro -->
                                        <?php if ($escuela->estado_registro == 'pre_registro'): ?>
                                            <span class="badge bg-warning float-end">Pre-Registro</span>
                                        <?php elseif ($escuela->estado_registro == 'pendiente'): ?>
                                            <span class="badge bg-info float-end">Pendiente</span>
                                        <?php elseif ($escuela->estado_registro == 'aprobado'): ?>
                                            <span class="badge bg-success float-end">Aprobada</span>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted">
                                <i class="fas fa-info-circle ged-icon"></i> Seleccione la escuela con la que desea trabajar
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?= Html::submitButton(
                                    '<i class="fas fa-check ged-icon"></i> ' . 
                                    ($escuela_actual ? 'Cambiar Escuela' : 'Seleccionar Escuela'), 
                                    [
                                        'class' => 'ged-btn ged-btn-success btn-lg w-100',
                                        'name' => 'select-escuela-button',
                                        'style' => 'padding: 12px;'
                                    ]
                                ) ?>
                            </div>
                            <div class="col-md-6">
                                <?= Html::a(
                                    '<i class="fas fa-plus-circle ged-icon"></i> Registrar Nueva Escuela',
                                    ['/escuela_club/escuela-registro/pre-registro'],
                                    [
                                        'class' => 'ged-btn ged-btn-primary btn-lg w-100',
                                        'style' => 'padding: 12px;'
                                    ]
                                ) ?>
                            </div>
                        </div>

                        <?php ActiveForm::end(); ?>

                        <!-- Lista de Escuelas en Pre-Registro -->
                        <?php 
                        // Filter pre-registration schools first
                        $escuelasPreRegistro = array_filter($escuelas, function($escuela) {
                            return $escuela->estado_registro == 'pre_registro';
                        });
                        ?>
                        
                        <!-- Formulario de Búsqueda por Cédula -->
                        <div class="card mb-4">
                            <h4 class="border-bottom pb-2">
                                <i class="fas fa-clock text-warning me-2"></i> 
                                Escuelas en Pre-Registro
                                <span class="badge bg-warning"><?= count($escuelasPreRegistro) ?></span>
                            </h4>
                            <p class="text-muted">Estas escuelas necesitan completar su registro para ser aprobadas.</p>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-search ged-icon"></i> Buscar Escuela por Cédula del Encargado
                                </h5>
                                <form method="get" action="<?= Url::to(['/ged/default/select-escuela']) ?>">
                                    <div class="row g-2">
                                        <div class="col-md-8">
                                            <input type="text" 
                                                name="cedula" 
                                                class="form-control" 
                                                placeholder="Ingrese la cédula del encargado..."
                                                value="<?= Html::encode($cedula) ?>"
                                                pattern="[0-9]{7,10}"
                                                title="La cédula debe tener entre 7 y 10 dígitos">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-search me-1"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <?php if ($cedula): ?>
                                    <div class="mt-2">
                                        <a href="<?= Url::to(['/ged/default/select-escuela']) ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Limpiar búsqueda
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        
                            <?php if (!empty($escuelasPreRegistro)): ?>
                                <div class="mt-3">
                                    <div class="list-group">
                                        <?php foreach ($escuelasPreRegistro as $escuela): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-1"><?= Html::encode($escuela->nombre) ?></h5>
                                                        <p class="mb-1">
                                                            <strong>Encargado:</strong> 
                                                            <?= $escuela->encargado ? 
                                                                Html::encode($escuela->encargado->p_nombre . ' ' . $escuela->encargado->p_apellido) : 
                                                                'No asignado' ?>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Cédula:</strong> 
                                                            <?= $escuela->encargado ? 
                                                                Html::encode($escuela->encargado->identificacion) : 
                                                                'No disponible' ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <strong>Dirección:</strong> <?= Html::encode($escuela->direccion_administrativa ?? 'No disponible') ?>
                                                        </small>
                                                    </div>
                                                    <div class="ms-3">
                                                        <?= Html::a(
                                                            '<i class="fas fa-edit me-1"></i> Completar Registro',
                                                            ['/escuela_club/escuela-registro/completar-registro', 'id' => $escuela->id],
                                                            ['class' => 'btn btn-warning btn-sm']
                                                        ) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($escuela_actual): ?>
                            <div class="text-center mt-3">
                                <?= Html::a(
                                    '<i class="fas fa-play-circle ged-icon"></i> Continuar al Sistema',
                                    ['/ged/default/index'],
                                    ['class' => 'ged-btn ged-btn-secondary btn-lg']
                                ) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna Lateral - Información del Sistema -->
        <div class="col-md-4">
            <!-- Tarjeta de Información del Sistema -->
            <div class="card ged-card ged-animated-card mb-4">
                <div class="card-header ged-card-header-info">
                    <h4 class="card-title ged-text-white mb-0">
                        <i class="fas fa-info-circle ged-icon"></i> Sistema GED
                    </h4>
                </div>
                <div class="card-body">
                    <p class="ged-text-info"><strong>Gestión Escolar Deportiva</strong></p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item ged-list-item">
                            <i class="fas fa-users text-primary me-2"></i> 
                            Gestión integral de atletas
                        </li>
                        <li class="list-group-item ged-list-item">
                            <i class="fas fa-money-bill-wave text-success me-2"></i> 
                            Control de aportes semanales
                        </li>
                        <li class="list-group-item ged-list-item">
                            <i class="fas fa-clipboard-check text-warning me-2"></i> 
                            Registro de asistencia
                        </li>
                        <li class="list-group-item ged-list-item">
                            <i class="fas fa-chart-bar text-info me-2"></i> 
                            Reportes en tiempo real
                        </li>
                        <li class="list-group-item ged-list-item">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i> 
                            Geolocalización
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tarjeta de Estadísticas Rápidas -->
            <div class="card ged-card ged-animated-card">
                <div class="card-header ged-card-header-success">
                    <h4 class="card-title ged-text-white mb-0">
                        <i class="fas fa-chart-line ged-icon"></i> Estadísticas
                    </h4>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?= $totalEscuelas ?></span>
                            <span class="stat-label">Total Escuelas</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $totalPreRegistro ?></span>
                            <span class="stat-label">Pre-Registro</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $totalAprobadas ?></span>
                            <span class="stat-label">Aprobadas</span>
                        </div>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <?php if ($totalEscuelas > 0): ?>
                            <div class="progress-bar bg-success" style="width: <?= ($totalAprobadas / $totalEscuelas * 100) ?>%"></div>
                            <div class="progress-bar bg-warning" style="width: <?= ($totalPreRegistro / $totalEscuelas * 100) ?>%"></div>
                            <div class="progress-bar bg-info" style="width: <?= ($totalPendientes / $totalEscuelas * 100) ?>%"></div>
                        <?php endif; ?>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">
                        <small>
                            <i class="fas fa-exclamation-triangle ged-icon"></i>
                            <strong>Nota:</strong> Todos los datos estarán asociados a la escuela seleccionada.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Card del Marketplace - NUEVA SECCIÓN -->
            <div class="card ged-card ged-animated-card mt-4">
                <div class="card-header ged-card-header-tienda">
                    <h4 class="card-title ged-text-white mb-0">
                        <i class="fas fa-store ged-icon"></i> Marketplace Deportivo
                    </h4>
                </div>
                <div class="card-body">
                    <div class="store-promo-content">
                        <div class="store-icon-large mb-3">
                            <i class="fas fa-shopping-bag fa-2x text-primary"></i>
                        </div>
                        <h5 class="store-title">Encuentra todo para el deporte</h5>
                        <p class="store-description">
                            Equipamiento, nutrición, entrenamiento y servicios para atletas, clubes y escuelas.
                        </p>
                        
                        <div class="store-features">
                            <div class="store-feature-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Productos locales
                            </div>
                            <div class="store-feature-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Servicios deportivos
                            </div>
                            <div class="store-feature-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Transacciones seguras
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="<?= Url::to(['/tienda/marketplace']) ?>" 
                           class="ged-btn ged-btn-tienda btn-lg w-100" 
                           id="btn-marketplace">
                            <i class="fas fa-rocket ged-icon"></i> Explorar Marketplace
                        </a>
                    </div>
                    
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            ¿Quieres vender? <a href="<?= Url::to(['/tienda/registro-vendedor']) ?>">Regístrate como vendedor</a>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card ged-card ged-animated-card mt-4">
                <div class="card-header ged-card-header-warning">
                    <h4 class="card-title ged-text-white mb-0">
                        <i class="fas fa-bolt ged-icon"></i> Acciones Rápidas
                    </h4>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <?= Html::a(
                            '<i class="fas fa-list ged-icon"></i> Ver Todas las Escuelas',
                            ['/escuela_club/escuela-registro/index'],
                            ['class' => 'quick-action-btn']
                        ) ?>
                        <?= Html::a(
                            '<i class="fas fa-question-circle ged-icon"></i> Ayuda del Sistema',
                            ['site/help'],
                            ['class' => 'quick-action-btn']
                        ) ?>
                        <?php if ($escuela_actual): ?>
                            <?= Html::a(
                                '<i class="fas fa-times ged-icon"></i> Limpiar Selección',
                                ['/ged/default/clear-escuela'],
                                [
                                    'class' => 'quick-action-btn',
                                    'data' => [
                                        'confirm' => '¿Está seguro de que desea limpiar la selección de escuela?',
                                        'method' => 'post'
                                    ]
                                ]
                            ) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>