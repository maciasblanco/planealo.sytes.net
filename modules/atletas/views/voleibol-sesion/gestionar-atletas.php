<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $sesion app\models\VoleibolSesion */
/* @var $atletas app\models\AtletasRegistro[] */
/* @var $asignados app\models\VoleibolSesionAtleta[] */

$this->title = 'Gestionar Atletas - Sesión ' . $sesion->id;
$this->params['breadcrumbs'][] = ['label' => 'Sesiones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Sesión ' . $sesion->id, 'url' => ['view', 'id' => $sesion->id]];
$this->params['breadcrumbs'][] = 'Gestionar Atletas';
?>
<div class="voleibol-gestionar-atletas">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Atleta</th>
                    <th>Equipo A</th>
                    <th>Equipo B</th>
                    <th>No participa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atletas as $atleta): ?>
                    <?php
                        $asignado = isset($asignados[$atleta->id]) ? $asignados[$atleta->id] : null;
                        $checkedA = ($asignado && $asignado->equipo == 'A');
                        $checkedB = ($asignado && $asignado->equipo == 'B');
                    ?>
                    <tr>
                        <td><?= Html::encode($atleta->nombre . ' ' . $atleta->apellido) ?></td>
                        <td class="text-center">
                            <input type="radio" name="equipo[<?= $atleta->id ?>]" value="A" <?= $checkedA ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="equipo[<?= $atleta->id ?>]" value="B" <?= $checkedB ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="equipo[<?= $atleta->id ?>]" value="" <?= (!$checkedA && !$checkedB) ? 'checked' : '' ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Guardar', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Cancelar', ['view', 'id' => $sesion->id], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>