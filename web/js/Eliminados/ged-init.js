// web/js/ged-init.js
// VERSIÓN SIMPLIFICADA PARA MENÚ DE 2 NIVELES
// Versión: 2.0.0
// Fecha: 2024-01-18

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 GED Init - Menú de 2 niveles');
    
    // ✅ 1. CORREGIR ANCHO DEL NAVBAR
    function fixNavbarLayout() {
        const navbar = document.querySelector('.navbar-contextual');
        if (navbar) {
            navbar.style.width = '100vw';
            navbar.style.maxWidth = '100vw';
            navbar.style.minWidth = '100vw';
        }
        document.body.style.overflowX = 'hidden';
    }
    
    // ✅ 2. VERIFICAR ESTRUCTURA DEL MENÚ
    function verifyMenuStructure() {
        setTimeout(() => {
            const dropdowns = document.querySelectorAll('.dropdown');
            console.log(`✅ ${dropdowns.length} dropdowns encontrados (2 niveles máximo)`);
            
            // Verificar que no haya submenús anidados
            const nestedDropdowns = document.querySelectorAll('.dropdown-menu .dropdown');
            if (nestedDropdowns.length > 0) {
                console.warn('⚠️ Se encontraron dropdowns anidados. Eliminar para mantener 2 niveles.');
            }
            
            // Verificar offcanvas móvil
            const offcanvas = document.getElementById('mobileMenuOffcanvas');
            if (offcanvas) {
                console.log('✅ Offcanvas móvil detectado');
            }
        }, 500);
    }
    
    // ✅ 3. MANEJAR RESIZE
    function handleResize() {
        fixNavbarLayout();
    }
    
    // ✅ 4. INICIALIZAR
    fixNavbarLayout();
    verifyMenuStructure();
    
    // ✅ 5. EVENT LISTENERS
    window.addEventListener('resize', handleResize);
    
    console.log('✅ ged-init.js cargado - Bootstrap maneja el menú');
});

// ✅ DEBUG MANUAL
window.debugMenu = function() {
    console.group('🔍 DEBUG MENÚ');
    console.log('Nivel 1 (navbar items):', document.querySelectorAll('.main-navigation > .nav-item').length);
    console.log('Nivel 2 (dropdown items):', document.querySelectorAll('.dropdown-menu > li').length);
    console.log('Nivel 3+ (ERROR si hay):', document.querySelectorAll('.dropdown-menu .dropdown-menu').length);
    
    // Verificar módulo actual
    const sidebar = document.querySelector('.sidebar-module-wrapper');
    console.log('Sidebar de módulo:', sidebar ? 'Presente' : 'Ausente');
    
    console.groupEnd();
};