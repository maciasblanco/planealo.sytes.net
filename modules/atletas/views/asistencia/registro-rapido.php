<?php
// modules/atletas/views/asistencia/registro-rapido.php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\AtletasRegistro;
use app\models\Escuela;

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    // ❌ MOSTRAR ERROR Y REDIRECCIÓN
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de registrar asistencia.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/index'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Registro Rápido de Asistencia - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Asistencia', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="asistencia-registro-rapido">
    <div class="card">
        <div class="card-header bg-success text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt"></i> <?= $this->title ?>
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-clock"></i> <?= date('d/m/Y H:i:s') ?>
                    </span>
                    <div class="mt-1">
                        <small class="text-light">Escuela: <?= Html::encode($nombre_escuela) ?></small>
                    </div>
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
                <div class="col-md-8">
                    <?php $form = ActiveForm::begin([
                        'id' => 'registro-rapido-form',
                        'options' => ['class' => 'form-horizontal'],
                    ]); ?>

                    <!-- ✅ CAMPO OCULTO ESCUELA -->
                    <?= $form->field($model, 'id_escuela')->hiddenInput(['value' => $id_escuela])->label(false) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'id_atleta')->dropDownList(
                                ArrayHelper::map($atletas, 'id', function($atleta) {
                                    return $atleta->p_nombre . ' ' . $atleta->p_apellido . ' (' . $atleta->identificacion . ')';
                                }),
                                [
                                    'prompt' => '-- Seleccione un Atleta --',
                                    'class' => 'form-control select2',
                                    'required' => true
                                ]
                            )->label('Atleta') ?>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Escuela</label>
                                <div class="form-control bg-light">
                                    <strong><?= Html::encode($nombre_escuela) ?></strong>
                                    <small class="text-muted d-block">ID: <?= $id_escuela ?></small>
                                </div>
                                <small class="form-text text-muted">Escuela actual de la sesión</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <?= $form->field($model, 'fecha_practica')->textInput([
                                'type' => 'date',
                                'value' => date('Y-m-d'),
                                'class' => 'form-control',
                                'readonly' => true
                            ]) ?>
                        </div>

                        <div class="col-md-4">
                            <?= $form->field($model, 'hora_entrada')->textInput([
                                'type' => 'time',
                                'value' => date('H:i'),
                                'class' => 'form-control',
                                'readonly' => true
                            ]) ?>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Estado</label>
                                <div class="mt-2">
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-check"></i> ASISTIÓ
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?= $form->field($model, 'comentarios')->textarea([
                        'rows' => 2,
                        'placeholder' => 'Observaciones o comentarios adicionales...'
                    ]) ?>

                    <div class="form-group mt-4">
                        <?= Html::submitButton('<i class="fas fa-save"></i> Registrar Asistencia', [
                            'class' => 'btn btn-success btn-lg w-100',
                            'id' => 'btn-registrar'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Información Rápida
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Escuela Activa:</strong>
                                <span class="badge bg-primary float-end"><?= Html::encode($nombre_escuela) ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Total Atletas:</strong>
                                <span class="badge bg-primary float-end"><?= count($atletas) ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Asistencias Hoy:</strong>
                                <span class="badge bg-success float-end" id="asistencias-hoy">
                                    <?= \app\models\Asistencia::find()
                                        ->where(['fecha_practica' => date('Y-m-d'), 'asistio' => true, 'eliminado' => false])
                                        ->andWhere(['id_escuela' => $id_escuela]) // ✅ FILTRADO POR ESCUELA
                                        ->count() ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <strong>Hora Actual:</strong>
                                <span class="badge bg-secondary float-end" id="hora-actual">
                                    <?= date('H:i:s') ?>
                                </span>
                            </div>

                            <hr>

                            <div class="text-center">
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb"></i> 
                                    <strong>Consejo:</strong> Use esta vista para registro rápido en tablets o dispositivos móviles.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de últimas asistencias registradas -->
                    <div class="card mt-3">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-history"></i> Últimas Asistencias
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" id="ultimas-asistencias">
                                <?php
                                $ultimasAsistencias = \app\models\Asistencia::find()
                                    ->joinWith(['atleta'])
                                    ->where(['fecha_practica' => date('Y-m-d')])
                                    ->andWhere(['id_escuela' => $id_escuela]) // ✅ FILTRADO POR ESCUELA
                                    ->orderBy(['d_creacion' => SORT_DESC])
                                    ->limit(5)
                                    ->all();
                                
                                if (empty($ultimasAsistencias)): ?>
                                    <div class="list-group-item text-center text-muted">
                                        No hay registros hoy
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($ultimasAsistencias as $asistencia): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= $asistencia->atleta->p_nombre ?> <?= $asistencia->atleta->p_apellido ?></h6>
                                                <small><?= date('H:i', strtotime($asistencia->hora_entrada)) ?></small>
                                            </div>
                                            <small class="text-muted">
                                                <?= $asistencia->escuela->nombre ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmación rápida -->
<div class="modal fade" id="confirmacionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Confirmación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="fas fa-check fa-3x text-success"></i>
                </div>
                <h5 id="nombre-atleta-confirmacion"></h5>
                <p class="text-muted" id="detalles-confirmacion"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn-nuevo-registro">
                    <i class="fas fa-plus"></i> Nuevo Registro
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// JavaScript para mejorar la experiencia de usuario
$this->registerJs(<<<JS
// Inicializar Select2 si está disponible
if (typeof $.fn.select2 !== 'undefined') {
    $('.select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true
    });
}

// Actualizar hora en tiempo real
function actualizarHora() {
    const ahora = new Date();
    const hora = ahora.getHours().toString().padStart(2, '0');
    const minutos = ahora.getMinutes().toString().padStart(2, '0');
    const segundos = ahora.getSeconds().toString().padStart(2, '0');
    $('#hora-actual').text(hora + ':' + minutos + ':' + segundos);
}
setInterval(actualizarHora, 1000);

// Enfoque automático en el primer campo
$('#asistencia-id_atleta').focus();

// Manejar envío del formulario - CORREGIDO
$('#registro-rapido-form').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const btn = $('#btn-registrar');
    const originalText = btn.html();
    
    // Mostrar loading
    btn.html('<i class="fas fa-spinner fa-spin"></i> Registrando...');
    btn.prop('disabled', true);
    
    // Enviar formulario de forma normal (no AJAX)
    form[0].submit();
});

// Botón para nuevo registro en el modal
$('#btn-nuevo-registro').on('click', function() {
    $('#confirmacionModal').modal('hide');
    $('#asistencia-id_atleta').focus();
});

// Atajos de teclado
$(document).on('keydown', function(e) {
    // Ctrl + Enter para enviar el formulario
    if (e.ctrlKey && e.keyCode === 13) {
        $('#registro-rapido-form').submit();
    }
    
    // Escape para limpiar el formulario
    if (e.keyCode === 27) {
        $('#registro-rapido-form')[0].reset();
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').val(null).trigger('change');
        }
    }
});

// Auto-enfoque en el campo de atleta al cargar la página
$(document).ready(function() {
    $('#asistencia-id_atleta').focus();
});
JS
);

// CSS adicional para mejorar la apariencia
$this->registerCss(<<<CSS
.select2-container .select2-selection--single {
    height: 38px;
    padding: 6px 12px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.btn-lg {
    padding: 12px 24px;
    font-size: 1.1rem;
    font-weight: 600;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.list-group-item:last-child {
    border-bottom: none;
}

#ultimas-asistencias {
    max-height: 200px;
    overflow-y: auto;
}

.badge {
    font-size: 0.8em;
}
CSS
);
?>