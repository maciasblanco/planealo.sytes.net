<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Escuela;

/** @var yii\web\View $this */
/** @var app\models\Escuela $model */

$this->title = $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Escuelas/Clubes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');
?>
<div class="escuela-view">
    <div class="card card-custom">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">
                    <i class="fas fa-school text-primary me-2"></i>
                    <?= Html::encode($this->title) ?>
                </h3>
            </div>
            <div class="card-tools">
                <?php if ($model->isAprobado() && $id_escuela != $model->id): ?>
                    <?= Html::a('<i class="fas fa-check me-1"></i> Seleccionar', ['select-escuela', 'id' => $model->id], [
                        'class' => 'btn btn-success me-1',
                        'data' => [
                            'confirm' => '¿Seleccionar esta escuela como activa?',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php elseif ($id_escuela == $model->id): ?>
                    <span class="badge badge-success p-2">
                        <i class="fas fa-check"></i> Escuela Activa
                    </span>
                <?php endif; ?>
                
                <?= Html::a('<i class="fas fa-edit me-1"></i> Editar', ['update', 'id' => $model->id], [
                    'class' => 'btn btn-primary me-1'
                ]) ?>
                <?= Html::a('<i class="fas fa-list me-1"></i> Volver', ['index'], [
                    'class' => 'btn btn-default'
                ]) ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h4 class="text-primary">Información General</h4>
                </div>
                <div class="col-md-4 text-right">
                    <?php
                    $badgeClass = 'badge-secondary';
                    if ($model->estado_registro === Escuela::ESTADO_APROBADO) {
                        $badgeClass = 'badge-success';
                    } elseif ($model->estado_registro === Escuela::ESTADO_PENDIENTE) {
                        $badgeClass = 'badge-warning';
                    } elseif ($model->estado_registro === Escuela::ESTADO_RECHAZADO) {
                        $badgeClass = 'badge-danger';
                    }
                    ?>
                    <span class="badge <?= $badgeClass ?> badge-lg p-2">
                        <?= $model->getEstadoRegistroLabel() ?>
                    </span>
                </div>
            </div>

            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute' => 'logo',
                        'format' => 'raw',
                        'value' => function($model) {
                            if ($model->logo) {
                                return Html::img($model->getLogoUrl(), [
                                    'class' => 'img-thumbnail',
                                    'style' => 'max-width: 150px;'
                                ]);
                            }
                            return '<span class="text-muted">No disponible</span>';
                        },
                    ],
                    'id',
                    'nombre',
                    [
                        'attribute' => 'tipo_entidad',
                        'value' => $model->tipo_entidad ? 'Escuela' : 'Club',
                    ],
                    'telefono',
                    'email:email',
                    [
                        'attribute' => 'estado_registro',
                        'value' => $model->getEstadoRegistroLabel(),
                    ],
                    [
                        'label' => 'Ubicación de Prácticas',
                        'value' => $model->getDireccionCompleta(),
                    ],
                    'direccion_administrativa',
                    [
                        'attribute' => 'id_estado',
                        'value' => $model->estado->estado ?? 'N/A',
                    ],
                    [
                        'attribute' => 'id_municipio',
                        'value' => $model->municipio->municipio ?? 'N/A',
                    ],
                    [
                        'attribute' => 'id_parroquia',
                        'value' => $model->parroquia->parroquia ?? 'N/A',
                    ],
                    'lat',
                    'lng',
                    [
                        'attribute' => 'mision',
                        'format' => 'ntext',
                        'visible' => !empty($model->mision),
                    ],
                    [
                        'attribute' => 'vision',
                        'format' => 'ntext',
                        'visible' => !empty($model->vision),
                    ],
                    [
                        'attribute' => 'objetivos',
                        'format' => 'ntext',
                        'visible' => !empty($model->objetivos),
                    ],
                    [
                        'attribute' => 'historia',
                        'format' => 'ntext',
                        'visible' => !empty($model->historia),
                    ],
                    [
                        'attribute' => 'horarios',
                        'format' => 'ntext',
                        'visible' => !empty($model->horarios),
                    ],
                    [
                        'attribute' => 'redes_sociales',
                        'format' => 'ntext',
                        'visible' => !empty($model->redes_sociales),
                    ],
                    'd_creacion:datetime',
                    'd_update:datetime',
                    [
                        'attribute' => 'comentarios_aprobacion',
                        'format' => 'ntext',
                        'visible' => !empty($model->comentarios_aprobacion),
                    ],
                    [
                        'attribute' => 'fecha_aprobacion',
                        'format' => 'datetime',
                        'visible' => !empty($model->fecha_aprobacion),
                    ],
                ],
                'options' => ['class' => 'table table-striped table-bordered detail-view'],
            ]) ?>

            <?php if ($model->lat && $model->lng): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="text-primary">
                        <i class="fas fa-map-marker-alt me-2"></i>Ubicación en el Mapa
                    </h5>
                    <div id="map" style="height: 300px; border: 1px solid #ddd; border-radius: 5px;"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Mostrar mapa si hay coordenadas
if ($model->lat && $model->lng) {
    $this->registerCssFile('https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
    $this->registerJsFile('https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', ['depends' => [yii\web\JqueryAsset::className()]]);
    
    $mapScript = <<< JS
    var map = L.map('map').setView([{$model->lat}, {$model->lng}], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    var marker = L.marker([{$model->lat}, {$model->lng}]).addTo(map);
    marker.bindPopup(
        '<strong>{$model->nombre}</strong><br>' +
        '{$model->getDireccionCompleta()}'
    ).openPopup();
JS;
    $this->registerJs($mapScript);
}
?>