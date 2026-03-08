<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\models\AtletasRegistro[] $atletas */

$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

$this->title = 'Atletas y Becas - ' . $nombre_escuela;
$this->params['breadcrumbs'][] = ['label' => 'Aportes Quincenales', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Becas', 'url' => ['becas']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="atletas-becas-index">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-users"></i> <?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-medal"></i> Volver a Becas', ['becas'], ['class' => 'btn btn-default']) ?>
            <?php if (Yii::$app->user->can('proponerBeca') || Yii::$app->user->id == 1): ?>
                <?= Html::a('<i class="fas fa-plus"></i> Asignar nueva beca', ['asignar-beca'], ['class' => 'btn btn-success']) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información de la Escuela -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-6">
                <strong><i class="fas fa-school"></i> Escuela Activa:</strong> <?= Html::encode($nombre_escuela) ?>
                <span class="badge bg-primary ms-2">ID: <?= $id_escuela ?></span>
            </div>
            <div class="col-md-6 text-right">
                <small class="text-muted">Sistema GED - Becas por Atleta</small>
            </div>
        </div>
    </div>

    <?php Pjax::begin(); ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Atleta</th>
                        <th>Identificación</th>
                        <th>Familia</th>
                        <th>Beca Activa</th>
                        <th>Beca Pendiente</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($atletas as $index => $atleta): 
                        $becaActiva = $atleta->becaActiva;
                        $becaPendiente = $atleta->becaPendiente;
                        $familia = $atleta->familia; // Asume que existe relación 'familia' en AtletasRegistro
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?></td>
                        <td><?= Html::encode($atleta->identificacion) ?></td>
                        <td>
                            <?php if ($familia): ?>
                                <?= Html::encode($familia->nombre_representante) ?>
                                <br><small class="text-muted">ID: <?= $familia->id_familia ?></small>
                            <?php else: ?>
                                <span class="text-muted">Sin familia</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($becaActiva): ?>
                                <span class="badge bg-success">Activa</span>
                                <?= Html::a('Ver', ['view-beca', 'id' => $becaActiva->id_beca], ['class' => 'btn btn-xs btn-info']) ?>
                                <br>
                                <small><?= Yii::$app->formatter->asDate($becaActiva->fecha_asignacion) ?> - <?= Yii::$app->formatter->asDate($becaActiva->fecha_vencimiento) ?></small>
                                <br>
                                <small><?= Html::encode($becaActiva->tipoBeca->nombre ?? '') ?></small>
                            <?php else: ?>
                                <span class="text-muted">No tiene</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($becaPendiente): ?>
                                <span class="badge bg-warning">Pendiente</span>
                                <?= Html::a('Ver', ['view-beca', 'id' => $becaPendiente->id_beca], ['class' => 'btn btn-xs btn-warning']) ?>
                                <br>
                                <small>Propuesta: <?= Yii::$app->formatter->asDate($becaPendiente->fecha_propuesta) ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($familia): ?>
                                <?php if (!$becaActiva && !$becaPendiente): ?>
                                    <?= Html::a('Asignar beca', ['asignar-beca', 'id_atleta' => $atleta->id], [
                                        'class' => 'btn btn-success btn-sm',
                                        'title' => 'Proponer nueva beca para este atleta'
                                    ]) ?>
                                <?php else: ?>
                                    <span class="text-muted">Ya tiene beca</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted" title="El atleta no pertenece a una familia">Sin familia</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php Pjax::end(); ?>
</div>

<?php
$css = <<<CSS
.badge.bg-success { background-color: #28a745; color: white; }
.badge.bg-warning { background-color: #ffc107; color: black; }
CSS;
$this->registerCss($css);
?>