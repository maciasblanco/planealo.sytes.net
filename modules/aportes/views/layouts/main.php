<?php
/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;

// Registrar AssetBundle principal
AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

// Datos de la escuela
$session = Yii::$app->session;
$idEscuela = $session->get('idEscuela', 0);
$nombreEscuela = $session->get('nombreEscuela', 'Selecciona una escuela');
$hasEscuela = !empty($idEscuela) && $idEscuela != 0;

// Detectar módulo actual
$currentModule = Yii::$app->controller->module->id ?? '';
$modulesConSidebar = ['atletas', 'tienda', 'escuela_club', 'aportes', 'reportes'];
$tieneSidebar = in_array($currentModule, $modulesConSidebar);

// Función helper para títulos de módulo
function getModuleTitle($moduleName)
{
    $titles = [
        'atletas' => 'Atletas',
        'tienda' => 'MarketPlace',
        'escuela_club' => 'Escuela/Club',
        'aportes' => 'Aportes',
        'reportes' => 'Reportes',
    ];
    
    return $titles[$moduleName] ?? 'Módulo';
}

// Detectar si es desktop para scripts específicos
$isDesktop = (!Yii::$app->request->isMobile || Yii::$app->request->isDesktop);
$this->params['isDesktop'] = $isDesktop;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<!-- ✅ NAVBAR PERSONALIZADO -->
<?= $this->render('_navbar', [
    'idEscuela' => $idEscuela,
    'nombreEscuela' => $nombreEscuela,
    'navbarVariant' => $hasEscuela ? 'escuela' : 'default'
]) ?>

<!-- ✅ CONTENIDO PRINCIPAL (SIN SIDEBAR EN DESKTOP) -->
<main id="main" class="flex-shrink-0" role="main">
    <div class="ged-main-content-wrapper">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <div class="ged-breadcrumbs-container">
                <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
            </div>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<!-- ✅ FOOTER -->
<footer id="footer" class="mt-auto py-3 bg-light">
    <div class="container-fluid">
        <div class="row align-items-center text-muted">
            <div class="col-md-6 text-center text-md-start">
                <i class="bi bi-graduation-cap me-2"></i>
                &copy; <?= date('Y') ?> Sistema GED v4.6
            </div>
            <div class="col-md-6 text-center text-md-end">
                <?php if ($idEscuela): ?>
                    <span class="badge bg-primary me-2">
                        <i class="bi bi-building"></i> Escuela ID: <?= $idEscuela ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning me-2">
                        <i class="bi bi-exclamation-triangle"></i> Selecciona una escuela
                    </span>
                <?php endif; ?>
                <span class="d-none d-md-inline"><?= Yii::powered() ?></span>
            </div>
        </div>
    </div>
</footer>

<!-- ✅ TEMPLATE PARA MENÚ MÓVIL (OCULTO) -->
<div id="mobile-menu-template" style="display: none;">
    <?= \app\components\MenuWidget::widget([
        'options' => [
            'class' => 'navbar-nav flex-column w-100',
            'mobileMode' => true
        ]
    ]) ?>
</div>

<!-- ✅ DATA PARA SIDEBAR MÓVIL (SOLO SI ESTAMOS EN UN MÓDULO CON SIDEBAR) -->
<?php if ($tieneSidebar): ?>
<div id="mobile-module-sidebar-data" style="display: none;" 
     data-module="<?= Html::encode($currentModule) ?>"
     data-title="<?= Html::encode(getModuleTitle($currentModule)) ?>">
     <!-- El contenido se cargará dinámicamente via JS -->
</div>
<?php endif; ?>
    <!-- ✅ SCRIPT DE INICIALIZACIÓN Y VERIFICACIÓN (CÓDIGO CONFLICTIVO ELIMINADO) -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 GED System iniciado - Modo: ' + (window.innerWidth < 992 ? 'Móvil' : 'Escritorio'));
    
    // Verificador de altura del navbar
    function verifyNavbarHeight() {
        const navbar = document.querySelector('.navbar-contextual');
        if (!navbar) return;
        
        const viewportHeight = window.innerHeight;
        const navbarHeight = navbar.offsetHeight;
        const minRequiredHeight = viewportHeight * 0.45; // 45vh
        
        console.log('📏 VERIFICACIÓN DE ALTURA NAVBAR:');
        console.log('• Altura viewport:', viewportHeight, 'px');
        console.log('• Altura navbar actual:', navbarHeight, 'px');
        console.log('• Altura mínima requerida (45vh):', Math.round(minRequiredHeight), 'px');
        console.log('• Cumple con 45vh:', navbarHeight >= minRequiredHeight ? '✅ SÍ' : '❌ NO');
        
        // Agregar indicador visual en modo debug
        if (window.location.href.includes('debug=navbar')) {
            navbar.setAttribute('data-height', navbarHeight + 'px');
            navbar.classList.add('debug-navbar-height');
        }
        
        // Ajustar dinámicamente si no cumple (solo en escritorio)
        if (window.innerWidth >= 992 && navbarHeight < minRequiredHeight) {
            console.log('🔄 Ajustando altura del navbar a 45vh...');
            navbar.style.minHeight = minRequiredHeight + 'px';
            document.body.style.paddingTop = minRequiredHeight + 'px';
        }
    }
    
    // Verificar que el body ocupe 100% del ancho
    function verifyWidth() {
        const bodyWidth = document.body.offsetWidth;
        const windowWidth = window.innerWidth;
        
        console.log('Body width:', bodyWidth, 'px');
        console.log('Window width:', windowWidth, 'px');
        
        if (Math.abs(bodyWidth - windowWidth) > 10) {
            console.warn('⚠️ Body no ocupa 100% del ancho. Diferencia:', windowWidth - bodyWidth, 'px');
        } else {
            console.log('✅ Body ocupa correctamente el 100% del ancho');
        }
    }
    
    // Verificar que no haya sidebar visible en desktop
    function verifyNoSidebar() {
        if (window.innerWidth >= 992) {
            const sidebar = document.querySelector('.sidebar-module-wrapper');
            if (sidebar && sidebar.offsetParent !== null) {
                console.warn('⚠️ Sidebar visible en desktop (debería estar oculto)');
                sidebar.style.display = 'none !important';
            }
        }
    }
    
    // Ajustar ancho del navbar
    function adjustNavbarWidth() {
        const navbar = document.querySelector('.navbar-contextual');
        if (navbar) {
            navbar.style.width = '100vw';
            navbar.style.maxWidth = '100vw';
        }
    }
    
    // Verificar estructura navbar en 1 línea
    function verifyNavbarStructure() {
        console.log('🔍 Verificando estructura navbar...');
        
        const navbar = document.querySelector('.navbar-contextual');
        const container = document.querySelector('.navbar-container');
        
        if (navbar && container) {
            // Verificar que estén en línea en desktop
            if (window.innerWidth >= 992) {
                const computedStyle = window.getComputedStyle(container);
                console.log('📊 Navbar container flex-direction:', computedStyle.flexDirection);
                console.log('📊 Navbar container display:', computedStyle.display);
                
                if (computedStyle.flexDirection === 'column') {
                    console.warn('⚠️ Navbar en COLUMNA en escritorio - corrigiendo...');
                    container.style.flexDirection = 'row';
                }
            }
        }
    }
    
    // Ejecutar todas las verificaciones
    verifyNavbarHeight();
    verifyWidth();
    verifyNoSidebar();
    adjustNavbarWidth();
    verifyNavbarStructure();
    
    // Evento para cambios de tamaño
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            verifyNavbarHeight();
            verifyWidth();
            verifyNoSidebar();
            adjustNavbarWidth();
            verifyNavbarStructure();
        }, 250);
    });
    
    // Debug del menú
    const menuItems = document.querySelectorAll('.main-navigation .nav-item');
    console.log(`📊 MenuWidget generó ${menuItems.length} items para escritorio`);
    
    // Verificar template para móvil
    const mobileTemplate = document.getElementById('mobile-menu-template');
    console.log('Template menú móvil:', mobileTemplate ? '✅ DISPONIBLE' : '❌ NO DISPONIBLE');
    
    // Verificar datos del módulo para móvil
    <?php if (isset($tieneSidebar) && $tieneSidebar): ?>
    const moduleData = document.getElementById('mobile-module-sidebar-data');
    console.log(`📱 Datos módulo para móvil: ${moduleData.dataset.module} - ${moduleData.dataset.title}`);
    <?php endif; ?>
    
    // Función global para debug
    window.checkNavbarHeight = verifyNavbarHeight;
    window.checkNavbarStructure = verifyNavbarStructure;
    
    // Función para verificar el estado del menú (nueva - solo para debug)
    window.checkMenuState = function() {
        console.group('🔍 ESTADO DEL MENÚ DESKTOP');
        const dropdowns = document.querySelectorAll('.dropdown');
        console.log(`Dropdowns totales: ${dropdowns.length}`);
        
        dropdowns.forEach((dropdown, i) => {
            const menu = dropdown.querySelector('.dropdown-menu');
            console.log(`Dropdown ${i}:`, {
                abierto: menu?.classList.contains('show'),
                nivel: menu?.closest('.dropdown-menu') ? 'submenu' : 'principal',
                zIndex: menu?.style.zIndex || 'no definido'
            });
        });
        console.groupEnd();
    };
});
</script>

<?php if (Yii::$app->view->params['isDesktop'] ?? true): ?>
<script>
// ==================================================
// ✅ SCRIPT EXCLUSIVO PARA DESKTOP - MENÚ MULTINIVEL
// ==================================================

document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar en desktop
    if (window.innerWidth < 992) {
        console.log('📱 Modo móvil - Script desktop desactivado');
        return;
    }
    
    console.log('🖥️ Iniciando solución para menú desktop multinivel');
    
    // Variables de control
    const hoverTimeouts = new WeakMap();
    const HOVER_DELAY = 200; // ms de tolerancia para mover entre niveles
    let activeDropdown = null;
    
    // 1. CONFIGURAR TODOS LOS DROPDOWNS
    const setupDropdowns = () => {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            if (!toggle || !menu) return;
            
            // Evento mouseenter (entrar al dropdown)
            dropdown.addEventListener('mouseenter', function(e) {
                // Cancelar timeout de cierre si existe
                const timeout = hoverTimeouts.get(this);
                if (timeout) {
                    clearTimeout(timeout);
                    hoverTimeouts.delete(this);
                }
                
                // Si hay un dropdown activo diferente, cerrarlo
                if (activeDropdown && activeDropdown !== this && 
                    !activeDropdown.contains(this)) {
                    closeDropdown(activeDropdown);
                }
                
                // Abrir este dropdown
                openDropdown(this);
                activeDropdown = this;
            });
            
            // Evento mouseleave (salir del dropdown)
            dropdown.addEventListener('mouseleave', function(e) {
                // Verificar si el mouse va hacia un submenu
                const relatedTarget = e.relatedTarget;
                const isGoingToSubmenu = relatedTarget && 
                    (relatedTarget.closest('.dropdown-menu') === menu || 
                     menu.contains(relatedTarget));
                
                if (!isGoingToSubmenu) {
                    // Programar cierre con delay
                    const timeout = setTimeout(() => {
                        if (activeDropdown === this) {
                            closeDropdown(this);
                            activeDropdown = null;
                        }
                    }, HOVER_DELAY);
                    
                    hoverTimeouts.set(this, timeout);
                }
            });
            
            // Para el menú dropdown también
            menu.addEventListener('mouseenter', function() {
                // Cancelar timeout de cierre del padre
                const parent = this.closest('.dropdown');
                if (parent) {
                    const timeout = hoverTimeouts.get(parent);
                    if (timeout) {
                        clearTimeout(timeout);
                        hoverTimeouts.delete(parent);
                    }
                }
            });
            
            menu.addEventListener('mouseleave', function(e) {
                const relatedTarget = e.relatedTarget;
                const parent = this.closest('.dropdown');
                
                if (!relatedTarget || !parent || !parent.contains(relatedTarget)) {
                    const timeout = setTimeout(() => {
                        if (parent && activeDropdown === parent) {
                            closeDropdown(parent);
                            activeDropdown = null;
                        }
                    }, HOVER_DELAY);
                    
                    if (parent) {
                        hoverTimeouts.set(parent, timeout);
                    }
                }
            });
        });
        
        console.log(`✅ Configurados ${dropdowns.length} dropdowns para desktop`);
    };
    
    // 2. FUNCIONES AUXILIARES
    const openDropdown = (dropdown) => {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!menu) return;
        
        // Usar Bootstrap para abrir (mantiene consistencia)
        const bsInstance = bootstrap.Dropdown.getInstance(dropdown);
        if (bsInstance) {
            bsInstance.show();
        } else {
            // Fallback manual
            menu.classList.add('show');
            dropdown.classList.add('show');
            menu.style.display = 'block';
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';
            
            // Ajustar z-index según nivel
            let level = 0;
            let parent = dropdown.parentElement;
            while (parent) {
                if (parent.classList.contains('dropdown-menu')) level++;
                parent = parent.parentElement;
            }
            menu.style.zIndex = 1110 + (level * 10);
        }
    };
    
    const closeDropdown = (dropdown) => {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!menu) return;
        
        // Usar Bootstrap para cerrar
        const bsInstance = bootstrap.Dropdown.getInstance(dropdown);
        if (bsInstance) {
            bsInstance.hide();
        } else {
            // Fallback manual
            menu.classList.remove('show');
            dropdown.classList.remove('show');
            menu.style.display = 'none';
            
            // Cerrar también todos los submenús
            const submenus = menu.querySelectorAll('.dropdown-menu.show');
            submenus.forEach(submenu => {
                submenu.classList.remove('show');
                submenu.style.display = 'none';
            });
        }
    };
    
    // 3. INICIALIZACIÓN
    const initDesktopMenu = () => {
        // Esperar a que Bootstrap esté listo
        if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) {
            setTimeout(initDesktopMenu, 100);
            return;
        }
        
        // Inicializar dropdowns de Bootstrap
        const dropdownElements = document.querySelectorAll('.dropdown');
        dropdownElements.forEach(dropdown => {
            // Evitar inicializar dropdowns dentro de offcanvas móvil
            if (!dropdown.closest('.offcanvas')) {
                new bootstrap.Dropdown(dropdown, {
                    autoClose: true,
                    reference: 'toggle'
                });
            }
        });
        
        // Configurar nuestros eventos personalizados
        setupDropdowns();
        
        // Prevenir cierre al hacer clic en items
        document.addEventListener('click', function(e) {
            if (e.target.closest('.dropdown-item')) {
                // Permitir que el clic se procese, luego cerrar menús
                setTimeout(() => {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                        menu.style.display = 'none';
                    });
                }, 300);
            }
        });
        
        console.log('✅ Sistema de menú desktop inicializado');
    };
    
    // Iniciar después de un breve delay
    setTimeout(initDesktopMenu, 500);
    
    // 4. REINICIALIZAR AL REDIMENSIONAR
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 992) {
                console.log('🔄 Reconfigurando menú desktop por resize');
                initDesktopMenu();
            }
        }, 250);
    });
});
</script>
<?php endif; ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>