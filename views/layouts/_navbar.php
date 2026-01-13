<?php
/**
 * @var yii\web\View $this
 * @var int $idEscuela
 * @var string $nombreEscuela
 * @var string $navbarVariant - 'default' | 'escuela'
 */

use yii\bootstrap5\Html;
use yii\helpers\Url;
use app\components\MenuWidget;

// ✅ DETECCIÓN DE RUTA ACTUAL
$currentRoute = Yii::$app->controller->route;
$isIndexRoute = $currentRoute === 'site/index';
$isLoginRoute = $currentRoute === 'site/login';
$isSignupRoute = $currentRoute === 'site/signup';

$showLoginButton = !Yii::$app->user->isGuest ? false : !$isLoginRoute;
$showSignupButton = !Yii::$app->user->isGuest ? false : (!$isSignupRoute && !$isLoginRoute);

// ✅ DETECCIÓN DE MÓDULO ACTUAL PARA SIDEBAR
$currentModule = Yii::$app->controller->module->id ?? '';
$modulesConSidebar = ['atletas', 'tienda', 'escuela_club', 'aportes', 'reportes'];
$tieneSidebar = in_array($currentModule, $modulesConSidebar);
?>

<!-- ================================================== -->
<!-- NAVBAR PRINCIPAL - 2 NIVELES                       -->
<!-- ================================================== -->

<!-- ✅ NAVBAR DESKTOP (fijo en top) -->
<nav class="navbar-contextual navbar navbar-expand-lg navbar-dark fixed-top" id="main-navbar">
    <div class="container-fluid p-0 m-0 w-100 vw-100">
        
        <!-- ✅ LOGO - IZQUIERDA -->
        <div class="navbar-brand-section">
            <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>" 
               title="Inicio - Sistema GED"
               onclick="return !<?= $isIndexRoute ? 'true' : 'false' ?>;">
                <?= Html::img('@web/img/logos/logoGed.png', [
                    'class' => 'navbar-logo',
                    'alt' => 'GED Logo - Sistema de Gestión Deportiva',
                    'loading' => 'eager',
                    'onerror' => "this.style.display='none'; this.nextElementSibling.style.display='block';"
                ]) ?>
                <div class="logo-fallback">
                    <strong>GED</strong><br>
                    <small>Sistema Deportivo</small>
                </div>
            </a>
        </div>

        <!-- ✅ TOGGLER PARA MÓVIL - OFFCANVAS BOOTSTRAP -->
        <button class="navbar-toggler d-lg-none ms-auto" 
                type="button" 
                data-bs-toggle="offcanvas"
                data-bs-target="#mobileMenuOffcanvas"
                aria-controls="mobileMenuOffcanvas"
                aria-expanded="false"
                aria-label="Mostrar menú de navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- ✅ CONTENIDO DESKTOP - COLAPSE BOOTSTRAP -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="navbar-container">
                
                <!-- ✅ MENÚ PRINCIPAL - SOLO DESKTOP (50%) -->
                <div class="navbar-menu-section d-none d-lg-flex">
                    <div class="section-container w-100">
                        <?= MenuWidget::widget([
                            'options' => [
                                'class' => 'navbar-nav main-navigation w-100',
                                'mobileMode' => false,
                            ]
                        ]) ?>
                    </div>
                </div>
                
                <!-- ✅ REDES SOCIALES - SOLO DESKTOP (15%) -->
                <div class="navbar-social-section d-none d-lg-flex">
                    <div class="section-container">
                        <div class="social-icons-vertical" aria-label="Redes sociales">
                            <a href="#" class="social-icon-circle" title="Facebook" aria-label="Facebook">
                                <i class="bi bi-facebook" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Twitter" aria-label="Twitter">
                                <i class="bi bi-twitter" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Instagram" aria-label="Instagram">
                                <i class="bi bi-instagram" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="YouTube" aria-label="YouTube">
                                <i class="bi bi-youtube" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- ✅ CONTROL DE USUARIO Y ESCUELA - SOLO DESKTOP (20%) -->
                <div class="navbar-control-section d-none d-lg-flex">
                    <div class="section-container">
                        
                        <!-- INFORMACIÓN DE ESCUELA -->
                        <div class="school-info">
                            <?php if ($idEscuela && $idEscuela > 0): ?>
                                <div class="escuela-activa-indicator">
                                    <small class="text-white d-block">
                                        <i class="bi bi-building me-1" aria-hidden="true"></i> 
                                        <strong id="current-school"><?= Html::encode(mb_strimwidth($nombreEscuela, 0, 25, '...')) ?></strong>
                                    </small>
                                    <small class="text-light opacity-75 d-block" id="current-school-id">
                                        ID: <?= $idEscuela ?>
                                    </small>
                                </div>
                                
                                <!-- SELECTOR DE ESCUELA -->
                                <div class="nav-item dropdown">
                                    <a class="nav-link text-white dropdown-toggle p-1" href="#" 
                                       id="navbarEscuelaDropdown" role="button" data-bs-toggle="dropdown" 
                                       aria-expanded="false" title="Cambiar Escuela" aria-label="Selector de escuela">
                                        <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Cambiar
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end escuela-selector-dropdown" 
                                         aria-labelledby="navbarEscuelaDropdown">
                                        <div class="px-3 py-2">
                                            <h6 class="dropdown-header">Seleccionar Escuela</h6>
                                            <select id="navbar-escuela-select" class="form-select form-select-sm" 
                                                    aria-label="Seleccionar escuela">
                                                <option value="">Buscar escuela...</option>
                                            </select>
                                            <div class="mt-2 text-center">
                                                <small class="text-muted">Escuela actual: <?= Html::encode(mb_strimwidth($nombreEscuela, 0, 30, '...')) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <!-- SIN ESCUELA SELECCIONADA -->
                                <div class="alert alert-warning py-1 mb-2" role="alert">
                                    <small>
                                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                        <strong>Sin escuela seleccionada</strong>
                                    </small>
                                </div>
                                
                                <!-- BOTÓN PARA SELECCIONAR ESCUELA -->
                                <a href="<?= Url::to(['/ged/default/index']) ?>" 
                                   class="btn btn-sm btn-outline-light w-100"
                                   title="Seleccionar escuela"
                                   aria-label="Seleccionar escuela">
                                    <i class="bi bi-building me-1" aria-hidden="true"></i>
                                    <span>Seleccionar</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- CONTROL DE SESIÓN -->
                        <div class="session-controls mt-2">
                            <?php if (Yii::$app->user->isGuest): ?>
                                <!-- USUARIO NO AUTENTICADO -->
                                <div class="d-flex flex-column gap-1">
                                    <?php if ($showLoginButton): ?>
                                    <a class="btn btn-sm btn-outline-light" 
                                       href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>" 
                                       title="Iniciar sesión"
                                       aria-label="Iniciar sesión">
                                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                                        <span class="d-none d-lg-inline">Login</span>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($showSignupButton): ?>
                                    <a class="btn btn-sm btn-outline-light" 
                                       href="<?= Yii::$app->urlManager->createUrl(['/site/signup']) ?>" 
                                       title="Registrarse"
                                       aria-label="Crear cuenta">
                                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
                                        <span class="d-none d-lg-inline">Registro</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- USUARIO AUTENTICADO -->
                                <div class="user-info mb-1 text-end">
                                    <small class="text-white d-block">
                                        <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                                        <?= Html::encode(mb_strimwidth(Yii::$app->user->identity->username ?? 'Usuario', 0, 20, '...')) ?>
                                    </small>
                                </div>
                                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline w-100']) ?>
                                    <?= Html::submitButton(
                                        '<i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-lg-inline">Cerrar</span>',
                                        [
                                            'class' => 'btn btn-sm btn-outline-light w-100',
                                            'title' => 'Cerrar sesión',
                                            'aria-label' => 'Cerrar sesión'
                                        ]
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

<!-- ✅ OFFCANVAS PARA MÓVIL - BOOTSTRAP NATIVO -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileMenuOffcanvas">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title">
            <i class="fas fa-bars me-2"></i>Menú Principal
        </h5>
        <button type="button" class="btn-close btn-close-white" 
                data-bs-dismiss="offcanvas" 
                aria-label="Cerrar menú"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?= MenuWidget::widget([
            'options' => [
                'class' => 'navbar-nav flex-column w-100',
                'mobileMode' => true,
            ]
        ]) ?>
        
        <!-- ✅ CONTROLES PARA MÓVIL EN EL OFFCANVAS -->
        <div class="border-top mt-3 p-3">
            <?php if ($idEscuela && $idEscuela > 0): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Escuela actual:</small>
                    <strong class="d-block"><?= Html::encode($nombreEscuela) ?></strong>
                    <small class="text-muted">ID: <?= $idEscuela ?></small>
                </div>
            <?php endif; ?>
            
            <?php if (Yii::$app->user->isGuest): ?>
                <div class="d-grid gap-2">
                    <a class="btn btn-primary" href="<?= Url::to(['/site/login']) ?>">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                    </a>
                    <a class="btn btn-outline-primary" href="<?= Url::to(['/site/signup']) ?>">
                        <i class="bi bi-person-plus me-2"></i>Registrarse
                    </a>
                </div>
            <?php else: ?>
                <div class="d-grid">
                    <small class="text-muted d-block mb-2">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= Html::encode(Yii::$app->user->identity->username ?? 'Usuario') ?>
                    </small>
                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) ?>
                        <?= Html::submitButton(
                            '<i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión',
                            ['class' => 'btn btn-outline-danger w-100']
                        ) ?>
                    <?= Html::endForm() ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ✅ SIDEBAR DE MÓDULO (para escritorio) -->
<?php if ($tieneSidebar && !Yii::$app->request->isAjax): ?>
<div class="sidebar-module-wrapper d-none d-md-block">
    <?= \app\components\ModuleMenuWidget::widget([
        'moduleName' => $currentModule,
    ]) ?>
</div>

<!-- ✅ NAVEGACIÓN DE MÓDULO PARA MÓVIL -->
<div class="d-md-none mobile-module-navbar">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand text-truncate">
                <i class="bi bi-menu-button-wide me-2"></i>
                <?= \app\components\ModuleMenuWidget::getModuleTitle($currentModule) ?>
            </span>
            <button class="navbar-toggler" type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#moduleNavMobile"
                    aria-expanded="false"
                    aria-label="Menú del módulo">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="moduleNavMobile">
                <?= \app\components\ModuleMenuWidget::widget([
                    'moduleName' => $currentModule,
                ]) ?>
            </div>
        </div>
    </nav>
</div>
<?php endif; ?>