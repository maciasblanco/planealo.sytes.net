<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Panel de Reportes';
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user;
$esAdmin = $user->can('admin');
$esRepresentante = $user->can('representante');
$esAtleta = $user->can('atleta');
?>

<div class="reportes-index">
    <div class="jumbotron">
        <h1>Sistema de Reportes</h1>
        <p class="lead">Seleccione una opción para visualizar los reportes.</p>
    </div>

    <div class="row">
        <?php if ($esAdmin || $esRepresentante): ?>
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Atletas</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Consulte la información de los atletas a su cargo.</p>
                    <?= Html::a('Ir a atletas', ['reportes/reportes-representantes'], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Asistencias</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Reportes de asistencia por fecha, atleta, etc.</p>
                    <?= Html::a('Ir a asistencias', ['reportes/asistencias'], ['class' => 'btn btn-success']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-header bg-warning text-white">
                    <h5 class="card-title mb-0">Deudas pendientes</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Resumen de deudas y aportes atrasados.</p>
                    <?= Html::a('Ir a deudas', ['reportes/deudas-pendientes'], ['class' => 'btn btn-warning']) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($esAtleta): ?>
        <div class="col-md-4 col-md-offset-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Mis estadísticas</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Consulte su información personal, pagos y asistencias.</p>
                    <?= Html::a('Ver mis estadísticas', ['reportes/estadisticas-atleta'], ['class' => 'btn btn-info']) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>