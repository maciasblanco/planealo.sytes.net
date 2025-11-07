<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Estado;
use yii\helpers\ArrayHelper;

$this->title = 'Pre-Registro de Escuela/Club';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="escuela-pre-registro">
    <div class="card card-custom">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">
                    <i class="fas fa-clipboard-list text-primary"></i>
                    Fase 1: Pre-Registro
                </h3>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h4><i class="fas fa-info-circle"></i> Información Importante</h4>
                <p>Complete la información básica de su escuela/club. En la siguiente fase podrá:</p>
                <ul>
                    <li>Seleccionar la ubicación exacta en el mapa</li>
                    <li>Completar información adicional</li>
                    <li>Subir el logo de la escuela</li>
                </ul>
            </div>

            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'nombre')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'tipo_entidad')->dropDownList([
                        '1' => 'Escuela',
                        '0' => 'Club'
                    ], ['prompt' => 'Seleccione...']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'telefono')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'id_estado')->dropDownList(
                        ArrayHelper::map(Estado::find()->where(['eliminado' => false])->orderBy('estado')->all(), 'id', 'estado'),
                        [
                            'prompt' => 'Seleccione Estado',
                            'id' => 'estado-dropdown'
                        ]
                    ) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'id_municipio')->dropDownList(
                        [],
                        [
                            'prompt' => 'Seleccione Municipio',
                            'id' => 'municipio-dropdown',
                            'disabled' => true
                        ]
                    ) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'id_parroquia')->dropDownList(
                        [],
                        [
                            'prompt' => 'Seleccione Parroquia',
                            'id' => 'parroquia-dropdown',
                            'disabled' => true
                        ]
                    ) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'direccion_practicas')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="form-group text-center mt-4">
                <?= Html::submitButton('<i class="fas fa-arrow-right"></i> Continuar a Fase 2', ['class' => 'btn btn-success btn-lg']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
// JavaScript para dropdowns dependientes
$script = <<< JS
$(document).ready(function() {
    // Cargar municipios cuando se selecciona estado
    $('#estado-dropdown').change(function() {
        var estadoId = $(this).val();
        $('#municipio-dropdown').empty().append('<option value="">Seleccione Municipio</option>').prop('disabled', true);
        $('#parroquia-dropdown').empty().append('<option value="">Seleccione Parroquia</option>').prop('disabled', true);
        
        if (estadoId) {
            $.get('/municipio/get-by-edo', { edo: estadoId }, function(data) {
                $('#municipio-dropdown').prop('disabled', false);
                $.each(data.results, function(index, municipio) {
                    $('#municipio-dropdown').append('<option value="' + municipio.id + '">' + municipio.text + '</option>');
                });
            });
        }
    });

    // Cargar parroquias cuando se selecciona municipio
    $('#municipio-dropdown').change(function() {
        var municipioId = $(this).val();
        $('#parroquia-dropdown').empty().append('<option value="">Seleccione Parroquia</option>').prop('disabled', true);
        
        if (municipioId) {
            $.get('/parroquia/get-by-muni', { muni: municipioId }, function(data) {
                $('#parroquia-dropdown').prop('disabled', false);
                $.each(data.results, function(index, parroquia) {
                    $('#parroquia-dropdown').append('<option value="' + parroquia.id + '">' + parroquia.text + '</option>');
                });
            });
        }
    });
});
JS;
$this->registerJs($script);