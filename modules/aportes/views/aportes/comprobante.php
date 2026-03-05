<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\AportesSemanales[] $aportes */
/** @var string $ids */
/** @var app\models\AtletasRegistro $atleta */
/** @var app\models\Escuela $escuela */
/** @var app\models\RegistroRepresentantes|null $representante */
/** @var string $codigoUnico */

$this->title = 'Comprobante de Pago - ' . $escuela->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Quincenales', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Obtener datos comunes del pago
$fechaPago = null;
$metodoPago = null;
$totalUSD = 0;
$totalBs = 0;

foreach ($aportes as $ap) {
    if ($ap->fecha_pago) $fechaPago = $ap->fecha_pago;
    if ($ap->metodo_pago) $metodoPago = $ap->metodo_pago;
    $totalUSD += $ap->monto;
    $totalBs += ($ap->monto_bs_original ?? 0);
}

// Determinar logo de la escuela
$logoGed = '/img/logos/logoGed.png';
$logoSidebar = $logoGed;
$idEscuela = $escuela->id;
if ($idEscuela > 0) {
    $posiblesLogos = [
        '/img/logos/escuelas/logo' . $idEscuela . '.png',
        '/img/logos/escuelas/logo' . $idEscuela . '.jpg',
        '/img/logos/escuelas/logo' . $idEscuela . '.jpeg',
        '/img/logos/escuelas/logo' . $idEscuela . '.svg'
    ];
    foreach ($posiblesLogos as $logo) {
        $rutaCompleta = Yii::getAlias('@webroot') . $logo;
        if (file_exists($rutaCompleta)) {
            $logoSidebar = $logo;
            break;
        }
    }
}
?>
<div class="comprobante-pago">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-receipt text-success"></i> <?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right no-print">
            <?= Html::a('<i class="fas fa-print"></i> Imprimir', '#', ['class' => 'btn btn-info', 'onclick' => 'window.print()']) ?>
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['gestion-atleta', 'atleta_id' => $atleta->id], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <!-- Código único -->
    <div class="alert alert-info text-center">
        <strong>Código de comprobante:</strong> <?= $codigoUnico ?>
    </div>

    <!-- Datos del pago -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Pago registrado exitosamente</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Datos del atleta</h6>
                    <p><strong>Nombre:</strong> <?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?></p>
                    <p><strong>Identificación:</strong> <?= Html::encode($atleta->identificacion) ?></p>
                    
                    <?php if ($representante): 
                        // Construir nombre completo del representante
                        $nombreRep = array_filter([
                            $representante->p_nombre,
                            $representante->s_nombre,
                            $representante->p_apellido,
                            $representante->s_apellido
                        ]);
                        $nombreCompletoRep = implode(' ', $nombreRep);
                        
                        // Obtener teléfono (prioridad cell, luego telf)
                        $telefonoRep = $representante->cell ?? $representante->telf ?? 'No registrado';
                    ?>
                        <p><strong>Representante:</strong> <?= Html::encode($nombreCompletoRep) ?></p>
                        <p><strong>Tel. representante:</strong> <?= Html::encode($telefonoRep) ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h6>Detalle del pago</h6>
                    <p><strong>Fecha de pago:</strong> <?= Yii::$app->formatter->asDate($fechaPago) ?></p>
                    <p><strong>Método de pago:</strong> <?= ucfirst($metodoPago) ?></p>
                    <p><strong>Total USD:</strong> $<?= number_format($totalUSD, 2) ?></p>
                    <?php if ($totalBs > 0): ?>
                        <p><strong>Total Bs:</strong> Bs. <?= number_format($totalBs, 2) ?></p>
                    <?php endif; ?>
                    <p><strong>Quincenas pagadas:</strong> <?= count($aportes) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle de quincenas pagadas -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Quincenas incluidas en este pago</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                    <tr>
                        <th>Fecha quincena</th>
                        <th># Quincena</th>
                        <th>Monto USD</th>
                        <th>Monto Bs.</th>
                        <th>Tasa cambio</th>
                        <th>Tipo</th>
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
                        <td class="text-right">
                            <?= $aporte->tipo_cambio ? number_format($aporte->tipo_cambio, 2) : '-' ?>
                        </td>
                        <td>
                            <?php
                            $tipos = [
                                'normal' => 'Normal',
                                'adelantado' => 'Adelantado',
                                'flexible' => 'Flexible',
                                'parcial' => 'Parcial',
                            ];
                            echo $tipos[$aporte->tipo_aporte] ?? ucfirst($aporte->tipo_aporte);
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <th colspan="2" class="text-right">Subtotales:</th>
                        <th class="text-right">$<?= number_format($totalUSD, 2) ?></th>
                        <th class="text-right"><?= $totalBs > 0 ? 'Bs. ' . number_format($totalBs, 2) : '-' ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Información de la escuela con logo -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?= Yii::getAlias('@web') . $logoSidebar ?>" alt="Logo escuela" style="max-height: 80px; margin-bottom: 10px;">
                    <h4><?= Html::encode($escuela->nombre) ?></h4>
                    <p class="text-muted">ID: <?= $escuela->id ?> | RIF: <?= Html::encode($escuela->rif ?? 'N/A') ?></p>
                    <p class="text-muted"><?= Html::encode($escuela->direccion ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensaje de agradecimiento -->
    <div class="alert alert-success text-center mt-4">
        <i class="fas fa-heart"></i> ¡Gracias por tu contribución! Tu apoyo es fundamental para el desarrollo deportivo.
    </div>

    <!-- Botones para compartir -->
    <div class="row mt-4 no-print">
        <div class="col-md-12 text-center">
            <div class="btn-group">
                <?php
                $mensaje = "📋 *COMPROBANTE DE PAGO - GED*\n";
                $mensaje .= "Código: $codigoUnico\n";
                $mensaje .= "🏫 Escuela: " . $escuela->nombre . "\n";
                $mensaje .= "👤 Atleta: " . $atleta->p_nombre . " " . $atleta->p_apellido . " (C.I. " . $atleta->identificacion . ")\n";
                if ($representante) {
                    $nombreRep = array_filter([
                        $representante->p_nombre,
                        $representante->s_nombre,
                        $representante->p_apellido,
                        $representante->s_apellido
                    ]);
                    $nombreCompletoRep = implode(' ', $nombreRep);
                    $mensaje .= "👥 Representante: " . $nombreCompletoRep . "\n";
                }
                $mensaje .= "💰 Total pagado: $" . number_format($totalUSD, 2);
                if ($totalBs > 0) $mensaje .= " (Bs. " . number_format($totalBs, 2) . ")";
                $mensaje .= "\n📅 Fecha de pago: " . Yii::$app->formatter->asDate($fechaPago) . "\n";
                $mensaje .= "💳 Método: " . ucfirst($metodoPago) . "\n";
                $mensaje .= "📆 Quincenas pagadas: " . count($aportes) . "\n\n";
                $mensaje .= "Detalle:\n";
                $maxDetalle = 5; // Mostrar hasta 5 quincenas en el mensaje
                $contador = 0;
                foreach ($aportes as $ap) {
                    if ($contador >= $maxDetalle) break;
                    $mensaje .= Yii::$app->formatter->asDate($ap->fecha_quincena) . " - Q#" . $ap->numero_quincena . " - $" . number_format($ap->monto, 2);
                    if ($ap->monto_bs_original) $mensaje .= " (Bs." . number_format($ap->monto_bs_original, 2) . ")";
                    $mensaje .= "\n";
                    $contador++;
                }
                $restantes = count($aportes) - $maxDetalle;
                if ($restantes > 0) {
                    $mensaje .= "y $restantes quincena(s) más...\n";
                }
                $mensaje .= "\n✅ Pago registrado en el sistema GED.";
                $mensajeUrl = urlencode($mensaje);
                ?>
                <?= Html::a('<i class="fab fa-whatsapp"></i> WhatsApp', 'https://wa.me/?text=' . $mensajeUrl, [
                    'class' => 'btn btn-success',
                    'target' => '_blank',
                    'title' => 'Enviar por WhatsApp'
                ]) ?>
                <?= Html::a('<i class="fas fa-envelope"></i> Correo', 'mailto:?subject=' . urlencode('Comprobante de pago GED - ' . $codigoUnico) . '&body=' . $mensajeUrl, [
                    'class' => 'btn btn-primary',
                    'title' => 'Enviar por correo'
                ]) ?>
                <?= Html::a('<i class="fas fa-download"></i> Guardar PDF', '#', [
                    'class' => 'btn btn-secondary',
                    'onclick' => 'window.print(); return false;',
                    'title' => 'Guardar como PDF (imprimir)'
                ]) ?>
            </div>
        </div>
    </div>
</div>

<?php
// Estilos para impresión
$this->registerCss("
@media print {
    .no-print { display: none !important; }
    .main-sidebar, .navbar, .footer, .breadcrumb { display: none; }
    .content-wrapper { margin-left: 0; }
    body { background: white; }
    .card { border: 1px solid #ddd; }
    .alert-info { background-color: #f0f0f0 !important; color: black !important; }
}
");
?>