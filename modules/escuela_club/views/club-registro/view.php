<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Club $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Clubs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="club-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'id_escuela',
            'id_estado',
            'id_municipio',
            'id_parroquia',
            'direccion_administrativa',
            'direccion_practicas',
            'lat',
            'lng',
            'nombre',
            'd_creacion',
            'u_creacion',
            'd_update',
            'u_update',
            'eliminado:boolean',
            'dir_ip',
        ],
    ]) ?>

</div>
