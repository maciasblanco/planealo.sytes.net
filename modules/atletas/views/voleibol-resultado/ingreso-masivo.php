<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $sesion app\models\VoleibolSesion */
/* @var $atletas app\models\VoleibolSesionAtleta[] */
/* @var $estadisticas app\models\EvaluacionEstadistica[] */
/* @var $valoresExistentes array */

$this->title = 'Ingreso de Resultados - Sesión ' . $sesion->id . ' (' . $sesion->fecha . ')';
$this->params['breadcrumbs'][] = ['label' => 'Sesiones', 'url' => ['/atletas/voleibol-sesion/index']];
$this->params['breadcrumbs'][] = ['label' => 'Sesión ' . $sesion->id, 'url' => ['/atletas/voleibol-sesion/view', 'id' => $sesion->id]];
$this->params['breadcrumbs'][] = 'Ingreso de Resultados';

// Determinar si hay sets (para mostrar pestañas)
$haySets = VoleibolSet::find()->where(['sesion_id' => $sesion->id])->count() > 0;
$sets = $haySets ? VoleibolSet::find()->where(['sesion_id' => $sesion->id])->orderBy('numero')->all() : [null];
$maxSet = $sesion->voleibolSets ? count($sesion->voleibolSets) : 0;

$js = <<<JS
    // Inicializar pestañas de Bootstrap
    $('#resultadoTabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
JS;
$this->registerJs($js);
?>
<div class="voleibol-ingreso-masivo">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(['id' => 'form-ingreso-masivo']); ?>

    <?php if ($haySets && count($sets) > 1): ?>
        <!-- Pestañas por set -->
        <ul class="nav nav-tabs" id="resultadoTabs" role="tablist">
            <?php foreach ($sets as $i => $set): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $i == 0 ? 'active' : '' ?>" id="set<?= $set->numero ?>-tab" data-bs-toggle="tab" href="#set<?= $set->numero ?>" role="tab" aria-controls="set<?= $set->numero ?>" aria-selected="<?= $i == 0 ? 'true' : 'false' ?>">
                        Set <?= $set->numero ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="tab-content mt-3">
        <?php foreach ($sets as $i => $set): ?>
            <?php $setNumero = $set ? $set->numero : 0; ?>
            <div class="tab-pane <?= $i == 0 ? 'show active' : '' ?>" id="set<?= $setNumero ?>" role="tabpanel" aria-labelledby="set<?= $setNumero ?>-tab">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="vertical-align: middle;">Atleta / Estadística</th>
                                <?php foreach ($estadisticas as $est): ?>
                                    <th class="text-center"><?= Html::encode($est->nombre) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atletas as $atleta): ?>
                                <tr>
                                    <td>
                                        <?= Html::encode($atleta->atleta->nombre . ' ' . $atleta->atleta->apellido) ?>
                                        <small class="text-muted">(Eq. <?= $atleta->equipo ?>)</small>
                                    </td>
                                    <?php foreach ($estadisticas as $est): ?>
                                        <?php
                                            $valor = isset($valoresExistentes[$atleta->atleta_id][$est->id][$setNumero]) ? $valoresExistentes[$atleta->atleta_id][$est->id][$setNumero] : '';
                                        ?>
                                        <td class="text-center">
                                            <input type="number" step="0.01" class="form-control form-control-sm" style="width: 80px; display: inline-block;"
                                                   name="resultado[<?= $atleta->atleta_id ?>][<?= $est->id ?>][<?= $setNumero ?>]"
                                                   value="<?= $valor ?>">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-group mt-3">
        <?= Html::submitButton('Guardar todos los resultados', ['class' => 'btn btn-primary btn-lg']) ?>
        <?= Html::a('Cancelar', ['/atletas/voleibol-sesion/view', 'id' => $sesion->id], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>