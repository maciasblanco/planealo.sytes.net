<?php
/**
 * @var yii\web\View $this
 * @var int $idEscuela
 * @var string $nombreEscuela
 * @var string $navbarVariant - 'default' | 'escuela'
 */

use yii\bootstrap5\Html;
use yii\helpers\Url;

$currentRoute = Yii::$app->controller->route;
$isIndexRoute = $currentRoute === 'site/index';
$isLoginRoute = $currentRoute === 'site/login';
$isSignupRoute = $currentRoute === 'site/signup';

$showLoginButton = !Yii::$app->user->isGuest ? false : !$isLoginRoute;
$showSignupButton = !Yii::$app->user->isGuest ? false : (!$isSignupRoute && !$isLoginRoute);
?>

<!-- ================================================== -->
<!-- NAVBAR CORREGIDO - TODAS LAS SECCIONES EN UNA LÍNEA -->
<!-- Estructura: 100vw → 90% contenedor → 12%|40.5%|4.5%|18% -->
<!-- ================================================== -->
<nav class="navbar navbar-contextual navbar-expand-lg fixed-top" id="main-navbar" aria-label="Navegación principal">
    <!-- ✅ CONTENEDOR PRINCIPAL (90% del ancho total) - FLEX EN LÍNEA -->
    <div class="navbar-container d-flex align-items-stretch w-100">
        
        <!-- ✅ LOGO (12%) - EN LÍNEA CON EL RESTO -->
        <div class="navbar-brand-section d-flex align-items-center">
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
        
        <!-- ✅ TOGGLER PARA MÓVIL (SOLO EN MÓVIL) - MODIFICADO PARA OFFCANVAS -->
        <button class="navbar-toggler d-lg-none ms-auto" type="button" 
                data-bs-toggle="offcanvas"
                data-bs-target="#mobileMenuOffcanvas"
                aria-controls="mobileMenuOffcanvas"
                aria-label="Abrir menú móvil">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- ✅ CONTENIDO DEL NAVBAR (88%) - EN LÍNEA EN DESKTOP -->
        <div class="collapse navbar-collapse show d-none d-lg-flex" id="navbarContent">
            <div class="navbar-sections-container d-flex align-items-stretch flex-grow-1">
                
                <!-- MENÚ PRINCIPAL (40.5%) -->
                <div class="navbar-menu-section d-flex align-items-center">
                    <?= \app\components\MenuWidget::widget([
                        'options' => [
                            'class' => 'navbar-nav main-navigation',
                            'mobileMode' => false,
                            'rootOnly' => false
                        ]
                    ]) ?>
                </div>
                
                <!-- REDES SOCIALES (4.5%) -->
                <div class="navbar-social-section d-flex align-items-center">
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
                
                <!-- CONTROL DE USUARIO Y ESCUELA (18%) -->
                <div class="navbar-control-section d-flex align-items-center">
                    <div class="control-content d-flex flex-column h-100 w-100">
                        <!-- INFORMACIÓN DE ESCUELA -->
                        <div class="school-info flex-grow-1">
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
                                <div class="nav-item dropdown mt-1">
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
                                   class="btn btn-sm btn-outline-light w-100 mt-1"
                                   title="Seleccionar escuela"
                                   aria-label="Seleccionar escuela">
                                    <i class="bi bi-building me-1" aria-hidden="true"></i>
                                    <span>Seleccionar</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- CONTROL DE SESIÓN -->
                        <div class="session-controls flex-grow-1 d-flex flex-column justify-content-end">
                            <?php if (Yii::$app->user->isGuest): ?>
                                <!-- USUARIO NO AUTENTICADO -->
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php if ($showLoginButton): ?>
                                    <a class="btn btn-sm btn-outline-light flex-grow-1" 
                                       href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>" 
                                       title="Iniciar sesión"
                                       aria-label="Iniciar sesión">
                                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                                        <span class="d-none d-lg-inline">Login</span>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($showSignupButton): ?>
                                    <a class="btn btn-sm btn-outline-light flex-grow-1" 
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
                                <div class="user-info text-end mb-1">
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

<!-- ✅ SIDEBAR MÓVIL (OFFCANVAS) -->
<div class="offcanvas offcanvas-start ged-mobile-sidebar" tabindex="-1" id="mobileMenuOffcanvas" 
     aria-labelledby="mobileMenuOffcanvasLabel">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title" id="mobileMenuOffcanvasLabel">
            <i class="bi bi-menu-app me-2"></i>Menú GED
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" 
                aria-label="Cerrar menú"></button>
    </div>
    <div class="offcanvas-body p-0">
        <!-- Menú móvil -->
        <div class="mobile-menu-container">
            <?= \app\components\MenuWidget::widget([
                'options' => [
                    'class' => 'nav flex-column mobile-nav-menu',
                    'mobileMode' => true,
                    'itemClass' => 'nav-item',
                    'linkClass' => 'nav-link'
                ]
            ]) ?>
            
            <!-- Información de escuela en móvil -->
            <?php if ($idEscuela && $idEscuela > 0): ?>
            <div class="mobile-school-info p-3 bg-light border-top">
                <h6 class="text-muted mb-2">
                    <i class="bi bi-building me-1"></i>Escuela activa
                </h6>
                <p class="mb-1"><strong><?= Html::encode($nombreEscuela) ?></strong></p>
                <small class="text-muted">ID: <?= $idEscuela ?></small>
            </div>
            <?php endif; ?>
            
            <!-- Redes sociales en móvil -->
            <div class="mobile-social-section p-3 border-top">
                <h6 class="text-muted mb-3">Síguenos</h6>
                <div class="d-flex justify-content-center gap-3">
                    <a href="#" class="social-icon-circle" title="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="social-icon-circle" title="Twitter">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="#" class="social-icon-circle" title="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" class="social-icon-circle" title="YouTube">
                        <i class="bi bi-youtube"></i>
                    </a>
                </div>
            </div>
            
            <!-- Control de sesión en móvil -->
            <div class="mobile-session-controls p-3 border-top">
                <?php if (Yii::$app->user->isGuest): ?>
                    <!-- USUARIO NO AUTENTICADO -->
                    <div class="d-grid gap-2">
                        <?php if ($showLoginButton): ?>
                        <a class="btn btn-outline-primary" 
                           href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>" 
                           title="Iniciar sesión">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($showSignupButton): ?>
                        <a class="btn btn-primary" 
                           href="<?= Yii::$app->urlManager->createUrl(['/site/signup']) ?>" 
                           title="Registrarse">
                            <i class="bi bi-person-plus me-2"></i>Crear cuenta
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- USUARIO AUTENTICADO -->
                    <div class="user-info-mobile mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-circle fs-4 me-3 text-primary"></i>
                            <div>
                                <strong><?= Html::encode(Yii::$app->user->identity->username ?? 'Usuario') ?></strong>
                                <?php if ($idEscuela && $idEscuela > 0): ?>
                                <div class="text-muted small">
                                    <i class="bi bi-building me-1"></i><?= Html::encode(mb_strimwidth($nombreEscuela, 0, 25, '...')) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-grid']) ?>
                        <?= Html::submitButton(
                            '<i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión',
                            [
                                'class' => 'btn btn-outline-danger',
                                'title' => 'Cerrar sesión'
                            ]
                        ) ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ✅ SCRIPT DE VERIFICACIÓN Y CORRECCIÓN -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Verificando estructura del navbar...');
    
    // Función para forzar navbar en una línea (solo en desktop)
    function forceSingleLineNavbar() {
        if (window.innerWidth >= 992) {
            const navbar = document.querySelector('.navbar-contextual');
            const container = document.querySelector('.navbar-container');
            const sectionsContainer = document.querySelector('.navbar-sections-container');
            
            if (container) {
                // Forzar display flex en línea
                container.style.display = 'flex';
                container.style.flexDirection = 'row';
                container.style.flexWrap = 'nowrap';
                container.style.alignItems = 'stretch';
                container.style.justifyContent = 'flex-start';
                container.style.width = '100%';
                container.style.margin = '0';
                container.style.padding = '0';
                container.style.gap = '0';
                
                // Asegurar que todos los hijos sean flex
                Array.from(container.children).forEach(child => {
                    child.style.display = 'flex';
                    child.style.flexShrink = '0';
                    child.style.margin = '0';
                    child.style.padding = '0';
                });
            }
            
            if (sectionsContainer) {
                sectionsContainer.style.display = 'flex';
                sectionsContainer.style.flexDirection = 'row';
                sectionsContainer.style.flexWrap = 'nowrap';
                sectionsContainer.style.alignItems = 'stretch';
                sectionsContainer.style.flexGrow = '1';
                sectionsContainer.style.margin = '0';
                sectionsContainer.style.padding = '0';
                sectionsContainer.style.gap = '0';
            }
            
            // Verificar que el collapse ocupe espacio
            const collapse = document.querySelector('.navbar-collapse');
            if (collapse) {
                collapse.style.display = 'flex';
                collapse.style.flexGrow = '1';
                collapse.style.margin = '0';
                collapse.style.padding = '0';
            }
            
            console.log('✅ Navbar forzado a una línea');
            
            // Verificar anchos
            const brandSection = document.querySelector('.navbar-brand-section');
            const menuSection = document.querySelector('.navbar-menu-section');
            const socialSection = document.querySelector('.navbar-social-section');
            const controlSection = document.querySelector('.navbar-control-section');
            
            console.log('📏 Anchos calculados:');
            console.log('• Logo:', brandSection?.offsetWidth, 'px');
            console.log('• Menú:', menuSection?.offsetWidth, 'px');
            console.log('• Social:', socialSection?.offsetWidth, 'px');
            console.log('• Control:', controlSection?.offsetWidth, 'px');
            
            // Verificar que la suma sea aproximadamente el 90% del viewport
            const totalWidth = (brandSection?.offsetWidth || 0) + 
                              (menuSection?.offsetWidth || 0) + 
                              (socialSection?.offsetWidth || 0) + 
                              (controlSection?.offsetWidth || 0);
            const viewportWidth = window.innerWidth;
            const expectedWidth = viewportWidth * 0.9;
            
            console.log('• Total actual:', totalWidth, 'px');
            console.log('• Total esperado (90% de viewport):', expectedWidth, 'px');
            console.log('• Diferencia:', Math.abs(totalWidth - expectedWidth), 'px');
        }
    }
    
    // Ejecutar inmediatamente
    setTimeout(forceSingleLineNavbar, 50);
    
    // Re-ejecutar en redimensionamiento
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(forceSingleLineNavbar, 250);
    });
    
    // Verificar altura de 45vh
    function verifyNavbarHeight() {
        const navbar = document.querySelector('.navbar-contextual');
        if (navbar) {
            const viewportHeight = window.innerHeight;
            const navbarHeight = navbar.offsetHeight;
            const minRequiredHeight = viewportHeight * 0.45;
            
            console.log('📏 Verificación altura 45vh:');
            console.log('• Viewport:', viewportHeight, 'px');
            console.log('• Navbar actual:', navbarHeight, 'px');
            console.log('• 45vh requerido:', Math.round(minRequiredHeight), 'px');
            console.log('• Cumple:', navbarHeight >= minRequiredHeight ? '✅ SÍ' : '❌ NO');
            
            if (window.innerWidth >= 992 && navbarHeight < minRequiredHeight) {
                console.log('🔄 Ajustando altura a 45vh...');
                navbar.style.height = minRequiredHeight + 'px';
                navbar.style.minHeight = minRequiredHeight + 'px';
                document.body.style.paddingTop = minRequiredHeight + 'px';
            }
        }
    }
    
    verifyNavbarHeight();
    
    // Script para manejar el sidebar móvil
    function setupMobileSidebar() {
        const offcanvas = document.getElementById('mobileMenuOffcanvas');
        if (offcanvas) {
            // Inicializar offcanvas de Bootstrap 5
            const bsOffcanvas = new bootstrap.Offcanvas(offcanvas);
            
            // Manejar eventos de apertura/cierre
            offcanvas.addEventListener('show.bs.offcanvas', function() {
                console.log('📱 Sidebar móvil abierto');
                document.body.style.overflow = 'hidden';
            });
            
            offcanvas.addEventListener('hidden.bs.offcanvas', function() {
                console.log('📱 Sidebar móvil cerrado');
                document.body.style.overflow = 'auto';
            });
            
            // Manejar dropdowns dentro del sidebar móvil
            const dropdownToggles = offcanvas.querySelectorAll('.dropdown-toggle');
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    if (window.innerWidth < 992) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const dropdownMenu = this.nextElementSibling;
                        if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                            dropdownMenu.classList.toggle('show');
                            this.setAttribute('aria-expanded', 
                                dropdownMenu.classList.contains('show') ? 'true' : 'false');
                        }
                    }
                });
            });
            
            // Cerrar dropdowns al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && !e.target.closest('.mobile-nav-menu')) {
                    offcanvas.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                        const toggle = menu.previousElementSibling;
                        if (toggle && toggle.classList.contains('dropdown-toggle')) {
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            });
        }
    }
    
    // Inicializar sidebar móvil
    if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
        setupMobileSidebar();
    } else {
        // Esperar a que Bootstrap se cargue
        setTimeout(setupMobileSidebar, 100);
    }
});
</script>