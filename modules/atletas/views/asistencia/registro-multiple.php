<?php
// modules/atletas/views/asistencia/registro-multiple.php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Escuela;
use app\models\AportesSemanales;

$this->title = 'Registro Múltiple de Asistencia';
$this->params['breadcrumbs'][] = ['label' => 'Asistencia', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Variables de control para la vista
$mostrarPanelAtletas = !empty($idEscuelaSeleccionada) && !empty($atletas);
$mostrarMensajeSinAtletas = !empty($idEscuelaSeleccionada) && empty($atletas);
$mostrarSeleccionEscuela = empty($idEscuelaSeleccionada);
?>

<div class="asistencia-registro-multiple" data-ged-module="registro-multiple">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users"></i> <?= $this->title ?>
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-clock"></i> <?= date('d/m/Y H:i:s') ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> 
                    <?= Yii::$app->session->getFlash('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= Yii::$app->session->getFlash('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($mostrarSeleccionEscuela): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-school"></i>
                    <h5>Seleccione una escuela para comenzar</h5>
                    <p>Por favor, elija una escuela del menú desplegable para cargar la lista de atletas.</p>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <?php $form = ActiveForm::begin([
                        'id' => 'registro-multiple-form',
                        'options' => ['class' => 'form-horizontal'],
                        'enableClientValidation' => false,
                        'enableAjaxValidation' => false,
                        'method' => 'get',
                    ]); ?>

                    <!-- Campo oculto para mantener la acción -->
                    <input type="hidden" name="r" value="atletas/asistencia/registro-multiple">

                    <!-- Selección de Escuela -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-school"></i> Paso 1: Seleccionar Escuela
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="form-label fw-bold">Escuela/Club:</label>
                                                <select name="id_escuela" id="select-escuela" class="form-control form-control-lg" required>
                                                    <option value="">-- Seleccione una Escuela --</option>
                                                    <?php foreach (Escuela::find()->where(['eliminado' => false])->orderBy(['nombre' => SORT_ASC])->all() as $escuela): ?>
                                                        <option value="<?= $escuela->id ?>" 
                                                            <?= $idEscuelaSeleccionada == $escuela->id ? 'selected' : '' ?>>
                                                            <?= Html::encode($escuela->nombre) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Fecha de Práctica:</label>
                                                <?= $form->field($model, 'fecha_practica')->textInput([
                                                    'type' => 'date',
                                                    'value' => date('Y-m-d'),
                                                    'class' => 'form-control',
                                                    'readonly' => false
                                                ])->label(false) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12 text-center">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-sync-alt"></i> Cargar Lista de Atletas
                                            </button>
                                            <small class="form-text text-muted d-block mt-2">
                                                Haga clic aquí después de seleccionar la escuela para cargar los atletas.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-info-circle"></i> Información de la Escuela
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="info-escuela" class="text-center">
                                        <?php if ($idEscuelaSeleccionada): ?>
                                            <?php 
                                            $escuela = Escuela::findOne($idEscuelaSeleccionada);
                                            if ($escuela): 
                                                $totalAtletas = count($atletas);
                                                $asistenciasHoy = \app\models\Asistencia::find()
                                                    ->where([
                                                        'fecha_practica' => date('Y-m-d'), 
                                                        'asistio' => true, 
                                                        'eliminado' => false, 
                                                        'id_escuela' => $idEscuelaSeleccionada
                                                    ])
                                                    ->count();
                                            ?>
                                                <h5><?= Html::encode($escuela->nombre) ?></h5>
                                                <div class="row text-center mt-3">
                                                    <div class="col-4">
                                                        <div class="bg-primary text-white p-2 rounded">
                                                            <h6 class="mb-0"><?= $totalAtletas ?></h6>
                                                            <small>Total Atletas</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="bg-success text-white p-2 rounded">
                                                            <h6 class="mb-0"><?= $asistenciasHoy ?></h6>
                                                            <small>Asistencias Hoy</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="bg-warning text-white p-2 rounded">
                                                            <h6 class="mb-0"><?= max(0, $totalAtletas - $asistenciasHoy) ?></h6>
                                                            <small>Sin Asistencia</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    <p class="mb-0">La escuela seleccionada no existe o fue eliminada.</p>
                                                    <small>Por favor, seleccione otra escuela.</small>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-muted">
                                                <i class="fas fa-info-circle"></i>
                                                Seleccione una escuela para ver la información
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($mostrarMensajeSinAtletas): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                            <h5 class="mt-2">No hay atletas registrados en esta escuela</h5>
                            <p>La escuela seleccionada no tiene atletas activos registrados.</p>
                            <div class="mt-3">
                                <?= Html::a('<i class="fas fa-user-plus"></i> Registrar Nuevo Atleta', 
                                    ['/atletas/registro/create', 'id_escuela' => $idEscuelaSeleccionada], 
                                    ['class' => 'btn btn-primary']) ?>
                                <?= Html::a('<i class="fas fa-redo"></i> Seleccionar Otra Escuela', 
                                    ['registro-multiple'], 
                                    ['class' => 'btn btn-secondary']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($mostrarPanelAtletas): ?>
                    <div id="panel-atletas">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-list"></i> Paso 2: Seleccionar Atletas que Asistieron
                                    <span id="contador-seleccionados" class="badge bg-light text-dark float-end">0 seleccionados</span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                            <label class="form-check-label fw-bold" for="select-all">
                                                <i class="fas fa-check-double"></i> Seleccionar Todos los Atletas Disponibles
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button type="button" id="btn-seleccionar-sin-asistencia" class="btn btn-warning btn-sm">
                                            <i class="fas fa-user-clock"></i> Seleccionar Solo Sin Asistencia Hoy
                                        </button>
                                        <button type="button" id="btn-deseleccionar-todos" class="btn btn-secondary btn-sm ms-2">
                                            <i class="fas fa-times"></i> Deseleccionar Todos
                                        </button>
                                    </div>
                                </div>

                                <div id="lista-atletas" class="atletas-list-container">
                                    <?php if (!empty($atletas)): ?>
                                        <?php foreach ($atletas as $index => $atleta): ?>
                                            <?php
                                            $fechaSeleccionada = !empty($model->fecha_practica) ? $model->fecha_practica : date('Y-m-d');
                                            $tieneAsistencia = \app\models\Asistencia::find()
                                                ->where([
                                                    'id_atleta' => $atleta->id, 
                                                    'fecha_practica' => $fechaSeleccionada, 
                                                    'eliminado' => false
                                                ])
                                                ->exists();
                                            
                                            $montoDeuda = AportesSemanales::calcularMontoDeuda($atleta->id);
                                            $semanasDeuda = AportesSemanales::calcularDeudaAtleta($atleta->id);
                                            $estadoDeuda = $montoDeuda <= 0 ? 'AL DÍA' : '$' . number_format($montoDeuda, 2) . ' (' . $semanasDeuda . ' semanas)';
                                            $claseDeuda = $montoDeuda <= 0 ? 'text-success' : 'text-danger';
                                            $claseFila = $tieneAsistencia ? 'bg-light text-muted' : '';
                                            ?>
                                            <div class="atleta-item row mb-2 p-2 border rounded align-items-center <?= $claseFila ?>" 
                                                 data-atleta-id="<?= $atleta->id ?>"
                                                 data-tiene-asistencia="<?= $tieneAsistencia ? 'true' : 'false' ?>">
                                                <div class="col-md-1">
                                                    <div class="form-check">
                                                        <input class="form-check-input atleta-checkbox" 
                                                               type="checkbox" 
                                                               name="id_atletas[]" 
                                                               value="<?= $atleta->id ?>" 
                                                               id="atleta-<?= $atleta->id ?>"
                                                               <?= $tieneAsistencia ? 'disabled' : '' ?>
                                                               data-atleta-nombre="<?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-11">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-3">
                                                            <strong class="d-block"><?= Html::encode($atleta->p_nombre) ?> <?= Html::encode($atleta->p_apellido) ?></strong>
                                                            <small class="text-muted">CI: <?= Html::encode($atleta->identificacion) ?></small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small><strong>Categoría:</strong><br><?= Html::encode($atleta->getCategoriaNombre()) ?></small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small><strong>Teléfono:</strong><br><?= Html::encode($atleta->cell ?: 'N/A') ?></small>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small><strong>Estado de Aportes:</strong><br>
                                                                <span class="<?= $claseDeuda ?> fw-bold">
                                                                    <?= $estadoDeuda ?>
                                                                </span>
                                                            </small>
                                                        </div>
                                                        <div class="col-md-2 text-center">
                                                            <?php if ($tieneAsistencia): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check"></i> Ya registrado
                                                                </span>
                                                                <small class="d-block text-muted"><?= $fechaSeleccionada ?></small>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">
                                                                    <i class="fas fa-user-clock"></i> Disponible
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <strong>Total Atletas:</strong> <span id="total-atletas" class="badge bg-primary"><?= count($atletas) ?></span>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Con Asistencia Hoy:</strong> <span id="con-asistencia" class="badge bg-success">0</span>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Disponibles:</strong> <span id="disponibles" class="badge bg-warning"><?= count($atletas) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-edit"></i> Paso 3: Comentarios y Guardar
                                </h6>
                            </div>
                            <div class="card-body">
                                <?= $form->field($model, 'comentarios')->textarea([
                                    'rows' => 3,
                                    'placeholder' => 'Observaciones o comentarios generales para todos los atletas seleccionados...'
                                ])->label('Comentarios (Opcional)') ?>

                                <div class="form-group mt-4">
                                    <?= Html::submitButton('<i class="fas fa-save"></i> Registrar Asistencia para Atletas Seleccionados', [
                                        'class' => 'btn btn-success btn-lg w-100',
                                        'id' => 'btn-registrar-multiple',
                                        'disabled' => true
                                    ]) ?>
                                    <small class="form-text text-muted text-center d-block mt-2">
                                        <i class="fas fa-info-circle"></i> Solo se registrarán los atletas que estén seleccionados y NO tengan asistencia previa en la fecha indicada.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ ÚNICO JS INLINE PERMITIDO POR PROTOCOLO - Inicialización mínima -->
<?php $this->registerJs(<<<JS
// Inicializador para módulo de registro múltiple
document.addEventListener('DOMContentLoaded', function() {
    if (typeof gedSystem !== 'undefined' && gedSystem.config && gedSystem.config.isInitialized) {
        if (document.querySelector('[data-ged-module="registro-multiple"]')) {
            gedSystem.initRegistroMultipleModule();
        }
    }
});
JS
); ?>