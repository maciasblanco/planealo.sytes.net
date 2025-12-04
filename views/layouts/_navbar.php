<?php
/**
 * @var yii\web\View $this
 * @var int $idEscuela
 * @var string $nombreEscuela
 * @var string $navbarVariant - 'default' | 'escuela'
 */

use yii\bootstrap5\Html;

// ✅ CONFIGURACIÓN ACTUALIZADA SEGÚN REUNIÓN
$logoWidth = '15%';
$menuWidth = '50%';
$socialWidth = '15%';
$controlWidth = '20%';

// ✅ NUEVO: Evitar bucle verificando ruta actual
$currentRoute = Yii::$app->controller->route;
$isIndexRoute = $currentRoute === 'site/index';
$isLoginRoute = $currentRoute === 'site/login';
$isSignupRoute = $currentRoute === 'site/signup';

// ✅ VERIFICACIÓN PARA PREVENIR BUCLE
$showLoginButton = !Yii::$app->user->isGuest ? false : !$isLoginRoute;
$showSignupButton = !Yii::$app->user->isGuest ? false : (!$isSignupRoute && !$isLoginRoute);

// Determinar clases CSS según el layout
$navbarClasses = 'navbar navbar-expand-lg navbar-dark navbar-contextual fixed-top';
$containerClasses = 'container-fluid';

// ✅ CSS CORREGIDO Y OPTIMIZADO
$this->registerCss("
.navbar-contextual {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 1030 !important;
    width: 100% !important;
}

.navbar-brand-section {
    width: {$logoWidth} !important;
    height: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.navbar-brand {
    width: 100% !important;
    height: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
    padding: 0 !important;
}

.navbar-logo {
    max-height: 85% !important;
    max-width: 90% !important;
    width: auto !important;
    height: auto !important;
    display: block !important;
    object-fit: contain !important;
}

.navbar-menu-section {
    width: {$menuWidth} !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.navbar-social-section {
    width: {$socialWidth} !important;
}

.navbar-control-section {
    width: {$controlWidth} !important;
}

.navbar-container {
    display: flex !important;
    width: 100% !important;
    align-items: center !important;
}

.section-container {
    width: 100% !important;
    display: flex !important;
    justify-content: center !important;
}

.main-navigation {
    width: 100% !important;
    display: flex !important;
    justify-content: center !important;
    flex-wrap: wrap !important;
}

/* ✅ ESTILOS PARA MÓVIL */
@media (max-width: 991.98px) {
    .navbar-menu-section {
        width: 100% !important;
        order: 3 !important;
        margin-top: 15px !important;
    }
    
    .navbar-social-section {
        width: 100% !important;
        order: 2 !important;
        justify-content: center !important;
        margin: 10px 0 !important;
    }
    
    .navbar-control-section {
        width: 100% !important;
        order: 1 !important;
    }
    
    .navbar-container {
        flex-direction: column !important;
    }
}

/* ✅ ESTILOS PARA REDES SOCIALES */
.social-icons-vertical {
    display: flex !important;
    justify-content: center !important;
    gap: 10px !important;
}

.social-icon-circle {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 35px !important;
    height: 35px !important;
    border-radius: 50% !important;
    background: rgba(255, 255, 255, 0.1) !important;
    color: white !important;
    text-decoration: none !important;
    transition: all 0.3s ease !important;
}

.social-icon-circle:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-2px) !important;
}
");

// ✅ DETECCIÓN DE MÓVIL PARA EL MENÚ
$mobileDetect = Yii::$app->has('mobileDetect') ? Yii::$app->mobileDetect->isMobile() : false;

?>

<!-- ================================================== -->
<!-- NAVBAR UNIFICADO - CON PREVENCIÓN DE BUCLE -->
<!-- ================================================== -->
<nav class="<?= $navbarClasses ?>" aria-label="Navegación principal">
    <div class="<?= $containerClasses ?>">
        <!-- ✅ LOGO - 15% -->
        <div class="navbar-brand-section">
            <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>" 
               title="Inicio - Sistema GED"
               onclick="return !<?= $isIndexRoute ? 'true' : 'false' ?>;">
                <?= Html::img('@web/img/logos/logoGed.png', [
                    'class' => 'navbar-logo',
                    'alt' => 'GED Logo - Sistema de Gestión Deportiva',
                    'loading' => 'eager',
                    'onerror' => "this.style.display='none'; this.nextElementSibling.style.display='block';",
                    'style' => 'display: block;'
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

        <!-- Contenido colapsable -->
        <div class="collapse navbar-collapse" id="navbarGedCollapse">
            <div class="navbar-container">
                
                <!-- ✅ SECCIÓN 1: Menú de Navegación Principal - 50% -->
                <div class="navbar-menu-section">
                    <div class="section-container">
                        <?= \app\components\MenuWidget::widget([
                            'options' => [
                                'class' => 'navbar-nav main-navigation',
                                'mobileMode' => $mobileDetect
                            ]
                        ]) ?>
                    </div>
                </div>
                
                <!-- ✅ SECCIÓN 2: Redes Sociales - 15% -->
                <div class="navbar-social-section">
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
                        <div class="w-100 text-center">
                            
                            <!-- Información de Escuela -->
                            <div class="school-info mb-3">
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
                                                <strong>Sin escuela seleccionada</strong>
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
                            
                            <!-- ✅ Control de Sesión CON PREVENCIÓN DE BUCLE -->
                            <div class="session-controls">
                                <?php if (Yii::$app->user->isGuest): ?>
                                    <!-- Usuario no autenticado -->
                                    <?php if ($showLoginButton): ?>
                                    <a class="btn btn-sm btn-outline-light w-100 mb-1" 
                                       href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>" 
                                       title="Iniciar sesión"
                                       aria-label="Iniciar sesión">
                                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Login
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($showSignupButton): ?>
                                    <a class="btn btn-sm btn-outline-light w-100" 
                                       href="<?= Yii::$app->urlManager->createUrl(['/site/signup']) ?>" 
                                       title="Registrarse"
                                       aria-label="Crear cuenta">
                                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Registro
                                    </a>
                                    <?php endif; ?>
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
                                            '<i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión',
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
    </div>
</nav>