<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\captcha\Captcha;

/* @var $this yii\web\View */
/* @var $model yii\base\DynamicModel */
/* @var $user app\models\User */

$this->title = 'Verificación de Email - Primer Acceso';
$this->params['breadcrumbs'][] = $this->title;

// Calcular tiempo transcurrido desde la creación de la cuenta
$userCreated = date('d/m/Y', $user->created_at);
$daysSinceCreation = floor((time() - $user->created_at) / (60 * 60 * 24));

// CSS para integración con Bootstrap 5 y tema principal
$css = <<<CSS
.verify-container {
    max-width: 900px;
    margin: 2rem auto;
}

.verify-panel {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
}

.verify-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-bottom: none;
}

.verify-title {
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.verify-body {
    padding: 2rem;
    background: #f8f9fa;
}

.verify-footer {
    background: #f1f3f4;
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    color: #6c757d;
    font-size: 0.875rem;
}

.user-info-box {
    background: #e7f3ff;
    border-left: 4px solid #0d6efd;
    border-radius: 8px;
    padding: 1.25rem;
}

.process-info-box {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 8px;
    padding: 1.25rem;
}

.security-info-box {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
    border-radius: 8px;
    padding: 1.25rem;
}

.captcha-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.captcha-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 1.25rem;
}

.captcha-image {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 5px;
    background: white;
}

.input-group-custom .input-group-text {
    background: #e9ecef;
    border: 2px solid #e3e3e3;
    border-right: none;
}

.input-group-custom .form-control {
    border: 2px solid #e3e3e3;
    border-left: none;
}

.input-group-custom:focus-within .input-group-text,
.input-group-custom:focus-within .form-control {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.1);
}

.btn-verify {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 12px 24px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 1rem 0;
}

.btn-verify:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 14px rgba(50,50,93,.1), 0 3px 6px rgba(0,0,0,.08);
}

.btn-cancel {
    border: 2px solid #6c757d;
    color: #6c757d;
    font-weight: 500;
    padding: 8px 20px;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #6c757d;
    color: white;
    border-color: #6c757d;
}

.verification-steps {
    margin: 1.5rem 0;
    padding-left: 1.5rem;
}

.verification-steps li {
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.info-list {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.info-list li {
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .verify-container {
        margin: 1rem;
    }
    
    .verify-body {
        padding: 1.5rem;
    }
    
    .verify-title {
        font-size: 1.25rem;
    }
}
CSS;

$this->registerCss($css);
?>
<div class="site-verify-email-first">
    <div class="verify-container">
        <div class="verify-panel">
            <div class="verify-header">
                <h1 class="verify-title">
                    <i class="fas fa-shield-alt"></i> <?= Html::encode($this->title) ?>
                </h1>
            </div>
            
            <div class="verify-body">
                <!-- Información del usuario -->
                <div class="user-info-box mb-4">
                    <h4 class="mb-3">
                        <i class="fas fa-user-circle me-2"></i>Información de su cuenta
                    </h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-user text-primary me-2"></i>
                                <strong class="me-2">Usuario:</strong>
                                <span><?= Html::encode($user->username) ?></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-id-card text-primary me-2"></i>
                                <strong class="me-2">Cédula:</strong>
                                <span><?= Html::encode($user->cedula) ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar-plus text-primary me-2"></i>
                                <strong class="me-2">Cuenta creada:</strong>
                                <span><?= $userCreated ?></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <strong class="me-2">Días desde creación:</strong>
                                <span><?= $daysSinceCreation ?> días</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Explicación del proceso -->
                <div class="process-info-box mb-4">
                    <h4 class="mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>¡Primer Acceso Detectado!
                    </h4>
                    <p class="mb-3">Al ser su primer acceso al sistema, debe completar los siguientes pasos de seguridad:</p>
                    <ol class="verification-steps">
                        <li><strong>Verificar su email real</strong> (no el temporal)</li>
                        <li><strong>Recibir un código de verificación</strong> (6 dígitos válido por 15 minutos)</li>
                        <li><strong>Validar el código recibido</strong></li>
                        <li><strong>Cambiar su contraseña</strong> (la contraseña temporal no es segura)</li>
                    </ol>
                    <p class="mb-0">
                        <i class="fas fa-hourglass-half me-1"></i>
                        <strong>Tiempo estimado:</strong> 3-5 minutos
                    </p>
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'verify-email-form',
                    'enableClientValidation' => true,
                    'options' => ['class' => 'needs-validation'],
                    'fieldConfig' => [
                        'options' => ['class' => 'mb-4'],
                        'inputOptions' => ['class' => 'form-control'],
                        'labelOptions' => ['class' => 'form-label fw-bold'],
                        'errorOptions' => ['class' => 'invalid-feedback'],
                        'template' => "{label}\n{input}\n{error}",
                    ],
                ]); ?>

                <!-- Campo para email real -->
                <div class="form-group">
                    <?= $form->field($model, 'email', [
                        'options' => ['class' => 'mb-4'],
                    ])->textInput([
                        'placeholder' => 'ejemplo@dominio.com',
                        'autofocus' => true,
                        'autocomplete' => 'email',
                        'class' => 'form-control form-control-lg'
                    ])->label('<i class="fas fa-envelope me-2"></i>Email Real') ?>
                    <div class="form-text">
                        <i class="fas fa-info-circle me-1"></i>
                        Ingrese su email real (no el temporal @temporal.com)
                    </div>
                </div>

                <!-- Campo para confirmar email -->
                <div class="form-group">
                    <?= $form->field($model, 'emailConfirm')->textInput([
                        'placeholder' => 'Repita su email',
                        'autocomplete' => 'email',
                        'class' => 'form-control form-control-lg'
                    ])->label('<i class="fas fa-check-circle me-2"></i>Confirmar Email') ?>
                </div>

                <!-- CAPTCHA de seguridad -->
                <div class="captcha-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lock me-2"></i>Verificación de Seguridad
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text mb-3">
                            Para prevenir accesos automatizados, por favor ingrese el código que ve en la imagen:
                        </p>
                        <div class="row align-items-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <?= $form->field($model, 'captcha')->widget(Captcha::className(), [
                                    'template' => '<div class="text-center">{image}</div>',
                                    'imageOptions' => [
                                        'class' => 'captcha-image',
                                        'style' => 'width: 100%; max-width: 200px;'
                                    ],
                                ])->label(false) ?>
                            </div>
                            <div class="col-md-8">
                                <?= $form->field($model, 'captcha')->textInput([
                                    'placeholder' => 'Ingrese el código de la imagen',
                                    'autocomplete' => 'off',
                                    'class' => 'form-control form-control-lg'
                                ])->label(false) ?>
                                <div class="form-text">
                                    <i class="fas fa-sync-alt me-1"></i>
                                    Haga clic en la imagen para generar un nuevo código
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="form-group">
                    <?= Html::submitButton('<i class="fas fa-paper-plane me-2"></i> Enviar Código de Verificación', [
                        'class' => 'btn btn-verify w-100',
                        'name' => 'verify-button',
                        'id' => 'verify-button'
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>

                <!-- Información importante -->
                <div class="security-info-box mt-4">
                    <h5 class="mb-3">
                        <i class="fas fa-exclamation-circle me-2"></i>Información Importante
                    </h5>
                    <ul class="info-list">
                        <li>El código de verificación será válido por <strong>15 minutos</strong></li>
                        <li>Solo tiene <strong>3 intentos</strong> para ingresar el código correctamente</li>
                        <li>Si excede los intentos, su cuenta será <strong>bloqueada por 24 horas</strong></li>
                        <li>No comparta este código con nadie</li>
                        <li>Asegúrese de ingresar un email válido y accesible</li>
                    </ul>
                </div>

                <!-- Opción para cancelar -->
                <div class="text-center mt-4">
                    <?= Html::a('<i class="fas fa-sign-out-alt me-2"></i> Cancelar y Salir', ['site/logout'], [
                        'class' => 'btn btn-cancel',
                        'data' => [
                            'confirm' => '¿Está seguro de que desea salir? Perderá el progreso de verificación.',
                            'method' => 'post'
                        ]
                    ]) ?>
                </div>
            </div>
            
            <div class="verify-footer">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small>
                            <i class="fas fa-clock me-1"></i> 
                            <?= date('d/m/Y H:i:s') ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small>
                            <i class="fas fa-shield-alt me-1"></i>
                            Sistema de Autenticación Segura
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// JavaScript para validación en tiempo real
$js = <<<JS
$(document).ready(function() {
    // Validar que los emails coincidan en tiempo real
    $('#verify-email-form').on('blur', '#dynamicmodel-email, #dynamicmodel-emailconfirm', function() {
        var email = $('#dynamicmodel-email').val();
        var emailConfirm = $('#dynamicmodel-emailconfirm').val();
        
        if (email && emailConfirm && email !== emailConfirm) {
            // Mostrar error visual
            var emailConfirmGroup = $('#dynamicmodel-emailconfirm').closest('.form-group');
            emailConfirmGroup.addClass('has-error');
            
            // Crear mensaje de error si no existe
            if (!$('#email-mismatch-error').length) {
                $('<div id="email-mismatch-error" class="invalid-feedback d-block">Los emails no coinciden</div>')
                    .insertAfter('#dynamicmodel-emailconfirm');
            }
            
            // Deshabilitar botón de envío
            $('#verify-button').prop('disabled', true).addClass('disabled');
        } else {
            // Remover error
            $('#dynamicmodel-emailconfirm').closest('.form-group').removeClass('has-error');
            $('#email-mismatch-error').remove();
            
            // Habilitar botón si todos los campos están llenos
            var isValid = email && emailConfirm && $('#dynamicmodel-captcha').val();
            $('#verify-button').prop('disabled', !isValid).toggleClass('disabled', !isValid);
        }
    });
    
    // Validar CAPTCHA en tiempo real
    $('#dynamicmodel-captcha').on('input', function() {
        var email = $('#dynamicmodel-email').val();
        var emailConfirm = $('#dynamicmodel-emailconfirm').val();
        var captcha = $(this).val();
        
        var isValid = email && emailConfirm && captcha;
        $('#verify-button').prop('disabled', !isValid).toggleClass('disabled', !isValid);
    });
    
    // Auto-completar email de confirmación cuando sea igual
    $('#dynamicmodel-email').on('blur', function() {
        var email = $(this).val();
        var emailConfirm = $('#dynamicmodel-emailconfirm').val();
        
        if (!emailConfirm && email) {
            $('#dynamicmodel-emailconfirm').val(email);
            // Trigger el evento blur para validar
            $('#dynamicmodel-emailconfirm').trigger('blur');
        }
    });
    
    // Prevenir envío si hay errores
    $('#verify-email-form').on('submit', function(e) {
        var email = $('#dynamicmodel-email').val();
        var emailConfirm = $('#dynamicmodel-emailconfirm').val();
        
        if (email !== emailConfirm) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Los emails no coinciden. Por favor, verifique.',
                confirmButtonColor: '#667eea'
            });
            return false;
        }
        
        // Validar formato de email
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Formato de email inválido. Por favor, ingrese un email válido.',
                confirmButtonColor: '#667eea'
            });
            return false;
        }
        
        // Mostrar loading
        $('#verify-button').html('<i class="fas fa-spinner fa-spin me-2"></i> Enviando...');
        $('#verify-button').prop('disabled', true);
        
        return true;
    });
    
    // Validar formato de email en tiempo real
    $('#dynamicmodel-email, #dynamicmodel-emailconfirm').on('blur', function() {
        var email = $(this).val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $('<div class="invalid-feedback">Formato de email inválido</div>').insertAfter(this);
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
    
    // Cargar FontAwesome si no está cargado
    if (typeof FontAwesome === 'undefined') {
        var faScript = document.createElement('script');
        faScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js';
        faScript.crossOrigin = 'anonymous';
        document.head.appendChild(faScript);
    }
    
    // Cargar SweetAlert2 para mejores alertas
    if (typeof Swal === 'undefined') {
        var swalScript = document.createElement('script');
        swalScript.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        document.head.appendChild(swalScript);
        
        var swalCss = document.createElement('link');
        swalCss.rel = 'stylesheet';
        swalCss.href = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css';
        document.head.appendChild(swalCss);
    }
});
JS;

$this->registerJs($js);
?>