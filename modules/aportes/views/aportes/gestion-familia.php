<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Familia $familia */
/** @var array $aportes */
/** @var float $totalPagado */
/** @var float $totalPendiente */
/** @var int $quincenasPagadas */
/** @var int $quincenasPendientes */
/** @var float $aporteBase */
/** @var float $descuentoMultiple */
/** @var array $becasActivas */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de gestionar una familia.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/select-escuela'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Gestión de Familia: ' . $familia->nombre_representante . ' - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Quincenales', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Familias', 'url' => ['familias']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="gestion-familia">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-users text-info"></i> <?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver a Familias', ['familias'], ['class' => 'btn btn-default']) ?>
            <?= Html::a('<i class="fas fa-plus"></i> Asignar Beca', ['asignar-beca'], ['class' => 'btn btn-success']) ?>
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
                <small class="text-muted">Sistema GED - Gestión Familiar</small>
            </div>
        </div>
    </div>

    <!-- Datos de la familia -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-address-card"></i> Datos de la Familia</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Representante:</strong> <?= Html::encode($familia->nombre_representante) ?>
                </div>
                <div class="col-md-3">
                    <strong>Email:</strong> <?= Html::encode($familia->email) ?>
                </div>
                <div class="col-md-3">
                    <strong>Teléfono:</strong> <?= Html::encode($familia->telefono) ?>
                </div>
                <div class="col-md-3">
                    <strong>Dirección:</strong> <?= Html::encode($familia->direccion) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de aportes -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Aporte Base</span>
                    <span class="info-box-number">$<?= number_format($aporteBase, 2) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-percent"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Descuento múltiple</span>
                    <span class="info-box-number"><?= $descuentoMultiple ?>%</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Pagado</span>
                    <span class="info-box-number">$<?= number_format($totalPagado, 2) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Pendiente</span>
                    <span class="info-box-number">$<?= number_format($totalPendiente, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Becas activas de la familia -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-medal"></i> Becas Activas en la Familia</h5>
        </div>
        <div class="card-body">
            <?php if (empty($becasActivas)): ?>
                <p class="text-muted">No hay becas activas para los atletas de esta familia.</p>
            <?php else: ?>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Atleta</th>
                            <th>Tipo de Beca</th>
                            <th>Fecha Asignación</th>
                            <th>Fecha Vencimiento</th>
                            <th>Descuento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($becasActivas as $item): 
                            $atleta = $item['atleta'];
                            $beca = $item['beca'];
                            $porcentaje = $beca->tipoBeca->porcentaje_descuento ?? 0;
                        ?>
                        <tr>
                            <td><?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?></td>
                            <td><?= Html::encode($beca->tipoBeca->nombre) ?></td>
                            <td><?= Yii::$app->formatter->asDate($beca->fecha_asignacion) ?></td>
                            <td><?= Yii::$app->formatter->asDate($beca->fecha_vencimiento) ?></td>
                            <td class="text-center"><?= $porcentaje ?>%</td>
                            <td>
                                <span class="badge bg-success">Activa</span>
                                <?= Html::a('<i class="fas fa-ban"></i> Revocar', ['revocar-beca', 'id_beca' => $beca->id_beca], [
                                    'class' => 'btn btn-danger btn-xs ml-2',
                                    'data' => [
                                        'confirm' => '¿Revocar esta beca?',
                                        'method' => 'post',
                                    ],
                                ]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <p>
                <?= Html::a('<i class="fas fa-plus"></i> Asignar nueva beca a un atleta de esta familia', 
                    ['asignar-beca', 'id_familia' => $familia->id_familia], 
                    ['class' => 'btn btn-sm btn-warning']) ?>
            </p>
        </div>
    </div>

    <!-- Historial de aportes de la familia -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Historial de Aportes Familiares</h5>
        </div>
        <div class="card-body">
            <?php if (empty($aportes)): ?>
                <p class="text-muted">No hay aportes registrados para esta familia.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha Quincena</th>
                                <th>Quincena #</th>
                                <th>Monto Base</th>
                                <th>Atletas</th>
                                <th>Descuento</th>
                                <th>Monto Final</th>
                                <th>Estado</th>
                                <th>Fecha Pago</th>
                                <th>Método</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aportes as $aporte): ?>
                            <tr class="<?= $aporte->estado == 'pendiente' ? 'table-warning' : 'table-success' ?>">
                                <td><?= Yii::$app->formatter->asDate($aporte->fecha_quincena) ?></td>
                                <td class="text-center"><?= $aporte->numero_quincena ?></td>
                                <td class="text-right">$<?= number_format($aporte->monto_base, 2) ?></td>
                                <td class="text-center"><?= $aporte->total_atletas_familia ?></td>
                                <td class="text-right">$<?= number_format($aporte->monto_ajuste, 2) ?></td>
                                <td class="text-right"><strong>$<?= number_format($aporte->monto, 2) ?></strong></td>
                                <td class="text-center">
                                    <span class="badge <?= $aporte->estado == 'pagado' ? 'bg-success' : 'bg-warning' ?>">
                                        <?= ucfirst($aporte->estado) ?>
                                    </span>
                                </td>
                                <td><?= $aporte->fecha_pago ? Yii::$app->formatter->asDate($aporte->fecha_pago) : '-' ?></td>
                                <td><?= ucfirst($aporte->metodo_pago ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// CSS para badges
$css = <<<CSS
.badge.bg-success { background-color: #28a745; color: white; }
.badge.bg-warning { background-color: #ffc107; color: black; }
.table-warning td { background-color: #fff3cd; }
.table-success td { background-color: #d4edda; }
CSS;
$this->registerCss($css);
?>