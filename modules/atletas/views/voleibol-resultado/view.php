<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\EvaluacionResultado */

$this->title = 'Resultado #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Resultados', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="evaluacion-resultado-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Actualizar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Eliminar', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => '¿Está seguro de eliminar este resultado?',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'label' => 'Sesión',
                'value' => $model->sesion->nombre . ' (' . $model->sesion->fecha . ')',
            ],
            [
                'label' => 'Atleta',
                'value' => $model->atleta->nombre . ' ' . $model->atleta->apellido,
            ],
            [
                'label' => 'Estadística',
                'value' => $model->estadistica->nombre,
            ],
            'valor_numerico',
            'set_numero',
            'd_creacion:datetime',
            [
                'label' => 'Creado por',
                'value' => $model->uCreacion ? $model->uCreacion->username : null,
            ],
            'd_update:datetime',
            [
                'label' => 'Actualizado por',
                'value' => $model->uUpdate ? $model->uUpdate->username : null,
            ],
            'eliminado:boolean',
            'dir_ip',
        ],
    ]) ?>

</div>