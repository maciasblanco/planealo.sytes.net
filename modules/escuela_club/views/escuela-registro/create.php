<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Escuela */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

$this->title = 'Crear Escuela/Club';
$this->params['breadcrumbs'][] = ['label' => 'Escuelas/Clubes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="escuela-create">

    <!-- Información del Sistema -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-6">
                <strong><i class="fas fa-school"></i> Sistema GED - Gestión de Escuelas</strong>
                <?php if (!empty($nombre_escuela)): ?>
                    <br><small>Escuela activa en sesión: <?= Html::encode($nombre_escuela) ?> (ID: <?= $id_escuela ?>)</small>
                <?php else: ?>
                    <br><small>No hay escuela activa en sesión - Modo administración</small>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-right">
                <small class="text-muted">Crear nueva escuela/club</small>
            </div>
        </div>
    </div>

    <div class="card card-custom">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">
                    <i class="fas fa-plus-circle text-primary"></i>
                    <?= Html::encode($this->title) ?>
                </h3>
            </div>
        </div>
        <div class="card-body">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>

</div>