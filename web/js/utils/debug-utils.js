// web/js/utils/debug-utils.js
// Funciones de debug y utilidades para el sistema GED

// ==================================================
// FUNCIONES DE DEBUG Y UTILIDADES
// ==================================================

function debugGEDSystem() {
    console.group('🐛 DEBUG GED SYSTEM v4.1 - PADDING MÍNIMO');
    console.log('GED System:', window.gedSystem);
    console.log('Estado:', window.gedSystem?.getCurrentState());
    console.log('Módulos cargados:', Object.keys(window.gedSystem?.modules || {}));
    
    const bodyWidth = document.body.offsetWidth;
    const viewportWidth = window.innerWidth;
    console.log(`📏 Body: ${bodyWidth}px, Viewport: ${viewportWidth}px, Diff: ${bodyWidth - viewportWidth}px`);
    
    // Verificar padding de elementos clave
    const elements = ['.navbar-contextual', '.main-content-wrapper', '.container-fluid', 'body'];
    elements.forEach(selector => {
        const el = document.querySelector(selector);
        if (el) {
            const style = window.getComputedStyle(el);
            console.log(`${selector}: padding-left=${style.paddingLeft}, width=${el.offsetWidth}px`);
        }
    });
    
    console.groupEnd();
}

window.debugGEDSystem = debugGEDSystem;

// ✅ FUNCIONES GLOBALES DE CONTROL
window.forceWidthFix = function() {
    if (window.gedSystem) {
        window.gedSystem.forceWidthFix();
        console.log('🔄 Full width fix ejecutado manualmente');
    }
};

window.checkOverflow = function() {
    if (window.gedSystem) {
        return window.gedSystem.checkOverflow();
    }
    return null;
};

window.updatePaddingConfig = function(minPx = 10, maxVH = 0.01) {
    if (window.gedSystem) {
        window.gedSystem.updatePaddingConfig(minPx, maxVH);
    }
};

window.getSystemState = function() {
    if (window.gedSystem) {
        return window.gedSystem.getCurrentState();
    }
    return null;
};

window.reloadOffCanvasMenu = function() {
    if (window.gedSystem && window.gedSystem.modules.sidebar) {
        window.gedSystem.modules.sidebar.loadMobileMenu();
        console.log('🔄 Menú del off-canvas recargado manualmente');
    }
};

window.forceNavbarRecalculation = function() {
    if (window.gedSystem) {
        window.gedSystem.forceMinimalPaddingRecalculation();
    }
};

window.limpiarCarrito = function() {
    sessionStorage.removeItem('ged-carrito');
    if (window.landingPageManager) {
        window.landingPageManager.carrito = [];
        window.landingPageManager.actualizarContadorCarrito();
    }
    console.log('🧹 Carrito limpiado');
};

function debugLandingPage() {
    console.group('🐛 DEBUG LANDING PAGE - CON PRODUCTOS');
    console.log('Landing Page Manager:', window.landingPageManager);
    console.log('Productos cargados:', window.landingPageManager?.productos);
    console.log('Carrito:', window.landingPageManager?.carrito);
    console.log('Total vendidos:', window.landingPageManager?.totalVendidos);
    console.groupEnd();
}

window.debugLandingPage = debugLandingPage;

// Modo desarrollo automático
if (window.location.href.indexOf('localhost') > -1 || window.location.href.indexOf('debug') > -1) {
    setTimeout(() => {
        debugGEDSystem();
        console.log('🔧 Modo desarrollo activo - Debug functions disponibles');
        console.log('ℹ️  Usa debugGEDSystem() para ver estado completo');
        console.log('ℹ️  Usa updatePaddingConfig(minPx, maxVH) para ajustar padding');
    }, 2000);
}