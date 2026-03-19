<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $sesion app\models\VoleibolSesion */
/* @var $set app\models\VoleibolSet */
/* @var $atletasPorEquipo array */
/* @var $alineaciones array */

$this->title = 'Alineación - Sesión ' . $sesion->id . ' - Set ' . $set->numero;
$this->params['breadcrumbs'][] = ['label' => 'Sesiones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Sesión ' . $sesion->id, 'url' => ['view', 'id' => $sesion->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="voleibol-alineacion">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Seleccione los 6 jugadores titulares para cada equipo. Las posiciones se numeran del 1 (zaguero derecho) al 6 (delantero centro) según la rotación estándar.
    </div>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <!-- Equipo A -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Equipo A</h4>
                </div>
                <div class="card-body">
                    <?php for ($pos = 1; $pos <= 6; $pos++): ?>
                        <?php
                        $selected = isset($alineaciones['A_' . $pos]) ? $alineaciones['A_' . $pos]->atleta_id : '';
                        ?>
                        <div class="form-group row mb-3">
                            <label class="col-sm-3 col-form-label">Posición <?= $pos ?></label>
                            <div class="col-sm-9">
                                <select name="alineacion[A][<?= $pos ?>]" class="form-control select-equipoA">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($atletasPorEquipo['A'] as $atleta): ?>
                                        <option value="<?= $atleta->id ?>" <?= $selected == $atleta->id ? 'selected' : '' ?>>
                                            <?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Equipo B -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4>Equipo B</h4>
                </div>
                <div class="card-body">
                    <?php for ($pos = 1; $pos <= 6; $pos++): ?>
                        <?php
                        $selected = isset($alineaciones['B_' . $pos]) ? $alineaciones['B_' . $pos]->atleta_id : '';
                        ?>
                        <div class="form-group row mb-3">
                            <label class="col-sm-3 col-form-label">Posición <?= $pos ?></label>
                            <div class="col-sm-9">
                                <select name="alineacion[B][<?= $pos ?>]" class="form-control select-equipoB">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($atletasPorEquipo['B'] as $atleta): ?>
                                        <option value="<?= $atleta->id ?>" <?= $selected == $atleta->id ? 'selected' : '' ?>>
                                            <?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group mt-4 text-center">
        <?= Html::submitButton('Guardar Alineación', ['class' => 'btn btn-success btn-lg']) ?>
        <?= Html::a('Cancelar', ['view', 'id' => $sesion->id], ['class' => 'btn btn-secondary btn-lg']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
// Validación con JavaScript para asegurar que no se repitan jugadores en el mismo equipo
$this->registerJs(<<<JS
function validarNoRepetidos(selector) {
    var selects = $(selector);
    var values = [];
    var valid = true;
    selects.each(function() {
        var val = $(this).val();
        if (val) {
            if (values.indexOf(val) !== -1) {
                valid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
            values.push(val);
        }
    });
    return valid;
}

$('form').on('submit', function(e) {
    var okA = validarNoRepetidos('.select-equipoA');
    var okB = validarNoRepetidos('.select-equipoB');
    if (!okA || !okB) {
        e.preventDefault();
        alert('No puede repetir el mismo jugador en más de una posición del mismo equipo.');
    }
});
JS
);
?>