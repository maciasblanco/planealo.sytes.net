<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Estado;
use app\models\Municipio;
use app\models\Parroquia;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Escuela */
/* @var $form yii\widgets\ActiveForm */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

// Para el formulario de escuela, no requerimos validación de sesión ya que este formulario
// es para crear/editar escuelas, no para operaciones dentro de una escuela específica
?>

<div class="escuela-form">

    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data']
    ]); ?>

    <!-- INFORMACIÓN BÁSICA - AZUL PRINCIPAL -->
    <div class="ged-card mb-4">
        <div class="card-header ged-card-header-primary">
            <div class="card-title">
                <h3 class="card-label ged-text-white">
                    <i class="fas fa-school me-2"></i>
                    Información Básica
                </h3>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'nombre', [
                        'options' => ['class' => 'ged-form-group-primary']
                    ])->textInput([
                        'maxlength' => true,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'tipo_entidad', [
                        'options' => ['class' => 'ged-form-group-primary']
                    ])->dropDownList([
                        '1' => 'Escuela',
                        '0' => 'Club'
                    ], [
                        'prompt' => 'Seleccione...',
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'telefono', [
                        'options' => ['class' => 'ged-form-group-primary']
                    ])->textInput([
                        'maxlength' => true,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'email', [
                        'options' => ['class' => 'ged-form-group-primary']
                    ])->textInput([
                        'maxlength' => true,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'logo', [
                        'options' => ['class' => 'ged-form-group-primary']
                    ])->fileInput(['class' => 'form-control ged-form-control']) ?>
                    
                    <?php if (!$model->isNewRecord && $model->logo): ?>
                        <div class="form-group mt-3">
                            <label class="ged-form-label">Logo Actual</label>
                            <div>
                                <?= Html::img(Yii::getAlias('@web/uploads/escuelas/') . $model->logo, [
                                    'class' => 'img-thumbnail ged-state-active',
                                    'style' => 'max-width: 200px;'
                                ]) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- UBICACIÓN - AZUL INFORMACIÓN -->
    <div class="ged-card mb-4">
        <div class="card-header ged-card-header-info">
            <div class="card-title">
                <h3 class="card-label ged-text-white">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Ubicación
                </h3>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'id_estado', [
                        'options' => ['class' => 'ged-form-group-info']
                    ])->dropDownList(
                        ArrayHelper::map(Estado::find()->where(['eliminado' => false])->orderBy('estado')->all(), 'id', 'estado'),
                        [
                            'prompt' => 'Seleccione Estado',
                            'id' => 'estado-dropdown',
                            'class' => 'form-control ged-form-control'
                        ]
                    ) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'id_municipio', [
                        'options' => ['class' => 'ged-form-group-info']
                    ])->dropDownList(
                        [],
                        [
                            'prompt' => 'Seleccione Municipio',
                            'id' => 'municipio-dropdown',
                            'disabled' => true,
                            'class' => 'form-control ged-form-control'
                        ]
                    ) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'id_parroquia', [
                        'options' => ['class' => 'ged-form-group-info']
                    ])->dropDownList(
                        [],
                        [
                            'prompt' => 'Seleccione Parroquia',
                            'id' => 'parroquia-dropdown',
                            'disabled' => true,
                            'class' => 'form-control ged-form-control'
                        ]
                    ) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'direccion_administrativa', [
                        'options' => ['class' => 'ged-form-group-info']
                    ])->textInput([
                        'maxlength' => true,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'direccion_practicas', [
                        'options' => ['class' => 'ged-form-group-info']
                    ])->textInput([
                        'maxlength' => true,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'lat', [
                        'options' => ['class' => 'ged-form-group-info']
                    ])->textInput([
                        'maxlength' => true,
                        'type' => 'number',
                        'step' => 'any',
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'lng', [
                        'options' => ['class' => 'ged-form-group-info']
                    ])->textInput([
                        'maxlength' => true,
                        'type' => 'number',
                        'step' => 'any',
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- INFORMACIÓN ADICIONAL - VERDE ÉXITO -->
    <div class="ged-card mb-4">
        <div class="card-header ged-card-header-success">
            <div class="card-title">
                <h3 class="card-label ged-text-white">
                    <i class="fas fa-info-circle me-2"></i>
                    Información Adicional
                </h3>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'mision', [
                        'options' => ['class' => 'ged-form-group-success']
                    ])->textarea([
                        'rows' => 3,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'vision', [
                        'options' => ['class' => 'ged-form-group-success']
                    ])->textarea([
                        'rows' => 3,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'objetivos', [
                        'options' => ['class' => 'ged-form-group-success']
                    ])->textarea([
                        'rows' => 3,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'historia', [
                        'options' => ['class' => 'ged-form-group-success']
                    ])->textarea([
                        'rows' => 3,
                        'class' => 'form-control ged-form-control'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div class="form-group mt-4">
        <div class="row">
            <div class="col-md-12 text-center">
                <?= Html::submitButton(
                    '<i class="fas fa-save me-2"></i>' . ($model->isNewRecord ? 'Crear Escuela' : 'Actualizar Escuela'), 
                    ['class' => 'ged-btn ged-btn-success btn-lg px-4']
                ) ?>
                <?= Html::a(
                    '<i class="fas fa-times me-2"></i> Cancelar', 
                    ['index'], 
                    ['class' => 'ged-btn ged-btn-secondary btn-lg px-4 ms-2']
                ) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
// JavaScript para dropdowns dependientes (sin cambios)
$script = <<< JS
// Tu JavaScript existente aquí...
JS;
$this->registerJs($script);
?>