<?php
// modules/atletas/views/asistencia/registro-multiple.php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\AtletasRegistro;
use app\models\Escuela;
use app\models\AportesSemanales;

$this->title = 'Registro Múltiple de Asistencia';
$this->params['breadcrumbs'][] = ['label' => 'Asistencia', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="asistencia-registro-multiple">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users"></i> <?= $this->title ?>
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-clock"></i> <?= date('d/m/Y H:i:s') ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> 
                    <?= Yii::$app->session->getFlash('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= Yii::$app->session->getFlash('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <?php $form = ActiveForm::begin([
                        'id' => 'registro-multiple-form',
                        'options' => ['class' => 'form-horizontal'],
                    ]); ?>

                    <!-- Selección de Escuela -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-school"></i> Paso 1: Seleccionar Escuela
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="form-label fw-bold">Escuela/Club:</label>
                                                <select name="id_escuela" id="select-escuela" class="form-control form-control-lg" required>
                                                    <option value="">-- Seleccione una Escuela --</option>
                                                    <?php foreach (Escuela::find()->where(['eliminado' => false])->orderBy(['nombre' => SORT_ASC])->all() as $escuela): ?>
                                                        <option value="<?= $escuela->id ?>" 
                                                            <?= $idEscuelaSeleccionada == $escuela->id ? 'selected' : '' ?>>
                                                            <?= $escuela->nombre ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Fecha:</label>
                                                <?= $form->field($model, 'fecha_practica')->textInput([
                                                    'type' => 'date',
                                                    'value' => date('Y-m-d'),
                                                    'class' => 'form-control',
                                                    'readonly' => true
                                                ])->label(false) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-info-circle"></i> Información
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="info-escuela" class="text-center">
                                        <?php if ($idEscuelaSeleccionada): ?>
                                            <?php 
                                            $escuela = Escuela::findOne($idEscuelaSeleccionada);
                                            $totalAtletas = count($atletas);
                                            $asistenciasHoy = \app\models\Asistencia::find()
                                                ->where(['fecha_practica' => date('Y-m-d'), 'asistio' => true, 'eliminado' => false, 'id_escuela' => $idEscuelaSeleccionada])
                                                ->count();
                                            ?>
                                            <h5><?= $escuela->nombre ?></h5>
                                            <div class="row text-center mt-3">
                                                <div class="col-4">
                                                    <div class="bg-primary text-white p-2 rounded">
                                                        <h6 class="mb-0"><?= $totalAtletas ?></h6>
                                                        <small>Total Atletas</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="bg-success text-white p-2 rounded">
                                                        <h6 class="mb-0"><?= $asistenciasHoy ?></h6>
                                                        <small>Asistencias Hoy</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="bg-warning text-white p-2 rounded">
                                                        <h6 class="mb-0"><?= $totalAtletas - $asistenciasHoy ?></h6>
                                                        <small>Sin Asistencia</small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">Seleccione una escuela para ver la información</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Atletas -->
                    <div id="panel-atletas" class="<?= !$idEscuelaSeleccionada ? 'd-none' : '' ?>">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-list"></i> Paso 2: Seleccionar Atletas que Asistieron
                                    <span id="contador-seleccionados" class="badge bg-light text-dark float-end">0 seleccionados</span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                            <label class="form-check-label fw-bold" for="select-all">
                                                <i class="fas fa-check-double"></i> Seleccionar Todos los Atletas
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button type="button" id="btn-seleccionar-sin-asistencia" class="btn btn-warning btn-sm">
                                            <i class="fas fa-user-clock"></i> Seleccionar Sin Asistencia Hoy
                                        </button>
                                    </div>
                                </div>

                                <div id="lista-atletas" style="max-height: 500px; overflow-y: auto;">
                                    <?php if ($idEscuelaSeleccionada && !empty($atletas)): ?>
                                        <?php foreach ($atletas as $atleta): ?>
                                            <?php
                                            $tieneAsistencia = \app\models\Asistencia::find()
                                                ->where(['id_atleta' => $atleta->id, 'fecha_practica' => date('Y-m-d'), 'eliminado' => false])
                                                ->exists();
                                            
                                            // Calcular deuda del atleta
                                            $montoDeuda = AportesSemanales::calcularMontoDeuda($atleta->id);
                                            $semanasDeuda = AportesSemanales::calcularDeudaAtleta($atleta->id);
                                            $estadoDeuda = $montoDeuda <= 0 ? 'AL DÍA' : '$' . number_format($montoDeuda, 2) . ' (' . $semanasDeuda . ' semanas)';
                                            $claseDeuda = $montoDeuda <= 0 ? 'text-success' : 'text-danger';
                                            ?>
                                            <div class="atleta-item row mb-2 p-2 border rounded align-items-center <?= $tieneAsistencia ? 'bg-light' : '' ?>">
                                                <div class="col-md-1">
                                                    <div class="form-check">
                                                        <input class="form-check-input atleta-checkbox" 
                                                               type="checkbox" 
                                                               name="id_atletas[]" 
                                                               value="<?= $atleta->id ?>" 
                                                               id="atleta-<?= $atleta->id ?>"
                                                               <?= $tieneAsistencia ? 'disabled' : '' ?>>
                                                    </div>
                                                </div>
                                                <div class="col-md-11">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-3">
                                                            <strong class="d-block"><?= $atleta->p_nombre ?> <?= $atleta->p_apellido ?></strong>
                                                            <small class="text-muted"><?= $atleta->identificacion ?></small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small><strong>Categoría:</strong><br><?= $atleta->getCategoriaNombre() ?></small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small><strong>Teléfono:</strong><br><?= $atleta->cell ?: 'N/A' ?></small>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small><strong>Deuda en Aportes:</strong><br>
                                                                <span class="<?= $claseDeuda ?> fw-bold">
                                                                    <?= $estadoDeuda ?>
                                                                </span>
                                                            </small>
                                                        </div>
                                                        <div class="col-md-2 text-center">
                                                            <?php if ($tieneAsistencia): ?>
                                                                <span class="badge bg-success"><i class="fas fa-check"></i> Ya tiene asistencia</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Sin asistencia</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php elseif ($idEscuelaSeleccionada): ?>
                                        <div class="alert alert-warning text-center">
                                            <i class="fas fa-exclamation-triangle"></i> No hay atletas registrados en esta escuela.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Comentarios y Botón de Envío -->
                        <div class="card mt-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-edit"></i> Paso 3: Comentarios y Guardar
                                </h6>
                            </div>
                            <div class="card-body">
                                <?= $form->field($model, 'comentarios')->textarea([
                                    'rows' => 3,
                                    'placeholder' => 'Observaciones o comentarios generales para todos los atletas seleccionados...'
                                ])->label('Comentarios (Opcional)') ?>

                                <div class="form-group mt-4">
                                    <?= Html::submitButton('<i class="fas fa-save"></i> Registrar Asistencia para Atletas Seleccionados', [
                                        'class' => 'btn btn-success btn-lg w-100',
                                        'id' => 'btn-registrar-multiple',
                                        'disabled' => !$idEscuelaSeleccionada
                                    ]) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// JavaScript para la funcionalidad
$this->registerJs(<<<JS
// Contador de seleccionados
function actualizarContador() {
    const seleccionados = $('.atleta-checkbox:checked').length;
    const totalHabilitados = $('.atleta-checkbox:not(:disabled)').length;
    
    $('#contador-seleccionados').text(seleccionados + ' de ' + totalHabilitados + ' seleccionados');
    
    // Habilitar/deshabilitar botón
    if (seleccionados > 0) {
        $('#btn-registrar-multiple').prop('disabled', false);
    } else {
        $('#btn-registrar-multiple').prop('disabled', true);
    }
}

// Seleccionar/deseleccionar todos los atletas habilitados
$('#select-all').on('change', function() {
    $('.atleta-checkbox:not(:disabled)').prop('checked', this.checked);
    actualizarContador();
});

// Actualizar contador cuando cambia cualquier checkbox
$(document).on('change', '.atleta-checkbox', function() {
    actualizarContador();
    
    // Actualizar checkbox "Seleccionar Todos"
    const totalHabilitados = $('.atleta-checkbox:not(:disabled)').length;
    const checkedHabilitados = $('.atleta-checkbox:not(:disabled):checked').length;
    $('#select-all').prop('checked', totalHabilitados > 0 && totalHabilitados === checkedHabilitados);
});

// Seleccionar atletas sin asistencia
$('#btn-seleccionar-sin-asistencia').on('click', function() {
    $('.atleta-checkbox:not(:disabled)').prop('checked', true);
    $('.atleta-checkbox:disabled').prop('checked', false);
    actualizarContador();
});

// Manejar envío del formulario
$('#registro-multiple-form').on('submit', function(e) {
    const seleccionados = $('.atleta-checkbox:checked').length;
    
    if (seleccionados === 0) {
        e.preventDefault();
        alert('Por favor seleccione al menos un atleta.');
        return false;
    }
    
    const btn = $('#btn-registrar-multiple');
    const originalText = btn.html();
    
    // Mostrar loading
    btn.html('<i class="fas fa-spinner fa-spin"></i> Registrando ' + seleccionados + ' atletas...');
    btn.prop('disabled', true);
});

// Cargar atletas cuando se selecciona una escuela
$('#select-escuela').on('change', function() {
    const idEscuela = $(this).val();
    
    if (!idEscuela) {
        $('#panel-atletas').addClass('d-none');
        $('#btn-registrar-multiple').prop('disabled', true);
        return;
    }
    
    // Recargar la página con la escuela seleccionada
    window.location.href = '?r=atletas/asistencia/registro-multiple&id_escuela=' + idEscuela;
});

// Inicializar si ya hay una escuela seleccionada
$(document).ready(function() {
    const idEscuela = $('#select-escuela').val();
    if (idEscuela) {
        actualizarContador();
    }
});
JS
);

// CSS adicional mejorado
$this->registerCss(<<<CSS
#lista-atletas {
    scrollbar-width: thin;
    scrollbar-color: #6c757d #f8f9fa;
}

#lista-atletas::-webkit-scrollbar {
    width: 8px;
}

#lista-atletas::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 4px;
}

#lista-atletas::-webkit-scrollbar-thumb {
    background: #6c757d;
    border-radius: 4px;
}

#lista-atletas::-webkit-scrollbar-thumb:hover {
    background: #495057;
}

.atleta-item:hover {
    background-color: #f8f9fa !important;
    border-color: #007bff !important;
}

.atleta-checkbox:checked + .form-check-label {
    font-weight: bold;
}

.atleta-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.atleta-checkbox:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.atleta-checkbox:disabled {
    background-color: #e9ecef;
    border-color: #6c757d;
    cursor: not-allowed;
}

.form-check-input {
    margin-top: 0;
    margin-left: 0;
}

.card-header {
    border-bottom: 2px solid rgba(0,0,0,0.1);
}

.bg-light {
    background-color: #f8f9fa !important;
}

.text-success {
    color: #28a745 !important;
    font-weight: bold;
}

.text-danger {
    color: #dc3545 !important;
    font-weight: bold;
}

.atleta-item {
    transition: all 0.2s ease;
}

.atleta-checkbox:checked ~ div .atleta-item {
    background-color: #e8f5e8 !important;
    border-color: #28a745 !important;
}
CSS
);
?>