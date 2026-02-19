<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Beca $model */
/** @var app\models\AtletasRegistro[] $atletas */
/** @var app\models\TipoBeca[] $tiposBeca */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de asignar becas.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/select-escuela'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Asignar Nueva Beca - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Quincenales', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Becas', 'url' => ['becas']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="asignar-beca">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-medal text-warning"></i> <?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver a Becas', ['becas'], ['class' => 'btn btn-default']) ?>
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
                <small class="text-muted">Sistema GED - Gestión de Becas</small>
            </div>
        </div>
    </div>

    <!-- Mensajes Flash -->
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fas fa-check"></i> <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fas fa-ban"></i> <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-graduation-cap"></i> Formulario de Asignación</h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'id_atleta')->dropDownList(
                        \yii\helpers\ArrayHelper::map($atletas, 'id', function($atleta) {
                            return $atleta->p_nombre . ' ' . $atleta->p_apellido . ' (' . $atleta->identificacion . ')';
                        }),
                        ['prompt' => 'Seleccionar atleta...', 'class' => 'form-control']
                    )->label('Atleta *') ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'id_tipo_beca')->dropDownList(
                        \yii\helpers\ArrayHelper::map($tiposBeca, 'id_tipo_beca', 'nombre'),
                        ['prompt' => 'Seleccionar tipo de beca...', 'class' => 'form-control']
                    )->label('Tipo de Beca *') ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'fecha_asignacion')->textInput([
                        'type' => 'date',
                        'class' => 'form-control',
                        'value' => date('Y-m-d')
                    ])->label('Fecha de Asignación *') ?>
                </div>
                <div class="col-md-4">
                    <!-- El campo fecha_vencimiento se calcula automáticamente según el tipo de beca, no se edita manualmente -->
                    <?= $form->field($model, 'fecha_vencimiento')->textInput([
                        'class' => 'form-control',
                        'readonly' => true,
                        'placeholder' => 'Se calcula automáticamente'
                    ])->label('Fecha de Vencimiento (calculada)') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'periodo_validez_meses')->textInput([
                        'class' => 'form-control',
                        'readonly' => true,
                        'placeholder' => 'Según tipo de beca'
                    ])->label('Periodo (meses)') ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'observaciones')->textarea([
                        'rows' => 3,
                        'placeholder' => 'Observaciones adicionales (opcional)'
                    ])->label('Observaciones') ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'autorizacion_excepcion')->checkbox([
                        'label' => 'Autorización de excepción (permite saltar la regla de "al menos un atleta sin beca")'
                    ]) ?>
                </div>
            </div>

            <div class="form-group mt-3">
                <?= Html::submitButton('<i class="fas fa-save"></i> Asignar Beca', [
                    'class' => 'btn btn-success btn-lg'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Información de reglas de negocio -->
    <div class="alert alert-light border mt-4">
        <h6><i class="fas fa-info-circle text-info"></i> Reglas de asignación de becas</h6>
        <ul class="mb-0">
            <li>Cada atleta puede tener solo una beca activa a la vez.</li>
            <li>Máximo 3 becas activas por familia.</li>
            <li>Máximo 1 beca de tipo "Entrenador" por familia.</li>
            <li>Debe quedar al menos un atleta sin beca en la familia (a menos que se active la autorización de excepción).</li>
            <li>La fecha de vencimiento se calcula automáticamente según el período de validez del tipo de beca.</li>
        </ul>
    </div>
</div>

<?php
// Script para actualizar dinámicamente fecha de vencimiento y período según el tipo de beca seleccionado
$js = <<<JS
$(document).ready(function() {
    // Cuando se selecciona un tipo de beca
    $('#beca-id_tipo_beca').change(function() {
        var tipoId = $(this).val();
        if (tipoId) {
            $.getJSON('index.php?r=aportes/aportes/get-tipo-beca&id=' + tipoId, function(data) {
                if (data) {
                    $('#beca-periodo_validez_meses').val(data.periodo_validez_meses);
                    // Calcular fecha de vencimiento sumando los meses a la fecha de asignación
                    var fechaAsignacion = $('#beca-fecha_asignacion').val();
                    if (fechaAsignacion && data.periodo_validez_meses) {
                        var fecha = new Date(fechaAsignacion);
                        fecha.setMonth(fecha.getMonth() + parseInt(data.periodo_validez_meses));
                        var año = fecha.getFullYear();
                        var mes = ('0' + (fecha.getMonth() + 1)).slice(-2);
                        var dia = ('0' + fecha.getDate()).slice(-2);
                        $('#beca-fecha_vencimiento').val(año + '-' + mes + '-' + dia);
                    } else {
                        $('#beca-fecha_vencimiento').val('');
                    }
                }
            });
        } else {
            $('#beca-periodo_validez_meses').val('');
            $('#beca-fecha_vencimiento').val('');
        }
    });

    // Si cambia la fecha de asignación, recalcular vencimiento
    $('#beca-fecha_asignacion').change(function() {
        $('#beca-id_tipo_beca').trigger('change');
    });
});
JS;
$this->registerJs($js);
?>