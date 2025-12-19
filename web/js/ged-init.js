// web/js/ged-init.js
// Inicialización global del sistema GED

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (!window.gedSystem) {
            window.gedSystem = new GEDSystem();
            console.log('🚀 Sistema GED v4.1 completamente inicializado');
        }
    }, 100);
    
    // Inicializar Landing Page Manager si es necesario
    if (document.querySelector('.landing-page')) {
        setTimeout(() => {
            if (typeof window.landingPageManager !== 'undefined') {
                console.log('✅ Landing Page Manager ya está cargado');
            } else if (typeof LandingPageManager !== 'undefined') {
                window.landingPageManager = new LandingPageManager();
                console.log('✅ Landing Page Manager inicializado correctamente');
            } else {
                console.error('❌ LandingPageManager no está definido');
            }
        }, 500);
    }
});

// Evento personalizado para notificar que el sistema está listo
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