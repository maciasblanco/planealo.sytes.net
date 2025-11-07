<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\modules\atletas\models\AtletasRegistroSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="atletas-registro-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'id_club') ?>

    <?= $form->field($model, 'id_escuela') ?>

    <?= $form->field($model, 'id_representante') ?>

    <?= $form->field($model, 'id_alergias') ?>

    <?php // echo $form->field($model, 'id_enfermedades') ?>

    <?php // echo $form->field($model, 'id_discapacidad') ?>

    <?php // echo $form->field($model, 'p_nombre') ?>

    <?php // echo $form->field($model, 's_nombre') ?>

    <?php // echo $form->field($model, 'p_apellido') ?>

    <?php // echo $form->field($model, 's_apellido') ?>

    <?php // echo $form->field($model, 'id_nac') ?>

    <?php // echo $form->field($model, 'identificacion') ?>

    <?php // echo $form->field($model, 'fn') ?>

    <?php // echo $form->field($model, 'sexo') ?>

    <?php // echo $form->field($model, 'estatura') ?>

    <?php // echo $form->field($model, 'peso') ?>

    <?php // echo $form->field($model, 'talla_franela') ?>

    <?php // echo $form->field($model, 'talla_short') ?>

    <?php // echo $form->field($model, 'cell') ?>

    <?php // echo $form->field($model, 'telf') ?>

    <?php // echo $form->field($model, 'asma')->checkbox() ?>

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
