<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Rechazar Beca';
$this->params['breadcrumbs'][] = ['label' => 'Becas', 'url' => ['becas']];
$this->params['breadcrumbs'][] = ['label' => 'Propuestas Pendientes', 'url' => ['propuestas-pendientes']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="rechazar-beca">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-body">
            <p><strong>Atleta:</strong> <?= Html::encode($beca->atleta->p_nombre . ' ' . $beca->atleta->p_apellido) ?></p>
            <p><strong>Tipo de beca:</strong> <?= Html::encode($beca->tipoBeca->nombre) ?></p>
            <p><strong>Propuesto por:</strong> <?= Html::encode($beca->propuestoPor->username ?? 'N/A') ?></p>
            <p><strong>Fecha propuesta:</strong> <?= Yii::$app->formatter->asDatetime($beca->fecha_propuesta) ?></p>

            <?php $form = ActiveForm::begin(); ?>
                <?= $form->field($model, 'motivo_rechazo')->textarea(['rows' => 3])->label('Motivo del rechazo') ?>
                <div class="form-group">
                    <?= Html::submitButton('Confirmar Rechazo', ['class' => 'btn btn-danger']) ?>
                    <?= Html::a('Cancelar', ['propuestas-pendientes'], ['class' => 'btn btn-default']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>