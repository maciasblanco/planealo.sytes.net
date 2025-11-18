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
                <p>Ahora complete la información adicional de su escuela/club. Los datos del encargado ya fueron registrados en la fase anterior.</p>
                <p><strong>Nota:</strong> Si necesita modificar los datos del encargado, podrá hacerlo posteriormente en la edición de la escuela.</p>
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

            <!-- SECCIÓN HORARIO DE LA ESCUELA -->
            <div class="section-title mt-4">
                <h4><i class="fas fa-clock"></i> Horario de Prácticas</h4>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Información importante:</strong> 
                Seleccione los horarios de práctica haciendo clic en las casillas correspondientes. Cada clic marca/desmarca una hora.
            </div>

            <div class="row">
                <div class="col-md-12">
                    <!-- Campo hidden para almacenar el horario seleccionado -->
                    <?= $form->field($model, 'horario')->hiddenInput(['id' => 'horario-data'])->label(false) ?>
                    
                    <div class="form-group">
                        <label>Seleccione los horarios de práctica:</label>
                        
                        <!-- Leyenda de colores -->
                        <div class="horario-legend mb-3">
                            <div class="legend-item">
                                <div class="legend-color legend-morning"></div>
                                <span>Mañana (6:00 AM - 12:00 PM)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color legend-afternoon"></div>
                                <span>Tarde (12:00 PM - 6:00 PM)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color legend-evening"></div>
                                <span>Noche (6:00 PM - 10:00 PM)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color legend-selected"></div>
                                <span>Seleccionado</span>
                            </div>
                        </div>
                        
                        <!-- Selector de tipo de horario -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Tipo de Horario:</label>
                                <select id="tipo-horario" class="form-control">
                                    <option value="">Seleccione un horario rápido...</option>
                                    <option value="manana">Mañana (Lunes a Viernes)</option>
                                    <option value="tarde">Tarde (Lunes a Viernes)</option>
                                    <option value="noche">Noche (Lunes a Viernes)</option>
                                    <option value="completo">Día Completo (Lunes a Viernes)</option>
                                    <option value="fin_semana">Fin de Semana (Sábado y Domingo)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Acciones rápidas:</label>
                                <div>
                                    <button type="button" id="select-all" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-check-square"></i> Seleccionar Todo
                                    </button>
                                    <button type="button" id="clear-all" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times-circle"></i> Limpiar Todo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Cuadrícula de horarios -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="horario-grid">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Hora/Día</th>
                                        <th>Lunes</th>
                                        <th>Martes</th>
                                        <th>Miércoles</th>
                                        <th>Jueves</th>
                                        <th>Viernes</th>
                                        <th>Sábado</th>
                                        <th>Domingo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Generar filas de horas (de 6:00 AM a 10:00 PM)
                                    for ($hora = 6; $hora <= 22; $hora++) {
                                        $horaDisplay = $hora <= 12 ? $hora . ':00 AM' : ($hora - 12) . ':00 PM';
                                        if ($hora === 12) $horaDisplay = '12:00 PM';
                                        echo '<tr>';
                                        echo '<td class="font-weight-bold bg-light">' . $horaDisplay . '</td>';
                                        
                                        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                                        foreach ($dias as $dia) {
                                            $id = $dia . '_' . $hora;
                                            echo '<td class="horario-cell" data-dia="' . $dia . '" data-hora="' . $hora . '" id="' . $id . '">';
                                            echo '<div class="text-center">';
                                            echo '<i class="fas fa-times text-muted"></i>';
                                            echo '</div>';
                                            echo '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Vista previa del horario seleccionado -->
                        <div class="mt-3">
                            <label class="font-weight-bold">Horario seleccionado:</label>
                            <div id="horario-preview" class="alert alert-light border" style="min-height: 80px;">
                                <small class="text-muted">No se han seleccionado horarios</small>
                            </div>
                        </div>
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
// Solo la inicialización del mapa con verificación
$js = <<< JS
// Esperar a que todo esté listo
setTimeout(function() {
    if (typeof initMapaEscuela === 'function' && !window.mapaInicializado) {
        console.log('📱 Inicializando mapa desde la vista...');
        initMapaEscuela();
    } else {
        console.log('ℹ️ Mapa ya inicializado o función no disponible');
    }
}, 1500);
JS;

$this->registerJs($js);
?>

<?php
// JavaScript mínimo para inicialización
$js = <<< JS
// El selector se inicializa automáticamente cuando el documento está listo
// a través del archivo horarioSelector.js
JS;

$this->registerJs($js);
?>