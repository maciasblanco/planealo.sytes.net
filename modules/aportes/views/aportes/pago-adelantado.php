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
$this->params['breadcrumbs'][] = ['label' => 'Aportes Quincenales', 'url' => ['index']];
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
                        <label>Quincenas a Adelantar:</label>
                        <select name="quincenas_adelanto" id="quincenas-adelanto" class="form-control" required>
                            <option value="1">1 quincena</option>
                            <option value="2">2 quincenas</option>
                            <option value="3">3 quincenas</option>
                            <option value="4">4 quincenas</option>
                            <option value="5">5 quincenas</option>
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

            <!-- Información de las quincenas que se van a pagar -->
            <div id="info-quincenas" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle"></i> 
                Se registrarán aportes para las siguientes <span id="cantidad-quincenas">0</span> quincenas futuras:
                <div id="lista-quincenas" class="mt-2 small">
                    <!-- La lista de quincenas se generará con JavaScript -->
                </div>
            </div>

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Importante:</strong> El pago por adelantado creará registros de aportes para quincenas futuras.
                Cada quincena tiene un costo de $<?= number_format(app\models\AportesSemanales::MONTO_QUINCENAL, 2) ?>.
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
// JavaScript para calcular el monto y mostrar información - ACTUALIZADO A QUINCENAS
$this->registerJs(<<<JS
    $(document).ready(function() {
        const MONTO_QUINCENAL = parseFloat('<?= app\models\AportesSemanales::MONTO_QUINCENAL ?>');
        
        function calcularMonto() {
            const quincenas = parseInt($('#quincenas-adelanto').val());
            const total = quincenas * MONTO_QUINCENAL;
            $('#monto-total').text('$' + total.toFixed(2));
            
            // Mostrar/ocultar información adicional
            if (quincenas > 0 && $('#select-atleta-adelantado').val()) {
                $('#info-quincenas').show();
                $('#cantidad-quincenas').text(quincenas);
                generarListaQuincenas(quincenas);
            } else {
                $('#info-quincenas').hide();
            }
        }
        
        function generarListaQuincenas(quincenas) {
            let fecha = new Date();
            let html = '<ul>';
            
            // Obtener la próxima fecha de quincena usando la función del modelo
            // En el controlador se usa AportesSemanales::calcularProximaQuincena()
            // Para simular en JavaScript, calculamos cada 15 días a partir del 15/01/2026
            let fechaInicio = new Date('2026-01-15');
            let hoy = new Date();
            
            // Encontrar la próxima quincena después de hoy
            let fechaActual = new Date(fechaInicio);
            
            // Avanzar hasta encontrar una quincena futura o igual a hoy
            while (fechaActual < hoy) {
                fechaActual.setDate(fechaActual.getDate() + 15);
            }
            
            // Ajustar para que sea exactamente día 15 o 30 del mes
            let dia = fechaActual.getDate();
            if (dia !== 15 && dia !== 30) {
                // Si no es 15 ni 30, ajustar al próximo 15 o 30
                if (dia < 15) {
                    fechaActual.setDate(15);
                } else if (dia < 30) {
                    fechaActual.setDate(30);
                } else {
                    // Si es mayor a 30, ir al 15 del próximo mes
                    fechaActual.setMonth(fechaActual.getMonth() + 1);
                    fechaActual.setDate(15);
                }
            }
            
            for (let i = 0; i < quincenas; i++) {
                const fechaStr = fechaActual.toISOString().split('T')[0];
                const quincenaNum = calcularNumeroQuincena(fechaActual);
                html += `<li>${fechaStr} (Quincena ${quincenaNum}) - $${MONTO_QUINCENAL.toFixed(2)}</li>`;
                
                // Siguiente quincena (15 días después)
                fechaActual.setDate(fechaActual.getDate() + 15);
                
                // Ajustar si el mes tiene menos de 30 días
                if (fechaActual.getDate() > 30) {
                    fechaActual.setDate(15);
                    fechaActual.setMonth(fechaActual.getMonth() + 1);
                }
            }
            
            html += '</ul>';
            $('#lista-quincenas').html(html);
        }
        
        function calcularNumeroQuincena(fecha) {
            // Fecha de inicio: 15/01/2026
            const inicio = new Date('2026-01-15');
            const diffTime = Math.abs(fecha - inicio);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const numeroQuincena = Math.floor(diffDays / 15) + 1;
            return numeroQuincena;
        }
        
        // Event listeners
        $('#quincenas-adelanto').change(calcularMonto);
        $('#select-atleta-adelantado').change(function() {
            if ($(this).val()) {
                calcularMonto();
            } else {
                $('#info-quincenas').hide();
            }
        });
        
        // Calcular monto inicial
        calcularMonto();
    });
JS
);