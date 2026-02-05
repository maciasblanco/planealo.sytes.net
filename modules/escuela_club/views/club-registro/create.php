<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Club $model */

$this->title = 'Create Club';
$this->params['breadcrumbs'][] = ['label' => 'Clubs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="club-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
