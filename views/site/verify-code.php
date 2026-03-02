<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ValidateCodeForm */
/* @var $session app\models\VerificationSession */

$this->title = 'Validar Código de Verificación';
$this->params['breadcrumbs'][] = $this->title;

// MOD CORRECCIÓN: Usar code_expires_at en lugar de expires_at
$expiresAt = strtotime($session->code_expires_at);
$currentTime = time();
$timeLeft = max(0, $expiresAt - $currentTime);
$minutesLeft = floor($timeLeft / 60);
$secondsLeft = $timeLeft % 60;
//die(__FILE__);
// MOD CORRECCIÓN: Calcular intentos fallidos a partir de attempts_remaining
$failedAttempts = 3 - $session->attempts_remaining;

// CSS específico para esta vista
$css = <<<CSS
.code-verification-container {
    max-width: 480px;
    margin: 3rem auto;
}

.code-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.code-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.code-title {
    font-weight: 700;
    font-size: 1.6rem;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.code-body {
    padding: 2rem;
    background: #f8f9fa;
}

.code-input-container {
    margin: 2rem 0;
}

.code-input {
    font-size: 2.5rem;
    letter-spacing: 15px;
    text-align: center;
    font-weight: bold;
    border: 2px solid #e3e3e3;
    border-radius: 10px;
    padding: 15px;
    background: white;
    transition: all 0.3s;
}

.code-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.timer-container {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    padding: 15px;
    margin: 1.5rem 0;
    text-align: center;
    border: 2px solid #e3e3e3;
}

.timer-display {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    font-family: 'Courier New', monospace;
}

.timer-warning {
    color: #dc3545;
    animation: pulse 1s infinite;
}

.timer-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.email-info {
    background: #e7f3ff;
    border-left: 4px solid #0d6efd;
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.email-label {
    font-size: 0.875rem;
    color: #0d6efd;
    margin-bottom: 0.25rem;
}

.email-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1a365d;
}

.attempts-warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
}

.attempts-count {
    font-weight: 700;
    color: #856404;
}

.action-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}

.btn-code-action {
    padding: 0.5rem 1.25rem;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-code-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

/* Responsive */
@media (max-width: 576px) {
    .code-verification-container {
        margin: 1rem;
    }
    
    .code-header {
        padding: 1.5rem;
    }
    
    .code-body {
        padding: 1.5rem;
    }
    
    .code-input {
        font-size: 2rem;
        letter-spacing: 10px;
        padding: 12px;
    }
    
    .timer-display {
        font-size: 1.25rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-code-action {
        width: 100%;
    }
}
CSS;

$this->registerCss($css);
?>

<div class="site-verify-code">
    <div class="code-verification-container">
        <div class="code-card">
            <div class="code-header">
                <h1 class="code-title">
                    <i class="fas fa-key"></i> <?= Html::encode($this->title) ?>
                </h1>
                <p class="mb-0 opacity-75">
                    Ingrese el código de 6 dígitos que recibió en su email
                </p>
            </div>
            
            <div class="code-body">
                <?php if (Yii::$app->session->hasFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= Yii::$app->session->getFlash('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (Yii::$app->session->hasFlash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= Yii::$app->session->getFlash('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Información del email -->
                <div class="email-info">
                    <div class="email-label">
                        <i class="fas fa-envelope me-1"></i> Email de destino
                    </div>
                    <div class="email-value">
                        <?= Html::encode($session->email) ?>
                    </div>
                </div>

                <!-- Temporizador -->
                <div class="timer-container">
                    <div class="timer-label">
                        <i class="fas fa-clock me-1"></i> Tiempo restante
                    </div>
                    <div class="timer-display <?= $minutesLeft < 5 ? 'timer-warning' : '' ?>">
                        <span id="countdown-minutes"><?= str_pad($minutesLeft, 2, '0', STR_PAD_LEFT) ?></span>:<span id="countdown-seconds"><?= str_pad($secondsLeft, 2, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <small class="text-muted">El código expira en <?= $minutesLeft ?> minutos</small>
                </div>

                <!-- Advertencia de intentos -->
                <?php if ($failedAttempts > 0): ?>
                    <div class="attempts-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Intentos fallidos:</strong> 
                        <span class="attempts-count"><?= $failedAttempts ?></span> de 3
                        <br>
                        <small>Después de 3 intentos fallidos, la sesión será bloqueada.</small>
                    </div>
                <?php endif; ?>

                <?php $form = ActiveForm::begin([
                    'id' => 'verify-code-form',
                    'options' => ['class' => 'needs-validation'],
                    'fieldConfig' => [
                        'options' => ['class' => 'mb-3'],
                        'inputOptions' => ['class' => 'form-control'],
                        'labelOptions' => ['class' => 'form-label fw-bold'],
                        'errorOptions' => ['class' => 'invalid-feedback'],
                    ],
                ]); ?>

                <div class="code-input-container">
                    <?= $form->field($model, 'verification_code', [
                        'options' => ['class' => 'mb-0']
                    ])->textInput([
                        'maxlength' => 6,
                        'class' => 'form-control code-input',
                        'placeholder' => '000000',
                        'autofocus' => true,
                        'autocomplete' => 'off',
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]{6}',
                        'title' => 'Ingrese exactamente 6 dígitos'
                    ])->label(false) ?>
                    
                    <div class="form-text text-center">
                        <i class="fas fa-info-circle me-1"></i>
                        Ingrese el código de 6 dígitos exactamente como apareció en el email
                    </div>
                </div>

                <?= $form->field($model, 'token')->hiddenInput(['value' => $session->session_token])->label(false) ?>

                <div class="d-grid gap-2 mt-4">
                    <?= Html::submitButton('<i class="fas fa-check-circle me-2"></i> Validar Código', [
                        'class' => 'btn btn-primary btn-lg',
                        'name' => 'validate-button',
                        'id' => 'validate-button'
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>

                <!-- Opciones adicionales -->
                <div class="action-buttons mt-4">
                    <?= Html::a(
                        '<i class="fas fa-redo me-1"></i> Reenviar código',
                        ['site/resend-code', 'token' => $session->session_token],
                        [
                            'class' => 'btn btn-outline-primary btn-code-action',
                            'data' => [
                                'method' => 'post',
                                'confirm' => '¿Desea reenviar el código de verificación?'
                            ]
                        ]
                    ) ?>
                    
                    <?= Html::a(
                        '<i class="fas fa-edit me-1"></i> Cambiar email',
                        ['site/verify-email-first'],
                        [
                            'class' => 'btn btn-outline-secondary btn-code-action',
                            'data' => [
                                'method' => 'post',
                                'params' => ['reset' => '1']
                            ]
                        ]
                    ) ?>
                    
                    <?= Html::a(
                        '<i class="fas fa-sign-out-alt me-1"></i> Cancelar',
                        ['site/logout'],
                        [
                            'class' => 'btn btn-outline-danger btn-code-action',
                            'data' => [
                                'method' => 'post',
                                'confirm' => '¿Está seguro de que desea cancelar la verificación?'
                            ]
                        ]
                    ) ?>
                </div>
            </div>
            
            <div class="card-footer bg-transparent border-top">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Código válido por 15 minutos
                        </small>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small>
                            <i class="fas fa-shield-alt me-1"></i>
                            Verificación de seguridad
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
$(document).ready(function() {
    // Contador regresivo
    let minutes = $minutesLeft;
    let seconds = $secondsLeft;
    
    function updateTimer() {
        if (minutes <= 0 && seconds <= 0) {
            // Tiempo expirado
            $('.timer-display').html('<span class="text-danger">¡TIEMPO EXPIRADO!</span>');
            $('.timer-display').addClass('timer-warning');
            $('#verify-code-form').find('input, button').prop('disabled', true);
            clearInterval(timerInterval);
            
            // Mostrar alerta
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Código expirado',
                    text: 'El código ha expirado. Por favor, solicite uno nuevo.',
                    confirmButtonColor: '#667eea'
                });
            }
            return;
        }
        
        seconds--;
        if (seconds < 0) {
            minutes--;
            seconds = 59;
        }
        
        // Actualizar display
        $('#countdown-minutes').text(minutes.toString().padStart(2, '0'));
        $('#countdown-seconds').text(seconds.toString().padStart(2, '0'));
        
        // Cambiar color cuando queden menos de 5 minutos
        if (minutes < 5) {
            $('.timer-display').addClass('timer-warning');
        }
    }
    
    let timerInterval = setInterval(updateTimer, 1000);
    
    // Validación del código en tiempo real
    $('#validatecodeform-verification_code').on('input', function() {
        let code = $(this).val();
        
        // Solo números, máximo 6 dígitos
        $(this).val(code.replace(/[^0-9]/g, '').substring(0, 6));
        
        // Habilitar botón si hay 6 dígitos
        let isValid = code.length === 6;
        $('#validate-button').prop('disabled', !isValid);
        
        // Agregar clase de validación
        if (code.length === 6) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid');
        }
    });
    
    // Auto-enfocar y auto-submit
    $('#validatecodeform-verification_code').on('keyup', function(e) {
        if ($(this).val().length === 6) {
            // Auto-submit si presiona Enter
            if (e.key === 'Enter') {
                $('#verify-code-form').submit();
            }
        }
    });
    
    // Prevenir envío con código inválido
    $('#verify-code-form').on('submit', function(e) {
        let code = $('#validatecodeform-verification_code').val();
        
        if (code.length !== 6 || !/^\d{6}$/.test(code)) {
            e.preventDefault();
            
            // Mostrar error
            $('#validatecodeform-verification_code').addClass('is-invalid');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Código inválido',
                    text: 'Por favor ingrese un código de 6 dígitos válido.',
                    confirmButtonColor: '#667eea'
                });
            } else {
                alert('Por favor ingrese un código de 6 dígitos válido.');
            }
            return false;
        }
        
        // Mostrar loading
        let submitBtn = $(this).find('button[type="submit"]');
        let originalHtml = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Validando...');
        submitBtn.prop('disabled', true);
        
        // Restaurar botón si hay error
        setTimeout(() => {
            submitBtn.html(originalHtml);
            submitBtn.prop('disabled', false);
        }, 3000);
        
        return true;
    });
    
    // Cargar FontAwesome si no está cargado
    if (typeof FontAwesome === 'undefined') {
        let faScript = document.createElement('script');
        faScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js';
        faScript.crossOrigin = 'anonymous';
        document.head.appendChild(faScript);
    }
    
    // Cargar SweetAlert2 si no está cargado
    if (typeof Swal === 'undefined') {
        let swalScript = document.createElement('script');
        swalScript.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        document.head.appendChild(swalScript);
        
        let swalCss = document.createElement('link');
        swalCss.rel = 'stylesheet';
        swalCss.href = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css';
        document.head.appendChild(swalCss);
    }
});
JS;

$this->registerJs($js);
?>