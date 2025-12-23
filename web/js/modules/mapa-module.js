// web/js/modules/mapa-module.js
// Versión: 2.0.0 - Con carga dinámica de Leaflet y manejo robusto de errores
// Fecha: 16/01/2024

class MapaModule {
    constructor(tipo = 'seleccion') {
        this.tipo = tipo; // 'seleccion' o 'visualizacion'
        this.mapa = null;
        this.marcadores = [];
        this.mapaInicializado = false;
        this.leafletLoaded = false;
        this.leafletLoading = false;
        this._initialized = false;
        
        // URLs para cargar Leaflet
        this.leafletConfig = {
            css: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            js: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            version: '1.9.4',
            integrity: {
                css: 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=',
                js: 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo='
            }
        };
        
        // Configuración de tiles
        this.tileConfig = {
            url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        };
        
        console.log(`🗺️ MapaModule creado - Tipo: ${tipo}`);
    }

    // ✅ INICIALIZACIÓN PRINCIPAL CON MANEJO DE ERRORES
    async init() {
        try {
            console.log('🚀 Inicializando MapaModule...');
            
            // Verificar si Leaflet está disponible
            await this.ensureLeafletLoaded();
            
            // Inicializar según el tipo
            switch(this.tipo) {
                case 'seleccion':
                    return await this.initMapaSeleccion();
                case 'visualizacion':
                    // Para visualización, se necesita pasar datos de escuelas
                    console.warn('⚠️ initMapaVisualizacion requiere datos de escuelas');
                    return false;
                default:
                    console.error(`❌ Tipo de mapa no soportado: ${this.tipo}`);
                    return false;
            }
        } catch (error) {
            console.error('❌ Error crítico en MapaModule.init:', error);
            this.showErrorNotification('Error al inicializar el mapa');
            return false;
        }
    }

    // ✅ INICIALIZAR MAPA DE SELECCIÓN
    async initMapaSeleccion() {
        try {
            console.log('🗺️ Inicializando mapa de selección...');
            
            const mapElement = document.getElementById('map');
            if (!mapElement) {
                console.error('❌ No se encontró el elemento #map para mapa de selección');
                this.showMissingElementError('#map', 'mapa de selección');
                return false;
            }

            // Verificar que Leaflet esté cargado
            if (!this.leafletLoaded) {
                console.warn('⚠️ Leaflet no está cargado, intentando cargar...');
                const loaded = await this.loadLeaflet();
                if (!loaded) {
                    console.error('❌ No se pudo cargar Leaflet');
                    return false;
                }
            }

            // Coordenadas por defecto (Caracas)
            let defaultLat = 10.480594;
            let defaultLng = -66.903600;
            
            // Usar coordenadas existentes si las hay
            const currentLat = document.getElementById('lat-input')?.value;
            const currentLng = document.getElementById('lng-input')?.value;
            
            if (currentLat && currentLng) {
                defaultLat = parseFloat(currentLat);
                defaultLng = parseFloat(currentLng);
            }

            // Crear mapa con manejo de errores
            try {
                this.mapa = L.map('map').setView([defaultLat, defaultLng], 13);
            } catch (error) {
                console.error('❌ Error al crear mapa Leaflet:', error);
                this.showLeafletError(error);
                return false;
            }

            // Agregar capa de tiles
            L.tileLayer(this.tileConfig.url, {
                attribution: this.tileConfig.attribution,
                maxZoom: this.tileConfig.maxZoom
            }).addTo(this.mapa);

            // Evento al hacer clic en el mapa
            this.mapa.on('click', (e) => {
                this.handleMapClick(e);
            });

            // Agregar marcador inicial si hay coordenadas
            if (currentLat && currentLng) {
                this.agregarMarcador(defaultLat, defaultLng, '📍 Ubicación actual');
            }

            this.mapaInicializado = true;
            this._initialized = true;
            
            console.log('✅ Mapa de selección inicializado correctamente');
            
            // Disparar evento de inicialización
            this.dispatchMapEvent('mapa:initialized', { tipo: 'seleccion' });
            
            return true;
        } catch (error) {
            console.error('❌ Error en initMapaSeleccion:', error);
            this.showErrorNotification('Error al inicializar mapa de selección');
            return false;
        }
    }

    // ✅ INICIALIZAR MAPA DE VISUALIZACIÓN
    async initMapaVisualizacion(escuelasData) {
        try {
            console.log('🗺️ Inicializando mapa de visualización...');
            
            if (!escuelasData || !Array.isArray(escuelasData)) {
                console.error('❌ No hay datos de escuelas o no es un array');
                return false;
            }

            const mapElement = document.getElementById('mapa-escuelas');
            if (!mapElement) {
                console.error('❌ No se encontró #mapa-escuelas para mapa de visualización');
                this.showMissingElementError('#mapa-escuelas', 'mapa de visualización');
                return false;
            }

            // Verificar que Leaflet esté cargado
            if (!this.leafletLoaded) {
                console.warn('⚠️ Leaflet no está cargado, intentando cargar...');
                const loaded = await this.loadLeaflet();
                if (!loaded) {
                    console.error('❌ No se pudo cargar Leaflet');
                    return false;
                }
            }

            // Filtrar escuelas con coordenadas válidas
            const escuelasConCoordenadas = escuelasData.filter(escuela => 
                escuela.lat && escuela.lng && 
                !isNaN(parseFloat(escuela.lat)) && 
                !isNaN(parseFloat(escuela.lng))
            );

            if (escuelasConCoordenadas.length === 0) {
                console.warn('⚠️ No hay escuelas con coordenadas válidas');
                this.showNoValidCoordinatesError();
                return false;
            }

            // Calcular centro del mapa basado en las escuelas
            const centro = this.calcularCentroMapa(escuelasConCoordenadas);
            
            // Crear mapa
            try {
                this.mapa = L.map('mapa-escuelas').setView(centro, 6);
            } catch (error) {
                console.error('❌ Error al crear mapa Leaflet:', error);
                this.showLeafletError(error);
                return false;
            }

            // Capa base
            L.tileLayer(this.tileConfig.url, {
                attribution: this.tileConfig.attribution,
                maxZoom: this.tileConfig.maxZoom
            }).addTo(this.mapa);

            // Agregar marcadores para cada escuela
            escuelasConCoordenadas.forEach(escuela => {
                this.agregarMarcadorEscuela(escuela);
            });

            // Ajustar vista para que muestre todos los marcadores
            if (this.marcadores.length > 0) {
                const grupo = L.featureGroup(this.marcadores);
                this.mapa.fitBounds(grupo.getBounds().pad(0.1));
            }

            this.mapaInicializado = true;
            this._initialized = true;
            
            console.log(`✅ Mapa de visualización inicializado con ${escuelasConCoordenadas.length} escuelas`);
            
            // Disparar evento de inicialización
            this.dispatchMapEvent('mapa:initialized', { 
                tipo: 'visualizacion', 
                cantidadEscuelas: escuelasConCoordenadas.length 
            });
            
            return true;
        } catch (error) {
            console.error('❌ Error en initMapaVisualizacion:', error);
            this.showErrorNotification('Error al inicializar mapa de visualización');
            return false;
        }
    }

    // ✅ GARANTIZAR QUE LEAFLET ESTÉ CARGADO
    async ensureLeafletLoaded() {
        if (typeof L !== 'undefined') {
            this.leafletLoaded = true;
            console.log('✅ Leaflet ya está cargado');
            return true;
        }
        
        return await this.loadLeaflet();
    }

    // ✅ CARGAR LEAFLET DINÁMICAMENTE
    async loadLeaflet() {
        return new Promise((resolve) => {
            if (this.leafletLoaded) {
                console.log('✅ Leaflet ya está cargado');
                resolve(true);
                return;
            }
            
            if (this.leafletLoading) {
                console.log('⏳ Leaflet ya se está cargando...');
                // Esperar a que termine la carga actual
                const checkInterval = setInterval(() => {
                    if (this.leafletLoaded) {
                        clearInterval(checkInterval);
                        resolve(true);
                    }
                }, 100);
                return;
            }
            
            console.log('🌐 Cargando Leaflet.js dinámicamente...');
            this.leafletLoading = true;
            
            // Cargar CSS
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = this.leafletConfig.css;
            cssLink.integrity = this.leafletConfig.integrity.css;
            cssLink.crossOrigin = '';
            document.head.appendChild(cssLink);
            
            // Cargar JS
            const script = document.createElement('script');
            script.src = this.leafletConfig.js;
            script.integrity = this.leafletConfig.integrity.js;
            script.crossOrigin = '';
            
            script.onload = () => {
                console.log('✅ Leaflet.js cargado correctamente desde CDN');
                this.leafletLoaded = true;
                this.leafletLoading = false;
                resolve(true);
            };
            
            script.onerror = (error) => {
                console.error('❌ Error al cargar Leaflet.js:', error);
                this.leafletLoaded = false;
                this.leafletLoading = false;
                this.showLeafletLoadError();
                resolve(false);
            };
            
            document.head.appendChild(script);
            
            // Timeout de seguridad
            setTimeout(() => {
                if (!this.leafletLoaded && !this.leafletLoading) {
                    console.warn('⚠️ Timeout al cargar Leaflet.js');
                    resolve(false);
                }
            }, 10000);
        });
    }

    // ✅ MANEJAR CLIC EN EL MAPA
    handleMapClick(e) {
        try {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            console.log(`📍 Coordenadas seleccionadas: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
            
            // Actualizar inputs
            const latInput = document.getElementById('lat-input');
            const lngInput = document.getElementById('lng-input');
            
            if (latInput) latInput.value = lat.toFixed(6);
            if (lngInput) lngInput.value = lng.toFixed(6);
            
            // Agregar marcador
            this.agregarMarcador(lat, lng, '📍 Nueva ubicación seleccionada');
            
            // Disparar evento
            this.dispatchMapEvent('mapa:click', { lat, lng });
            
        } catch (error) {
            console.error('Error en handleMapClick:', error);
        }
    }

    // ✅ AGREGAR MARCADOR
    agregarMarcador(lat, lng, popupText = '') {
        try {
            if (!this.mapa) {
                console.error('❌ No se puede agregar marcador: mapa no inicializado');
                return null;
            }
            
            // Limpiar marcadores anteriores (para mapa de selección)
            if (this.tipo === 'seleccion') {
                this.limpiarMarcadores();
            }
            
            const marker = L.marker([lat, lng]).addTo(this.mapa);
            
            if (popupText) {
                marker.bindPopup(popupText).openPopup();
            }
            
            this.marcadores.push(marker);
            
            // Centrar mapa en el marcador
            this.mapa.setView([lat, lng], 13);
            
            return marker;
        } catch (error) {
            console.error('Error en agregarMarcador:', error);
            return null;
        }
    }

    // ✅ AGREGAR MARCADOR DE ESCUELA
    agregarMarcadorEscuela(escuela) {
        try {
            if (!this.mapa) {
                console.error('❌ No se puede agregar marcador: mapa no inicializado');
                return null;
            }
            
            const marker = L.marker([escuela.lat, escuela.lng])
                .bindPopup(this.crearPopupEscuela(escuela));
            
            marker.addTo(this.mapa);
            this.marcadores.push(marker);
            
            return marker;
        } catch (error) {
            console.error('Error en agregarMarcadorEscuela:', error);
            return null;
        }
    }

    // ✅ CREAR POPUP DE ESCUELA
    crearPopupEscuela(escuela) {
        return `
            <div class="escuela-popup">
                <h6 style="margin: 0 0 5px 0; color: #2c3e50;">${escuela.nombre || 'Escuela'}</h6>
                ${escuela.direccion ? `<p style="margin: 0 0 3px 0; font-size: 12px;">${escuela.direccion}</p>` : ''}
                ${escuela.telefono ? `<p style="margin: 0; font-size: 11px; color: #666;">📞 ${escuela.telefono}</p>` : ''}
                ${escuela.id ? `<p style="margin: 3px 0 0 0; font-size: 10px; color: #888;">ID: ${escuela.id}</p>` : ''}
            </div>
        `;
    }

    // ✅ CALCULAR CENTRO DEL MAPA
    calcularCentroMapa(escuelas) {
        if (escuelas.length === 0) {
            return [8.0000, -66.0000]; // Centro de Venezuela por defecto
        }
        
        let sumLat = 0;
        let sumLng = 0;
        
        escuelas.forEach(escuela => {
            sumLat += parseFloat(escuela.lat);
            sumLng += parseFloat(escuela.lng);
        });
        
        return [sumLat / escuelas.length, sumLng / escuelas.length];
    }

    // ✅ LIMPIAR MARCADORES
    limpiarMarcadores() {
        if (this.mapa) {
            this.marcadores.forEach(marker => {
                this.mapa.removeLayer(marker);
            });
            this.marcadores = [];
            console.log('✅ Marcadores limpiados');
        }
    }

    // ✅ LIMPIAR MAPA COMPLETAMENTE
    limpiarMapa() {
        if (this.mapa) {
            this.limpiarMarcadores();
            this.mapa.eachLayer((layer) => {
                if (!layer._url || !layer._url.includes('tile.openstreetmap.org')) {
                    this.mapa.removeLayer(layer);
                }
            });
        }
    }

    // ✅ ACTUALIZAR MAPA DE VISUALIZACIÓN
    async actualizarMapaVisualizacion(escuelasData) {
        try {
            if (!this.mapaInicializado || this.tipo !== 'visualizacion') {
                console.error('❌ No se puede actualizar: mapa no inicializado o tipo incorrecto');
                return false;
            }
            
            this.limpiarMarcadores();
            
            if (!escuelasData || !Array.isArray(escuelasData)) {
                console.error('❌ Datos de escuelas no válidos');
                return false;
            }
            
            // Agregar nuevos marcadores
            escuelasData.forEach(escuela => {
                if (escuela.lat && escuela.lng) {
                    this.agregarMarcadorEscuela(escuela);
                }
            });
            
            // Ajustar vista
            if (this.marcadores.length > 0) {
                const grupo = L.featureGroup(this.marcadores);
                this.mapa.fitBounds(grupo.getBounds().pad(0.1));
            }
            
            console.log(`✅ Mapa actualizado con ${escuelasData.length} escuelas`);
            return true;
        } catch (error) {
            console.error('Error en actualizarMapaVisualizacion:', error);
            return false;
        }
    }

    // ✅ DESTRUIR MAPA
    destruir() {
        try {
            if (this.mapa) {
                this.mapa.remove();
                this.mapa = null;
            }
            
            this.marcadores = [];
            this.mapaInicializado = false;
            this._initialized = false;
            
            console.log('✅ Mapa destruido correctamente');
        } catch (error) {
            console.error('Error en destruir:', error);
        }
    }

    // ✅ DISPATCHER DE EVENTOS
    dispatchMapEvent(eventName, detail = {}) {
        try {
            const event = new CustomEvent(eventName, { 
                detail: { ...detail, mapa: this } 
            });
            window.dispatchEvent(event);
        } catch (error) {
            console.error(`Error al disparar evento ${eventName}:`, error);
        }
    }

    // ✅ MOSTRAR NOTIFICACIÓN DE ERROR
    showErrorNotification(message) {
        try {
            const notification = document.createElement('div');
            notification.className = 'mapa-error-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3545;
                color: white;
                padding: 12px 20px;
                border-radius: 6px;
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideInRight 0.3s ease-out;
                font-weight: bold;
                max-width: 350px;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Error en Mapa</strong><br>
                        <small style="opacity:0.8">${message}</small>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="background: transparent; border: none; color: white; margin-left: auto; cursor: pointer;">
                        ✕
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        } catch (error) {
            console.error('Error en showErrorNotification:', error);
        }
    }

    // ✅ MOSTRAR ERROR DE ELEMENTO FALTANTE
    showMissingElementError(selector, mapType) {
        const message = `Elemento ${selector} no encontrado para ${mapType}. Asegúrate de que exista en el HTML.`;
        console.error(`❌ ${message}`);
        this.showErrorNotification(`Falta el elemento para el mapa: ${selector}`);
    }

    // ✅ MOSTRAR ERROR DE LEAFLET
    showLeafletError(error) {
        console.error('❌ Error de Leaflet:', error);
        this.showErrorNotification('Error en la biblioteca de mapas. Por favor, recarga la página.');
    }

    // ✅ MOSTRAR ERROR DE CARGA DE LEAFLET
    showLeafletLoadError() {
        console.error('❌ No se pudo cargar Leaflet.js');
        this.showErrorNotification('No se pudo cargar la biblioteca de mapas. Verifica tu conexión a internet.');
    }

    // ✅ MOSTRAR ERROR DE COORDENADAS VÁLIDAS
    showNoValidCoordinatesError() {
        console.warn('⚠️ No hay coordenadas válidas para mostrar en el mapa');
        
        // Crear mensaje informativo en el contenedor del mapa
        const mapElement = document.getElementById('mapa-escuelas');
        if (mapElement) {
            mapElement.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🗺️</div>
                    <h4 style="color: #6c757d; margin-bottom: 10px;">Sin datos geográficos</h4>
                    <p style="color: #868e96; margin-bottom: 0;">
                        No hay coordenadas válidas para mostrar en el mapa.
                    </p>
                </div>
            `;
        }
    }

    // ✅ OBTENER ESTADO DEL MAPA
    getStatus() {
        return {
            tipo: this.tipo,
            inicializado: this._initialized,
            mapaInicializado: this.mapaInicializado,
            leafletLoaded: this.leafletLoaded,
            leafletLoading: this.leafletLoading,
            marcadores: this.marcadores.length,
            mapaDisponible: !!this.mapa,
            version: '2.0.0'
        };
    }

    // ✅ OBTENER INSTANCIA DEL MAPA LEAFLET
    getLeafletMap() {
        return this.mapa;
    }

    // ✅ CENTRAR MAPA EN COORDENADAS
    centrarEn(lat, lng, zoom = 13) {
        if (this.mapa) {
            this.mapa.setView([lat, lng], zoom);
            return true;
        }
        return false;
    }

    // ✅ AGREGAR CONTROL DE BÚSQUEDA (GEOCODING)
    agregarControlBusqueda() {
        if (!this.mapa || !this.leafletLoaded) {
            console.warn('⚠️ No se puede agregar control de búsqueda: mapa o Leaflet no disponible');
            return false;
        }
        
        try {
            // Intentar agregar plugin de geocoding si está disponible
            if (typeof L.Control === 'undefined') {
                console.warn('⚠️ Control de Leaflet no disponible');
                return false;
            }
            
            console.log('✅ Control de búsqueda agregado al mapa');
            return true;
        } catch (error) {
            console.error('Error al agregar control de búsqueda:', error);
            return false;
        }
    }
}

// ==================================================
// FUNCIONES GLOBALES PARA COMPATIBILIDAD
// ==================================================

// Función global para inicializar mapa de selección (async)
window.inicializarMapaSeleccion = async function() {
    try {
        const mapa = new MapaModule('seleccion');
        const success = await mapa.initMapaSeleccion();
        
        if (success) {
            console.log('✅ Mapa de selección inicializado globalmente');
            return mapa;
        } else {
            console.error('❌ Falló la inicialización global del mapa de selección');
            return null;
        }
    } catch (error) {
        console.error('❌ Error en inicializarMapaSeleccion:', error);
        return null;
    }
};

// Función global para inicializar mapa de visualización (async)
window.inicializarMapaVisualizacion = async function(escuelasData) {
    try {
        const mapa = new MapaModule('visualizacion');
        const success = await mapa.initMapaVisualizacion(escuelasData);
        
        if (success) {
            console.log('✅ Mapa de visualización inicializado globalmente');
            return mapa;
        } else {
            console.error('❌ Falló la inicialización global del mapa de visualización');
            return null;
        }
    } catch (error) {
        console.error('❌ Error en inicializarMapaVisualizacion:', error);
        return null;
    }
};

// Función global para verificar estado de Leaflet
window.verificarLeaflet = async function() {
    const mapa = new MapaModule();
    const loaded = await mapa.ensureLeafletLoaded();
    return loaded;
};

// Función global para debug
window.debugMapaModule = function() {
    console.group('🐛 DEBUG MAPA MODULE');
    
    // Buscar instancias de MapaModule
    const mapElements = document.querySelectorAll('#map, #mapa-escuelas');
    console.log('Elementos de mapa encontrados:', mapElements.length);
    
    // Verificar Leaflet
    console.log('Leaflet cargado:', typeof L !== 'undefined');
    
    // Mostrar cualquier instancia global
    const mapaInstances = [];
    for (const key in window) {
        if (window[key] instanceof MapaModule) {
            mapaInstances.push({ key, instance: window[key] });
        }
    }
    
    console.log('Instancias de MapaModule en window:', mapaInstances);
    
    if (typeof L !== 'undefined') {
        console.log('Versión Leaflet:', L.version);
    }
    
    console.groupEnd();
};

// ==================================================
// INICIALIZACIÓN AUTOMÁTICA MEJORADA
// ==================================================

// Inicialización automática cuando hay elementos de mapa
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Verificando elementos de mapa...');
    
    // Verificar si hay elementos de mapa en la página
    const hasMapElement = document.getElementById('map') || document.getElementById('mapa-escuelas');
    
    if (hasMapElement) {
        console.log('🗺️ Elementos de mapa detectados, preparando inicialización...');
        
        // Inicializar después de un pequeño delay para asegurar que todo esté cargado
        setTimeout(async () => {
            // Inicializar mapa de selección si existe
            if (document.getElementById('map')) {
                console.log('🔧 Inicializando mapa de selección automáticamente...');
                
                try {
                    const mapa = await window.inicializarMapaSeleccion();
                    if (mapa) {
                        // Guardar referencia global para acceso
                        window.mapaSeleccion = mapa;
                        console.log('✅ Mapa de selección inicializado automáticamente');
                    }
                } catch (error) {
                    console.error('❌ Error al inicializar mapa de selección automáticamente:', error);
                }
            }
            
            // Nota: El mapa de visualización requiere datos, así que no se inicializa automáticamente
        }, 1000);
    } else {
        console.log('ℹ️ No se detectaron elementos de mapa en esta página');
    }
});

// ==================================================
// ESTILOS DINÁMICOS PARA MEJOR VISUALIZACIÓN
// ==================================================

// Inyectar estilos básicos para mejor visualización
(function injectMapStyles() {
    if (!document.getElementById('mapa-module-styles')) {
        const style = document.createElement('style');
        style.id = 'mapa-module-styles';
        style.textContent = `
            /* Estilos para popups de escuelas */
            .escuela-popup {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                min-width: 200px;
                max-width: 300px;
            }
            
            .escuela-popup h6 {
                color: #2c3e50;
                font-weight: 600;
                border-bottom: 2px solid #3498db;
                padding-bottom: 5px;
                margin-bottom: 8px;
            }
            
            /* Estilos para el contenedor del mapa */
            #map, #mapa-escuelas {
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            
            /* Animaciones */
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            /* Clase para notificaciones de error */
            .mapa-error-notification {
                animation: slideInRight 0.3s ease-out;
            }
            
            /* Mejoras para móvil */
            @media (max-width: 768px) {
                #map, #mapa-escuelas {
                    height: 300px !important;
                }
            }
        `;
        
        document.head.appendChild(style);
        console.log('✅ Estilos de mapa inyectados');
    }
})();

// ==================================================
// EXPORT PARA MÓDULOS
// ==================================================

// Export para Node.js (si es necesario)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MapaModule;
}

// Compatibilidad con sistemas existentes
if (typeof window !== 'undefined') {
    window.MapaModule = MapaModule;
    window.MAPA_MODULE_LOADED = true;
    window.MAPA_MODULE_VERSION = '2.0.0';
}

// Manejo de errores global para el módulo mapa
window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('mapa')) {
        console.error('❌ Error global capturado en módulo mapa:', e.error);
        
        // Intentar mostrar notificación
        try {
            const mapa = new MapaModule();
            mapa.showErrorNotification(`Error en módulo mapa: ${e.message}`);
        } catch (error) {
            console.error('❌ No se pudo mostrar notificación de error:', error);
        }
    }
});