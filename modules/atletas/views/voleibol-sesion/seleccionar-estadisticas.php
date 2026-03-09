<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $sesion app\models\VoleibolSesion */
/* @var $estadisticasDisponibles app\models\EvaluacionEstadistica[] */
/* @var $seleccionadasActuales array */

$this->title = 'Seleccionar Estadísticas - Sesión ' . $sesion->id;
$this->params['breadcrumbs'][] = ['label' => 'Sesiones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Sesión ' . $sesion->id, 'url' => ['view', 'id' => $sesion->id]];
$this->params['breadcrumbs'][] = 'Seleccionar Estadísticas';
?>
<div class="voleibol-seleccionar-estadisticas">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <?php foreach ($estadisticasDisponibles as $est): ?>
            <div class="col-md-4">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="estadisticas[]" value="<?= $est->id ?>" <?= in_array($est->id, $seleccionadasActuales) ? 'checked' : '' ?>>
                        <?= Html::encode($est->nombre) ?>
                        <small class="text-muted">(<?= Html::encode($est->unidad) ?>)</small>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-group mt-3">
        <?= Html::submitButton('Guardar selección', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Cancelar', ['view', 'id' => $sesion->id], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>