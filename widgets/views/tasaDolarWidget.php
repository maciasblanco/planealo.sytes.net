<?php
// widgets/views/tasaDolarWidget.php

use yii\helpers\Html;
use yii\helpers\Url;

// USAR LA CONSTANTE CORRECTA: MONTO_QUINCENAL_USD en lugar de MONTO_SEMANAL
$montoQuincenal = \app\models\AportesSemanales::MONTO_QUINCENAL_USD;
$montoQuincenalBs = $tasaActual * $montoQuincenal;
?>

<div class="tasa-dolar-widget card <?= $compact ? 'compact' : '' ?>">
    <div class="card-header bg-info text-white py-2">
        <small>
            <i class="fas fa-dollar-sign me-1"></i> 
            Tasa del Dólar
            <?php if (!$compact): ?>
                <span class="float-end">
                    <i class="fas fa-calendar-alt me-1"></i> Sistema Quincenal
                </span>
            <?php endif; ?>
        </small>
    </div>
    <div class="card-body p-3">
        <div class="row align-items-center">
            <div class="col-7">
                <h4 class="mb-0">Bs. <?= number_format($tasaActual, 2, ',', '.') ?></h4>
                <p class="text-muted small mb-0">
                    <i class="fas fa-calendar-day me-1"></i> 
                    <?= date('d/m/Y') ?>
                </p>
            </div>
            <div class="col-5 text-end">
                <div class="btn-group" role="group">
                    <a href="<?= Url::to(['/tasa-dolar/index']) ?>" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-chart-line"></i>
                    </a>
                    <?php if ($showCalculator): ?>
                        <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#calculadoraModal">
                            <i class="fas fa-calculator"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!$compact): ?>
            <hr class="my-2">
            <div class="row">
                <div class="col-12">
                    <p class="small mb-1">
                        <i class="fas fa-money-bill-wave me-1"></i>
                        <strong>Aporte Quincenal:</strong>
                    </p>
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-primary">USD $<?= number_format($montoQuincenal, 2, '.', ',') ?></span>
                        <span class="badge bg-warning text-dark">Bs. <?= number_format($montoQuincenalBs, 2, ',', '.') ?></span>
                    </div>
                    <p class="small text-muted mt-1 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Cada 15 días (días 15 y último día del mes)
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($showCalculator && !$compact): ?>
    <!-- Modal de calculadora -->
    <div class="modal fade" id="calculadoraModal" tabindex="-1" role="dialog" aria-labelledby="calculadoraModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calculadoraModalLabel">
                        <i class="fas fa-calculator me-1"></i> Calculadora de Divisas
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="montoUsd">Monto en USD</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" step="0.01" class="form-control" id="montoUsd" placeholder="0.00" value="<?= $montoQuincenal ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="montoBs">Monto en Bs</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Bs.</span>
                            </div>
                            <input type="number" step="0.01" class="form-control" id="montoBs" placeholder="0.00" value="<?= number_format($montoQuincenalBs, 2, '.', '') ?>">
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Tasa actual: <strong>Bs. <?= number_format($tasaActual, 2, ',', '.') ?></strong> por USD $1.00
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="calcularDivisa()">Calcular</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calcularDivisa() {
            let tasa = <?= $tasaActual ?>;
            let montoUsd = parseFloat(document.getElementById('montoUsd').value);
            let montoBs = parseFloat(document.getElementById('montoBs').value);

            // Si se modifica USD, calcular Bs
            if (!isNaN(montoUsd) && montoUsd >= 0) {
                let resultado = montoUsd * tasa;
                document.getElementById('montoBs').value = resultado.toFixed(2);
            }
            // Si se modifica Bs, calcular USD
            else if (!isNaN(montoBs) && montoBs >= 0) {
                let resultado = montoBs / tasa;
                document.getElementById('montoUsd').value = resultado.toFixed(2);
            }
        }

        // Calcular al cambiar el valor en USD
        document.getElementById('montoUsd').addEventListener('input', function() {
            document.getElementById('montoBs').value = '';
            calcularDivisa();
        });

        // Calcular al cambiar el valor en Bs
        document.getElementById('montoBs').addEventListener('input', function() {
            document.getElementById('montoUsd').value = '';
            calcularDivisa();
        });
    </script>
<?php endif; ?>