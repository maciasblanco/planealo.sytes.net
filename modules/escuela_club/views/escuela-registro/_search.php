<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Estado;
use app\models\Municipio;
use app\models\Parroquia;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\modules\escuela_club\models\EscuelaSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="escuela-search card card-custom mb-4">
    <div class="card-header">
        <div class="card-title">
            <h3 class="card-label">
                <i class="fas fa-search text-primary"></i>
                Filtros de Búsqueda
            </h3>
        </div>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-light-primary" data-toggle="collapse" data-target="#searchForm">
                <i class="fas fa-expand-arrows-alt"></i>
            </button>
        </div>
    </div>
    
    <div class="card-body collapse show" id="searchForm">
        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
            'options' => [
                'data-pjax' => 1,
                'class' => 'form-horizontal'
            ],
        ]); ?>

        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'nombre')->textInput([
                    'placeholder' => 'Buscar por nombre...',
                    'class' => 'form-control form-control-solid'
                ]) ?>
            </div>
            
            <div class="col-md-3">
                <?= $form->field($model, 'tipo_entidad')->dropDownList([
                    '' => 'Todos',
                    '1' => 'Escuela',
                    '0' => 'Club'
                ], [
                    'class' => 'form-control form-control-solid'
                ]) ?>
            </div>
            
            <div class="col-md-2">
                <?= $form->field($model, 'eliminado')->dropDownList([
                    '' => 'Todos',
                    '0' => 'Activos',
                    '1' => 'Eliminados'
                ], [
                    'class' => 'form-control form-control-solid'
                ]) ?>
            </div>
            
            <div class="col-md-3">
                <?= $form->field($model, 'telefono')->textInput([
                    'placeholder' => 'Teléfono...',
                    'class' => 'form-control form-control-solid'
                ]) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'id_estado')->dropDownList(
                    ArrayHelper::map(Estado::find()->where(['eliminado' => false])->orderBy('estado')->all(), 'id', 'estado'),
                    [
                        'prompt' => 'Seleccione Estado',
                        'class' => 'form-control form-control-solid',
                        'id' => 'estado-dropdown'
                    ]
                ) ?>
            </div>
            
            <div class="col-md-4">
                <?= $form->field($model, 'id_municipio')->dropDownList(
                    [],
                    [
                        'prompt' => 'Seleccione Municipio',
                        'class' => 'form-control form-control-solid',
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
                        'class' => 'form-control form-control-solid',
                        'id' => 'parroquia-dropdown',
                        'disabled' => true
                    ]
                ) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <?= Html::submitButton('<i class="fas fa-search"></i> Buscar', [
                        'class' => 'btn btn-primary',
                        'style' => 'background-color: #6f42c1; border-color: #6f42c1;'
                    ]) ?>
                    <?= Html::a('<i class="fas fa-redo"></i> Limpiar', ['index'], [
                        'class' => 'btn btn-secondary'
                    ]) ?>
                    <div class="float-right">
                        <span class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Use los filtros para encontrar escuelas/clubes específicos
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
// JavaScript para dropdowns dependientes
$script = <<< JS
$(document).ready(function() {
    // Cuando cambia el estado
    $('#estado-dropdown').change(function() {
        var estadoId = $(this).val();
        $('#municipio-dropdown').html('<option value="">Cargando...</option>');
        $('#parroquia-dropdown').html('<option value="">Seleccione municipio primero</option>');
        
        if (estadoId) {
            $('#municipio-dropdown').prop('disabled', false);
            $.get('/planealo/index.php?r=escuela-club/escuela/municipios', { estado_id: estadoId }, function(data) {
                $('#municipio-dropdown').html(data);
                $('#parroquia-dropdown').html('<option value="">Seleccione municipio</option>');
                $('#parroquia-dropdown').prop('disabled', true);
            });
        } else {
            $('#municipio-dropdown').html('<option value="">Seleccione estado primero</option>');
            $('#municipio-dropdown').prop('disabled', true);
            $('#parroquia-dropdown').html('<option value="">Seleccione municipio primero</option>');
            $('#parroquia-dropdown').prop('disabled', true);
        }
    });
    
    // Cuando cambia el municipio
    $('#municipio-dropdown').change(function() {
        var municipioId = $(this).val();
        $('#parroquia-dropdown').html('<option value="">Cargando...</option>');
        
        if (municipioId) {
            $('#parroquia-dropdown').prop('disabled', false);
            $.get('/planealo/index.php?r=escuela-club/escuela/parroquias', { municipio_id: municipioId }, function(data) {
                $('#parroquia-dropdown').html(data);
            });
        } else {
            $('#parroquia-dropdown').html('<option value="">Seleccione municipio</option>');
            $('#parroquia-dropdown').prop('disabled', true);
        }
    });
});
JS;
$this->registerJs($script);
?>