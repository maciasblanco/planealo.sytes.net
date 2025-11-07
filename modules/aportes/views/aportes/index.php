<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $atletasConEstadisticas array */
/* @var $totalRecaudado float */
/* @var $pendientes int */
/* @var $deudaTotal float */
/* @var $atletasConDeuda int */
/* @var $topAtletas array */
/* @var $totalAtletas int */
/* @var $erroresProcesamiento array */

$this->title = 'Gestión de Aportes - Todos los Atletas';
$this->params['breadcrumbs'][] = $this->title;

// DEBUG: Información para troubleshooting
$debugInfo = [
    'total_atletas' => $totalAtletas,
    'atletas_con_estadisticas' => count($atletasConEstadisticas),
    'total_recaudado' => $totalRecaudado,
    'errores_procesamiento' => isset($erroresProcesamiento) ? count($erroresProcesamiento) : 0
];
?>

<div class="aportes-index">
    <div class="row">
        <div class="col-md-8">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="col-md-4 text-right">
            <?= Html::a('<i class="fas fa-sync-alt"></i> Actualizar', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <!-- Información de Debug (solo para desarrollo) -->
    <div class="alert alert-info d-none" id="debug-info">
        <strong>Debug Info:</strong><br>
        <pre><?= print_r($debugInfo, true) ?></pre>
    </div>

    <!-- Mostrar errores de procesamiento si existen -->
    <?php if (!empty($erroresProcesamiento)): ?>
    <div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-triangle"></i> Errores durante el procesamiento</h5>
        <p>Se encontraron <?= count($erroresProcesamiento) ?> errores al procesar los atletas. Algunos datos pueden estar incompletos.</p>
        <details>
            <summary>Ver detalles técnicos</summary>
            <ul>
                <?php foreach (array_slice($erroresProcesamiento, 0, 5) as $error): // Mostrar solo los primeros 5 errores ?>
                    <li><small><?= Html::encode($error) ?></small></li>
                <?php endforeach; ?>
            </ul>
        </details>
    </div>
    <?php endif; ?>

    <!-- PANEL DE ESTADÍSTICAS -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-success">
                <div class="info-box-content">
                    <span class="info-box-text">Total Recaudado</span>
                    <span class="info-box-number">$<?= number_format($totalRecaudado, 2) ?></span>
                    <span class="info-box-detail">De todos los atletas</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <div class="info-box-content">
                    <span class="info-box-text">Aportes Pendientes</span>
                    <span class="info-box-number"><?= $pendientes ?></span>
                    <span class="info-box-detail">Semanas por pagar</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-danger">
                <div class="info-box-content">
                    <span class="info-box-text">Deuda Total</span>
                    <span class="info-box-number">$<?= number_format($deudaTotal, 2) ?></span>
                    <span class="info-box-detail"><?= $atletasConDeuda ?> atletas</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-info">
                <div class="info-box-content">
                    <span class="info-box-text">Total Atletas</span>
                    <span class="info-box-number"><?= $totalAtletas ?></span>
                    <span class="info-box-detail">En la escuela</span>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="btn-group" role="group">
                <?= Html::a('<i class="fas fa-user"></i> Gestión por Atleta', ['gestion-atleta'], ['class' => 'btn btn-success']) ?>
                <?= Html::a('<i class="fas fa-users"></i> Registro Masivo', ['registro-masivo'], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fas fa-money-bill-wave"></i> Pago Múltiple', ['pago-multiple'], ['class' => 'btn btn-warning']) ?>
                <?= Html::a('<i class="fas fa-forward"></i> Pago Adelantado', ['pago-adelantado'], ['class' => 'btn btn-info']) ?>
            </div>
        </div>
    </div>

    <!-- LISTA DE ATLETAS -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> Lista de Atletas
                <span class="badge badge-light"><?= $totalAtletas ?> atletas registrados</span>
                <span class="badge badge-info ml-2"><?= count($atletasConEstadisticas) ?> procesados</span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($atletasConEstadisticas)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <h4>No se encontraron atletas</h4>
                    <p>No hay atletas registrados en esta escuela o no se pudo cargar la información.</p>
                    <?php if ($totalAtletas > 0): ?>
                        <div class="alert alert-danger mt-3">
                            <strong>Inconsistencia detectada:</strong> 
                            El sistema reporta <?= $totalAtletas ?> atletas pero no se pudieron cargar sus datos.
                            <br>Esto puede deberse a un error en la base de datos o en el procesamiento.
                        </div>
                    <?php endif; ?>
                    <?= Html::a('<i class="fas fa-plus"></i> Registrar Nuevo Atleta', ['/atletas/atletas-registro/create'], ['class' => 'btn btn-success']) ?>
                </div>
            <?php else: ?>
                <?php if (count($atletasConEstadisticas) < $totalAtletas): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Advertencia:</strong> Solo se pudieron procesar <?= count($atletasConEstadisticas) ?> de <?= $totalAtletas ?> atletas.
                        <?php if (!empty($erroresProcesamiento)): ?>
                            <br>Revise la sección de errores arriba para más detalles.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Mostrando <strong><?= count($atletasConEstadisticas) ?></strong> de <strong><?= $totalAtletas ?></strong> atletas
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="tabla-atletas">
                        <thead class="thead-dark">
                            <tr>
                                <th width="50px">#</th>
                                <th>Nombre del Atleta</th>
                                <th class="text-center">Monto Total Pagado</th>
                                <th class="text-center">Monto Total Deuda</th>
                                <th class="text-center">Monto Total Adelantado</th>
                                <th class="text-center">Semanas Adelantadas</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atletasConEstadisticas as $index => $item): ?>
                                <?php 
                                $atleta = $item['atleta'];
                                $montoPagado = $item['montoPagado'];
                                $montoDeuda = $item['montoDeuda'];
                                $montoAdelantado = $item['montoAdelantado'];
                                $semanasAdelantadas = $item['semanasAdelantadas'];
                                
                                // Calcular semanas pagadas y totales
                                $semanasPagadas = $montoPagado / app\models\AportesSemanales::MONTO_SEMANAL;
                                $semanasDeuda = $montoDeuda / app\models\AportesSemanales::MONTO_SEMANAL;
                                ?>
                                <tr>
                                    <td class="text-center"><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) ?></strong>
                                        <?php if (!empty($atleta->s_nombre) || !empty($atleta->s_apellido)): ?>
                                            <br><small><?= Html::encode(trim($atleta->s_nombre . ' ' . $atleta->s_apellido)) ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-muted"><?= Html::encode($atleta->identificacion) ?></small>
                                        <?php if (isset($item['error']) && $item['error']): ?>
                                            <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error en datos</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-success font-weight-bold">
                                            $<?= number_format($montoPagado, 2) ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <?= number_format($semanasPagadas, 1) ?> semanas
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($montoDeuda > 0): ?>
                                            <span class="text-danger font-weight-bold">
                                                $<?= number_format($montoDeuda, 2) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?= number_format($semanasDeuda, 1) ?> semanas
                                            </small>
                                        <?php else: ?>
                                            <span class="text-success">Al día</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($montoAdelantado > 0): ?>
                                            <span class="text-info font-weight-bold">
                                                $<?= number_format($montoAdelantado, 2) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?= number_format($semanasAdelantadas, 1) ?> semanas
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($semanasAdelantadas > 0): ?>
                                            <span class="badge badge-info">
                                                <?= number_format($semanasAdelantadas, 1) ?> semanas
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?= Html::a('<i class="fas fa-edit"></i> Gestionar', 
                                            ['gestion-atleta', 'atleta_id' => $atleta->id], 
                                            ['class' => 'btn btn-sm btn-primary']
                                        ) ?>
                                        <?= Html::a('<i class="fas fa-eye"></i> Ver', 
                                            ['/atletas/atletas-registro/view', 'id' => $atleta->id], 
                                            ['class' => 'btn btn-sm btn-info', 'target' => '_blank']
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Información adicional -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-light">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                <strong>Información:</strong> 
                                Monto semanal: $<?= number_format(app\models\AportesSemanales::MONTO_SEMANAL, 2) ?> | 
                                Atletas con deuda: <?= $atletasConDeuda ?> | 
                                Última actualización: <?= date('d/m/Y H:i:s') ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// JavaScript para mejorar la experiencia
$this->registerJs(<<<JS
    $(document).ready(function() {
        // Inicializar DataTables si hay datos
        if ($('#tabla-atletas').length && $('#tabla-atletas tbody tr').length > 0) {
            $('#tabla-atletas').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
                },
                "responsive": true,
                "pageLength": 25,
                "order": [[1, 'asc']],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                "columnDefs": [
                    { "orderable": false, "targets": [6] } // Deshabilitar ordenamiento en columna de acciones
                ]
            });
        }
        
        // Mostrar información de debug con Ctrl+Shift+D
        $(document).keydown(function(e) {
            if (e.ctrlKey && e.shiftKey && e.keyCode == 68) {
                $('#debug-info').toggleClass('d-none');
            }
        });

        // Botón para exportar datos
        $('#btn-exportar').click(function() {
            alert('Funcionalidad de exportación en desarrollo');
        });

        // Resaltar filas con deuda
        $('#tabla-atletas tbody tr').each(function() {
            var deudaCell = $(this).find('td:eq(3)');
            if (deudaCell.find('.text-danger').length > 0) {
                $(this).addClass('table-warning');
            }
        });
    });

    // Función para buscar atletas rápidamente
    function buscarAtletas() {
        var input = document.getElementById('buscar-atleta');
        var filter = input.value.toUpperCase();
        var table = document.getElementById('tabla-atletas');
        var tr = table.getElementsByTagName('tr');

        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByTagName('td')[1];
            if (td) {
                var txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }       
        }
    }
JS
);

// Registrar CSS para DataTables si no está incluido
$this->registerCssFile('https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css');
$this->registerJsFile('https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>

<style>
.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: 0.25rem;
    background: #fff;
    display: flex;
    margin-bottom: 1rem;
    min-height: 80px;
    padding: 0.5rem;
    transition: transform 0.2s;
}
.info-box:hover {
    transform: translateY(-2px);
}
.info-box .info-box-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.2;
    flex: 1;
    padding: 0 10px;
}
.info-box .info-box-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-transform: uppercase;
    font-weight: bold;
    font-size: 0.875rem;
}
.info-box .info-box-number {
    display: block;
    font-weight: bold;
    font-size: 1.5rem;
    margin-bottom: 5px;
}
.info-box .info-box-detail {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
}

/* Estilos para la tabla */
.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}
.table-warning {
    background-color: #fff3cd !important;
}

/* Badges personalizados */
.badge {
    font-size: 0.75em;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .info-box {
        margin-bottom: 0.5rem;
    }
    .info-box .info-box-number {
        font-size: 1.2rem;
    }
    .btn-group .btn {
        margin-bottom: 5px;
    }
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.alert {
    animation: fadeIn 0.5s ease-in;
}

/* Estilos para el buscador */
.buscador-atletas {
    margin-bottom: 15px;
}
.buscador-atletas input {
    border-radius: 20px;
    padding: 8px 15px;
    border: 1px solid #ddd;
}
</style>

<!-- Buscador rápido (opcional) -->
<div class="buscador-atletas d-none">
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
        </div>
        <input type="text" id="buscar-atleta" class="form-control" placeholder="Buscar atleta por nombre..." onkeyup="buscarAtletas()">
    </div>
</div>