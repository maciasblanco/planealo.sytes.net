<?php

use app\models\Discapacidad;
use app\models\Enfermedades;
use app\models\Nacionalidad;
use app\models\Sexo;
use app\models\Alergias;
use app\models\CategoriaAtletas;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\AtletasRegistro $model */
/** @var yii\widgets\ActiveForm $form */

// ✅ VALIDACIÓN DE SESIÓN - BLINDAJE GED
$session = Yii::$app->session;
$id_escuela = $session->get('id_escuela');
$nombre_escuela = $session->get('nombre_escuela');

if (empty($id_escuela)) {
    // ❌ MOSTRAR ERROR Y REDIRECCIÓN
    echo '<div class="alert alert-danger text-center">
            <h4><i class="fa fa-exclamation-triangle"></i> Escuela No Seleccionada</h4>
            <p>Debe seleccionar una escuela antes de registrar atletas.</p>
            ' . Html::a('Seleccionar Escuela', ['/ged/default/index'], ['class' => 'btn btn-primary']) . '
          </div>';
    return;
}

// ✅ ASIGNAR DIRECTAMENTE AL MODELO DESDE LA SESIÓN
$model->id_escuela = $id_escuela;
?>

<div class="container-registro-atletas-form">
    <section id="resgistroAtletas">
        <div class="row">
            <div class="section-title">
                <h2>Registro Atletas - <?= Html::encode($nombre_escuela) ?></h2>
            </div> 
        </div>
        <?php $form = ActiveForm::begin(); ?> 
        
        <!-- ✅ SOLO UN CAMPO OCULTO PARA ESCUELA -->
        <?= $form->field($model, 'id_escuela')->hiddenInput(['value' => $id_escuela])->label(false) ?>
        
            <div id="form-ingresoAtleta" class="row">
                <div class="col-md-12">
                    
                    <!-- ===== DATOS DEL ATLETA ===== -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Datos del Atleta - Escuela: <?= Html::encode($nombre_escuela) ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="text-primary">Voleibol - <?= Html::encode($nombre_escuela) ?></h5>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div class="alert alert-info py-1">
                                        <small><strong>Escuela ID:</strong> <?= $id_escuela ?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Datos Personales del Atleta -->
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Datos Personales</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'p_nombre')->textInput(['maxlength' => true])->label('Primer Nombre *') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 's_nombre')->textInput(['maxlength' => true])->label('Segundo Nombre') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'p_apellido')->textInput(['maxlength' => true])->label('Primer Apellido *') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 's_apellido')->textInput(['maxlength' => true])->label('Segundo Apellido') ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <?= $form->field($model, 'id_nac')->dropDownList(
                                                ArrayHelper::map(Nacionalidad::find()->orderBy('id')->all(), 'id', 'letra'),
                                                [
                                                    'prompt' => 'Seleccione...',
                                                    'class' => 'form-control',
                                                ]
                                            )->label('Nacionalidad *'); ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?= $form->field($model, 'identificacion')->textInput(['maxlength' => true])->label('Cédula *') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'fn')->textInput([
                                                'type' => 'date',
                                                'class' => 'form-control'
                                            ])->label('Fecha de Nacimiento *') ?>
                                        </div>
                                        <div class="col-md-1">
                                            <?= $form->field($model, 'edad')->textInput([
                                                'readonly' => true,
                                                'class' => 'form-control bg-light'
                                            ])->label('Edad'); ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?= $form->field($model, 'categoria')->textInput([
                                                'readonly' => true,
                                                'class' => 'form-control bg-light'
                                            ])->label('Categoría'); ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?= $form->field($model, 'sexo')->dropDownList(
                                                ArrayHelper::map(Sexo::find()->orderBy('id')->all(), 'id', 'descripcion'),
                                                [
                                                    'prompt' => 'Seleccione...',
                                                    'class' => 'form-control',
                                                ]
                                            )->label('Sexo *'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos Médicos -->
                            <div class="card mb-3">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0">Datos Médicos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <?= $form->field($model, 'id_alergias')->dropDownList(
                                                ArrayHelper::map(Alergias::find()->orderBy('id')->all(), 'id', 'descripcion'),
                                                [
                                                    'prompt' => 'Seleccione...',
                                                    'class' => 'form-control',
                                                ]
                                            )->label('Alergias'); ?>
                                        </div>
                                        <div class="col-md-4">
                                            <?= $form->field($model, 'id_enfermedades')->dropDownList(
                                                ArrayHelper::map(Enfermedades::find()->orderBy('id')->all(), 'id', 'descripcion'),
                                                [
                                                    'prompt' => 'Seleccione...',
                                                    'class' => 'form-control',
                                                ]
                                            )->label('Enfermedades Crónicas'); ?>
                                        </div>
                                        <div class="col-md-4">
                                            <?= $form->field($model, 'id_discapacidad')->dropDownList(
                                                ArrayHelper::map(Discapacidad::find()->orderBy('id')->all(), 'id', 'descripcion'),
                                                [
                                                    'prompt' => 'Seleccione...',
                                                    'class' => 'form-control',
                                                ]
                                            )->label('Discapacidad'); ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <?= $form->field($model, 'asma')->checkbox([
                                                'label' => '¿Padece de asma?'
                                            ]) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos Antropométricos -->
                            <div class="card mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">Datos Antropométricos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'estatura')->textInput([
                                                'type' => 'number',
                                                'step' => '0.01',
                                                'min' => '0',
                                                'max' => '2.50',
                                                'placeholder' => 'Ej: 1.75'
                                            ])->label('Estatura (metros) *') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'peso')->textInput([
                                                'type' => 'number',
                                                'step' => '0.1',
                                                'min' => '0',
                                                'max' => '200',
                                                'placeholder' => 'Ej: 65.5'
                                            ])->label('Peso (kg)') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'talla_franela')->textInput(['maxlength' => true])->label('Talla Franela *') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'talla_short')->textInput(['maxlength' => true])->label('Talla Short *') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos de Contacto del Atleta -->
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Datos de Contacto del Atleta</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'cell')->textInput(['maxlength' => true])->label('Teléfono Celular *') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'telf')->textInput(['maxlength' => true])->label('Teléfono Fijo') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== DATOS DEL REPRESENTANTE ===== -->
                    <div class="card mb-4">
                        <div class="card-header" style="background-color: #6f42c1; color: white;">
                            <h4 class="mb-0">Datos del Representante</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <?= $form->field($model, 'p_nombre_representante')->textInput(['maxlength' => true])->label('Primer Nombre *') ?>
                                </div>
                                <div class="col-md-3">
                                    <?= $form->field($model, 's_nombre_representante')->textInput(['maxlength' => true])->label('Segundo Nombre') ?>
                                </div>
                                <div class="col-md-3">
                                    <?= $form->field($model, 'p_apellido_representante')->textInput(['maxlength' => true])->label('Primer Apellido *') ?>
                                </div>
                                <div class="col-md-3">
                                    <?= $form->field($model, 's_apellido_representante')->textInput(['maxlength' => true])->label('Segundo Apellido') ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <?= $form->field($model, 'id_nac_representante')->dropDownList(
                                        ArrayHelper::map(Nacionalidad::find()->orderBy('id')->all(), 'id', 'letra'),
                                        [
                                            'prompt' => 'Seleccione...',
                                            'class' => 'form-control',
                                        ]
                                    )->label('Nacionalidad *'); ?>
                                </div>
                                <div class="col-md-3">
                                    <?= $form->field($model, 'identificacion_representante')->textInput(['maxlength' => true])->label('Cédula *') ?>
                                </div>
                                <div class="col-md-3">
                                    <?= $form->field($model, 'cell_representante')->textInput(['maxlength' => true])->label('Teléfono Celular *') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== DATOS SOLO USO ESCUELA/CLUB ===== -->
                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <h4 class="mb-0">Datos Solo Uso Escuela/Club</h4>
                        </div>
                        <div class="card-body">
                            
                            <!-- Información de la Escuela/Club -->
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0">Información de la Escuela/Club</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <?= $form->field($model, 'nombreEscuelaClub')->textInput([
                                                'value' => $nombre_escuela,
                                                'readonly' => true,
                                                'class' => 'form-control bg-light'
                                            ])->label('Escuela/Club Deportivo') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contactos de Emergencia -->
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">Contactos de Emergencia</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Nota en amarillo mejorada -->
                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>INFORMACIÓN EXCLUSIVA PARA CONTACTOS INSTITUCIONALES:</strong> 
                                        Los números proporcionados deben estar disponibles las 24 horas para situaciones de emergencia.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'telf_emergencia1')->textInput(['maxlength' => true])->label('Teléfono Emergencia 1 *') ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?= $form->field($model, 'telf_emergencia2')->textInput(['maxlength' => true])->label('Teléfono Emergencia 2') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>            
            </div>

            <!-- Botón de Envío -->
            <div class="row mt-4">
                <div class="col-md-4 offset-md-4">
                    <div class="d-grid">
                        <?= Html::submitButton('<i class="fas fa-save me-2"></i>Guardar Registro Atleta', ['class' => 'btn btn-success btn-lg']) ?>
                    </div>
                </div>
            </div> 
        <?php ActiveForm::end(); ?>                      
    </section>

<script type="text/javascript">
// Función para calcular edad y categoría
function calcularEdadYCategoria() {
    var fechaNacimiento = $('#atletasregistro-fn').val();
    
    if (!fechaNacimiento) {
        $('#atletasregistro-edad').val('');
        $('#atletasregistro-categoria').val('');
        return;
    }
    
    // Calcular edad
    var fechaNac = new Date(fechaNacimiento);
    var hoy = new Date();
    var edad = hoy.getFullYear() - fechaNac.getFullYear();
    var mes = hoy.getMonth() - fechaNac.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
        edad--;
    }
    
    $('#atletasregistro-edad').val(edad);
    
    // Obtener categoría via AJAX
    $.ajax({
        url: '<?= Yii::$app->urlManager->createUrl(['atletas/atletas-registro/calcular-categoria']) ?>',
        type: 'POST',
        data: {
            edad: edad,
            _csrf: '<?= Yii::$app->request->getCsrfToken() ?>'
        },
        success: function(response) {
            console.log('Respuesta del servidor:', response); // Para debug
            if (response.success) {
                $('#atletasregistro-categoria').val(response.categoria);
            } else {
                $('#atletasregistro-categoria').val('SIN CATEGORÍA');
            }
        },
        error: function(xhr, status, error) {
            console.log('Error en AJAX:', error); // Para debug
            $('#atletasregistro-categoria').val('SIN CATEGORÍA');
        }
    });
}

// Evento cuando cambia la fecha de nacimiento
$('#atletasregistro-fn').on('change', function(){
    calcularEdadYCategoria();
});

// Calcular al cargar la página si ya hay una fecha
$(document).ready(function(){
    if ($('#atletasregistro-fn').val()) {
        calcularEdadYCategoria();
    }
});
</script>   
</div>