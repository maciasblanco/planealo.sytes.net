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
    <!-- ✅ SCRIPT DE INICIALIZACIÓN Y VERIFICACIÓN -->
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
    
    // ==================================================
    // ✅ CORRECCIÓN DE Z-INDEX PARA SUBMENÚS
    // ==================================================
    
    function fixDropdownZIndex() {
        // Solo aplica en desktop
        if (window.innerWidth >= 992) {
            console.log('🔧 Aplicando correcciones de z-index a submenús...');
            
            // 1. Asegurar que el navbar y contenedores sean visibles
            const elementsToMakeVisible = [
                '.navbar-contextual',
                '.navbar-container',
                '.navbar-sections-container',
                '.navbar-collapse',
                '.navbar-menu-section',
                '.main-navigation'
            ];
            
            elementsToMakeVisible.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.style.overflow = 'visible';
                    el.style.position = 'relative';
                });
            });
            
            // 2. Aplicar z-index correcto a dropdowns
            const dropdownMenus = document.querySelectorAll('.dropdown-menu');
            dropdownMenus.forEach((menu, index) => {
                // Calcular nivel del dropdown
                let level = 0;
                let parent = menu.parentElement;
                
                while (parent) {
                    if (parent.classList.contains('dropdown-menu')) {
                        level++;
                    }
                    if (parent.classList.contains('navbar-nav')) {
                        break;
                    }
                    parent = parent.parentElement;
                }
                
                // Asignar z-index según nivel
                const baseZIndex = 1110;
                const zIndex = baseZIndex + (level * 10);
                
                menu.style.zIndex = zIndex;
                menu.style.position = 'absolute';
                menu.style.overflow = 'visible';
                menu.style.transform = 'none';
                
                // Debug info
                if (window.location.href.includes('debug=zindex')) {
                    menu.setAttribute('data-dropdown-level', level);
                    menu.setAttribute('data-z-index', zIndex);
                    menu.style.border = '1px solid #ff0';
                }
            });
            
            // 3. Asegurar que los dropdown-toggles tengan buen z-index
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
            dropdownToggles.forEach(toggle => {
                toggle.style.position = 'relative';
                toggle.style.zIndex = '1100';
            });
            
            console.log(`✅ Corrección aplicada a ${dropdownMenus.length} dropdowns`);
        }
    }
    
    // Ejecutar inmediatamente
    setTimeout(fixDropdownZIndex, 300);
    
    // Re-ejecutar en eventos importantes
    window.addEventListener('resize', fixDropdownZIndex);
    
    // Corregir cuando Bootstrap muestre un dropdown
    document.addEventListener('show.bs.dropdown', function() {
        setTimeout(fixDropdownZIndex, 100);
    });
    
    // También corregir al pasar el mouse sobre dropdowns
    const dropdownHoverElements = document.querySelectorAll('.dropdown');
    dropdownHoverElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            setTimeout(fixDropdownZIndex, 50);
        });
    });
    
    // Función global para debug
    window.debugDropdowns = function() {
        console.group('🐛 DEBUG DROPDOWNS');
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach((menu, i) => {
            console.log(`Dropdown ${i}:`, {
                zIndex: menu.style.zIndex || getComputedStyle(menu).zIndex,
                position: menu.style.position || getComputedStyle(menu).position,
                visible: menu.offsetParent !== null,
                level: menu.getAttribute('data-dropdown-level') || '0'
            });
        });
        console.groupEnd();
    };
    
    // ==================================================
    // ✅ VERIFICACIÓN DE SUBMENÚS VISIBLES
    // ==================================================
    
    function verifyDropdownVisibility() {
        if (window.innerWidth >= 992) {
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            let hiddenDropdowns = 0;
            
            dropdowns.forEach(menu => {
                const computedStyle = window.getComputedStyle(menu);
                if (computedStyle.visibility === 'hidden' || 
                    computedStyle.opacity === '0' || 
                    menu.offsetParent === null) {
                    hiddenDropdowns++;
                }
            });
            
            if (hiddenDropdowns > 0 && window.location.href.includes('debug=dropdowns')) {
                console.warn(`⚠️ ${hiddenDropdowns} dropdowns podrían estar ocultos`);
            }
        }
    }
    
    // Verificar periódicamente
    setInterval(verifyDropdownVisibility, 2000);
    
    // ==================================================
    // ✅ SCRIPT TEMPORAL PARA VERIFICAR Z-INDEX (eliminar después)
    // ==================================================
    
    if (window.location.href.includes('debug=navbar')) {
        const style = document.createElement('style');
        style.textContent = `
            .dropdown-menu {
                box-shadow: 0 0 0 2px rgba(255,0,0,0.5) !important;
            }
            .dropdown-menu .dropdown-menu {
                box-shadow: 0 0 0 2px rgba(0,255,0,0.5) !important;
            }
            .dropdown-menu .dropdown-menu .dropdown-menu {
                box-shadow: 0 0 0 2px rgba(0,0,255,0.5) !important;
            }
        `;
        document.head.appendChild(style);
        console.log('🔍 Modo debug de dropdowns activado');
    }
});
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>