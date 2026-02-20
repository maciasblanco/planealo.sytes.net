<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Deudas Pendientes';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="deudas-pendientes">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <?= Html::encode($this->title) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($deudas)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> No hay deudas pendientes en este momento.
            </div>
        <?php else: ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h2><?= count($deudas) ?></h2>
                            <h6>Atletas con deuda</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h2><?= Yii::$app->formatter->asCurrency($totalDeuda) ?></h2>
                            <h6>Monto total adeudado</h6>
                        </div>
                    </div>
                </div>
            </div>

            <?php foreach ($deudas as $deuda): ?>
                <div class="card mb-3 border-danger">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= Html::encode($deuda['atleta']->nombreCompleto) ?></h5>
                        <span class="badge bg-light text-dark"><?= Yii::$app->formatter->asCurrency($deuda['monto']) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($deuda['detalle'])): ?>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Concepto</th>
                                        <th>Monto</th>
                                        <th>Vencimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deuda['detalle'] as $aporte): ?>
                                        <tr>
                                            <td><?= Yii::$app->formatter->asDate($aporte->fecha) ?></td>
                                            <td><?= Html::encode($aporte->concepto ?: 'Aporte semanal') ?></td>
                                            <td class="text-danger"><?= Yii::$app->formatter->asCurrency($aporte->monto) ?></td>
                                            <td><?= Yii::$app->formatter->asDate($aporte->fecha_vencimiento) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted">No hay detalles disponibles.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>