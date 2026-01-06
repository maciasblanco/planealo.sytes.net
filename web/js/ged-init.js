// web/js/ged-init.js
// VERSIÓN SIMPLIFICADA - SOLUCIÓN DEFINITIVA PARA SUBMENÚS
// Versión: 4.3.0 - Enfoque simplificado
// Fecha: 2024-01-18

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 GED Init - Inicializando sistema...');
    
    // ✅ 1. CARGAR CSS PARA SUBMENÚS
    loadSubmenuStyles();
    
    // ✅ 2. INICIALIZACIÓN BÁSICA
    setTimeout(() => {
        fixNavbarLayout();
        initBootstrapComponents();
        setupSimpleNestedDropdowns(); // NUEVO: enfoque simplificado
    }, 100);
    
    // ✅ 3. INICIALIZAR SISTEMA PRINCIPAL
    setTimeout(() => {
        if (!window.gedSystem && typeof GEDSystem !== 'undefined') {
            try {
                window.gedSystem = new GEDSystem();
                console.log('✅ Sistema GED v4.3 inicializado');
            } catch (error) {
                console.error('❌ Error al inicializar GEDSystem:', error);
            }
        }
    }, 300);
    
    // ✅ 4. CONFIGURACIÓN RESPONSIVE
    setupResponsiveBehavior();
    
    console.log('✅ ged-init.js cargado - Enfoque simplificado');
});

// ==================================================
// FUNCIONES PRINCIPALES
// ==================================================

function loadSubmenuStyles() {
    if (!document.getElementById('ged-submenu-styles')) {
        const link = document.createElement('link');
        link.id = 'ged-submenu-styles';
        link.rel = 'stylesheet';
        link.href = '/css/_submenus.css';
        document.head.appendChild(link);
        console.log('✅ CSS de submenús cargado');
    }
}

function fixNavbarLayout() {
    console.log('🔧 Aplicando correcciones de navbar...');
    
    try {
        // Forzar ancho completo
        const navbar = document.querySelector('.navbar-contextual');
        if (navbar) {
            navbar.style.width = '100vw';
            navbar.style.maxWidth = '100vw';
            navbar.style.minWidth = '100vw';
        }
        
        document.body.style.overflowX = 'hidden';
        console.log('✅ Layout corregido');
    } catch (error) {
        console.error('❌ Error en fixNavbarLayout:', error);
    }
}

function initBootstrapComponents() {
    console.log('🔧 Inicializando componentes Bootstrap...');
    
    try {
        // Solo inicializar dropdowns del primer nivel
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            const topLevelDropdowns = document.querySelectorAll('.navbar-nav > .dropdown > .dropdown-toggle');
            topLevelDropdowns.forEach(toggle => {
                try {
                    new bootstrap.Dropdown(toggle);
                } catch (e) {
                    console.warn('⚠️ Error inicializando dropdown:', e.message);
                }
            });
            console.log(`✅ Dropdowns del primer nivel inicializados: ${topLevelDropdowns.length}`);
        }
    } catch (error) {
        console.error('❌ Error en initBootstrapComponents:', error);
    }
}

// ==================================================
// SOLUCIÓN SIMPLIFICADA PARA SUBMENÚS ANIDADOS
// ==================================================

function setupSimpleNestedDropdowns() {
    console.log('🔧 Configurando submenús anidados (enfoque simplificado)...');
    
    // Solo en desktop
    if (window.innerWidth >= 992) {
        setupDesktopSubmenus();
    } else {
        setupMobileSubmenus();
    }
}

function setupDesktopSubmenus() {
    console.log('💻 Configurando submenús para desktop...');
    
    const submenus = document.querySelectorAll('.dropdown-submenu');
    
    submenus.forEach(submenu => {
        const toggle = submenu.querySelector('.dropdown-toggle');
        const menu = submenu.querySelector('.dropdown-menu');
        
        if (!toggle || !menu) return;
        
        // Remover cualquier evento previo
        submenu.removeEventListener('mouseenter', submenu._enterHandler);
        submenu.removeEventListener('mouseleave', submenu._leaveHandler);
        
        // Evento mouseenter - mostrar submenú
        const enterHandler = () => {
            menu.style.display = 'block';
            
            // Ajustar posición si se sale de la pantalla
            const rect = submenu.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            
            if (rect.right + menuRect.width > viewportWidth - 10) {
                menu.style.left = 'auto';
                menu.style.right = '100%';
            } else {
                menu.style.left = '100%';
                menu.style.right = 'auto';
            }
        };
        
        // Evento mouseleave - ocultar submenú
        const leaveHandler = () => {
            setTimeout(() => {
                if (!submenu.matches(':hover') && !menu.matches(':hover')) {
                    menu.style.display = 'none';
                }
            }, 100);
        };
        
        // Evento para el menú también
        menu.removeEventListener('mouseleave', menu._leaveHandler);
        const menuLeaveHandler = () => {
            setTimeout(() => {
                if (!submenu.matches(':hover') && !menu.matches(':hover')) {
                    menu.style.display = 'none';
                }
            }, 100);
        };
        
        // Guardar referencias
        submenu._enterHandler = enterHandler;
        submenu._leaveHandler = leaveHandler;
        menu._leaveHandler = menuLeaveHandler;
        
        // Asignar eventos
        submenu.addEventListener('mouseenter', enterHandler);
        submenu.addEventListener('mouseleave', leaveHandler);
        menu.addEventListener('mouseleave', menuLeaveHandler);
        
        // Ocultar inicialmente
        menu.style.display = 'none';
    });
    
    console.log(`✅ ${submenus.length} submenús configurados para desktop`);
}

function setupMobileSubmenus() {
    console.log('📱 Configurando submenús para móvil...');
    
    const submenuToggles = document.querySelectorAll('.dropdown-submenu > .dropdown-toggle');
    
    submenuToggles.forEach(toggle => {
        toggle.removeEventListener('click', toggle._clickHandler);
        
        const clickHandler = (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const submenu = toggle.closest('.dropdown-submenu');
            const menu = submenu.querySelector('.dropdown-menu');
            
            if (!menu) return;
            
            // Alternar visibilidad
            const isVisible = menu.style.display === 'block';
            
            // Cerrar otros submenús al mismo nivel
            const siblings = submenu.parentElement.querySelectorAll('.dropdown-submenu');
            siblings.forEach(sibling => {
                if (sibling !== submenu) {
                    const siblingMenu = sibling.querySelector('.dropdown-menu');
                    if (siblingMenu) {
                        siblingMenu.style.display = 'none';
                    }
                }
            });
            
            menu.style.display = isVisible ? 'none' : 'block';
        };
        
        toggle._clickHandler = clickHandler;
        toggle.addEventListener('click', clickHandler);
        
        // Ocultar inicialmente
        const menu = toggle.closest('.dropdown-submenu')?.querySelector('.dropdown-menu');
        if (menu) {
            menu.style.display = 'none';
        }
    });
    
    console.log(`✅ ${submenuToggles.length} submenús configurados para móvil`);
}

// ==================================================
// COMPORTAMIENTO RESPONSIVE
// ==================================================

function setupResponsiveBehavior() {
    let lastWidth = window.innerWidth;
    
    window.addEventListener('resize', () => {
        clearTimeout(window.resizeTimeout);
        window.resizeTimeout = setTimeout(() => {
            const currentWidth = window.innerWidth;
            const wasMobile = lastWidth < 992;
            const isDesktop = currentWidth >= 992;
            
            if ((wasMobile && isDesktop) || (!wasMobile && !isDesktop)) {
                console.log(`🔄 Cambio de viewport: ${isDesktop ? 'Desktop' : 'Mobile'}`);
                setupSimpleNestedDropdowns();
            }
            
            lastWidth = currentWidth;
        }, 250);
    });
}

// ==================================================
// DEBUG Y VERIFICACIÓN
// ==================================================

// Hacer funciones disponibles globalmente para debug
window.debugMenuStructure = function() {
    console.group('🔍 DEBUG ESTRUCTURA DEL MENÚ');
    
    const stats = {
        'Elementos del menú': document.querySelectorAll('.main-navigation li').length,
        'Dropdowns (nivel 1)': document.querySelectorAll('.navbar-nav > .dropdown').length,
        'Submenús anidados': document.querySelectorAll('.dropdown-submenu').length,
        'Niveles máximos detectados': getMaxMenuDepth(),
        'CSS cargado': !!document.getElementById('ged-submenu-styles')
    };
    
    Object.entries(stats).forEach(([name, value]) => {
        console.log(`${name}: ${value}`);
    });
    
    // Mostrar árbol del menú
    console.group('Árbol del menú:');
    const topItems = document.querySelectorAll('.navbar-nav > li');
    topItems.forEach((item, idx) => {
        const label = item.querySelector('.nav-link, .dropdown-toggle')?.textContent?.trim() || `Item ${idx + 1}`;
        const submenus = item.querySelectorAll('.dropdown-submenu');
        console.log(`• ${label} ${submenus.length > 0 ? `→ ${submenus.length} submenús` : ''}`);
        
        submenus.forEach((submenu, subIdx) => {
            const subLabel = submenu.querySelector('.dropdown-toggle')?.textContent?.trim() || `Submenu ${subIdx + 1}`;
            console.log(`  ├─ ${subLabel}`);
            
            // Mostrar sub-submenús
            const nested = submenu.querySelectorAll('.dropdown-submenu');
            nested.forEach((nestedSub, nestedIdx) => {
                const nestedLabel = nestedSub.querySelector('.dropdown-toggle')?.textContent?.trim() || `Nested ${nestedIdx + 1}`;
                console.log(`  │  └─ ${nestedLabel}`);
            });
        });
    });
    console.groupEnd();
    
    console.groupEnd();
};

function getMaxMenuDepth() {
    let maxDepth = 0;
    
    const calculateDepth = (element, currentDepth) => {
        maxDepth = Math.max(maxDepth, currentDepth);
        const submenus = element.querySelectorAll('.dropdown-submenu');
        submenus.forEach(submenu => {
            calculateDepth(submenu, currentDepth + 1);
        });
    };
    
    const menuContainer = document.querySelector('.main-navigation');
    if (menuContainer) {
        calculateDepth(menuContainer, 0);
    }
    
    return maxDepth;
}

// Verificación automática
setTimeout(() => {
    const submenuCount = document.querySelectorAll('.dropdown-submenu').length;
    if (submenuCount > 0) {
        console.log(`✅ Se detectaron ${submenuCount} submenús anidados`);
        // Forzar configuración inicial
        setupSimpleNestedDropdowns();
    } else {
        console.warn('⚠️ No se detectaron submenús anidados. Verifica MenuWidget.php');
    }
}, 1500);