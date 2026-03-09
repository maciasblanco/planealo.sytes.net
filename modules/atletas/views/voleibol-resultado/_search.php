<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\search\EvaluacionResultadoSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="evaluacion-resultado-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => ['data-pjax' => 1],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'id_sesion') ?>

    <?= $form->field($model, 'id_atleta') ?>

    <?= $form->field($model, 'id_estadistica') ?>

    <?= $form->field($model, 'valor_numerico') ?>

    <?php // echo $form->field($model, 'set_numero') ?>

    <?php // echo $form->field($model, 'd_creacion') ?>

    <?php // echo $form->field($model, 'u_creacion') ?>

    <div class="form-group">
        <?= Html::submitButton('Buscar', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Limpiar', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>