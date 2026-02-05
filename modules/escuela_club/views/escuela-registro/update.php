<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Escuela $model */

$this->title = 'Actualizar Escuela: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Escuelas/Clubes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nombre, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Actualizar';
?>
<div class="escuela-update">
    <div class="card card-custom">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">
                    <i class="fas fa-edit text-primary me-2"></i>
                    <?= Html::encode($this->title) ?>
                </h3>
            </div>
        </div>
        <div class="card-body">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>