<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\VoleibolSesion */
/* @var $escuelas array */

$this->title = 'Editar Sesión: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Sesiones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nombre, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>
<div class="voleibol-sesion-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="voleibol-sesion-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'escuela_id')->dropDownList($escuelas, ['prompt' => 'Seleccione una escuela']) ?>

        <?= $form->field($model, 'categoria_id')->dropDownList(ArrayHelper::map($model->escuela->categorias, 'id', 'nombre'), ['prompt' => 'Seleccione categoría (opcional)']) ?>

        <?= $form->field($model, 'nombre')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'fecha')->input('date') ?>

        <div class="form-group">
            <?= Html::submitButton('Guardar', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>