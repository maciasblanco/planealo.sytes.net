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

<!-- ✅ OFFCANVAS MÓVIL CON SECCIÓN DE CONTROL COMPLETA -->
<div class="offcanvas offcanvas-start ged-mobile-sidebar" tabindex="-1" id="mobileMenuOffcanvas">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title"><i class="bi bi-menu-app me-2"></i>Menú GED</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="mobile-menu-container">
            
            <!-- ✅ SECCIÓN DE CONTROL MÓVIL COMPLETA -->
            <div class="mobile-control-section p-3 border-bottom bg-light-blue">
                
                <!-- 1. INFORMACIÓN DE ESCUELA -->
                <div class="school-info-mobile mb-3">
                    <?php if ($idEscuela): ?>
                        <div class="escuela-activa-indicator-mobile bg-primary text-white p-3 rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-building fs-5 me-2"></i>
                                <div class="flex-grow-1">
                                    <strong class="d-block"><?= Html::encode(mb_strimwidth($nombreEscuela, 0, 30, '...')) ?></strong>
                                    <small class="opacity-75">ID: <?= $idEscuela ?></small>
                                </div>
                                <span class="badge bg-white text-primary">Activa</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 mb-3" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Sin escuela seleccionada</strong>
                            </div>
                        </div>
                        <a href="<?= Url::to(['/ged/default/index']) ?>" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-building me-2"></i>Seleccionar escuela
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- 2. INFORMACIÓN DE USUARIO (si está autenticado) -->
                <?php if (!Yii::$app->user->isGuest): ?>
                    <div class="user-info-mobile bg-white p-3 rounded shadow-sm mb-3">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar-mobile bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-person fs-5"></i>
                            </div>
                            <div class="ms-3">
                                <strong class="d-block text-dark"><?= Html::encode(mb_strimwidth(Yii::$app->user->identity->username ?? 'Usuario', 0, 20, '...')) ?></strong>
                                <small class="text-muted">Sesión activa</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 3. CONTROLES RÁPIDOS -->
                <div class="mobile-quick-controls d-grid gap-2">
                    <?php if (Yii::$app->user->isGuest): ?>
                        <a class="btn btn-outline-primary" href="<?= Url::to(['/site/login']) ?>">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
                        </a>
                        <a class="btn btn-primary" href="<?= Url::to(['/site/signup']) ?>">
                            <i class="bi bi-person-plus me-2"></i>Crear cuenta
                        </a>
                    <?php else: ?>
                        <div class="d-grid gap-2">
                            <a href="<?= Url::to(['/user/profile']) ?>" class="btn btn-outline-primary">
                                <i class="bi bi-person-circle me-2"></i>Mi perfil
                            </a>
                            <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-grid']) ?>
                                <?= Html::submitButton(
                                    '<i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión',
                                    ['class' => 'btn btn-outline-danger']
                                ) ?>
                            <?= Html::endForm() ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
            
            <!-- ✅ MENÚ MÓVIL -->
            <?= \app\components\MenuWidget::widget([
                'options' => [],
                'mobileMode' => true
            ]) ?>
            
            <!-- ✅ FOOTER DEL OFFCANVAS -->
            <div class="mobile-session-controls p-3 border-top">
                <small class="text-muted d-block text-center mb-2">
                    <i class="bi bi-info-circle me-1"></i>GED v4.7
                </small>
                <?php if (Yii::$app->user->isGuest): ?>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/site/login']) ?>">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
                        </a>
                    </div>
                <?php else: ?>
                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-grid']) ?>
                        <?= Html::submitButton(
                            '<i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión',
                            ['class' => 'btn btn-outline-danger btn-sm']
                        ) ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>