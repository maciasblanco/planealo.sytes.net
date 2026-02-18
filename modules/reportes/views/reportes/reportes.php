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
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Atletas</h3>
                </div>
                <div class="panel-body">
                    <p>Consulte la información de los atletas a su cargo.</p>
                    <?= Html::a('Ir a atletas', ['reportes/atletas'], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title">Asistencias</h3>
                </div>
                <div class="panel-body">
                    <p>Reportes de asistencia por fecha, atleta, etc.</p>
                    <?= Html::a('Ir a asistencias', ['reportes/asistencias'], ['class' => 'btn btn-success']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title">Deudas pendientes</h3>
                </div>
                <div class="panel-body">
                    <p>Resumen de deudas y aportes atrasados.</p>
                    <?= Html::a('Ir a deudas', ['reportes/deudas-pendientes'], ['class' => 'btn btn-warning']) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($esAtleta): ?>
        <div class="col-md-4 col-md-offset-4">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Mis estadísticas</h3>
                </div>
                <div class="panel-body">
                    <p>Consulte su información personal, pagos y asistencias.</p>
                    <?= Html::a('Ver mis estadísticas', ['reportes/estadisticas-atleta'], ['class' => 'btn btn-info']) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>