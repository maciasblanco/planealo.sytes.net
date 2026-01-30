<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ChangePasswordForm */
/* @var $user app\models\User */

$this->title = 'Cambiar Contraseña - Primer Acceso';
$this->params['breadcrumbs'][] = $this->title;

$css = <<<CSS
.password-change-container {
    max-width: 500px;
    margin: 3rem auto;
}

.password-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.password-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.password-title {
    font-weight: 700;
    font-size: 1.6rem;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.password-body {
    padding: 2rem;
    background: #f8f9fa;
}

.password-input-group {
    position: relative;
    margin-bottom: 1.5rem;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    z-index: 10;
    padding: 5px;
}

.password-toggle:hover {
    color: #667eea;
}

.password-strength {
    height: 6px;
    border-radius: 3px;
    margin-top: 5px;
    background: #e9ecef;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 3px;
}

.strength-text {
    font-size: 0.875rem;
    margin-top: 5px;
    font-weight: 500;
}

.strength-requirements {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.25rem;
    margin: 1.5rem 0;
    border: 1px solid #e3e3e3;
}

.requirement-list {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

.requirement-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.requirement-icon {
    width: 20px;
    text-align: center;
    margin-right: 10px;
    font-size: 0.875rem;
}

.requirement-met {
    color: #28a745;
}

.requirement-unmet {
    color: #dc3545;
}

.requirement-text {
    flex: 1;
}

.password-match {
    font-size: 0.875rem;
    margin-top: 5px;
}

.match-valid {
    color: #28a745;
}

.match-invalid {
    color: #dc3545;
}

.user-verified {
    background: #d4edda;
    border-left: 4px solid #28a745;
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.security-info {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1.5rem;
    font-size: 0.875rem;
}

.security-info ul {
    margin-bottom: 0;
    padding-left: 1.25rem;
}

.security-info li {
    margin-bottom: 5px;
}

.btn-password-submit {
    padding: 0.875rem 2rem;
    font-weight: 600;
    font-size: 1.1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 1rem;
}

.btn-password-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 576px) {
    .password-change-container {
        margin: 1rem;
    }
    
    .password-header {
        padding: 1.5rem;
    }
    
    .password-body {
        padding: 1.5rem;
    }
    
    .password-title {
        font-size: 1.4rem;
    }
}
CSS;

$this->registerCss($css);
?>

<div class="site-change-password-first">
    <div class="password-change-container">
        <div class="password-card">
            <div class="password-header">
                <h1 class="password-title">
                    <i class="fas fa-lock"></i> <?= Html::encode($this->title) ?>
                </h1>
                <p class="mb-0 opacity-75">
                    Establezca una nueva contraseña segura para su cuenta
                </p>
            </div>
            
            <div class="password-body">
                <?php if (Yii::$app->session->hasFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= Yii::$app->session->getFlash('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Verificación completada -->
                <div class="user-verified">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                        <div>
                            <h5 class="mb-1 text-success">¡Verificación completada!</h5>
                            <p class="mb-0">Usuario: <strong><?= Html::encode($user->username) ?></strong></p>
                            <small class="text-muted">Ahora debe establecer una contraseña segura y permanente.</small>
                        </div>
                    </div>
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'change-password-form',
                    'options' => ['class' => 'needs-validation'],
                    'fieldConfig' => [
                        'options' => ['class' => 'mb-3'],
                        'inputOptions' => ['class' => 'form-control'],
                        'labelOptions' => ['class' => 'form-label fw-bold'],
                        'errorOptions' => ['class' => 'invalid-feedback'],
                    ],
                ]); ?>

                <!-- Nueva contraseña -->
                <div class="password-input-group">
                    <?= $form->field($model, 'new_password', [
                        'template' => "{label}\n<div class='input-group'>{input}<button type='button' class='password-toggle' id='toggle-new-password'><i class='fas fa-eye'></i></button></div>\n<div class='password-strength'><div class='strength-bar' id='strength-bar'></div></div><div class='strength-text' id='strength-text'></div>\n{error}"
                    ])->passwordInput([
                        'placeholder' => 'Ingrese nueva contraseña',
                        'autofocus' => true,
                        'id' => 'new-password',
                        'class' => 'form-control'
                    ])->label('<i class="fas fa-key me-2"></i>Nueva Contraseña') ?>
                </div>

                <!-- Confirmar contraseña -->
                <div class="password-input-group">
                    <?= $form->field($model, 'confirm_password', [
                        'template' => "{label}\n<div class='input-group'>{input}<button type='button' class='password-toggle' id='toggle-confirm-password'><i class='fas fa-eye'></i></button></div>\n<div class='password-match' id='password-match'></div>\n{error}"
                    ])->passwordInput([
                        'placeholder' => 'Confirme la nueva contraseña',
                        'id' => 'confirm-password',
                        'class' => 'form-control'
                    ])->label('<i class="fas fa-check-double me-2"></i>Confirmar Contraseña') ?>
                </div>

                <!-- Requisitos de contraseña -->
                <div class="strength-requirements">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-clipboard-check me-2"></i>Requisitos de seguridad:
                    </h6>
                    <ul class="requirement-list">
                        <li class="requirement-item">
                            <span class="requirement-icon" id="req-length-icon">
                                <i class="fas fa-times requirement-unmet"></i>
                            </span>
                            <span class="requirement-text" id="req-length-text">Mínimo 8 caracteres</span>
                        </li>
                        <li class="requirement-item">
                            <span class="requirement-icon" id="req-uppercase-icon">
                                <i class="fas fa-times requirement-unmet"></i>
                            </span>
                            <span class="requirement-text" id="req-uppercase-text">Al menos una letra mayúscula (A-Z)</span>
                        </li>
                        <li class="requirement-item">
                            <span class="requirement-icon" id="req-lowercase-icon">
                                <i class="fas fa-times requirement-unmet"></i>
                            </span>
                            <span class="requirement-text" id="req-lowercase-text">Al menos una letra minúscula (a-z)</span>
                        </li>
                        <li class="requirement-item">
                            <span class="requirement-icon" id="req-number-icon">
                                <i class="fas fa-times requirement-unmet"></i>
                            </span>
                            <span class="requirement-text" id="req-number-text">Al menos un número (0-9)</span>
                        </li>
                        <li class="requirement-item">
                            <span class="requirement-icon" id="req-special-icon">
                                <i class="fas fa-times requirement-unmet"></i>
                            </span>
                            <span class="requirement-text" id="req-special-text">Al menos un carácter especial (!@#$%^&*()_+-=)</span>
                        </li>
                    </ul>
                </div>

                <!-- Información de seguridad -->
                <div class="security-info">
                    <h6 class="fw-bold mb-2">
                        <i class="fas fa-shield-alt me-2"></i>Importante:
                    </h6>
                    <ul>
                        <li>No podrá utilizar contraseñas anteriores (se guardan las últimas 5)</li>
                        <li>La contraseña debe ser diferente a su nombre de usuario</li>
                        <li>Se recomienda cambiar su contraseña periódicamente</li>
                        <li>No comparta su contraseña con nadie</li>
                    </ul>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <?= Html::submitButton('<i class="fas fa-save me-2"></i> Establecer Contraseña', [
                        'class' => 'btn btn-primary btn-password-submit',
                        'name' => 'change-password-button',
                        'id' => 'change-password-button',
                        'disabled' => true
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
            
            <div class="card-footer bg-transparent border-top">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small>
                            <i class="fas fa-user-check me-1"></i>
                            Verificado: <?= date('d/m/Y H:i:s') ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small>
                            <i class="fas fa-lock me-1"></i>
                            Seguridad de contraseña
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
    let passwordValid = false;
    let passwordMatch = false;
    let requirementsMet = {
        length: false,
        uppercase: false,
        lowercase: false,
        number: false,
        special: false
    };
    
    // Función para evaluar fortaleza de contraseña
    function checkPasswordStrength(password) {
        let strength = 0;
        
        // Reset requirements
        requirementsMet = {
            length: false,
            uppercase: false,
            lowercase: false,
            number: false,
            special: false
        };
        
        // Longitud mínima (8 caracteres)
        if (password.length >= 8) {
            strength += 1;
            requirementsMet.length = true;
            updateRequirementUI('length', true);
        } else {
            updateRequirementUI('length', false);
        }
        
        // Mayúsculas
        if (/[A-Z]/.test(password)) {
            strength += 1;
            requirementsMet.uppercase = true;
            updateRequirementUI('uppercase', true);
        } else {
            updateRequirementUI('uppercase', false);
        }
        
        // Minúsculas
        if (/[a-z]/.test(password)) {
            strength += 1;
            requirementsMet.lowercase = true;
            updateRequirementUI('lowercase', true);
        } else {
            updateRequirementUI('lowercase', false);
        }
        
        // Números
        if (/[0-9]/.test(password)) {
            strength += 1;
            requirementsMet.number = true;
            updateRequirementUI('number', true);
        } else {
            updateRequirementUI('number', false);
        }
        
        // Caracteres especiales
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
            strength += 1;
            requirementsMet.special = true;
            updateRequirementUI('special', true);
        } else {
            updateRequirementUI('special', false);
        }
        
        // Actualizar barra de fortaleza
        let percentage = (strength / 5) * 100;
        $('#strength-bar').css('width', percentage + '%');
        
        // Color y texto según fortaleza
        let strengthText = '';
        let strengthColor = '';
        
        if (strength <= 1) {
            strengthText = 'Muy débil';
            strengthColor = '#dc3545';
        } else if (strength <= 2) {
            strengthText = 'Débil';
            strengthColor = '#fd7e14';
        } else if (strength <= 3) {
            strengthText = 'Regular';
            strengthColor = '#ffc107';
        } else if (strength <= 4) {
            strengthText = 'Fuerte';
            strengthColor = '#28a745';
        } else {
            strengthText = 'Muy fuerte';
            strengthColor = '#20c997';
        }
        
        $('#strength-bar').css('background', strengthColor);
        $('#strength-text').text(strengthText).css('color', strengthColor);
        
        // Verificar si cumple todos los requisitos
        passwordValid = Object.values(requirementsMet).every(val => val === true);
        
        updateSubmitButton();
        
        return strength;
    }
    
    // Actualizar UI de requisitos
    function updateRequirementUI(type, met) {
        let icon = $('#req-' + type + '-icon i');
        let text = $('#req-' + type + '-text');
        
        if (met) {
            icon.removeClass('fa-times requirement-unmet').addClass('fa-check requirement-met');
            text.css('color', '#28a745');
        } else {
            icon.removeClass('fa-check requirement-met').addClass('fa-times requirement-unmet');
            text.css('color', '#dc3545');
        }
    }
    
    // Verificar coincidencia de contraseñas
    function checkPasswordMatch() {
        let password = $('#new-password').val();
        let confirm = $('#confirm-password').val();
        
        if (!password || !confirm) {
            $('#password-match').text('').removeClass('match-valid match-invalid');
            passwordMatch = false;
            return;
        }
        
        if (password === confirm) {
            $('#password-match').html('<i class="fas fa-check me-1"></i> Las contraseñas coinciden');
            $('#password-match').addClass('match-valid').removeClass('match-invalid');
            $('#confirm-password').removeClass('is-invalid').addClass('is-valid');
            passwordMatch = true;
        } else {
            $('#password-match').html('<i class="fas fa-times me-1"></i> Las contraseñas no coinciden');
            $('#password-match').addClass('match-invalid').removeClass('match-valid');
            $('#confirm-password').removeClass('is-valid').addClass('is-invalid');
            passwordMatch = false;
        }
        
        updateSubmitButton();
    }
    
    // Actualizar estado del botón de envío
    function updateSubmitButton() {
        let canSubmit = passwordValid && passwordMatch;
        $('#change-password-button').prop('disabled', !canSubmit);
        
        if (canSubmit) {
            $('#change-password-button').removeClass('btn-secondary').addClass('btn-primary');
        } else {
            $('#change-password-button').removeClass('btn-primary').addClass('btn-secondary');
        }
    }
    
    // Event listeners
    $('#new-password').on('input', function() {
        checkPasswordStrength($(this).val());
        checkPasswordMatch();
    });
    
    $('#confirm-password').on('input', checkPasswordMatch);
    
    // Alternar visibilidad de contraseña
    $('#toggle-new-password').on('click', function() {
        let input = $('#new-password');
        let type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });
    
    $('#toggle-confirm-password').on('click', function() {
        let input = $('#confirm-password');
        let type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });
    
    // Validación antes del envío
    $('#change-password-form').on('submit', function(e) {
        if (!passwordValid || !passwordMatch) {
            e.preventDefault();
            
            let errorMessage = 'Por favor, corrija los siguientes errores:';
            let errors = [];
            
            if (!passwordValid) {
                errors.push('La contraseña no cumple todos los requisitos de seguridad');
            }
            
            if (!passwordMatch) {
                errors.push('Las contraseñas no coinciden');
            }
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en la contraseña',
                    html: errorMessage + '<br><ul class="text-start"><li>' + errors.join('</li><li>') + '</li></ul>',
                    confirmButtonColor: '#667eea'
                });
            } else {
                alert(errorMessage + '\\n- ' + errors.join('\\n- '));
            }
            return false;
        }
        
        // Mostrar loading
        let submitBtn = $(this).find('button[type="submit"]');
        let originalHtml = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Estableciendo...');
        submitBtn.prop('disabled', true);
        
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
    
    // Enfocar el primer campo
    $('#new-password').focus();
});
JS;

$this->registerJs($js);
?>