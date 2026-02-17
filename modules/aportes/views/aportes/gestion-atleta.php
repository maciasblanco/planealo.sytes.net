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
/** @var int $quincenasDeuda */
/** @var float $montoDeuda */
/** @var array $quincenasPendientes */
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
$this->params['breadcrumbs'][] = ['label' => 'Aportes Quincenales', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Obtener tasa actual del dólar desde la base de datos
$tasaDolarActual = \app\models\TasaDolar::getTasaActual();

// Pre-calcular valores para JavaScript usando constantes del modelo
$montoQuincenalDolares = \app\models\AportesSemanales::MONTO_QUINCENAL_USD;
$montoQuincenalBolivares = $tasaDolarActual * $montoQuincenalDolares;
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
                <?php foreach ($atletas as $a): ?>
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
                        <span class="info-box-text">Quincenas Pagadas</span>
                        <span class="info-box-number"><?= count($historialDeudas) - $quincenasDeuda ?></span>
                        <span class="info-box-detail">Total: <?= count($historialDeudas) ?> quincenas</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <div class="info-box-content">
                        <span class="info-box-text">Quincenas Deuda</span>
                        <span class="info-box-number"><?= $quincenasDeuda ?></span>
                        <span class="info-box-detail">Desde: 15/01/2026</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <div class="info-box-content">
                        <span class="info-box-text">Monto Deuda</span>
                        <span class="info-box-number">$<?= number_format($montoDeuda, 2) ?></span>
                        <span class="info-box-detail">$<?= number_format(\app\models\AportesSemanales::MONTO_QUINCENAL_USD, 2) ?> por quincena</span>
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
                            <strong>Aporte Quincenal Equivalente:</strong> 
                            Bs. <?= number_format($tasaDolarActual * $montoQuincenalDolares, 2) ?>
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
                                    <?= $form->field($model, 'fecha_quincena')->textInput([
                                        'type' => 'date',
                                        'class' => 'form-control',
                                        'required' => true
                                    ])->label('Fecha Quincena') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'monto')->textInput([
                                        'type' => 'number',
                                        'step' => '0.01',
                                        'class' => 'form-control',
                                        'id' => 'monto-dolares-individual',
                                        'value' => $montoQuincenalDolares,
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
                                        <input type="number" step="0.01" min="<?= $montoQuincenalDolares ?>" class="form-control" 
                                               name="monto_flexible" id="monto-flexible" 
                                               value="<?= $montoQuincenalDolares ?>" required>
                                        <small class="form-text text-muted">
                                            Mínimo: $<?= number_format($montoQuincenalDolares, 2) ?> (1 quincena)
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
                                        <label>Quincenas Equivalentes</label>
                                        <div class="form-control-plaintext border rounded p-2 bg-light" id="quincenas-equivalentes">
                                            1 quincena completa
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
                        <?php if (!empty($quincenasPendientes)): ?>
                            <?php $form = ActiveForm::begin(['action' => ['/aportes/aportes/gestion-atleta', 'atleta_id' => $atleta->id]]); ?>
                                <input type="hidden" name="tipo_accion" value="multiple">
                                <input type="hidden" name="id_escuela" value="<?= $id_escuela ?>">
                                
                                <div class="form-group">
                                    <label>Seleccionar Quincenas Pendientes:</label>
                                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                        <?php foreach ($quincenasPendientes as $quincena): ?>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="quincenas[]" value="<?= $quincena['fecha_quincena'] ?>" checked>
                                                    <?= Yii::$app->formatter->asDate($quincena['fecha_quincena'], 'medium') ?>
                                                    (Quincena <?= $quincena['numero_quincena'] ?>) - $<?= number_format($quincena['monto'], 2) ?>
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
                                No hay quincenas pendientes para pago múltiple.
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
                                <label>Quincenas a Adelantar *</label>
                                <select name="quincenas_adelanto" class="form-control" id="quincenas-adelanto" required>
                                    <option value="1">1 quincena</option>
                                    <option value="2">2 quincenas</option>
                                    <option value="3">3 quincenas</option>
                                    <option value="4">4 quincenas</option>
                                    <option value="5">5 quincenas</option>
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
                                <h5 class="mb-0"><i class="fas fa-history"></i> Historial Completo (Desde 15/01/2026)</h5>
                            </div>
                            <div class="col-md-4 text-right">
                                <span class="badge badge-light">Total: <?= count($historialDeudas) ?> quincenas</span>
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
                                            <th>Fecha Quincena</th>
                                            <th>Quincena #</th>
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
                                        <?php foreach ($historialDeudas as $quincena): ?>
                                            <tr class="<?= $quincena['estado'] == 'pendiente' ? 'table-warning' : 'table-success' ?>">
                                                <td><?= Yii::$app->formatter->asDate($quincena['fecha_quincena'], 'long') ?></td>
                                                <td class="text-center"><?= $quincena['numero_quincena'] ?></td>
                                                <td class="text-center">
                                                    <?php if ($quincena['estado'] == 'pagado'): ?>
                                                        <span class="badge badge-success">Pagado</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= isset($quincena['fecha_pago']) && $quincena['fecha_pago'] ? Yii::$app->formatter->asDate($quincena['fecha_pago'], 'medium') : '-' ?>
                                                </td>
                                                <td>
                                                    <?= isset($quincena['metodo_pago']) ? ucfirst($quincena['metodo_pago']) : '-' ?>
                                                </td>
                                                <td class="text-right">
                                                    <strong>$<?= number_format($quincena['monto'], 2) ?></strong>
                                                    <?php if (isset($quincena['pago_parcial']) && $quincena['pago_parcial']): ?>
                                                        <br><small class="text-muted">Parcial</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <?= isset($quincena['monto_bs']) && $quincena['monto_bs'] ? 'Bs. ' . number_format($quincena['monto_bs'], 2) : '-' ?>
                                                </td>
                                                <td class="text-right">
                                                    <?= isset($quincena['tasa_cambio']) && $quincena['tasa_cambio'] ? number_format($quincena['tasa_cambio'], 2) : '-' ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $tipo = $quincena['tipo_aporte'] ?? 'normal';
                                                    $badgeClass = 'badge-light';
                                                    $label = 'Normal';
                                                    if ($tipo == 'adelantado') {
                                                        $badgeClass = 'badge-info';
                                                        $label = 'Adelantado';
                                                    } elseif ($tipo == 'flexible') {
                                                        $badgeClass = 'badge-primary';
                                                        $label = 'Flexible';
                                                    } elseif ($tipo == 'parcial') {
                                                        $badgeClass = 'badge-secondary';
                                                        $label = 'Parcial';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>"><?= $label ?></span>
                                                </td>
                                                <td>
                                                    <small><?= isset($quincena['comentarios']) ? Html::encode($quincena['comentarios']) : '-' ?></small>
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
                                                    foreach ($historialDeudas as $quincena) {
                                                        if (isset($quincena['monto_bs']) && $quincena['monto_bs']) {
                                                            $totalBs += $quincena['monto_bs'];
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
                                        <h3 class="text-success"><?= count($historialDeudas) - $quincenasDeuda ?></h3>
                                        <p class="text-muted">Quincenas Pagadas</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <h3 class="text-warning"><?= $quincenasDeuda ?></h3>
                                        <p class="text-muted">Quincenas Pendientes</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <h3 class="text-info"><?= count($historialDeudas) ?></h3>
                                        <p class="text-muted">Total Quincenas</p>
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
// JavaScript para conversión de moneda y cálculos - VERSIÓN MEJORADA PARA QUINCENAS
$js = <<<JS
$(document).ready(function() {
    const MONTO_QUINCENAL_BOLIVARES = parseFloat('$montoQuincenalBolivares');
    const MONTO_QUINCENAL_DOLARES = parseFloat('$montoQuincenalDolares');
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
            
            // Mostrar quincenas equivalentes
            var quincenas = montoDolares / MONTO_QUINCENAL_DOLARES;
            if (quincenas > 1) {
                $('#monto-dolares-individual').next('.help-block').remove();
                $('#monto-dolares-individual').after('<div class="help-block text-info"><small>Equivale a ' + quincenas.toFixed(1) + ' quincenas</small></div>');
            }
        }
    }
    
    // Conversión para pago flexible
    function calcularDesgloseFlexible() {
        const montoDolares = parseFloat($('#monto-flexible').val()) || 0;
        
        // Calcular quincenas equivalentes
        const quincenasEquivalentes = montoDolares / MONTO_QUINCENAL_DOLARES;
        const quincenasCompletas = Math.floor(quincenasEquivalentes);
        const montoRestante = montoDolares - (quincenasCompletas * MONTO_QUINCENAL_DOLARES);
        
        // Actualizar display
        let texto = '';
        if (quincenasCompletas > 0) {
            texto += quincenasCompletas + ' quincena(s) completa(s)'; 
        }
        if (montoRestante > 0) {
            if (texto) texto += ' + ';
            texto += '$' + montoRestante.toFixed(2) + ' (parcial)';
        }
        $('#quincenas-equivalentes').text(texto || '0 quincenas');
        
        // Calcular monto en bolívares
        if (TASA_ACTUAL > 0) {
            const montoBs = montoDolares * TASA_ACTUAL;
            $('#monto-bolivares-flexible').val(montoBs.toFixed(2));
        }
        
        // Mostrar desglose detallado
        if (montoDolares >= MONTO_QUINCENAL_DOLARES) {
            let desglose = '<ul class="mb-0">';
            if (quincenasCompletas > 0) {
                desglose += '<li>' + quincenasCompletas + ' quincena(s) × $' + MONTO_QUINCENAL_DOLARES.toFixed(2) + ' = $' + (quincenasCompletas * MONTO_QUINCENAL_DOLARES).toFixed(2) + '</li>';
            }
            if (montoRestante > 0) {
                desglose += '<li>Aporte parcial: $' + montoRestante.toFixed(2) + '</li>';
                desglose += '<li><small>Saldo disponible para próxima quincena: $' + (MONTO_QUINCENAL_DOLARES - montoRestante).toFixed(2) + '</small></li>';
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
        var quincenas = $('input[name="quincenas[]"]:checked').length;
        var montoTotalDolares = quincenas * MONTO_QUINCENAL_DOLARES;
        var montoTotalBs = montoTotalDolares * TASA_ACTUAL;
        
        $('#monto-bolivares-multiple').val(montoTotalBs.toFixed(2));
    }
    
    // Calcular monto total en bolívares para pago adelantado
    function calcularMontoTotalAdelanto() {
        var quincenas = parseInt($('#quincenas-adelanto').val()) || 0;
        var montoTotalDolares = quincenas * MONTO_QUINCENAL_DOLARES;
        var montoTotalBs = montoTotalDolares * TASA_ACTUAL;
        
        $('#monto-bolivares-adelanto').val(montoTotalBs.toFixed(2));
    }
    
    // ===== EVENT LISTENERS =====
    
    // Pago Individual
    $('#monto-dolares-individual').on('input', calcularConversionIndividual);
    
    // Aporte Flexible
    $('#monto-flexible').on('input', calcularDesgloseFlexible);
    
    // Pago Múltiple
    $(document).on('change', 'input[name="quincenas[]"]', function() {
        calcularMontoTotalMultiple();
    });
    
    // Pago Adelantado
    $('#quincenas-adelanto').on('change', function() {
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