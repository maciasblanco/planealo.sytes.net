<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\modules\epcSanAgustin\atletas\models\ClubRegistroSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="club-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'id_escuela') ?>

    <?= $form->field($model, 'id_estado') ?>

    <?= $form->field($model, 'id_municipio') ?>

    <?= $form->field($model, 'id_parroquia') ?>

    <?php // echo $form->field($model, 'direccion_administrativa') ?>

    <?php // echo $form->field($model, 'direccion_practicas') ?>

    <?php // echo $form->field($model, 'lat') ?>

    <?php // echo $form->field($model, 'lng') ?>

    <?php // echo $form->field($model, 'nombre') ?>

    <?php // echo $form->field($model, 'd_creacion') ?>

    <?php // echo $form->field($model, 'u_creacion') ?>

    <?php // echo $form->field($model, 'd_update') ?>

    <?php // echo $form->field($model, 'u_update') ?>

    <?php // echo $form->field($model, 'eliminado')->checkbox() ?>

    <?php // echo $form->field($model, 'dir_ip') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
