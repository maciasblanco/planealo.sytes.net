<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $atleta app\models\AtletasRegistro */
/* @var $representante app\models\RegistroRepresentantes|null */
/* @var $escuela app\models\Escuela|null */
/* @var $aportes array */
/* @var $totalPagado float */
/* @var $tasaCambio float */
/* @var $datosPago string */

$this->title = 'Recibo de Pago - ' . $atleta->p_nombre . ' ' . $atleta->p_apellido;
$this->params['breadcrumbs'][] = ['label' => 'Reportes', 'url' => ['reportes-representantes']];
$this->params['breadcrumbs'][] = $this->title;

// Función para generar enlace de WhatsApp
function whatsappLink($texto) {
    $texto = urlencode($texto);
    return "https://wa.me/?text=$texto";
}

// Función para generar enlace de correo
function emailLink($asunto, $cuerpo) {
    $asunto = urlencode($asunto);
    $cuerpo = urlencode($cuerpo);
    return "mailto:?subject=$asunto&body=$cuerpo";
}

// Preparar texto resumen para compartir
$resumen = "RECIBO DE PAGO\n";
$resumen .= "Atleta: {$atleta->p_nombre} {$atleta->p_apellido}\n";
if ($representante) {
    $resumen .= "Representante: {$representante->p_nombre} {$representante->p_apellido} - CI: {$representante->identificacion}\n";
}
$resumen .= "Total pagado: $" . number_format($totalPagado, 2) . "\n";
$resumen .= "Tasa de cambio: Bs. " . number_format($tasaCambio, 2) . " por USD\n";
$resumen .= "Datos para el pago: $datosPago\n";
$resumen .= "Generado: " . date('d/m/Y H:i');
?>

<div class="recibo-pago">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-xs-4">
                            <?php if ($escuela && $escuela->logo): ?>
                                <img src="<?= Yii::getAlias('@web') . '/' . $escuela->logo ?>" 
                                     alt="Logo <?= Html::encode($escuela->nombre) ?>" 
                                     style="max-height: 80px; max-width: 200px;">
                            <?php else: ?>
                                <div style="width: 200px; height: 80px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                    Logo de la escuela
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-xs-8 text-right">
                            <h2 class="box-title" style="font-size: 28px; font-weight: bold; color: #28a745;">
                                <i class="fas fa-receipt"></i> RECIBO DE PAGO
                            </h2>
                            <p class="text-muted"><?= date('d/m/Y H:i') ?></p>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <!-- Datos del representante y atleta -->
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Datos del Representante</h4>
                            <?php if ($representante): ?>
                                <p><strong>Nombre:</strong> <?= Html::encode($representante->p_nombre . ' ' . $representante->p_apellido) ?></p>
                                <p><strong>Cédula:</strong> <?= Html::encode($representante->identificacion) ?></p>
                                <p><strong>Teléfono:</strong> <?= Html::encode($representante->cell ?? 'N/A') ?></p>
                            <?php else: ?>
                                <p class="text-muted">No hay representante registrado.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h4>Datos del Atleta</h4>
                            <p><strong>Nombre:</strong> <?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?></p>
                            <p><strong>Cédula:</strong> <?= Html::encode($atleta->identificacion) ?></p>
                            <p><strong>Escuela:</strong> <?= $escuela ? Html::encode($escuela->nombre) : 'N/A' ?></p>
                        </div>
                    </div>

                    <hr>

                    <!-- Historial de pagos -->
                    <h4>Historial de Pagos</h4>
                    <?php if (empty($aportes)): ?>
                        <p class="text-muted">No hay pagos registrados para este atleta.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fecha Quincena</th>
                                        <th>N° Quincena</th>
                                        <th>Monto (USD)</th>
                                        <th>Monto (Bs)</th>
                                        <th>Fecha Pago</th>
                                        <th>Método</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($aportes as $aporte): ?>
                                    <tr>
                                        <td><?= Yii::$app->formatter->asDate($aporte->fecha_quincena) ?></td>
                                        <td class="text-center"><?= $aporte->numero_quincena ?></td>
                                        <td class="text-right">$<?= number_format($aporte->monto, 2) ?></td>
                                        <td class="text-right">
                                            <?= $aporte->monto_bs_original ? 'Bs. ' . number_format($aporte->monto_bs_original, 2) : '-' ?>
                                        </td>
                                        <td><?= $aporte->fecha_pago ? Yii::$app->formatter->asDate($aporte->fecha_pago) : '-' ?></td>
                                        <td><?= ucfirst($aporte->metodo_pago ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-right">Total:</th>
                                        <th class="text-right">$<?= number_format($totalPagado, 2) ?></th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <!-- Datos para el pago -->
                    <div class="well">
                        <h4><i class="fas fa-credit-card"></i> Datos para el pago</h4>
                        <p><?= nl2br(Html::encode($datosPago)) ?></p>
                    </div>

                    <!-- Botones de compartir -->
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <?php
                            $whatsappLink = whatsappLink($resumen);
                            $emailLink = emailLink('Recibo de Pago - ' . $atleta->p_nombre . ' ' . $atleta->p_apellido, $resumen);
                            ?>
                            <a href="<?= $whatsappLink ?>" target="_blank" class="btn btn-success" title="Compartir por WhatsApp">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="<?= $emailLink ?>" class="btn btn-info" title="Enviar por correo">
                                <i class="fas fa-envelope"></i> Correo
                            </a>
                            <button onclick="window.print()" class="btn btn-default" title="Imprimir">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <p class="text-muted">Este documento es un comprobante de pago generado por el sistema GED.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Estilos para impresión
$this->registerCss("
@media print {
    .btn, .box-footer, .breadcrumb, .navbar, .footer, .back-to-top, .sidebar-left, .main-footer {
        display: none !important;
    }
    .box {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    .box-header {
        background-color: #fff !important;
        color: #000 !important;
    }
    h2 {
        color: #000 !important;
    }
    table {
        border-collapse: collapse;
        width: 100%;
    }
    table th, table td {
        border: 1px solid #000;
        padding: 5px;
    }
}
");
?>