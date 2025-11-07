<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Nacionalidad;
use app\models\Sexo;
use app\models\Alergias;
use app\models\Enfermedades;
use app\models\Discapacidad;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var app\models\AtletasRegistro $model */

$this->title = $model->p_nombre . ' ' . $model->p_apellido;
$this->params['breadcrumbs'][] = ['label' => 'Atletas Registros', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

// Helper functions para mostrar datos relacionados
function getNacionalidad($id) {
    $nac = Nacionalidad::findOne($id);
    return $nac ? $nac->letra : 'No especificado';
}

function getSexo($id) {
    $sexo = Sexo::findOne($id);
    return $sexo ? $sexo->descripcion : 'No especificado';
}

function getAlergia($id) {
    $alergia = Alergias::findOne($id);
    return $alergia ? $alergia->descripcion : 'Ninguna';
}

function getEnfermedad($id) {
    $enfermedad = Enfermedades::findOne($id);
    return $enfermedad ? $enfermedad->descripcion : 'Ninguna';
}

function getDiscapacidad($id) {
    $discapacidad = Discapacidad::findOne($id);
    return $discapacidad ? $discapacidad->descripcion : 'Ninguna';
}

function getBooleanText($value) {
    return $value ? 'Sí' : 'No';
}
?>
<div class="atletas-registro-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="btn-group">
            <?= Html::a('Regresar', ['index'], ['class' => 'btn btn-secondary']) ?>
            <?= Html::a('Actualizar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancelar', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => '¿Está seguro de que desea eliminar este atleta?',
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>

    <!-- ===== DATOS DEL ATLETA ===== -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-user me-2"></i>Datos del Atleta</h4>
        </div>
        <div class="card-body">
            
            <!-- Datos Personales del Atleta -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Datos Personales</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Primer Nombre:</strong>
                            <p class="mb-2"><?= Html::encode($model->p_nombre) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Segundo Nombre:</strong>
                            <p class="mb-2"><?= Html::encode($model->s_nombre) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Primer Apellido:</strong>
                            <p class="mb-2"><?= Html::encode($model->p_apellido) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Segundo Apellido:</strong>
                            <p class="mb-2"><?= Html::encode($model->s_apellido) ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <strong>Nacionalidad:</strong>
                            <p class="mb-2"><?= getNacionalidad($model->id_nac) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Cédula:</strong>
                            <p class="mb-2"><?= Html::encode($model->identificacion) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Fecha Nacimiento:</strong>
                            <p class="mb-2"><?= Yii::$app->formatter->asDate($model->fn, 'php:d/m/Y') ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Edad:</strong>
                            <p class="mb-2"><?= Html::encode($model->getEdad()) ?> años</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Categoría:</strong>
                            <p class="mb-2"><?= Html::encode($model->getCategoriaCalculada()) ?></p>
                        </div>
                        <div class="col-md-2">
                            <strong>Sexo:</strong>
                            <p class="mb-2"><?= getSexo($model->sexo) ?></p>
                        </div>
                        <div class="col-md-2">
                            <strong>Categoría:</strong>
                            <p class="mb-2"><?= Html::encode($model->categoria) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos Médicos -->
            <div class="card mb-3">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Datos Médicos</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Alergias:</strong>
                            <p class="mb-2"><?= getAlergia($model->id_alergias) ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Enfermedades Crónicas:</strong>
                            <p class="mb-2"><?= getEnfermedad($model->id_enfermedades) ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Discapacidad:</strong>
                            <p class="mb-2"><?= getDiscapacidad($model->id_discapacidad) ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Asma:</strong>
                            <p class="mb-2"><?= getBooleanText($model->asma) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos Antropométricos -->
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-weight me-2"></i>Datos Antropométricos</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Estatura (mts):</strong>
                            <p class="mb-2"><?= Html::encode($model->estatura) ?> m</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Peso (Kgs):</strong>
                            <p class="mb-2"><?= Html::encode($model->peso) ?> kg</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Talla Franela:</strong>
                            <p class="mb-2"><?= Html::encode($model->talla_franela) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Talla Short:</strong>
                            <p class="mb-2"><?= Html::encode($model->talla_short) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos de Contacto del Atleta -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Datos de Contacto del Atleta</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Teléfono Celular:</strong>
                            <p class="mb-2"><?= Html::encode($model->cell) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Teléfono Fijo:</strong>
                            <p class="mb-2"><?= Html::encode($model->telf) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== DATOS DEL REPRESENTANTE ===== -->
    <div class="card mb-4">
        <div class="card-header" style="background-color: #6f42c1; color: white;">
            <h4 class="mb-0"><i class="fas fa-user-friends me-2"></i>Datos del Representante</h4>
        </div>
        <div class="card-body">
            <?php if ($model->id_representante): ?>
                <?php
                $representante = \app\models\RegistroRepresentantes::findOne($model->id_representante);
                if ($representante): ?>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Primer Nombre:</strong>
                            <p class="mb-2"><?= Html::encode($representante->p_nombre) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Segundo Nombre:</strong>
                            <p class="mb-2"><?= Html::encode($representante->s_nombre) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Primer Apellido:</strong>
                            <p class="mb-2"><?= Html::encode($representante->p_apellido) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Segundo Apellido:</strong>
                            <p class="mb-2"><?= Html::encode($representante->s_apellido) ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <strong>Nacionalidad:</strong>
                            <p class="mb-2"><?= getNacionalidad($representante->id_nac) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Cédula:</strong>
                            <p class="mb-2"><?= Html::encode($representante->identificacion) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Teléfono Celular:</strong>
                            <p class="mb-2"><?= Html::encode($representante->cell) ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se encontró información del representante.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No se ha asignado un representante.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== DATOS SOLO USO ESCUELA/CLUB ===== -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0"><i class="fas fa-school me-2"></i>Datos Solo Uso Escuela/Club</h4>
        </div>
        <div class="card-body">
            
            <!-- Información de la Escuela/Club -->
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de la Escuela/Club</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>ID Escuela:</strong>
                            <p class="mb-2"><?= Html::encode($model->id_escuela) ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong>ID Club:</strong>
                            <p class="mb-2"><?= Html::encode($model->id_club) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>Información del Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Fecha Creación:</strong>
                            <p class="mb-2"><?= Yii::$app->formatter->asDatetime($model->d_creacion, 'php:d/m/Y H:i') ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Usuario Creación:</strong>
                            <p class="mb-2"><?= Html::encode($model->u_creacion) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Fecha Actualización:</strong>
                            <p class="mb-2"><?= Yii::$app->formatter->asDatetime($model->d_update, 'php:d/m/Y H:i') ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Usuario Actualización:</strong>
                            <p class="mb-2"><?= Html::encode($model->u_update) ?></p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <strong>Eliminado:</strong>
                            <p class="mb-2"><?= getBooleanText($model->eliminado) ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>IP Registro:</strong>
                            <p class="mb-2"><?= Html::encode($model->dir_ip) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de acción en la parte inferior -->
    <div class="card">
        <div class="card-body text-center">
            <div class="btn-group" role="group">
                <?= Html::a('<i class="fas fa-arrow-left me-2"></i>Regresar al Listado', ['index'], ['class' => 'btn btn-secondary']) ?>
                <?= Html::a('<i class="fas fa-edit me-2"></i>Actualizar Datos', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fas fa-times me-2"></i>Cancelar Registro', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => '¿Está seguro de que desea eliminar este atleta? Esta acción no se puede deshacer.',
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        </div>
    </div>

</div>

<?php
// CSS adicional para mejorar la presentación
$this->registerCss(<<<CSS
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }
    .card-body p {
        margin-bottom: 0.5rem;
        padding: 0.5rem;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
    }
    .btn-group .btn {
        margin: 0 0.25rem;
    }
    strong {
        color: #495057;
        font-weight: 600;
    }
CSS
);
?>