<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $atleta app\models\AtletasRegistro */
/* @var $representante app\models\RegistroRepresentantes|null */
/* @var $escuela app\models\Escuela|null */
/* @var $deudas array */
/* @var $totalDeuda float */
/* @var $becaActiva app\models\Beca|null */
/* @var $porcentajeBeca int */
/* @var $tasaCambio float */
/* @var $datosPago string */

$this->title = 'Balance de Pagos - ' . $atleta->p_nombre . ' ' . $atleta->p_apellido;
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

// Calcular monto con descuento de beca si aplica
$descuento = 0;
$totalConDescuento = $totalDeuda;
if ($becaActiva && $porcentajeBeca > 0) {
    $descuento = $totalDeuda * ($porcentajeBeca / 100);
    $totalConDescuento = $totalDeuda - $descuento;
}

// Preparar texto resumen para compartir (ahora incluye el total con descuento)
$resumen = "BALANCE DE PAGOS\n";
$resumen .= "Atleta: {$atleta->p_nombre} {$atleta->p_apellido}\n";
if ($representante) {
    $resumen .= "Representante: {$representante->p_nombre} {$representante->p_apellido} - CI: {$representante->identificacion}\n";
}
if ($becaActiva) {
    $resumen .= "Total adeudado (sin beca): $" . number_format($totalDeuda, 2) . "\n";
    $resumen .= "Beca activa: {$porcentajeBeca}% de descuento\n";
    $resumen .= "Descuento: $" . number_format($descuento, 2) . "\n";
    $resumen .= "Total a pagar (con beca): $" . number_format($totalConDescuento, 2) . "\n";
} else {
    $resumen .= "Monto total adeudado: $" . number_format($totalDeuda, 2) . "\n";
}
$resumen .= "Tasa de cambio: Bs. " . number_format($tasaCambio, 2) . " por USD\n";
$resumen .= "Datos para el pago: $datosPago\n";
$resumen .= "Generado: " . date('d/m/Y H:i');

// --- Obtener datos de sesión para el logo ---
$logoUrl = Yii::$app->session->get('logo_url');
$nombreEscuela = Yii::$app->session->get('nombre_escuela', $escuela->nombre ?? '');
$tamanoLogo = Yii::$app->session->get('tamano_logo', '200x200');
list($ancho, $alto) = explode('x', $tamanoLogo);

// Calcular número de pagos atrasados
$numDeudas = count($deudas);
?>

<div class="recibo-cobro">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-xs-4">
                            <?php if ($logoUrl): ?>
                                <img src="<?= $logoUrl ?>" 
                                     alt="Logo <?= Html::encode($nombreEscuela) ?>" 
                                     style="max-height: <?= $alto ?>px; max-width: <?= $ancho ?>px;"
                                     onerror="this.style.display='none'; this.parentNode.querySelector('.logo-fallback').classList.remove('d-none');">
                                <div class="logo-fallback d-none" style="width: <?= $ancho ?>px; height: <?= $alto ?>px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                    <?= Html::encode($nombreEscuela) ?>
                                </div>
                            <?php else: ?>
                                <div style="width: <?= $ancho ?>px; height: <?= $alto ?>px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                    <?= Html::encode($nombreEscuela) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-xs-8 text-right">
                            <h2 class="box-title" style="font-size: 28px; font-weight: bold; color: #dc3545;">
                                <i class="fas fa-file-invoice"></i> BALANCE DE PAGOS
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

                    <!-- Información de deuda y beca (modificada para reflejar descuento) -->
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Resumen de Deuda</h4>
                            <?php if ($becaActiva): ?>
                                <p><strong>Total adeudado (sin beca):</strong> <span style="font-size: 1.2em;">$<?= number_format($totalDeuda, 2) ?></span></p>
                                <p><strong>Descuento por beca (<?= $porcentajeBeca ?>%):</strong> <span class="text-success">- $<?= number_format($descuento, 2) ?></span></p>
                                <p><strong>Total a pagar (con beca):</strong> <span style="font-size: 1.5em; color: #28a745;">$<?= number_format($totalConDescuento, 2) ?></span></p>
                            <?php else: ?>
                                <p><strong>Total adeudado:</strong> <span style="font-size: 1.5em; color: #dc3545;">$<?= number_format($totalDeuda, 2) ?></span></p>
                            <?php endif; ?>
                            <p><strong>Equivalente en Bs:</strong> Bs. <?= number_format($totalConDescuento * $tasaCambio, 2) ?> (tasa Bs. <?= number_format($tasaCambio, 2) ?>)</p>
                            <p><strong>Número de pagos atrasados:</strong> <?= $numDeudas ?></p>
                        </div>
                        <div class="col-md-6">
                            <h4>Beca Activa</h4>
                            <?php if ($becaActiva): ?>
                                <p><span class="badge badge-success">Sí</span> - <?= Html::encode($becaActiva->tipoBeca->nombre ?? 'Beca') ?> (<?= $porcentajeBeca ?>% descuento)</p>
                            <?php else: ?>
                                <p><span class="badge badge-secondary">No</span></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <!-- Detalle de quincenas pendientes (muestra montos originales sin descuento) -->
                    <h4>Quincenas Pendientes (montos base)</h4>
                    <?php if (empty($deudas)): ?>
                        <p class="text-success">El atleta no tiene deudas pendientes.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fecha Quincena</th>
                                        <th>N° Quincena</th>
                                        <th>Monto Base (USD)</th>
                                        <th>Monto Estimado (Bs)</th>
                                        <th>Días de atraso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deudas as $deuda): 
                                        $diasAtraso = round((time() - strtotime($deuda->fecha_quincena)) / (60*60*24));
                                    ?>
                                    <tr>
                                        <td><?= Yii::$app->formatter->asDate($deuda->fecha_quincena) ?></td>
                                        <td class="text-center"><?= $deuda->numero_quincena ?></td>
                                        <td class="text-right">$<?= number_format($deuda->monto, 2) ?></td>
                                        <td class="text-right">Bs. <?= number_format($deuda->monto * $tasaCambio, 2) ?></td>
                                        <td class="text-center"><?= $diasAtraso ?> días</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-right">Total base:</th>
                                        <th class="text-right">$<?= number_format($totalDeuda, 2) ?></th>
                                        <th class="text-right">Bs. <?= number_format($totalDeuda * $tasaCambio, 2) ?></th>
                                        <th></th>
                                    </tr>
                                    <?php if ($becaActiva): ?>
                                    <tr class="table-success">
                                        <th colspan="2" class="text-right">Total con beca (<?= $porcentajeBeca ?>% desc.):</th>
                                        <th class="text-right text-success">$<?= number_format($totalConDescuento, 2) ?></th>
                                        <th class="text-right text-success">Bs. <?= number_format($totalConDescuento * $tasaCambio, 2) ?></th>
                                        <th></th>
                                    </tr>
                                    <?php endif; ?>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- NOTIFICACIÓN DE POLÍTICA DE SUSPENSIÓN (siempre visible) -->
                        <div class="alert alert-info mt-3" style="border-left: 5px solid #17a2b8; background-color: #d1ecf1; color: #0c5460;">
                            <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-right: 10px;"></i>
                            <strong>Política de la escuela:</strong> Al acumular <strong>3 o más pagos atrasados</strong>, 
                            el atleta será suspendido temporalmente de las prácticas hasta que regularice su situación. 
                            Actualmente, este atleta tiene <strong><?= $numDeudas ?> pago(s) atrasado(s)</strong>.
                            <?php if ($numDeudas >= 3): ?>
                                <br><span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ¡Ha alcanzado el límite! Se aplicará la suspensión si no se regulariza.</span>
                            <?php endif; ?>
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
                            $emailLink = emailLink('Balance de Pagos - ' . $atleta->p_nombre . ' ' . $atleta->p_apellido, $resumen);
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
                    <p class="text-muted">Este documento es un balance de pagos generado por el sistema GED.</p>
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
    img {
        max-width: 100% !important;
        height: auto !important;
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
    .alert {
        border: 1px solid #17a2b8 !important;
        background-color: #fff !important;
        color: #000 !important;
    }
}
");
?>