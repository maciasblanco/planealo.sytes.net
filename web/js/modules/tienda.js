// js/tienda.js - Módulo específico para funcionalidades de Tienda
// Creado para no interferir con ged.js existente

class TiendaModule {
    constructor() {
        this.init();
    }
    
    init() {
        console.log('🛍️ Módulo Tienda inicializado');
        this.bindEvents();
        this.checkTiendaAccess();
    }
    
    bindEvents() {
        // Evento para el botón de marketplace
        const btnMarketplace = document.getElementById('btn-marketplace');
        if (btnMarketplace) {
            btnMarketplace.addEventListener('click', (e) => {
                this.handleMarketplaceClick(e);
            });
        }
        
        // Eventos para enlaces de registro vendedor
        const vendedorLinks = document.querySelectorAll('a[href*="registro-vendedor"]');
        vendedorLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                this.handleVendedorRegistroClick(e);
            });
        });
    }
    
    handleMarketplaceClick(e) {
        console.log('🎯 Navegando al marketplace...');
        
        // Opcional: agregar analytics o tracking
        this.trackEvent('marketplace_access', 'select_escuela_page');
        
        // El enlace href normal manejará la navegación
        // No necesitamos prevenir el comportamiento por defecto
    }
    
    handleVendedorRegistroClick(e) {
        console.log('🎯 Navegando al registro de vendedor...');
        
        this.trackEvent('vendedor_registro_click', 'select_escuela_page');
        
        // Opcional: podríamos agregar un modal de información primero
        // Por ahora dejamos que el enlace normal funcione
    }
    
    checkTiendaAccess() {
        // Verificar si el usuario tiene permisos para acceder a la tienda
        // Esto podría expandirse para verificar roles múltiples
        console.log('🔍 Verificando acceso a tienda...');
        
        // Por ahora, todos los usuarios autenticados pueden acceder
        // En el futuro, verificar roles: deportivo, vendedor, o ambos
    }
    
    trackEvent(action, location) {
        // Función para tracking de eventos (puede integrarse con Google Analytics)
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                'event_category': 'tienda',
                'event_label': location
            });
        }
        
        // También logging interno
        console.log(`📊 Evento Tienda: ${action} desde ${location}`);
    }
    
    // Método para futura expansión - cargar productos destacados
    loadFeaturedProducts() {
        // Podría cargar productos destacados via AJAX
        console.log('🔄 Cargando productos destacados...');
        
        // Ejemplo de implementación futura:
        /*
        $.ajax({
            url: '/tienda/api/productos-destacados',
            type: 'GET',
            success: (response) => {
                this.displayFeaturedProducts(response);
            },
            error: (error) => {
                console.error('Error cargando productos destacados:', error);
            }
        });
        */
    }
    
    // Método para futura expansión - mostrar productos
    displayFeaturedProducts(products) {
        // Lógica para mostrar productos en un carrusel o grid
        console.log('📦 Mostrando productos destacados:', products);
    }
}

// Inicialización automática cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si estamos en una página relevante para tienda
    const tiendaElements = document.querySelectorAll('[class*="tienda"], [id*="tienda"], [href*="tienda"]');
    
    if (tiendaElements.length > 0 || window.location.pathname.includes('tienda')) {
        setTimeout(() => {
            if (!window.tiendaModule) {
                window.tiendaModule = new TiendaModule();
                console.log('🚀 Módulo Tienda completamente inicializado');
            }
        }, 100);
    }
});

// Manejo de errores global para el módulo tienda
window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('tienda')) {
        console.error('❌ Error en módulo Tienda:', e.error);
    }
});

// Exportar para uso en otros módulos si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TiendaModule;
}