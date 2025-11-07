<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\AportesSemanales $model */
/** @var app\models\AtletasRegistro $atleta */
/** @var array $atletas */
/** @var array $historialDeudas */
/** @var int $semanasDeuda */
/** @var float $montoDeuda */
/** @var array $semanasPendientes */
/** @var int $posicionTop */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de gestionar aportes.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/select-escuela'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Gestión Integral de Aportes - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Semanales', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Obtener tasa actual del dólar desde la base de datos
$tasaDolarActual = \app\models\TasaDolar::getTasaActual();

// Pre-calcular valores para JavaScript
$montoSemanalDolares = \app\models\AportesSemanales::MONTO_SEMANAL;
$montoSemanalBolivares = $tasaDolarActual * $montoSemanalDolares;

// ✅ CORREGIDO - Filtrar atletas por escuela actual
$atletasFiltrados = \app\models\AtletasRegistro::find()
    ->where(['id_escuela' => $id_escuela])
    ->all();
?>

<div class="gestion-atleta">
    <!-- Mensajes Flash -->
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fas fa-check"></i>
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fas fa-ban"></i>
            <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('warning')): ?>
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fas fa-exclamation-triangle"></i>
            <?= Yii::$app->session->getFlash('warning') ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver al Listado', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <!-- Información de la Escuela -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-6">
                <strong><i class="fas fa-school"></i> Escuela Activa:</strong> <?= Html::encode($nombre_escuela) ?>
                <span class="badge bg-primary ms-2">ID: <?= $id_escuela ?></span>
            </div>
            <div class="col-md-6 text-right">
                <small class="text-muted">Sistema GED - Gestión de Aportes</small>
            </div>
        </div>
    </div>

    <!-- SOLUCIÓN ULTRA-SIMPLE - Lista de enlaces -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-user"></i> Seleccionar Atleta</h4>
        </div>
        <div class="card-body">
            <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($atletasFiltrados as $a): ?>
                    <?= Html::a(
                        '<i class="fas fa-user"></i> ' . $a->p_nombre . ' ' . $a->p_apellido . ' (' . $a->identificacion . ')',
                        ['/aportes/aportes/gestion-atleta', 'atleta_id' => $a->id],
                        [
                            'class' => 'list-group-item list-group-item-action' . ($atleta && $atleta->id == $a->id ? ' active' : '')
                        ]
                    ) ?>
                <?php endforeach; ?>
            </div>
            <div class="mt-2">
                <?= Html::a('<i class="fas fa-sync"></i> Limpiar Selección', ['/aportes/aportes/gestion-atleta'], ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </div>

    <?php if ($atleta): ?>
        <!-- Reconocimientos Top -->
        <?php if ($posicionTop): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-warning text-center">
                        <h3>
                            <?php if ($posicionTop == 1): ?>
                                <i class="fas fa-trophy text-warning"></i> COPA DE ORO
                            <?php elseif ($posicionTop == 2): ?>
                                <i class="fas fa-trophy text-secondary"></i> COPA DE PLATA
                            <?php elseif ($posicionTop == 3): ?>
                                <i class="fas fa-trophy" style="color: #cd7f32;"></i> COPA DE BRONCE
                            <?php endif; ?>
                        </h3>
                        <h4>¡FELICITACIONES <?= strtoupper($atleta->p_nombre) ?>!</h4>
                        <p>Eres el atleta #<?= $posicionTop ?> en aportes realizados. ¡Sigue así!</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Resumen del Atleta -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <div class="info-box-content">
                        <span class="info-box-text">Atleta</span>
                        <span class="info-box-number" style="font-size: 1.1rem;">
                            <?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?>
                        </span>
                        <span class="info-box-detail"><?= Html::encode($atleta->identificacion) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <div class="info-box-content">
                        <span class="info-box-text">Semanas Pagadas</span>
                        <span class="info-box-number"><?= count($historialDeudas) - $semanasDeuda ?></span>
                        <span class="info-box-detail">Total: <?= count($historialDeudas) ?> semanas</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <div class="info-box-content">
                        <span class="info-box-text">Semanas Deuda</span>
                        <span class="info-box-number"><?= $semanasDeuda ?></span>
                        <span class="info-box-detail">Desde: 15 Sep 2024</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <div class="info-box-content">
                        <span class="info-box-text">Monto Deuda</span>
                        <span class="info-box-number">$<?= number_format($montoDeuda, 2) ?></span>
                        <span class="info-box-detail">$<?= number_format(\app\models\AportesSemanales::MONTO_SEMANAL, 2) ?> por semana</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Tasa de Cambio -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-6">
                            <strong><i class="fas fa-dollar-sign"></i> Tasa de Cambio Actual:</strong> 
                            Bs. <?= number_format($tasaDolarActual, 2) ?> por $1.00
                            <br><small class="text-muted">Obtenida automáticamente del sistema</small>
                        </div>
                        <div class="col-md-6">
                            <strong>Aporte Semanal Equivalente:</strong> 
                            Bs. <?= number_format($montoSemanalBolivares, 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Columna Izquierda: Formularios -->
            <div class="col-md-4">
                <!-- Pago Individual -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Pago Individual</h5>
                    </div>
                    <div class="card-body">
                        <?php $form = ActiveForm::begin([
                            'action' => ['/aportes/aportes/gestion-atleta', 'atleta_id' => $atleta->id],
                            'enableClientValidation' => true,
                        ]); ?>
                            <input type="hidden" name="tipo_accion" value="individual">
                            
                            <?= $form->field($model, 'atleta_id')->hiddenInput(['value' => $atleta->id])->label(false) ?>
                            <?= $form->field($model, 'id_escuela')->hiddenInput(['value' => $id_escuela])->label(false) ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($model, 'fecha_viernes')->textInput([
                                        'type' => 'date',
                                        'class' => 'form-control',
                                        'required' => true
                                    ])->label('Fecha Viernes') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'monto')->textInput([
                                        'type' => 'number',
                                        'step' => '0.01',
                                        'class' => 'form-control',
                                        'id' => 'monto-dolares-individual',
                                        'value' => \app\models\AportesSemanales::MONTO_SEMANAL,
                                        'required' => true
                                    ])->label('Monto ($)') ?>
                                </div>
                            </div>

                            <!-- Campos hidden para tasa_cambio y monto_bs -->
                            <input type="hidden" name="tasa_cambio" id="tasa-cambio-hidden" value="<?= $tasaDolarActual ?>">
                            <input type="hidden" name="monto_bs" id="monto-bolivares-hidden">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tasa de Cambio Actual (Bs. por $1)</label>
                                        <div class="form-control-plaintext border rounded p-2 bg-light">
                                            <strong>Bs. <?= number_format($tasaDolarActual, 2) ?></strong>
                                        </div>
                                        <small class="form-text text-muted">Tasa obtenida automáticamente del sistema</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Monto en Bolívares (Calculado automáticamente)</label>
                                        <input type="text" class="form-control" 
                                               id="monto-bolivares-display"
                                               placeholder="Se calculará automáticamente" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($model, 'estado')->dropDownList([
                                        'pendiente' => 'Pendiente',
                                        'pagado' => 'Pagado'
                                    ], ['class' => 'form-control', 'required' => true])->label('Estado') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'metodo_pago')->dropDownList([
                                        'efectivo' => 'Efectivo',
                                        'transferencia' => 'Transferencia',
                                        'pago_movil' => 'Pago Móvil'
                                    ], ['prompt' => 'Seleccionar...', 'class' => 'form-control', 'required' => true])->label('Método Pago') ?>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($model, 'fecha_pago')->textInput([
                                        'type' => 'date',
                                        'class' => 'form-control'
                                    ])->label('Fecha de Pago') ?>
                                </div>
                            </div>
                            
                            <?= $form->field($model, 'comentarios')->textarea(['rows' => 2])->label('Comentarios') ?>

                            <div class="form-group">
                                <?= Html::submitButton('<i class="fas fa-save"></i> Guardar Pago Individual', [
                                    'class' => 'btn btn-success btn-block'
                                ]) ?>
                            </div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>

                <!-- Aporte Flexible -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-coins"></i> Aporte Flexible</h5>
                    </div>
                    <div class="card-body">
                        <?php $form = ActiveForm::begin(['action' => ['/aportes/aportes/gestion-atleta', 'atleta_id' => $atleta->id]]); ?>
                            <input type="hidden" name="tipo_accion" value="flexible">
                            <input type="hidden" name="id_escuela" value="<?= $id_escuela ?>">
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Monto Total a Aportar ($) *</label>
                                        <input type="number" step="0.01" min="2" class="form-control" 
                                               name="monto_flexible" id="monto-flexible" 
                                               value="<?= \app\models\AportesSemanales::MONTO_SEMANAL ?>" required>
                                        <small class="form-text text-muted">
                                            Mínimo: $<?= number_format(\app\models\AportesSemanales::MONTO_SEMANAL, 2) ?> (1 semana)
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tasa de Cambio Actual (Bs. por $1)</label>
                                        <div class="form-control-plaintext border rounded p-2 bg-light">
                                            <strong>Bs. <?= number_format($tasaDolarActual, 2) ?></strong>
                                            <input type="hidden" name="tasa_cambio_flexible" id="tasa-cambio-flexible" value="<?= $tasaDolarActual ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Monto en Bolívares</label>
                                        <input type="number" step="0.01" class="form-control" 
                                               name="monto_bs_flexible" id="monto-bolivares-flexible"
                                               placeholder="Se calculará automáticamente" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Semanas Equivalentes</label>
                                        <div class="form-control-plaintext border rounded p-2 bg-light" id="semanas-equivalentes">
                                            1 semana completa
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desglose del aporte -->
                            <div id="desglose-aporte" class="alert alert-light border" style="display: none;">
                                <h6><i class="fas fa-calculator"></i> Desglose del Aporte:</h6>
                                <div id="detalle-desglose"></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label>Fecha Pago *</label>
                                    <input type="date" name="fecha_pago_flexible" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Método Pago *</label>
                                    <select name="metodo_pago_flexible" class="form-control" required>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="pago_movil">Pago Móvil</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label>Comentarios</label>
                                <input type="text" name="comentarios_flexible" class="form-control" placeholder="Aporte flexible...">
                            </div>

                            <div class="form-group">
                                <?= Html::submitButton('<i class="fas fa-calculator"></i> Procesar Aporte Flexible', [
                                    'class' => 'btn btn-info btn-block'
                                ]) ?>
                            </div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>

                <!-- Pago Múltiple -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Pago Múltiple</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($semanasPendientes)): ?>
                            <?php $form = ActiveForm::begin(['action' => ['/aportes/aportes/gestion-atleta', 'atleta_id' => $atleta->id]]); ?>
                                <input type="hidden" name="tipo_accion" value="multiple">
                                <input type="hidden" name="id_escuela" value="<?= $id_escuela ?>">
                                
                                <div class="form-group">
                                    <label>Seleccionar Semanas Pendientes:</label>
                                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                        <?php foreach ($semanasPendientes as $semana): ?>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="semanas[]" value="<?= $semana['fecha_viernes'] ?>" checked>
                                                    <?= Yii::$app->formatter->asDate($semana['fecha_viernes'], 'medium') ?>
                                                    (Semana <?= $semana['numero_semana'] ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Conversión para pago múltiple - TASA AUTOMÁTICA -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Tasa de Cambio Actual (Bs. por $1)</label>
                                            <div class="form-control-plaintext border rounded p-2 bg-light">
                                                <strong>Bs. <?= number_format($tasaDolarActual, 2) ?></strong>
                                                <input type="hidden" id="tasa-cambio-multiple" name="tasa_cambio_multiple" value="<?= $tasaDolarActual ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Monto Total en Bs. (Calculado automáticamente)</label>
                                            <input type="number" step="0.01" class="form-control" 
                                                   id="monto-bolivares-multiple" name="monto_bs_multiple" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Fecha Pago *</label>
                                        <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Método Pago *</label>
                                        <select name="metodo_pago" class="form-control" required>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="transferencia">Transferencia</option>
                                            <option value="pago_movil">Pago Móvil</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Comentarios</label>
                                    <input type="text" name="comentarios" class="form-control" placeholder="Observaciones...">
                                </div>

                                <div class="form-group">
                                    <?= Html::submitButton('<i class="fas fa-money-bill-wave"></i> Pagar Seleccionados', [
                                        'class' => 'btn btn-primary btn-block'
                                    ]) ?>
                                </div>
                            <?php ActiveForm::end(); ?>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-check-circle"></i><br>
                                No hay semanas pendientes para pago múltiple.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pago Adelantado -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Pago Adelantado</h5>
                    </div>
                    <div class="card-body">
                        <?php $form = ActiveForm::begin(['action' => ['/aportes/aportes/gestion-atleta', 'atleta_id' => $atleta->id]]); ?>
                            <input type="hidden" name="tipo_accion" value="adelantado">
                            <input type="hidden" name="id_escuela" value="<?= $id_escuela ?>">
                            
                            <div class="form-group">
                                <label>Semanas a Adelantar *</label>
                                <select name="semanas_adelanto" class="form-control" id="semanas-adelanto" required>
                                    <option value="1">1 semana</option>
                                    <option value="2">2 semanas</option>
                                    <option value="3">3 semanas</option>
                                    <option value="4">4 semanas</option>
                                    <option value="5">5 semanas</option>
                                </select>
                            </div>

                            <!-- Conversión para pago adelantado - TASA AUTOMÁTICA -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tasa de Cambio Actual (Bs. por $1)</label>
                                        <div class="form-control-plaintext border rounded p-2 bg-light">
                                            <strong>Bs. <?= number_format($tasaDolarActual, 2) ?></strong>
                                            <input type="hidden" id="tasa-cambio-adelanto" name="tasa_cambio_adelanto" value="<?= $tasaDolarActual ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Monto Total en Bs. (Calculado automáticamente)</label>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="monto-bolivares-adelanto" name="monto_bs_adelanto" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label>Fecha Pago *</label>
                                    <input type="date" name="fecha_pago_adelanto" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Método Pago *</label>
                                    <select name="metodo_pago_adelanto" class="form-control" required>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="pago_movil">Pago Móvil</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Comentarios</label>
                                <input type="text" name="comentarios_adelanto" class="form-control" placeholder="Pago por adelantado...">
                            </div>

                            <div class="form-group">
                                <?= Html::submitButton('<i class="fas fa-forward"></i> Pagar por Adelantado', [
                                    'class' => 'btn btn-warning btn-block'
                                ]) ?>
                            </div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Historial -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-0"><i class="fas fa-history"></i> Historial Completo (Desde 15 Sep 2024)</h5>
                            </div>
                            <div class="col-md-4 text-right">
                                <span class="badge badge-light">Total: <?= count($historialDeudas) ?> semanas</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historialDeudas)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-2x"></i><br>
                                No hay historial de aportes para este atleta.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Fecha Viernes</th>
                                            <th>Semana</th>
                                            <th>Estado</th>
                                            <th>Fecha Pago</th>
                                            <th>Método</th>
                                            <th>Monto $</th>
                                            <th>Monto Bs.</th>
                                            <th>Tasa</th>
                                            <th>Tipo</th>
                                            <th>Comentarios</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historialDeudas as $semana): ?>
                                            <tr class="<?= $semana['estado'] == 'pendiente' ? 'table-warning' : 'table-success' ?>">
                                                <td><?= Yii::$app->formatter->asDate($semana['fecha_viernes'], 'long') ?></td>
                                                <td class="text-center"><?= $semana['numero_semana'] ?></td>
                                                <td class="text-center">
                                                    <?php if ($semana['estado'] == 'pagado'): ?>
                                                        <span class="badge badge-success">Pagado</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= isset($semana['fecha_pago']) && $semana['fecha_pago'] ? Yii::$app->formatter->asDate($semana['fecha_pago'], 'medium') : '-' ?>
                                                </td>
                                                <td>
                                                    <?= isset($semana['metodo_pago']) ? ucfirst($semana['metodo_pago']) : '-' ?>
                                                </td>
                                                <td class="text-right">
                                                    <strong>$<?= number_format($semana['monto'], 2) ?></strong>
                                                    <?php if (isset($semana['es_parcial']) && $semana['es_parcial']): ?>
                                                        <br><small class="text-muted">Parcial</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <?= isset($semana['monto_bs']) && $semana['monto_bs'] ? 'Bs. ' . number_format($semana['monto_bs'], 2) : '-' ?>
                                                </td>
                                                <td class="text-right">
                                                    <?= isset($semana['tasa_cambio']) && $semana['tasa_cambio'] ? number_format($semana['tasa_cambio'], 2) : '-' ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (isset($semana['tipo_aporte'])): ?>
                                                        <?php if ($semana['tipo_aporte'] == 'adelantado'): ?>
                                                            <span class="badge badge-info">Adelantado</span>
                                                        <?php elseif ($semana['tipo_aporte'] == 'flexible'): ?>
                                                            <span class="badge badge-primary">Flexible</span>
                                                        <?php elseif ($semana['tipo_aporte'] == 'parcial'): ?>
                                                            <span class="badge badge-secondary">Parcial</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-light">Normal</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge badge-light">Normal</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?= isset($semana['comentarios']) ? Html::encode($semana['comentarios']) : '-' ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Totales:</strong></td>
                                            <td class="text-right"><strong>$<?= number_format(array_sum(array_column($historialDeudas, 'monto')), 2) ?></strong></td>
                                            <td class="text-right">
                                                <strong>
                                                    <?php
                                                    $totalBs = 0;
                                                    foreach ($historialDeudas as $semana) {
                                                        if (isset($semana['monto_bs']) && $semana['monto_bs']) {
                                                            $totalBs += $semana['monto_bs'];
                                                        }
                                                    }
                                                    echo $totalBs > 0 ? 'Bs. ' . number_format($totalBs, 2) : '-';
                                                    ?>
                                                </strong>
                                            </td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Resumen Estadístico -->
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <h3 class="text-success"><?= count($historialDeudas) - $semanasDeuda ?></h3>
                                        <p class="text-muted">Semanas Pagadas</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <h3 class="text-warning"><?= $semanasDeuda ?></h3>
                                        <p class="text-muted">Semanas Pendientes</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <h3 class="text-info"><?= count($historialDeudas) ?></h3>
                                        <p class="text-muted">Total Semanas</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <h3 class="text-primary">$<?= number_format($montoDeuda, 2) ?></h3>
                                        <p class="text-muted">Deuda Actual</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-2x mb-3"></i>
            <h4>Selecciona un atleta para comenzar</h4>
            <p>Usa el selector superior para elegir un atleta y gestionar sus aportes.</p>
        </div>
    <?php endif; ?>
</div>

<?php
// JavaScript para conversión de moneda y cálculos - VERSIÓN MEJORADA
$js = <<<JS
$(document).ready(function() {
    const MONTO_SEMANAL = parseFloat('$montoSemanalBolivares');
    const MONTO_SEMANAL_DOLARES = parseFloat('$montoSemanalDolares');
    const TASA_ACTUAL = parseFloat('$tasaDolarActual');
    
    // ===== FUNCIONES DE CONVERSIÓN =====
    
    // Conversión para pago individual
    function calcularConversionIndividual() {
        var montoDolares = parseFloat($('#monto-dolares-individual').val()) || 0;
        
        if (TASA_ACTUAL > 0 && montoDolares > 0) {
            // Calcular de $ a Bs
            var nuevoMontoBs = montoDolares * TASA_ACTUAL;
            $('#monto-bolivares-hidden').val(nuevoMontoBs.toFixed(2));
            $('#monto-bolivares-display').val('Bs. ' + nuevoMontoBs.toFixed(2));
            
            // Mostrar semanas equivalentes
            var semanas = montoDolares / MONTO_SEMANAL_DOLARES;
            if (semanas > 1) {
                $('#monto-dolares-individual').next('.help-block').remove();
                $('#monto-dolares-individual').after('<div class="help-block text-info"><small>Equivale a ' + semanas.toFixed(1) + ' semanas</small></div>');
            }
        }
    }
    
    // Conversión para pago flexible
    function calcularDesgloseFlexible() {
        const montoDolares = parseFloat($('#monto-flexible').val()) || 0;
        
        // Calcular semanas equivalentes
        const semanasEquivalentes = montoDolares / MONTO_SEMANAL_DOLARES;
        const semanasCompletas = Math.floor(semanasEquivalentes);
        const montoRestante = montoDolares - (semanasCompletas * MONTO_SEMANAL_DOLARES);
        
        // Actualizar display
        let texto = '';
        if (semanasCompletas > 0) {
            texto += semanasCompletas + ' semana(s) completa(s)'; 
        }
        if (montoRestante > 0) {
            if (texto) texto += ' + ';
            texto += '$' + montoRestante.toFixed(2) + ' (parcial)';
        }
        $('#semanas-equivalentes').text(texto || '0 semanas');
        
        // Calcular monto en bolívares
        if (TASA_ACTUAL > 0) {
            const montoBs = montoDolares * TASA_ACTUAL;
            $('#monto-bolivares-flexible').val(montoBs.toFixed(2));
        }
        
        // Mostrar desglose detallado
        if (montoDolares >= MONTO_SEMANAL_DOLARES) {
            let desglose = '<ul class="mb-0">';
            if (semanasCompletas > 0) {
                desglose += '<li>' + semanasCompletas + ' semana(s) × $' + MONTO_SEMANAL_DOLARES.toFixed(2) + ' = $' + (semanasCompletas * MONTO_SEMANAL_DOLARES).toFixed(2) + '</li>';
            }
            if (montoRestante > 0) {
                desglose += '<li>Aporte parcial: $' + montoRestante.toFixed(2) + '</li>';
                desglose += '<li><small>Saldo disponible para próxima semana: $' + (MONTO_SEMANAL_DOLARES - montoRestante).toFixed(2) + '</small></li>';
            }
            desglose += '<li><strong>Total: $' + montoDolares.toFixed(2) + '</strong></li>';
            desglose += '</ul>';
            
            $('#detalle-desglose').html(desglose);
            $('#desglose-aporte').show();
        } else {
            $('#desglose-aporte').hide();
        }
    }
    
    // Calcular monto total en bolívares para pago múltiple
    function calcularMontoTotalMultiple() {
        var semanas = $('input[name="semanas[]"]:checked').length;
        var montoTotalDolares = semanas * MONTO_SEMANAL_DOLARES;
        var montoTotalBs = montoTotalDolares * TASA_ACTUAL;
        
        $('#monto-bolivares-multiple').val(montoTotalBs.toFixed(2));
    }
    
    // Calcular monto total en bolívares para pago adelantado
    function calcularMontoTotalAdelanto() {
        var semanas = parseInt($('#semanas-adelanto').val()) || 0;
        var montoTotalDolares = semanas * MONTO_SEMANAL_DOLARES;
        var montoTotalBs = montoTotalDolares * TASA_ACTUAL;
        
        $('#monto-bolivares-adelanto').val(montoTotalBs.toFixed(2));
    }
    
    // ===== EVENT LISTENERS =====
    
    // Pago Individual
    $('#monto-dolares-individual').on('input', calcularConversionIndividual);
    
    // Aporte Flexible
    $('#monto-flexible').on('input', calcularDesgloseFlexible);
    
    // Pago Múltiple
    $('input[name="semanas[]"]').change(function() {
        calcularMontoTotalMultiple();
    });
    
    // Pago Adelantado
    $('#semanas-adelanto').on('change', function() {
        calcularMontoTotalAdelanto();
    });
    
    // ===== INICIALIZACIÓN =====
    
    // Inicializar cálculos
    calcularConversionIndividual();
    calcularDesgloseFlexible();
    calcularMontoTotalMultiple();
    calcularMontoTotalAdelanto();
    
    // Auto-calcular al cargar la página
    setTimeout(function() {
        calcularConversionIndividual();
        calcularDesgloseFlexible();
    }, 500);
});
JS;

$this->registerJs($js);
?>