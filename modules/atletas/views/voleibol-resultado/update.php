<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\EvaluacionResultado */

$this->title = 'Actualizar Resultado #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Resultados', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Resultado #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Actualizar';
?>
<div class="evaluacion-resultado-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>