<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\AtletasRegistro $model */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    // ❌ MOSTRAR ERROR Y REDIRECCIÓN
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de registrar atletas.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/index'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$this->title = 'Registro de Atletas - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Registro de Atletas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="atletas-registro-create">

    <!-- Información de la Escuela -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-6">
                <strong><i class="fas fa-school"></i> Escuela Activa:</strong> <?= Html::encode($nombre_escuela) ?>
                <span class="badge bg-primary ms-2">ID: <?= $id_escuela ?></span>
            </div>
            <div class="col-md-6 text-right">
                <small class="text-muted">Sistema GED - Registro de Atletas</small>
            </div>
        </div>
    </div>

    <h1><?= Html::encode($this->title) ?></h1>
    
    <?= $this->render('_form', [
        'model' => $model,            
        'id' => $id_escuela,  // ✅ Usar ID de sesión
        'nombre' => $nombre_escuela, // ✅ Usar nombre de sesión
    ]) ?>

</div>