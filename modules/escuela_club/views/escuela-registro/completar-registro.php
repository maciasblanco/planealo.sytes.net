<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Completar Registro: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Escuelas/Clubes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="escuela-completar-registro">
    <div class="card card-custom">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">
                    <i class="fas fa-map-marked-alt text-primary"></i>
                    Fase 2: Completar Registro
                </h3>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h4><i class="fas fa-map-marker-alt"></i> Seleccione la Ubicación Exacta</h4>
                <p>Use el mapa para seleccionar la ubicación exacta de la cancha donde se practica. Haga clic en el mapa para establecer las coordenadas.</p>
            </div>

            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

            <!-- Mapa para seleccionar ubicación -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="control-label">Seleccione la ubicación en el mapa:</label>
                        <div id="map" style="height: 400px; border: 2px solid #007bff; border-radius: 5px;"></div>
                    </div>
                </div>
            </div>

            <!-- Campos ocultos para coordenadas -->
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'lat')->textInput(['maxlength' => true, 'id' => 'lat-input', 'readonly' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'lng')->textInput(['maxlength' => true, 'id' => 'lng-input', 'readonly' => true]) ?>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'logoFile')->fileInput() ?>
                    <?php if ($model->logo): ?>
                        <div class="form-group">
                            <label>Logo Actual:</label>
                            <div>
                                <?= Html::img($model->getLogoUrl(), ['class' => 'img-thumbnail', 'style' => 'max-width: 200px;']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'mision')->textarea(['rows' => 3]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'vision')->textarea(['rows' => 3]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'horarios')->textarea(['rows' => 3]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'objetivos')->textarea(['rows' => 3]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'historia')->textarea(['rows' => 3]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'redes_sociales')->textarea(['rows' => 2]) ?>
                </div>
            </div>

            <div class="form-group text-center mt-4">
                <?= Html::submitButton('<i class="fas fa-check-circle"></i> Completar Registro', [
                    'class' => 'btn btn-success btn-lg px-4',
                    'data' => [
                        'confirm' => '¿Está seguro de que la ubicación seleccionada es correcta?',
                        'method' => 'post'
                    ]
                ]) ?>
                <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['pre-registro'], ['class' => 'btn btn-secondary btn-lg px-4 ms-2']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
// Incluir Leaflet CSS y JS
$this->registerCssFile('https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
$this->registerJsFile('https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', ['depends' => [yii\web\JqueryAsset::className()]]);

$mapScript = <<< JS
// Inicializar mapa
var map = L.map('map').setView([6.4238, -66.5897], 6);
var marker = null;

// Capa base de OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Inicializar marcador si hay coordenadas existentes
if ($('#lat-input').val() && $('#lng-input').val()) {
    var existingLat = parseFloat($('#lat-input').val());
    var existingLng = parseFloat($('#lng-input').val());
    
    if (!isNaN(existingLat) && !isNaN(existingLng)) {
        marker = L.marker([existingLat, existingLng]).addTo(map);
        map.setView([existingLat, existingLng], 15);
        
        // Agregar popup informativo
        marker.bindPopup('Ubicación actual de la escuela').openPopup();
    }
}

// Evento click en el mapa
map.on('click', function(e) {
    var lat = e.latlng.lat;
    var lng = e.latlng.lng;
    
    // Actualizar campos
    $('#lat-input').val(lat.toFixed(6));
    $('#lng-input').val(lng.toFixed(6));
    
    // Mover o crear marcador
    if (marker) {
        marker.setLatLng(e.latlng);
    } else {
        marker = L.marker(e.latlng).addTo(map);
    }
    
    // Mostrar coordenadas en popup
    marker.bindPopup(
        'Ubicación seleccionada:<br>' +
        'Lat: ' + lat.toFixed(6) + '<br>' +
        'Lng: ' + lng.toFixed(6)
    ).openPopup();
});

// Función para centrar mapa según ubicación seleccionada
function centrarMapa() {
    var estadoId = $('#estado-dropdown').val();
    var municipioId = $('#municipio-dropdown').val();
    var parroquiaId = $('#parroquia-dropdown').val();
    
    if (estadoId) {
        $.get('/escuela-pre-registro/obtener-coordenadas', {
            estado_id: estadoId,
            municipio_id: municipioId,
            parroquia_id: parroquiaId
        }, function(data) {
            if (data.success && data.lat && data.lng) {
                map.setView([data.lat, data.lng], data.zoom || 12);
                
                // Si no hay marcador, crear uno en la nueva ubicación
                if (!marker) {
                    marker = L.marker([data.lat, data.lng]).addTo(map);
                    $('#lat-input').val(data.lat);
                    $('#lng-input').val(data.lng);
                }
            }
        }).fail(function() {
            console.error('Error al cargar coordenadas');
        });
    }
}

// Centrar mapa cuando cambie la ubicación
$('#estado-dropdown, #municipio-dropdown, #parroquia-dropdown').change(function() {
    centrarMapa();
});

// Centrar mapa al cargar si hay ubicación
$(document).ready(function() {
    // Si no hay coordenadas pero hay ubicación seleccionada, centrar el mapa
    if (!$('#lat-input').val() && $('#estado-dropdown').val()) {
        centrarMapa();
    }
});

// Validación antes de enviar el formulario
$('form').on('submit', function(e) {
    if (!$('#lat-input').val() || !$('#lng-input').val()) {
        alert('Por favor, seleccione una ubicación en el mapa antes de enviar el formulario.');
        e.preventDefault();
        return false;
    }
    
    if (!$('#estado-dropdown').val() || !$('#municipio-dropdown').val() || !$('#parroquia-dropdown').val()) {
        alert('Por favor, complete toda la información de ubicación (Estado, Municipio y Parroquia).');
        e.preventDefault();
        return false;
    }
    
    return true;
});
JS;
$this->registerJs($mapScript);
?>