<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var array $atletasConDeuda */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    // ❌ MOSTRAR ERROR Y REDIRECCIÓN
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de realizar pagos múltiples.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/index'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Pago Múltiple de Semanas - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Semanales', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="pago-multiple">
    <div class="row">
        <div class="col-md-8">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-default']) ?>
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
                <small class="text-muted">Sistema GED - Pago Múltiple</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($atletasConDeuda)): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle"></i> No hay atletas con deudas pendientes en <?= Html::encode($nombre_escuela) ?>.
                </div>
            <?php else: ?>
                <?php $form = ActiveForm::begin(); ?>
                
                <!-- ✅ CAMPO OCULTO ESCUELA -->
                <input type="hidden" name="id_escuela" value="<?= $id_escuela ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Seleccionar Atleta:</label>
                            <select name="atleta_id" id="select-atleta" class="form-control" required>
                                <option value="">Seleccionar atleta...</option>
                                <?php foreach ($atletasConDeuda as $atleta): ?>
                                    <?php 
                                    $deuda = app\models\AportesSemanales::calcularDeudaAtleta($atleta->id);
                                    $montoDeuda = app\models\AportesSemanales::calcularMontoDeuda($atleta->id);
                                    ?>
                                    <option value="<?= $atleta->id ?>" 
                                            data-deuda="<?= $deuda ?>" 
                                            data-monto="<?= $montoDeuda ?>">
                                        <?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?>
                                        (<?= $deuda ?> semanas - $<?= number_format($montoDeuda, 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fecha de Pago:</label>
                            <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Método de Pago:</label>
                            <select name="metodo_pago" class="form-control" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="pago_movil">Pago Móvil</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Panel de semanas pendientes (se mostrará cuando seleccione un atleta) -->
                <div id="panel-semanas" style="display: none;">
                    <div class="form-group">
                        <label>Semanas Pendientes:</label>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> 
                            El atleta seleccionado tiene <span id="total-semanas">0</span> semanas pendientes 
                            por un total de $<span id="total-monto">0.00</span>.
                            Todas las semanas pendientes serán marcadas como pagadas.
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Comentarios:</label>
                    <input type="text" name="comentarios" class="form-control" placeholder="Observaciones del pago...">
                </div>

                <div class="form-group">
                    <?= Html::submitButton('<i class="fas fa-money-bill-wave"></i> Procesar Pago Múltiple', [
                        'class' => 'btn btn-success btn-lg',
                        'id' => 'btn-pagar'
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// JavaScript para mejorar la experiencia
$this->registerJs(<<<JS
    $(document).ready(function() {
        $('#select-atleta').change(function() {
            const atletaId = $(this).val();
            const selectedOption = $(this).find('option:selected');
            const deuda = selectedOption.data('deuda');
            const monto = selectedOption.data('monto');
            
            if (atletaId) {
                $('#panel-semanas').show();
                $('#total-semanas').text(deuda);
                $('#total-monto').text(monto.toFixed(2));
                $('#btn-pagar').prop('disabled', false);
            } else {
                $('#panel-semanas').hide();
                $('#btn-pagar').prop('disabled', true);
            }
        });
        
        // Inicialmente deshabilitar el botón
        $('#btn-pagar').prop('disabled', true);
    });
JS
);