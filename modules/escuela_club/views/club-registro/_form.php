<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Club $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="club-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id_escuela')->textInput() ?>

    <?= $form->field($model, 'id_estado')->textInput() ?>

    <?= $form->field($model, 'id_municipio')->textInput() ?>

    <?= $form->field($model, 'id_parroquia')->textInput() ?>

    <?= $form->field($model, 'direccion_administrativa')->textInput() ?>

    <?= $form->field($model, 'direccion_practicas')->textInput() ?>

    <?= $form->field($model, 'lat')->textInput() ?>

    <?= $form->field($model, 'lng')->textInput() ?>

    <?= $form->field($model, 'nombre')->textInput() ?>

    <?= $form->field($model, 'd_creacion')->textInput() ?>

    <?= $form->field($model, 'u_creacion')->textInput() ?>

    <?= $form->field($model, 'd_update')->textInput() ?>

    <?= $form->field($model, 'u_update')->textInput() ?>

    <?= $form->field($model, 'eliminado')->checkbox() ?>

    <?= $form->field($model, 'dir_ip')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
