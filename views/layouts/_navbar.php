<?php
/**
 * @var yii\web\View $this
 * @var int $idEscuela
 * @var string $nombreEscuela
 * @var string $navbarVariant - 'default' | 'escuela'
 */

use yii\bootstrap5\Html;

// ✅ REGISTRAR ARCHIVOS CSS Y JS PARA ANCHO COMPLETO
$this->registerCssFile('@web/css/navbar.css', ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]);
$this->registerCssFile('@web/css/fullwidth-fix.css', ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]);
$this->registerJsFile('@web/js/fullwidth-fix.js', ['depends' => [\yii\bootstrap5\BootstrapPluginAsset::class]]);

// ✅ CONFIGURACIÓN ACTUALIZADA
$logoWidth = '15%';
$menuWidth = '50%';
$socialWidth = '15%';
$controlWidth = '20%';

// ✅ PREVENCIÓN DE BUCLE
$currentRoute = Yii::$app->controller->route;
$isIndexRoute = $currentRoute === 'site/index';
$isLoginRoute = $currentRoute === 'site/login';
$isSignupRoute = $currentRoute === 'site/signup';

$showLoginButton = !Yii::$app->user->isGuest ? false : !$isLoginRoute;
$showSignupButton = !Yii::$app->user->isGuest ? false : (!$isSignupRoute && !$isLoginRoute);

// ✅ CLASES PARA ANCHO COMPLETO
$navbarClasses = 'navbar navbar-expand-lg navbar-dark navbar-contextual fixed-top w-100 vw-100';
$containerClasses = 'container-fluid p-0 m-0 w-100 vw-100';

// ✅ DETECCIÓN DE MÓVIL
$mobileDetect = Yii::$app->has('mobileDetect') ? Yii::$app->mobileDetect->isMobile() : false;

?>

<!-- ================================================== -->
<!-- NAVBAR UNIFICADO - ANCHO COMPLETO 100% -->
<!-- ================================================== -->
<nav class="<?= $navbarClasses ?>" aria-label="Navegación principal" style="width: 100vw !important; max-width: 100vw !important;">
    <div class="<?= $containerClasses ?>">
        <!-- ✅ LOGO - 15% -->
        <div class="navbar-brand-section" style="width: 15%; min-width: 200px;">
            <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>" 
               title="Inicio - Sistema GED"
               onclick="return !<?= $isIndexRoute ? 'true' : 'false' ?>;">
                <?= Html::img('@web/img/logos/logoGed.png', [
                    'class' => 'navbar-logo',
                    'alt' => 'GED Logo - Sistema de Gestión Deportiva',
                    'loading' => 'eager',
                    'onerror' => "this.style.display='none'; this.nextElementSibling.style.display='block';"
                ]) ?>
                <div style="display: none; background: #6c3483; color: white; padding: 10px; border-radius: 5px; text-align: center;">
                    <strong>GED</strong><br>
                    <small>Sistema Deportivo</small>
                </div>
            </a>
        </div>

        <!-- Toggler para móviles -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarGedCollapse" 
                aria-controls="navbarGedCollapse" aria-expanded="false" 
                aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- ✅ CONTENIDO COLAPSABLE - TODO EN UN SOLO MENÚ -->
        <div class="collapse navbar-collapse w-100 vw-100" id="navbarGedCollapse">
            <div class="navbar-container w-100 vw-100">
                
                <!-- ✅ SECCIÓN 1: Menú de Navegación Principal - 50% -->
                <div class="navbar-menu-section w-100">
                    <div class="section-container w-100">
                        <?= \app\components\MenuWidget::widget([
                            'options' => [
                                'class' => 'navbar-nav main-navigation w-100',
                                'mobileMode' => $mobileDetect
                            ]
                        ]) ?>
                    </div>
                </div>
                
                <!-- ✅ SECCIÓN 2: Redes Sociales - 15% (OCULTO EN MÓVIL) -->
                <div class="navbar-social-section d-none d-lg-block">
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
                
                <!-- ✅ SECCIÓN 3: Control de Usuario y Escuela - 20% -->
                <div class="navbar-control-section">
                    <div class="section-container">
                        <!-- Información de Escuela (OCULTO EN MÓVIL) -->
                        <div class="school-info mb-2 d-none d-lg-block">
                            <div class="school-search-container mb-2">
                                <?php if ($idEscuela && $idEscuela > 0): ?>
                                    <div class="escuela-activa-indicator">
                                        <small class="text-white d-block">
                                            <i class="bi bi-building" aria-hidden="true"></i> 
                                            <strong id="current-school"><?= Html::encode($nombreEscuela) ?></strong>
                                        </small>
                                        <small class="text-light opacity-75 d-block" id="current-school-id">
                                            ID: <?= $idEscuela ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning py-1 mb-2" role="alert">
                                        <small>
                                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                            <strong>Sin escuela</strong>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Buscador/Selector de Escuelas -->
                            <?php if ($navbarVariant === 'default'): ?>
                                <div class="school-search-container mb-2">
                                    <div class="input-group input-group-sm">
                                        <input type="text" 
                                            id="schoolSearch" 
                                            class="form-control" 
                                            placeholder="Buscar escuela..."
                                            aria-label="Buscar escuela"
                                            autocomplete="off">
                                        <button class="btn btn-outline-light" type="button" id="searchSchoolBtn" aria-label="Buscar">
                                            <i class="bi bi-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div id="schoolSearchResults" class="search-results-dropdown" aria-live="polite"></div>
                                </div>
                            <?php else: ?>
                                <div class="nav-item dropdown mb-2">
                                    <a class="nav-link text-white dropdown-toggle p-1" href="#" 
                                       id="navbarEscuelaDropdown" role="button" data-bs-toggle="dropdown" 
                                       aria-expanded="false" title="Cambiar Escuela" aria-label="Selector de escuela">
                                        <i class="bi bi-building me-1" aria-hidden="true"></i>Escuela
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end escuela-selector-dropdown" 
                                         aria-labelledby="navbarEscuelaDropdown">
                                        <div class="px-3 py-2">
                                            <h6 class="dropdown-header">Seleccionar Escuela</h6>
                                            <select id="navbar-escuela-select" class="form-select form-select-sm" 
                                                    aria-label="Seleccionar escuela">
                                                <option value="">Buscar escuela...</option>
                                            </select>
                                            <?php if ($idEscuela && $idEscuela > 0): ?>
                                                <div class="mt-2 text-center">
                                                    <small class="text-muted">Escuela actual: <?= Html::encode($nombreEscuela) ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>                        
                        
                        <!-- ✅ Control de Sesión - VISIBLE EN TODOS LOS DISPOSITIVOS -->
                        <div class="session-controls">
                            <?php if (Yii::$app->user->isGuest): ?>
                                <!-- Usuario no autenticado -->
                                <div class="d-flex flex-column flex-lg-row gap-2">
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
                                <!-- Usuario autenticado -->
                                <div class="user-info mb-2">
                                    <small class="text-white d-block">
                                        <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                                        <?= Html::encode(Yii::$app->user->identity->username ?? 'Usuario') ?>
                                    </small>
                                </div>
                                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline w-100']) ?>
                                    <?= Html::submitButton(
                                        '<i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-lg-inline">Cerrar Sesión</span>',
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

<!-- ✅ SCRIPT INLINE PARA FORZAR ANCHO COMPLETO INMEDIATAMENTE -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar correcciones inmediatamente
    setTimeout(() => {
        // Forzar ancho completo del navbar
        const navbar = document.querySelector('.navbar-contextual');
        if (navbar) {
            navbar.style.width = '100vw';
            navbar.style.maxWidth = '100vw';
            navbar.style.minWidth = '100vw';
            navbar.style.left = '0';
            navbar.style.right = '0';
        }
        
        // Forzar ancho completo del body
        document.body.style.width = '100vw';
        document.body.style.maxWidth = '100vw';
        document.body.style.overflowX = 'hidden';
        
        console.log('✅ Correcciones de ancho aplicadas inmediatamente');
    }, 50);
});
</script>