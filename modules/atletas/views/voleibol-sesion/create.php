<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\VoleibolSesion */
/* @var $escuelas array */

$this->title = 'Nueva Sesión de Voleibol';
$this->params['breadcrumbs'][] = ['label' => 'Sesiones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="voleibol-sesion-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="voleibol-sesion-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'escuela_id')->dropDownList($escuelas, ['prompt' => 'Seleccione una escuela']) ?>

        <?= $form->field($model, 'categoria_id')->dropDownList([], ['prompt' => 'Seleccione categoría (opcional)']) ?>

        <?= $form->field($model, 'nombre')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'fecha')->input('date') ?>

        <div class="form-group">
            <?= Html::submitButton('Siguiente: Gestionar Atletas', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

<?php
// Cargar categorías vía AJAX cuando se seleccione escuela
$urlCategorias = \yii\helpers\Url::to(['/catalogos/categoria-atletas/listar-por-escuela']);
$this->registerJs("
$('#voleibolesion-escuela_id').change(function() {
    var escuelaId = $(this).val();
    $.get('$urlCategorias', {escuela_id: escuelaId}, function(data) {
        var options = '<option value=\"\">Seleccione categoría (opcional)</option>';
        $.each(data, function(key, value) {
            options += '<option value=\"' + key + '\">' + value + '</option>';
        });
        $('#voleibolesion-categoria_id').html(options);
    }, 'json');
});
");
?>