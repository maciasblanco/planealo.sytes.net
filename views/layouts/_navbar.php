<?php
use yii\bootstrap5\Html;
use yii\helpers\Url;

$idEscuela = Yii::$app->session->get('idEscuela', 0);
$nombreEscuela = Yii::$app->session->get('nombreEscuela', 'Selecciona una escuela');
?>

<nav class="navbar navbar-contextual navbar-expand-lg fixed-top" id="main-navbar">
    <div class="navbar-container d-flex align-items-stretch w-100">
        
        <div class="navbar-brand-section d-flex align-items-center">
            <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>">
                <?= Html::img('@web/img/logos/logoGed.png', [
                    'class' => 'navbar-logo',
                    'alt' => 'GED Logo',
                    'loading' => 'eager',
                    'onerror' => "this.style.display='none'; this.parentNode.querySelector('.logo-fallback').classList.remove('d-none');"
                ]) ?>
                <div class="logo-fallback d-none" aria-hidden="true">
                    <strong>GED</strong><br>
                    <small>Sistema Deportivo</small>
                </div>
            </a>
        </div>
        
        <button class="navbar-toggler d-lg-none ms-auto" type="button" 
                data-bs-toggle="offcanvas"
                data-bs-target="#mobileMenuOffcanvas">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse show d-none d-lg-flex" id="navbarContent">
            <div class="navbar-sections-container d-flex align-items-stretch flex-grow-1">
                
                <div class="navbar-menu-section d-flex align-items-center">
                    <!-- ✅ DESKTOP: Menú normal con dropdowns -->
                    <?= \app\components\MenuWidget::widget([
                        'options' => ['class' => 'navbar-nav main-navigation']
                    ]) ?>
                </div>
                
                <div class="navbar-social-section d-flex align-items-center">
                    <div class="social-icons-vertical">
                        <a href="#" class="social-icon-circle" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-icon-circle" title="Twitter"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-icon-circle" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-icon-circle" title="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                
                <div class="navbar-control-section d-flex align-items-center">
                    <div class="control-content d-flex flex-column h-100 w-100">
                        <div class="school-info flex-grow-1">
                            <?php if ($idEscuela): ?>
                                <div class="escuela-activa-indicator">
                                    <small class="text-white d-block">
                                        <i class="bi bi-building me-1"></i> 
                                        <strong><?= Html::encode(mb_strimwidth($nombreEscuela, 0, 25, '...')) ?></strong>
                                    </small>
                                    <small class="text-light opacity-75 d-block">ID: <?= $idEscuela ?></small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning py-1 mb-2" role="alert">
                                    <small><i class="bi bi-exclamation-triangle me-1"></i><strong>Sin escuela seleccionada</strong></small>
                                </div>
                                <a href="<?= Url::to(['/ged/default/index']) ?>" class="btn btn-sm btn-outline-light w-100 mt-1">
                                    <i class="bi bi-building me-1"></i><span>Seleccionar</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="session-controls flex-grow-1 d-flex flex-column justify-content-end">
                            <?php if (Yii::$app->user->isGuest): ?>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a class="btn btn-sm btn-outline-light flex-grow-1" href="<?= Url::to(['/site/login']) ?>">
                                        <i class="bi bi-box-arrow-in-right me-1"></i><span class="d-none d-lg-inline">Login</span>
                                    </a>
                                    <a class="btn btn-sm btn-outline-light flex-grow-1" href="<?= Url::to(['/site/signup']) ?>">
                                        <i class="bi bi-person-plus me-1"></i><span class="d-none d-lg-inline">Registro</span>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="user-info text-end mb-1">
                                    <small class="text-white d-block">
                                        <i class="bi bi-person-circle me-1"></i>
                                        <?= Html::encode(mb_strimwidth(Yii::$app->user->identity->username ?? 'Usuario', 0, 20, '...')) ?>
                                    </small>
                                </div>
                                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline w-100']) ?>
                                    <?= Html::submitButton(
                                        '<i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-lg-inline">Cerrar</span>',
                                        ['class' => 'btn btn-sm btn-outline-light w-100']
                                    ) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- ✅ OFFCANVAS MÓVIL CON ESTRUCTURA CORRECTA -->
<div class="offcanvas offcanvas-start ged-mobile-sidebar" tabindex="-1" id="mobileMenuOffcanvas">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title"><i class="bi bi-menu-app me-2"></i>Menú GED</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="mobile-menu-container">
            <!-- ✅ CORRECCIÓN CRÍTICA: mobileMode=true como parámetro separado -->
            <?= \app\components\MenuWidget::widget([
                'options' => [],  // Sin clases específicas para mantener flexibilidad
                'mobileMode' => true  // ← PARÁMETRO CORRECTO, FUERA DE 'options'
            ]) ?>
            
            <div class="mobile-session-controls p-3 border-top">
                <?php if (Yii::$app->user->isGuest): ?>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" href="<?= Url::to(['/site/login']) ?>">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
                        </a>
                        <a class="btn btn-primary" href="<?= Url::to(['/site/signup']) ?>">
                            <i class="bi bi-person-plus me-2"></i>Crear cuenta
                        </a>
                    </div>
                <?php else: ?>
                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-grid']) ?>
                        <?= Html::submitButton(
                            '<i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión',
                            ['class' => 'btn btn-outline-danger']
                        ) ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>