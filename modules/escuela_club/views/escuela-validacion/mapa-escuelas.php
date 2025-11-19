<?php
// [file name]: modules/escuela_club/views/escuela-validacion/mapa-escuelas.php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Escuela;
use app\assets\AppAsset;

/** @var yii\web\View $this */
/** @var app\models\Escuela[] $escuelas */

$this->title = 'Mapa de Escuelas y Clubes Registrados';
$this->params['breadcrumbs'][] = $this->title;

// Obtener escuelas aprobadas si no se pasan como parámetro
if (!isset($escuelas)) {
    $escuelas = Escuela::findActive()->all();
}

// Preparar datos para el mapa
$escuelasData = [];
foreach ($escuelas as $escuela) {
    if ($escuela->lat && $escuela->lng) {
        $escuelasData[] = [
            'id' => $escuela->id,
            'nombre' => Html::encode($escuela->nombre),
            'tipo' => $escuela->tipo_entidad ? 'Escuela' : 'Club',
            'direccion' => Html::encode($escuela->getDireccionCompleta()),
            'telefono' => Html::encode($escuela->telefono),
            'email' => Html::encode($escuela->email),
            'lat' => (float)$escuela->lat,
            'lng' => (float)$escuela->lng,
            'logo' => $escuela->getLogoUrl(),
            'url' => Url::to(['/escuela-club/escuela-registro/view', 'id' => $escuela->id]),
        ];
    }
}

// Registrar assets
AppAsset::register($this);
?>

<div class="escuela-mapa">
    <div class="card card-custom">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-map-marked-alt"></i> <?= Html::encode($this->title) ?>
            </h3>
            <div class="card-tools">
                <span class="badge badge-light badge-pill">
                    <?= count($escuelasData) ?> escuelas en el mapa
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Controles del mapa -->
            <div class="map-controls p-3 border-bottom">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label for="filtro-tipo" class="font-weight-bold">Filtrar por tipo:</label>
                            <select id="filtro-tipo" class="form-control form-control-sm">
                                <option value="todos">Todos los tipos</option>
                                <option value="escuela">Solo Escuelas</option>
                                <option value="club">Solo Clubes</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label for="buscar-escuela" class="font-weight-bold">Buscar escuela:</label>
                            <input type="text" id="buscar-escuela" class="form-control form-control-sm" 
                                   placeholder="Escriba el nombre de la escuela...">
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="btn-group btn-group-sm">
                            <button type="button" id="zoom-in" class="btn btn-outline-primary">
                                <i class="fas fa-search-plus"></i> Acercar
                            </button>
                            <button type="button" id="zoom-out" class="btn btn-outline-primary">
                                <i class="fas fa-search-minus"></i> Alejar
                            </button>
                            <button type="button" id="reset-view" class="btn btn-outline-secondary">
                                <i class="fas fa-sync"></i> Vista inicial
                            </button>
                            <button type="button" id="mi-ubicacion" class="btn btn-outline-info">
                                <i class="fas fa-location-arrow"></i> Mi ubicación
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedor del mapa -->
            <div id="mapa-escuelas" style="height: 600px; width: 100%;"></div>

            <!-- Leyenda del mapa -->
            <div class="map-legend p-3 border-top">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Leyenda:</h6>
                        <div class="legend-items">
                            <div class="legend-item d-inline-block mr-3">
                                <i class="fas fa-map-marker-alt text-success fa-lg"></i>
                                <span class="ml-1">Escuela</span>
                            </div>
                            <div class="legend-item d-inline-block mr-3">
                                <i class="fas fa-map-marker-alt text-info fa-lg"></i>
                                <span class="ml-1">Club</span>
                            </div>
                            <div class="legend-item d-inline-block">
                                <i class="fas fa-map-marker-alt text-warning fa-lg"></i>
                                <span class="ml-1">Seleccionado</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Pase el cursor sobre cualquier marcador para ver la información de la escuela
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel lateral de información (opcional) -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Información del Mapa
                    </h5>
                </div>
                <div class="card-body">
                    <div id="info-panel">
                        <p class="text-muted">Seleccione una escuela en el mapa para ver detalles</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> Escuelas en el Mapa (<?= count($escuelasData) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div id="lista-escuelas" class="list-group" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($escuelasData as $escuela): ?>
                            <a href="#" class="list-group-item list-group-item-action escuela-item" 
                               data-id="<?= $escuela['id'] ?>" 
                               data-lat="<?= $escuela['lat'] ?>" 
                               data-lng="<?= $escuela['lng'] ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= $escuela['nombre'] ?></h6>
                                    <small class="text-<?= $escuela['tipo'] === 'Escuela' ? 'success' : 'info' ?>">
                                        <?= $escuela['tipo'] ?>
                                    </small>
                                </div>
                                <p class="mb-1"><?= $escuela['direccion'] ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-phone"></i> <?= $escuela['telefono'] ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Pasar datos a JavaScript
$this->registerJs("var escuelasData = " . json_encode($escuelasData) . ";", \yii\web\View::POS_HEAD);
?>