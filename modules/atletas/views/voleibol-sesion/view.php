<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use app\models\VoleibolSet;

/* @var $this yii\web\View */
/* @var $model app\models\VoleibolSesion */
/* @var $setActivo app\models\VoleibolSet */
/* @var $estadisticasSeleccionadas app\models\EvaluacionEstadistica[] */
/* @var $atletasEquipoA app\models\VoleibolSesionAtleta[] */
/* @var $atletasEquipoB app\models\VoleibolSesionAtleta[] */

$this->title = 'Sesión ' . $model->id . ' - ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Sesiones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Registrar JS para actualización en tiempo real (opcional)
$js = <<<JS
    function actualizarMarcador() {
        $.get('actualizar-marcador', {id: $model->id}, function(data) {
            $('#puntos-a').text(data.puntos_a);
            $('#puntos-b').text(data.puntos_b);
        });
    }
    // setInterval(actualizarMarcador, 5000); // descomentar si se desea polling
JS;
$this->registerJs($js);
?>
<div class="voleibol-sesion-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if ($model->estado == 'A'): ?>
            <!-- ✅ NUEVO BOTÓN ALINEACIÓN -->
            <?= Html::a('Alineación', ['alineacion', 'id' => $model->id], ['class' => 'btn btn-info']) ?>
            <?= Html::a('Gestionar Atletas', ['gestionar-atletas', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Seleccionar Estadísticas', ['seleccionar-estadisticas', 'id' => $model->id], ['class' => 'btn btn-info']) ?>
            <?= Html::a('Ingresar Resultados', ['/atletas/voleibol-resultado/ingreso-masivo', 'sesion_id' => $model->id], ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Finalizar Sesión', ['finalizar', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => '¿Está seguro de finalizar esta sesión?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
        <?= Html::a('Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
    </p>

    <div class="row">
        <div class="col-md-6">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    [
                        'label' => 'Escuela',
                        'value' => $model->escuela->nombre,
                    ],
                    [
                        'label' => 'Categoría',
                        'value' => $model->categoria ? $model->categoria->nombre : 'Sin categoría',
                    ],
                    'nombre',
                    'fecha:date',
                    [
                        'attribute' => 'estado',
                        'value' => $model->estado == 'A' ? 'Activa' : 'Finalizada',
                    ],
                    'created_at:datetime',
                    [
                        'label' => 'Creado por',
                        'value' => $model->creador->username,
                    ],
                ],
            ]) ?>
        </div>
        <div class="col-md-6">
            <h3>Marcador del Set Activo</h3>
            <?php if ($setActivo): ?>
                <div class="card">
                    <div class="card-body">
                        <h4>Set <?= $setActivo->numero ?></h4>
                        <div class="row text-center">
                            <div class="col">
                                <h2>Equipo A</h2>
                                <span id="puntos-a" class="display-1"><?= $setActivo->puntos_a ?></span>
                            </div>
                            <div class="col">
                                <h2>-</h2>
                            </div>
                            <div class="col">
                                <h2>Equipo B</h2>
                                <span id="puntos-b" class="display-1"><?= $setActivo->puntos_b ?></span>
                            </div>
                        </div>
                        <?= Html::button('Finalizar Set', [
                            'id' => 'btn-finalizar-set',
                            'class' => 'btn btn-success btn-lg btn-block',
                            'data-id' => $setActivo->id,
                        ]) ?>
                    </div>
                </div>
            <?php else: ?>
                <p>No hay set activo.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <h3>Equipo A</h3>
            <ul class="list-group">
                <?php foreach ($atletasEquipoA as $sa): ?>
                    <li class="list-group-item"><?= Html::encode($sa->atleta->nombre . ' ' . $sa->atleta->apellido) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="col-md-6">
            <h3>Equipo B</h3>
            <ul class="list-group">
                <?php foreach ($atletasEquipoB as $sa): ?>
                    <li class="list-group-item"><?= Html::encode($sa->atleta->nombre . ' ' . $sa->atleta->apellido) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h3>Estadísticas seleccionadas</h3>
            <?php if ($estadisticasSeleccionadas): ?>
                <ul>
                    <?php foreach ($estadisticasSeleccionadas as $est): ?>
                        <li><?= Html::encode($est->nombre) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No se han seleccionado estadísticas para esta sesión.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h3>Historial de sets</h3>
            <?php
            $setsProvider = new ArrayDataProvider([
                'allModels' => $model->voleibolSets,
                'sort' => ['attributes' => ['numero']],
                'pagination' => false,
            ]);
            ?>
            <?= GridView::widget([
                'dataProvider' => $setsProvider,
                'columns' => [
                    'numero',
                    'puntos_a',
                    'puntos_b',
                    [
                        'attribute' => 'ganador',
                        'value' => function($set) {
                            if ($set->ganador == 'A') return 'Equipo A';
                            if ($set->ganador == 'B') return 'Equipo B';
                            return '-';
                        },
                    ],
                    'estado',
                ],
            ]); ?>
        </div>
    </div>

</div>

<?php
// Script AJAX para finalizar set
$urlFinalizarSet = \yii\helpers\Url::to(['finalizar-set']);
$this->registerJs("
$('#btn-finalizar-set').click(function() {
    var setId = $(this).data('id');
    $.post('$urlFinalizarSet', {id: setId}, function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    }, 'json');
});
");
?>