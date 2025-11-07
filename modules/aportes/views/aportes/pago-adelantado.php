<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var array $atletas */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    // ❌ MOSTRAR ERROR Y REDIRECCIÓN
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de realizar pagos adelantados.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/index'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Pago por Adelantado - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Semanales', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="pago-adelantado">
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
                <small class="text-muted">Sistema GED - Pago Adelantado</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>
            
            <!-- ✅ CAMPO OCULTO ESCUELA -->
            <input type="hidden" name="id_escuela" value="<?= $id_escuela ?>">
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Seleccionar Atleta:</label>
                        <select name="atleta_id" id="select-atleta-adelantado" class="form-control" required>
                            <option value="">Seleccionar atleta...</option>
                            <?php foreach ($atletas as $atleta): ?>
                                <option value="<?= $atleta->id ?>">
                                    <?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?>
                                    (<?= Html::encode($atleta->identificacion) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Semanas a Adelantar:</label>
                        <select name="semanas_adelanto" id="semanas-adelanto" class="form-control" required>
                            <option value="1">1 semana</option>
                            <option value="2">2 semanas</option>
                            <option value="3">3 semanas</option>
                            <option value="4">4 semanas</option>
                            <option value="5">5 semanas</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fecha de Pago:</label>
                        <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Monto Total:</label>
                        <div id="monto-total" class="form-control-plaintext font-weight-bold text-success" style="font-size: 1.2rem;">
                            $0.00
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
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
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Comentarios:</label>
                        <input type="text" name="comentarios" class="form-control" placeholder="Pago por adelantado...">
                    </div>
                </div>
            </div>

            <!-- Información de las semanas que se van a pagar -->
            <div id="info-semanas" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle"></i> 
                Se registrarán aportes para las siguientes <span id="cantidad-semanas">0</span> semanas futuras:
                <div id="lista-semanas" class="mt-2 small">
                    <!-- La lista de semanas se generará con JavaScript -->
                </div>
            </div>

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Importante:</strong> El pago por adelantado creará registros de aportes para semanas futuras.
                Cada semana tiene un costo de $<?= number_format(app\models\AportesSemanales::MONTO_SEMANAL, 2) ?>.
            </div>

            <div class="form-group text-center">
                <?= Html::submitButton('<i class="fas fa-forward"></i> Procesar Pago Adelantado', [
                    'class' => 'btn btn-info btn-lg',
                    'id' => 'btn-procesar'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
// JavaScript para calcular el monto y mostrar información
$this->registerJs(<<<JS
    $(document).ready(function() {
        const MONTO_SEMANAL = parseFloat('<?= app\models\AportesSemanales::MONTO_SEMANAL ?>');
        
        function calcularMonto() {
            const semanas = parseInt($('#semanas-adelanto').val());
            const total = semanas * MONTO_SEMANAL;
            $('#monto-total').text('$' + total.toFixed(2));
            
            // Mostrar/ocultar información adicional
            if (semanas > 0 && $('#select-atleta-adelantado').val()) {
                $('#info-semanas').show();
                $('#cantidad-semanas').text(semanas);
                generarListaSemanas(semanas);
            } else {
                $('#info-semanas').hide();
            }
        }
        
        function generarListaSemanas(semanas) {
            let fecha = new Date();
            let html = '<ul>';
            
            // Ajustar al próximo viernes si no es viernes
            const diaSemana = fecha.getDay();
            if (diaSemana !== 5) { // 5 = viernes
                const diasHastaViernes = (5 - diaSemana + 7) % 7;
                fecha.setDate(fecha.getDate() + diasHastaViernes);
            }
            
            for (let i = 0; i < semanas; i++) {
                const fechaStr = fecha.toISOString().split('T')[0];
                const semanaNum = getWeekNumber(fecha);
                html += `<li>${fechaStr} (Semana ${semanaNum}) - $${MONTO_SEMANAL.toFixed(2)}</li>`;
                fecha.setDate(fecha.getDate() + 7); // Siguiente semana
            }
            
            html += '</ul>';
            $('#lista-semanas').html(html);
        }
        
        function getWeekNumber(d) {
            d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay()||7));
            var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
            var weekNo = Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7);
            return weekNo;
        }
        
        // Event listeners
        $('#semanas-adelanto').change(calcularMonto);
        $('#select-atleta-adelantado').change(function() {
            if ($(this).val()) {
                calcularMonto();
            } else {
                $('#info-semanas').hide();
            }
        });
        
        // Calcular monto inicial
        calcularMonto();
    });
JS
);