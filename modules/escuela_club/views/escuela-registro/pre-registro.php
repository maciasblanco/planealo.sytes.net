<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Estado;
use yii\helpers\ArrayHelper;
use app\assets\AppAsset;

AppAsset::register($this);

$this->title = 'Pre-Registro de Escuela/Club';
$this->params['breadcrumbs'][] = $this->title;

// URLs para AJAX
$urlMunicipios = Yii::$app->urlManager->createUrl(['/municipio/get-by-edo']);
$urlParroquias = Yii::$app->urlManager->createUrl(['/parroquia/get-by-muni']);
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
                <p>Complete la información básica de su escuela/club. Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                <p>En la siguiente fase podrá:</p>
                <ul>
                    <li>Seleccionar la ubicación exacta en el mapa</li>
                    <li>Completar información adicional</li>
                    <li>Subir el logo de la escuela/club</li>
                </ul>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'pre-registro-form',
                'enableClientValidation' => true,
                'enableAjaxValidation' => false,
                'validateOnChange' => true,
                'validateOnBlur' => true,
                'options' => [
                    'enctype' => 'multipart/form-data',
                    'novalidate' => 'novalidate'
                ]
            ]); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'nombre', [
                        'inputOptions' => [
                            'placeholder' => 'Ingrese el nombre de la escuela/club',
                            'class' => 'form-control'
                        ]
                    ])->textInput(['maxlength' => true])->label('Nombre de la Escuela/Club <span class="text-danger">*</span>', ['class' => 'required-field']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'tipo_entidad')->dropDownList([
                        '1' => 'Escuela',
                        '0' => 'Club'
                    ], [
                        'prompt' => 'Seleccione...',
                        'class' => 'form-control'
                    ])->label('Tipo de Entidad <span class="text-danger">*</span>', ['class' => 'required-field']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'telefono', [
                        'inputOptions' => [
                            'placeholder' => 'Ej: 0412-1234567',
                            'class' => 'form-control'
                        ]
                    ])->textInput(['maxlength' => true])->label('Teléfono <span class="text-danger">*</span>', ['class' => 'required-field']) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'email', [
                        'inputOptions' => [
                            'placeholder' => 'ejemplo@correo.com',
                            'class' => 'form-control'
                        ]
                    ])->textInput(['maxlength' => true])->label('Correo Electrónico <span class="text-danger">*</span>', ['class' => 'required-field']) ?>
                </div>
            </div>

            <!-- SECCIÓN UBICACIÓN CON DROPDOWNS DEPENDIENTES -->
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'id_estado')->dropDownList(
                        ArrayHelper::map(Estado::find()->orderBy(['estado' => SORT_ASC])->all(), 'id', 'estado'),
                        [
                            'prompt' => 'Seleccione Estado',
                            'id' => 'estado',
                            'class' => 'form-control estado-select',
                        ]
                    )->label('Estado <span class="text-danger">*</span>', ['class' => 'required-field']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'id_municipio')->dropDownList(
                        [],
                        [
                            'prompt' => 'Primero seleccione un estado',
                            'id' => 'municipio',
                            'class' => 'form-control municipio-select',
                            'disabled' => true,
                        ]
                    )->label('Municipio <span class="text-danger">*</span>', ['class' => 'required-field']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'id_parroquia')->dropDownList(
                        [],
                        [
                            'prompt' => 'Primero seleccione un municipio',
                            'id' => 'parroquia',
                            'class' => 'form-control parroquia-select',
                            'disabled' => true,
                        ]
                    )->label('Parroquia <span class="text-danger">*</span>', ['class' => 'required-field']) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'direccion_practicas', [
                        'inputOptions' => [
                            'placeholder' => 'Ingrese la dirección donde se realizan las prácticas',
                            'class' => 'form-control'
                        ]
                    ])->textInput(['maxlength' => true])->label('Dirección de Prácticas (Ubicación de la Cancha) <span class="text-danger">*</span>', ['class' => 'required-field']) ?>
                </div>
            </div>

            <div class="form-group text-center mt-4">
                <?= Html::submitButton('<i class="fas fa-arrow-right"></i> Continuar a Fase 2', [
                    'class' => 'btn btn-success btn-lg',
                    'id' => 'submit-btn'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
// Solo la inicialización del script externo
$this->registerJs("
    initDropdownsDependientes({
        urlMunicipios: '$urlMunicipios',
        urlParroquias: '$urlParroquias'
    });
", \yii\web\View::POS_READY);
?>

<style>
.required-field label {
    font-weight: bold;
}
.help-block {
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
}
.field-pre-registro-form-nombre.required label,
.field-pre-registro-form-tipo_entidad.required label,
.field-pre-registro-form-telefono.required label,
.field-pre-registro-form-email.required label,
.field-pre-registro-form-id_estado.required label,
.field-pre-registro-form-id_municipio.required label,
.field-pre-registro-form-id_parroquia.required label,
.field-pre-registro-form-direccion_practicas.required label {
    color: #495057;
    font-weight: 600;
}
</style>