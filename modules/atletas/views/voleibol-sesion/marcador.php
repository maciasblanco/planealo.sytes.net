<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $sesion app\models\VoleibolSesion */
/* @var $set app\models\VoleibolSet */
/* @var $alineacion array */
/* @var $sustitucionesUsadas array */
/* @var $maxSustituciones int */
/* @var $banca array */

$this->title = 'Marcador en Vivo - Sesión ' . $sesion->id . ' - Set ' . $set->numero;
$this->params['breadcrumbs'][] = ['label' => 'Sesiones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Sesión ' . $sesion->id, 'url' => ['view', 'id' => $sesion->id]];
$this->params['breadcrumbs'][] = $this->title;

// Función auxiliar para renderizar la cuadrícula de alineación
function renderAlineacionEquipo($equipo, $alineacion, $color) {
    $posiciones = [1,6,5,2,3,4]; // Orden visual típico: fila trasera (1,6,5) y delantera (2,3,4)
    $html = '<div class="row">';
    foreach ($posiciones as $pos) {
        $atleta = isset($alineacion[$equipo][$pos]) ? $alineacion[$equipo][$pos] : null;
        $nombre = $atleta ? Html::encode($atleta->p_nombre . ' ' . $atleta->p_apellido) : 'Vacío';
        $html .= '<div class="col-4 mb-2">';
        $html .= '<div class="card border-' . $color . '">';
        $html .= '<div class="card-header bg-' . $color . ' text-white text-center p-1">Pos ' . $pos . '</div>';
        $html .= '<div class="card-body p-2 text-center small">' . $nombre . '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

// Preparar datos de banca para JavaScript de forma segura (compatible con PHP 5.3+)
$bancaA_json = [];
$bancaB_json = [];

if (isset($banca['A']) && is_array($banca['A'])) {
    foreach ($banca['A'] as $atleta) {
        $bancaA_json[] = [
            'id' => $atleta->id,
            'nombre' => $atleta->p_nombre . ' ' . $atleta->p_apellido
        ];
    }
}
if (isset($banca['B']) && is_array($banca['B'])) {
    foreach ($banca['B'] as $atleta) {
        $bancaB_json[] = [
            'id' => $atleta->id,
            'nombre' => $atleta->p_nombre . ' ' . $atleta->p_apellido
        ];
    }
}
?>

<div class="voleibol-marcador">

    <div class="row">
        <div class="col-12 text-center">
            <h1>
                <span class="badge bg-primary fs-1"><?= $set->puntos_a ?></span>
                <span class="fs-2 mx-3">-</span>
                <span class="badge bg-danger fs-1"><?= $set->puntos_b ?></span>
            </h1>
            <h3>Set <?= $set->numero ?> <?= $set->estado == 'F' ? '(Finalizado)' : '' ?></h3>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Equipo A -->
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h4>Equipo A 
                        <small class="float-end">Sustituciones: <?= $sustitucionesUsadas['A'] ?>/<?= $maxSustituciones ?></small>
                    </h4>
                </div>
                <div class="card-body">
                    <?= renderAlineacionEquipo('A', $alineacion, 'primary') ?>
                    <div class="text-center mt-3">
                        <?= Html::button('<i class="fas fa-plus-circle"></i> Punto', [
                            'class' => 'btn btn-primary btn-lg btn-punto',
                            'data-equipo' => 'A',
                            'data-set' => $set->id,
                        ]) ?>
                        <?= Html::button('<i class="fas fa-sync-alt"></i> Rotar', [
                            'class' => 'btn btn-warning btn-lg btn-rotar',
                            'data-equipo' => 'A',
                            'data-set' => $set->id,
                        ]) ?>
                        <?= Html::button('<i class="fas fa-exchange-alt"></i> Sustituir', [
                            'class' => 'btn btn-info btn-lg btn-sustituir',
                            'data-equipo' => 'A',
                            'data-set' => $set->id,
                            'data-bs-toggle' => 'modal',
                            'data-bs-target' => '#sustitucionModal',
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipo B -->
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4>Equipo B 
                        <small class="float-end">Sustituciones: <?= $sustitucionesUsadas['B'] ?>/<?= $maxSustituciones ?></small>
                    </h4>
                </div>
                <div class="card-body">
                    <?= renderAlineacionEquipo('B', $alineacion, 'danger') ?>
                    <div class="text-center mt-3">
                        <?= Html::button('<i class="fas fa-plus-circle"></i> Punto', [
                            'class' => 'btn btn-danger btn-lg btn-punto',
                            'data-equipo' => 'B',
                            'data-set' => $set->id,
                        ]) ?>
                        <?= Html::button('<i class="fas fa-sync-alt"></i> Rotar', [
                            'class' => 'btn btn-warning btn-lg btn-rotar',
                            'data-equipo' => 'B',
                            'data-set' => $set->id,
                        ]) ?>
                        <?= Html::button('<i class="fas fa-exchange-alt"></i> Sustituir', [
                            'class' => 'btn btn-info btn-lg btn-sustituir',
                            'data-equipo' => 'B',
                            'data-set' => $set->id,
                            'data-bs-toggle' => 'modal',
                            'data-bs-target' => '#sustitucionModal',
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de sustitución -->
<div class="modal fade" id="sustitucionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Realizar Sustitución</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-sustitucion">
                    <input type="hidden" name="set_id" id="sust-set-id" value="<?= $set->id ?>">
                    <input type="hidden" name="equipo" id="sust-equipo" value="">
                    <div class="mb-3">
                        <label for="atleta_sale" class="form-label">Jugador que sale</label>
                        <select class="form-control" id="atleta_sale" name="atleta_sale_id" required>
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="atleta_entra" class="form-label">Jugador que entra</label>
                        <select class="form-control" id="atleta_entra" name="atleta_entra_id" required>
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-ejecutar-sustitucion">Ejecutar</button>
            </div>
        </div>
    </div>
</div>

<?php
// URLs para las acciones AJAX
$urlObtenerAlineacion = Url::to(['obtener-alineacion']);
$urlSumarPunto = Url::to(['sumar-punto']);
$urlRotar = Url::to(['rotar']);
$urlSustituir = Url::to(['sustituir']);

// Script JavaScript mejorado
$js = <<<JS
// Objeto global para almacenar alineación y banca
var banca = {
    A: { cancha: [], banca: [] },
    B: { cancha: [], banca: [] }
};

// Cargar alineación actual desde el servidor
$(function() {
    // Cargar cancha equipo A
    $.get('$urlObtenerAlineacion', {sesion_id: $sesion->id, set_id: $set->id, equipo: 'A'}, function(data) {
        if (data.success) {
            banca.A.cancha = data.alineacion.map(function(item) { return item; });
        } else {
            console.warn('No se pudo cargar alineación del equipo A');
        }
    }).fail(function() {
        console.error('Error al cargar alineación A');
    });

    // Cargar cancha equipo B
    $.get('$urlObtenerAlineacion', {sesion_id: $sesion->id, set_id: $set->id, equipo: 'B'}, function(data) {
        if (data.success) {
            banca.B.cancha = data.alineacion.map(function(item) { return item; });
        } else {
            console.warn('No se pudo cargar alineación del equipo B');
        }
    }).fail(function() {
        console.error('Error al cargar alineación B');
    });

    // Asignar banca (jugadores en el banco) - preparados desde PHP
    banca.A.banca = $bancaA_json;
    banca.B.banca = $bancaB_json;
});

// Sumar punto
$('.btn-punto').click(function() {
    var btn = $(this);
    var equipo = btn.data('equipo');
    var set_id = btn.data('set');
    $.post('$urlSumarPunto', {set_id: set_id, equipo: equipo}, function(data) {
        if (data.success) {
            if (data.terminado) {
                alert(data.mensaje);
                location.reload();
            } else {
                // Actualizar marcador
                $('.badge.bg-primary.fs-1').text(data.puntos_a);
                $('.badge.bg-danger.fs-1').text(data.puntos_b);
            }
        } else {
            alert('Error: ' + data.error);
        }
    }, 'json');
});

// Rotar
$('.btn-rotar').click(function() {
    var btn = $(this);
    var equipo = btn.data('equipo');
    var set_id = btn.data('set');
    $.post('$urlRotar', {set_id: set_id, equipo: equipo}, function(data) {
        if (data.success) {
            alert('Rotación aplicada');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    }, 'json');
});

// Abrir modal de sustitución
$('.btn-sustituir').click(function() {
    var equipo = $(this).data('equipo');
    var set_id = $(this).data('set');
    $('#sust-equipo').val(equipo);
    $('#sust-set-id').val(set_id);

    var cancha = banca[equipo].cancha;
    var bancalist = banca[equipo].banca;

    var saleSelect = $('#atleta_sale');
    saleSelect.empty().append('<option value="">Seleccione...</option>');
    cancha.forEach(function(atleta) {
        saleSelect.append('<option value="' + atleta.atleta_id + '">' + atleta.nombre + '</option>');
    });

    var entraSelect = $('#atleta_entra');
    entraSelect.empty().append('<option value="">Seleccione...</option>');
    bancalist.forEach(function(atleta) {
        entraSelect.append('<option value="' + atleta.id + '">' + atleta.nombre + '</option>');
    });
});

// Ejecutar sustitución
$('#btn-ejecutar-sustitucion').click(function() {
    var set_id = $('#sust-set-id').val();
    var equipo = $('#sust-equipo').val();
    var sale_id = $('#atleta_sale').val();
    var entra_id = $('#atleta_entra').val();

    if (!sale_id || !entra_id) {
        alert('Seleccione ambos jugadores');
        return;
    }

    $.post('$urlSustituir', {
        set_id: set_id,
        equipo: equipo,
        atleta_sale_id: sale_id,
        atleta_entra_id: entra_id
    }, function(data) {
        if (data.success) {
            $('#sustitucionModal').modal('hide');
            alert('Sustitución realizada');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    }, 'json');
});
JS;

$this->registerJs($js);
?>