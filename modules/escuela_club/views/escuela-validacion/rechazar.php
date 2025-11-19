<?php
// [file name]: modules/escuela_club/views/escuela-validacion/rechazar.php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Escuela $model */

$this->title = 'Rechazar Escuela: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Validación', 'url' => ['pendientes']];
$this->params['breadcrumbs'][] = 'Rechazar';
?>
<div class="escuela-validacion-rechazar">

    <div class="card card-custom">
        <div class="card-header bg-danger text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-exclamation-triangle"></i> <?= Html::encode($this->title) ?>
            </h3>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-circle"></i> Confirmar Rechazo</h5>
                <p class="mb-0">Está a punto de rechazar la escuela <strong><?= Html::encode($model->nombre) ?></strong>. 
                Por favor, proporcione los motivos del rechazo.</p>
            </div>

            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'comentarios_aprobacion')->textarea([
                        'rows' => 6,
                        'placeholder' => 'Describa los motivos del rechazo...',
                        'class' => 'form-control',
                        'name' => 'comentarios'  // Importante: name específico
                    ])->label('Motivos del Rechazo') ?>
                </div>
            </div>

            <div class="form-group text-center mt-4">
                <?= Html::submitButton('<i class="fas fa-times"></i> Confirmar Rechazo', [
                    'class' => 'btn btn-danger btn-lg',
                    'data' => [
                        'confirm' => '¿Está completamente seguro de RECHAZAR esta escuela?',
                    ]
                ]) ?>
                
                <?= Html::a('<i class="fas fa-arrow-left"></i> Cancelar', ['pendientes'], [
                    'class' => 'btn btn-secondary btn-lg ml-2'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>