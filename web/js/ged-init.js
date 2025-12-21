// web/js/ged-init.js
// Inicialización global del sistema GED

document.addEventListener('DOMContentLoaded', () => {
    console.log('📄 GED Init - Documento cargado, inicializando sistema...');
    
    // Inicializar el sistema principal
    setTimeout(() => {
        if (!window.gedSystem && typeof GEDSystem !== 'undefined') {
            window.gedSystem = new GEDSystem();
            console.log('🚀 Sistema GED v4.1 completamente inicializado');
            
            // Disparar evento de listo
            window.dispatchEvent(new CustomEvent('ged:ready', { 
                detail: { 
                    version: '4.1.0', 
                    features: ['minimal-padding', 'full-width-fix', 'responsive-sidebar'],
                    paddingConfig: {
                        minPx: 10,
                        maxVH: 0.01
                    }
                } 
            }));
        } else if (window.gedSystem) {
            console.log('ℹ️ Sistema GED ya estaba inicializado');
        } else {
            console.error('❌ Clase GEDSystem no encontrada');
        }
    }, 100);
    
    // Inicializar Landing Page Manager si es necesario
    if (document.querySelector('.landing-page')) {
        setTimeout(() => {
            if (typeof window.landingPageManager !== 'undefined') {
                console.log('✅ Landing Page Manager ya está cargado');
            } else if (typeof LandingPageManager !== 'undefined') {
                window.landingPageManager = new LandingPageManager();
                window.landingPageManager.init();
                console.log('✅ Landing Page Manager inicializado correctamente');
            } else {
                console.warn('⚠️ LandingPageManager no está definido para esta página');
            }
        }, 500);
    }
    
    // Inicializar módulos específicos si están disponibles
    this.initializeOptionalModules();
});

// Función para inicializar módulos opcionales
function initializeOptionalModules() {
    const modulesToCheck = [
        { name: 'OffCanvasSidebar', globalVar: 'gedOffcanvas' },
        { name: 'ReportesModule', globalVar: 'reportesModule' },
        { name: 'HorarioModule', globalVar: 'horarioModuleInstance' },
        { name: 'TiendaModule', globalVar: 'tiendaModule' },
        { name: 'MapaModule', globalVar: 'mapaModule' }
    ];
    
    modulesToCheck.forEach(module => {
        if (typeof window[module.name] !== 'undefined' && !window[module.globalVar]) {
            try {
                window[module.globalVar] = new window[module.name]();
                console.log(`✅ Módulo ${module.name} inicializado`);
            } catch (error) {
                console.warn(`⚠️ No se pudo inicializar ${module.name}:`, error);
            }
        }
    });
}

// Export para Node.js (si es necesario)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { 
        GEDSystem, 
        LandingPageManager, 
        OffCanvasSidebar, 
        NavbarManager, 
        SchoolSearch, 
        ComponentsManager 
    };
}