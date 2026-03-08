<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Iniciar Sesión';
$this->params['breadcrumbs'][] = $this->title;

// CSS personalizado para la vista de login
$this->registerCss(<<<CSS
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
    }
    .login-card {
        width: 100%;
        max-width: 1000px;
        border: none;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.1);
        background-color: #fff;
    }
    .login-left {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        min-height: 400px;
    }
    .login-left img {
        max-width: 80%;
        max-height: 200px;
        /* filter: brightness(0) invert(1);  MOD: eliminado para que la imagen sea visible */
    }
    .login-right {
        padding: 3rem;
    }
    .login-right h1 {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
    }
    .login-right .subtitle {
        color: #6c757d;
        margin-bottom: 2rem;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .btn-login {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 0.75rem;
        font-weight: 600;
        width: 100%;
        color: white;
        border-radius: 0.5rem;
        transition: all 0.3s;
    }
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(102, 126, 234, 0.4);
    }
    .login-footer {
        margin-top: 2rem;
        text-align: center;
        color: #6c757d;
    }
    .login-footer a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }
    .login-footer a:hover {
        text-decoration: underline;
    }
    @media (max-width: 768px) {
        .login-left {
            min-height: 200px;
            padding: 1rem;
        }
        .login-left img {
            max-height: 100px;
        }
        .login-right {
            padding: 2rem;
        }
    }
CSS);
?>

<div class="login-container">
    <div class="login-card row g-0">
        <!-- Columna izquierda: imagen del logo -->
        <div class="col-md-6 login-left">
            <img src="<?= Yii::getAlias('@web') ?>/img/logos/logoGed.png" 
                 alt="Logo GED"
                 class="img-fluid"
                 title="Sistema GED - Gestión Escuelas Deportivas">
        </div>

        <!-- Columna derecha: formulario de login -->
        <div class="col-md-6 login-right">
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="subtitle">Ingresa tus credenciales para acceder al sistema</p>

            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label fw-bold'],
                    'inputOptions' => ['class' => 'form-control form-control-lg'],
                    'errorOptions' => ['class' => 'invalid-feedback'],
                ],
            ]); ?>

                <?= $form->field($model, 'username')->textInput([
                    'autofocus' => true,
                    'placeholder' => 'Ingresa tu usuario'
                ]) ?>

                <?= $form->field($model, 'password', [
                    // MOD: se añade input-group para el icono de visibilidad
                    'template' => '{label}<div class="input-group">{input}<button class="btn btn-outline-secondary" type="button" id="togglePassword"><i class="bi bi-eye"></i></button></div>{error}',
                ])->passwordInput([
                    'placeholder' => 'Ingresa tu contraseña',
                    'id' => 'loginform-password'  // Aseguramos el ID para el JS
                ]) ?>

                <?= $form->field($model, 'rememberMe')->checkbox([
                    'template' => "<div class=\"form-check\">{input} {label}</div>\n{error}",
                    'class' => 'form-check-input',
                    'labelOptions' => ['class' => 'form-check-label'],
                ]) ?>

                <div class="form-group">
                    <?= Html::submitButton(
                        '<i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión',
                        ['class' => 'btn btn-login', 'name' => 'login-button']
                    ) ?>
                </div>

            <?php ActiveForm::end(); ?>

            <div class="login-footer">
                <p>¿No tienes cuenta? <a href="<?= Yii::$app->urlManager->createUrl(['/site/signup']) ?>">Regístrate</a></p>
                <p><a href="<?= Yii::$app->urlManager->createUrl(['/site/request-password-reset']) ?>">¿Olvidaste tu contraseña?</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// MOD: Script para mostrar/ocultar contraseña
$this->registerJs(<<<JS
    document.getElementById('togglePassword').addEventListener('click', function (e) {
        let input = document.getElementById('loginform-password');
        let icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
JS);
?>