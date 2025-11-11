<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use app\assets\AppAsset;

AppAsset::register($this);

/** @var yii\web\View $this */
/** @var app\models\Escuela[] $escuelas */

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

                    <!-- Formulario de Selección -->
                    <?php if (empty($escuelas)): ?>
                        <div class="alert alert-ged-danger text-center">
                            <h4><i class="fas fa-exclamation-triangle ged-icon"></i> No Hay Escuelas Disponibles</h4>
                            <p>No se encontraron escuelas activas en el sistema.</p>
                            <div class="mt-3">
                                <?= Html::a(
                                    '<i class="fas fa-plus-circle ged-icon"></i> Registrar Primera Escuela',
                                    ['/escuela-club/escuela-pre-registro/pre-registro'],
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
                                <strong>Seleccione una Escuela:</strong>
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
                                    ['/escuela-club/escuela-pre-registro/pre-registro'],
                                    [
                                        'class' => 'ged-btn ged-btn-primary btn-lg w-100',
                                        'style' => 'padding: 12px;'
                                    ]
                                ) ?>
                            </div>
                        </div>

                        <?php ActiveForm::end(); ?>

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
                            <span class="stat-number"><?= count($escuelas) ?></span>
                            <span class="stat-label">Escuelas Activas</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $escuela_actual ? '1' : '0' ?></span>
                            <span class="stat-label">Seleccionada</span>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">
                        <small>
                            <i class="fas fa-exclamation-triangle ged-icon"></i>
                            <strong>Nota:</strong> Todos los datos estarán asociados a la escuela seleccionada.
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
                            ['/escuela-club/escuela-registro/index'],
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