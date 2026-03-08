<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Detalle de Beca #' . $beca->id_beca;
$this->params['breadcrumbs'][] = ['label' => 'Becas', 'url' => ['becas']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="view-beca">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $beca,
                'attributes' => [
                    'id_beca',
                    [
                        'label' => 'Atleta',
                        'value' => $beca->atleta ? $beca->atleta->p_nombre . ' ' . $beca->atleta->p_apellido : 'N/A',
                    ],
                    [
                        'label' => 'Tipo de Beca',
                        'value' => $beca->tipoBeca->nombre ?? 'N/A',
                    ],
                    [
                        'label' => 'Familia',
                        'value' => $beca->familia ? $beca->familia->nombre_representante : 'N/A',
                    ],
                    'fecha_asignacion:date',
                    'fecha_vencimiento:date',
                    'periodo_validez_meses',
                    [
                        'label' => 'Propuesto por',
                        'value' => $beca->propuestoPor ? $beca->propuestoPor->username : 'N/A',
                    ],
                    'fecha_propuesta:datetime',
                    [
                        'label' => 'Aprobado por',
                        'value' => $beca->aprobadoPor ? $beca->aprobadoPor->username : 'N/A',
                    ],
                    'estado_aprobacion',
                    'estado_ciclo',
                    'renovable:boolean',
                    'motivo_rechazo:ntext',
                    'observaciones:ntext',
                    'autorizacion_excepcion:boolean',
                    'd_creacion:datetime',
                ],
            ]) ?>

            <h3>Historial de cambios</h3>
            <?php if ($beca->historial): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($beca->historial as $h): ?>
                        <tr>
                            <td><?= Yii::$app->formatter->asDatetime($h->fecha_creacion) ?></td>
                            <td><?= Html::encode($h->motivo) ?></td>
                            <td><?= Html::encode($h->usuario->username ?? 'N/A') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay historial registrado.</p>
            <?php endif; ?>
        </div>
    </div>
</div>