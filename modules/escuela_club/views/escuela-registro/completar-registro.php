<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\assets\AppAsset;

AppAsset::register($this);

$this->title = 'Completar Registro de Escuela/Club - Fase 2';
$this->params['breadcrumbs'][] = ['label' => 'Escuelas/Clubes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="escuela-completar-registro">
    <div class="card card-custom">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">
                    <i class="fas fa-map-marker-alt text-primary"></i>
                    Fase 2: Completar Registro
                </h3>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle"></i> Pre-Registro Completado</h4>
                <p>Ahora complete la información adicional de su escuela/club. Todos los campos son opcionales excepto donde se indique.</p>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'completar-registro-form',
                'options' => ['enctype' => 'multipart/form-data']
            ]); ?>

            <!-- SECCIÓN INFORMACIÓN ADICIONAL -->
            <div class="section-title">
                <h4><i class="fas fa-info-circle"></i> Información Adicional</h4>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'mision')->textarea(['rows' => 3, 'class' => 'form-control']) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'vision')->textarea(['rows' => 3, 'class' => 'form-control']) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'objetivos')->textarea(['rows' => 3, 'class' => 'form-control']) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'historia')->textarea(['rows' => 5, 'class' => 'form-control']) ?>
                </div>
            </div>

            <!-- SECCIÓN LOGO -->
            <div class="section-title mt-4">
                <h4><i class="fas fa-image"></i> Logo de la Escuela/Club</h4>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'logoFile')->fileInput(['class' => 'form-control-file']) ?>
                    <small class="form-text text-muted">
                        Formatos permitidos: PNG, JPG, JPEG, GIF. Tamaño máximo: 2MB.
                    </small>
                </div>
                <div class="col-md-6">
                    <?php if ($model->logo): ?>
                        <div class="current-logo">
                            <p><strong>Logo actual:</strong></p>
                            <?= Html::img($model->getLogoUrl(), [
                                'class' => 'img-thumbnail',
                                'style' => 'max-width: 200px; max-height: 200px;'
                            ]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECCIÓN UBICACIÓN EN MAPA -->
            <div class="section-title mt-4">
                <h4><i class="fas fa-map-marked-alt"></i> Ubicación en Mapa</h4>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Información importante:</strong> 
                Las coordenadas (Latitud y Longitud) se pueden obtener mediante el mapa interactivo 
                o se completarán automáticamente basándose en la dirección proporcionada.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'lat')->textInput([
                        'class' => 'form-control',
                        'placeholder' => 'Ej: 10.480594',
                        'id' => 'lat-input'
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'lng')->textInput([
                        'class' => 'form-control',
                        'placeholder' => 'Ej: -66.903600',
                        'id' => 'lng-input'
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Mapa Interactivo</label>
                        <div id="map" style="height: 300px; width: 100%; border: 1px solid #ddd; border-radius: 4px; background-color: #f8f9fa;"></div>
                        <small class="form-text text-muted">
                            Haga clic en el mapa para establecer la ubicación exacta de la escuela/club.
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-group text-center mt-4">
                <?= Html::submitButton('<i class="fas fa-check"></i> Completar Registro', [
                    'class' => 'btn btn-success btn-lg',
                    'id' => 'submit-btn'
                ]) ?>
                
                <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['view', 'id' => $model->id], [
                    'class' => 'btn btn-secondary btn-lg ml-2'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
// Solo la inicialización del mapa con timeout para asegurar que el DOM esté listo
$this->registerJs("
    setTimeout(function() {
        if (typeof initMapaEscuela === 'function') {
            initMapaEscuela();
        } else {
            console.error('La función initMapaEscuela no está disponible');
        }
    }, 500);
");
?>