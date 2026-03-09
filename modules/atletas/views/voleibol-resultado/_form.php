<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\VoleibolSesion;
use app\models\AtletasRegistro;
use app\models\EvaluacionEstadistica;

/* @var $this yii\web\View */
/* @var $model app\models\EvaluacionResultado */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="evaluacion-resultado-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id_sesion')->dropDownList(
        ArrayHelper::map(VoleibolSesion::find()->orderBy('fecha DESC')->all(), 'id', function($sesion) {
            return $sesion->nombre . ' (' . $sesion->fecha . ')';
        }),
        ['prompt' => 'Seleccione sesión']
    ) ?>

    <?= $form->field($model, 'id_atleta')->dropDownList(
        ArrayHelper::map(AtletasRegistro::find()->orderBy('apellido')->all(), 'id', 'nombreCompleto'),
        ['prompt' => 'Seleccione atleta']
    ) ?>

    <?= $form->field($model, 'id_estadistica')->dropDownList(
        ArrayHelper::map(EvaluacionEstadistica::find()->where(['activo' => true])->all(), 'id', 'nombre'),
        ['prompt' => 'Seleccione estadística']
    ) ?>

    <?= $form->field($model, 'valor_numerico')->textInput(['type' => 'number', 'step' => '0.01']) ?>

    <?= $form->field($model, 'set_numero')->textInput(['type' => 'number', 'min' => 1, 'max' => 5]) ?>

    <div class="form-group">
        <?= Html::submitButton('Guardar', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>