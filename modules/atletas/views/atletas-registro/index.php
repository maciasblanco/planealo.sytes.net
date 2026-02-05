<?php

use app\models\AtletasRegistro;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use app\models\Escuela;
use app\models\RegistroRepresentantes;
use app\models\AportesSemanales;
use app\models\CategoriaAtletas;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var app\modules\atletas\models\AtletasRegistroSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    // ❌ MOSTRAR ERROR Y REDIRECCIÓN
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de ver atletas.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/index'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

$titulo = 'Atletas Registrados - ' . $nombre_escuela;
$this->title = $titulo;
$this->params['breadcrumbs'][] = $this->title;

// Obtener categorías para el dropdown
$categorias = CategoriaAtletas::find()
    ->where(['activo' => true])
    ->orWhere(['activo' => 1])
    ->all();
$categoriasList = ArrayHelper::map($categorias, 'nombre', 'nombre');

// Registrar Font Awesome
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
?>
<div class="atletas-registro-index">
    <section id="resgistroEscuelaClub">
        
        <!-- Información de la Escuela -->
        <div class="alert alert-info mb-4">
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fas fa-school"></i> Escuela Activa:</strong> <?= Html::encode($nombre_escuela) ?>
                    <span class="badge bg-primary ms-2">ID: <?= $id_escuela ?></span>
                </div>
                <div class="col-md-6 text-right">
                    <small class="text-muted">Sistema GED - Lista de Atletas</small>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-center w-100">
                <?= Html::encode($this->title) ?><br>
                <small class="text-muted"><?= Html::encode($nombre_escuela) ?></small>
            </h1>
        </div>

        <p>
            <?= Html::a('<i class="fas fa-running me-2"></i>Registrar Nuevo Atleta', ['create', 'id' => $id_escuela, 'nombre' => $nombre_escuela], ['class' => 'btn btn-success btn-lg']) ?>
        </p>

        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'options' => ['class' => 'grid-view table-responsive'],
            'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn', 'header' => 'N°'],

                // Nombre de la Escuela
                [
                    'attribute' => 'id_escuela',
                    'label' => 'ESCUELA/CLUB',
                    'value' => function($model) {
                        $escuela = Escuela::findOne($model->id_escuela);
                        return $escuela ? mb_strtoupper($escuela->nombre) : 'SIN ESCUELA';
                    },
                    'filter' => false,
                    'contentOptions' => ['style' => 'text-transform: uppercase; font-weight: bold;']
                ],

                // ✅ NOMBRE COMPLETO DEL ATLETA CON FILTRO
                [
                    'attribute' => 'nombreCompleto',
                    'label' => 'NOMBRE COMPLETO DEL ATLETA',
                    'value' => function($model) {
                        $nombreCompleto = trim($model->p_nombre . ' ' . $model->s_nombre . ' ' . $model->p_apellido . ' ' . $model->s_apellido);
                        return mb_strtoupper($nombreCompleto);
                    },
                    'filter' => Html::input('text', 'AtletasRegistroSearch[nombreCompleto]', $searchModel->nombreCompleto, [
                        'class' => 'form-control',
                        'placeholder' => 'Buscar por nombre...'
                    ]),
                    'contentOptions' => ['style' => 'text-transform: uppercase;']
                ],

                // Teléfono del atleta
                [
                    'attribute' => 'cell',
                    'label' => 'TELÉFONO ATLETA',
                    'value' => function($model) {
                        return $model->cell ? mb_strtoupper($model->cell) : 'SIN TELÉFONO';
                    },
                    'contentOptions' => ['style' => 'text-transform: uppercase;']
                ],

                // Deuda en aportes - USANDO EL MODELO APORTES SEMANALES
                [
                    'label' => 'DEUDA EN APORTES',
                    'value' => function($model) {
                        // Usar el método del modelo AportesSemanales para calcular la deuda
                        $montoDeuda = AportesSemanales::calcularMontoDeuda($model->id);
                        $semanasDeuda = AportesSemanales::calcularDeudaAtleta($model->id);
                        
                        if ($montoDeuda <= 0) {
                            return 'AL DÍA';
                        } else {
                            return '$' . number_format($montoDeuda, 2) . ' (' . $semanasDeuda . ' semanas)';
                        }
                    },
                    'contentOptions' => function($model) {
                        $montoDeuda = AportesSemanales::calcularMontoDeuda($model->id);
                        
                        if ($montoDeuda <= 0) {
                            return [
                                'style' => 'text-transform: uppercase; font-weight: bold;',
                                'class' => 'text-success'
                            ];
                        } else {
                            return [
                                'style' => 'text-transform: uppercase; font-weight: bold;',
                                'class' => 'text-danger'
                            ];
                        }
                    }
                ],

                // ✅ CATEGORÍA CON FILTRO
                [
                    'attribute' => 'categoriaNombre',
                    'label' => 'CATEGORÍA',
                    'value' => function($model) {
                        return $model->getCategoriaNombre();
                    },
                    'filter' => Html::input('text', 'AtletasRegistroSearch[categoriaNombre]', $searchModel->categoriaNombre, [
                        'class' => 'form-control',
                        'placeholder' => 'Buscar categoría...'
                    ]),
                    'contentOptions' => ['style' => 'text-transform: uppercase; font-weight: bold;']
                ],

                // Teléfono representante
                [
                    'label' => 'TELÉFONO REPRESENTANTE',
                    'value' => function($model) {
                        if ($model->id_representante) {
                            $representante = RegistroRepresentantes::findOne($model->id_representante);
                            return $representante && $representante->cell ? 
                                   mb_strtoupper($representante->cell) : 'SIN TELÉFONO';
                        }
                        return 'SIN REPRESENTANTE';
                    },
                    'contentOptions' => ['style' => 'text-transform: uppercase;']
                ],

                // Fecha de registro
                [
                    'attribute' => 'd_creacion',
                    'label' => 'FECHA REGISTRO',
                    'value' => function($model) {
                        return Yii::$app->formatter->asDate($model->d_creacion, 'php:d/m/Y');
                    },
                    'contentOptions' => ['style' => 'text-align: center;']
                ],

                [
                    'class' => ActionColumn::className(),
                    'header' => 'ACCIONES',
                    'template' => '{view} {update} {delete}',
                    'contentOptions' => ['style' => 'text-align: center; width: 130px;'],
                    'buttons' => [
                        'view' => function ($url, $model) use ($id_escuela, $nombre_escuela) {
                            return Html::a('<i class="fas fa-search"></i>', 
                                ['view', 'id' => $model->id, 'id_escuela' => $id_escuela, 'nombre' => $nombre_escuela], 
                                [
                                    'class' => 'btn btn-sm btn-info action-btn',
                                    'title' => 'Ver detalles',
                                ]);
                        },
                        'update' => function ($url, $model) use ($id_escuela, $nombre_escuela) {
                            return Html::a('<i class="fas fa-edit"></i>', 
                                ['update', 'id' => $model->id, 'id_escuela' => $id_escuela, 'nombre' => $nombre_escuela], 
                                [
                                    'class' => 'btn btn-sm btn-warning action-btn',
                                    'title' => 'Editar',
                                ]);
                        },
                        'delete' => function ($url, $model) use ($id_escuela, $nombre_escuela) {
                            return Html::a('<i class="fas fa-trash-alt"></i>', 
                                ['delete', 'id' => $model->id, 'id_escuela' => $id_escuela, 'nombre' => $nombre_escuela], 
                                [
                                    'class' => 'btn btn-sm btn-danger action-btn',
                                    'title' => 'Eliminar',
                                    'data' => [
                                        'confirm' => '¿Está seguro de que desea eliminar este atleta?',
                                        'method' => 'post',
                                    ],
                                ]);
                        },
                    ],
                ],
            ],
        ]); ?>
    </section>
</div>

<style>
/* Estilos modernos y juveniles para los botones */
.action-btn {
    margin: 2px;
    padding: 6px 10px;
    border-radius: 8px;
    border: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-info.action-btn {
    background: linear-gradient(135deg, #17a2b8, #138496);
}

.btn-warning.action-btn {
    background: linear-gradient(135deg, #ffc107, #e0a800);
}

.btn-danger.action-btn {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.action-btn i {
    font-size: 14px;
}

/* Estilos para la columna de deuda */
.text-danger {
    color: #dc3545 !important;
    font-weight: bold;
}

.text-success {
    color: #28a745 !important;
    font-weight: bold;
}

/* Estilos para los filtros */
.form-control {
    border-radius: 6px;
    border: 1px solid #ddd;
    padding: 6px 12px;
}

.form-control:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
}
</style>