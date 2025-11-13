/**
 * Mapa interactivo para selección de ubicación de escuelas
 */

// Variable global para controlar si el mapa ya fue inicializado
let mapaInicializado = false;
let mapaEscuela = null;
let marcador = null;

function initMapaEscuela() {
    console.log('🔍 Intentando inicializar mapa para escuela...');
    
    // Si ya está inicializado, no hacer nada
    if (mapaInicializado) {
        console.log('ℹ️ El mapa ya estaba inicializado, omitiendo...');
        return;
    }
    
    // Verificar que el elemento del mapa exista
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('❌ No se encontró el elemento con id "map"');
        return;
    }
    
    console.log('✅ Elemento del mapa encontrado');

    // Cargar Leaflet CSS y JS dinámicamente
    function cargarLeaflet() {
        return new Promise((resolve, reject) => {
            // Verificar si Leaflet ya está cargado
            if (typeof L !== 'undefined') {
                console.log('✅ Leaflet ya está cargado');
                resolve();
                return;
            }

            // Cargar CSS de Leaflet
            if (!document.querySelector('link[href*="leaflet"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                link.crossOrigin = '';
                document.head.appendChild(link);
                console.log('✅ CSS de Leaflet cargado');
            }

            // Cargar JS de Leaflet
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            
            script.onload = function() {
                console.log('✅ Leaflet JS cargado correctamente');
                // Esperar un poco para que todo se inicialice
                setTimeout(resolve, 100);
            };
            
            script.onerror = function() {
                console.error('❌ Error cargando Leaflet');
                reject(new Error('No se pudo cargar Leaflet'));
            };
            
            document.head.appendChild(script);
        });
    }

    // Función principal para crear el mapa
    function crearMapa() {
        console.log('🗺️ Creando mapa interactivo...');
        
        try {
            // Verificar si el contenedor ya tiene un mapa
            if (mapElement._leaflet_id) {
                console.log('🔄 El contenedor ya tiene un mapa, limpiando...');
                // Si ya existe un mapa, limpiar el contenedor
                mapElement._leaflet_id = null;
                mapElement.innerHTML = '';
            }
            
            // Coordenadas por defecto (Caracas)
            let defaultLat = 10.480594;
            let defaultLng = -66.903600;
            
            // Usar coordenadas del modelo si existen
            const currentLat = $('#lat-input').val();
            const currentLng = $('#lng-input').val();
            
            if (currentLat && currentLng) {
                defaultLat = parseFloat(currentLat);
                defaultLng = parseFloat(currentLng);
                console.log('📍 Usando coordenadas existentes:', defaultLat, defaultLng);
            } else {
                console.log('📍 Usando coordenadas por defecto:', defaultLat, defaultLng);
            }
            
            // Crear mapa
            mapaEscuela = L.map('map').setView([defaultLat, defaultLng], 13);
            console.log('✅ Mapa creado');
            
            // Agregar capa de tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(mapaEscuela);
            console.log('✅ Capa de tiles agregada');
            
            // Si hay coordenadas existentes, agregar marcador
            if (currentLat && currentLng) {
                marcador = L.marker([defaultLat, defaultLng]).addTo(mapaEscuela)
                    .bindPopup('📍 Ubicación actual de la escuela/club')
                    .openPopup();
                console.log('✅ Marcador existente agregado');
            }
            
            // Evento al hacer clic en el mapa
            mapaEscuela.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                
                console.log('🖱️ Click en mapa - Coordenadas:', lat, lng);
                
                // Actualizar inputs
                $('#lat-input').val(lat.toFixed(6));
                $('#lng-input').val(lng.toFixed(6));
                
                // Remover marcador anterior si existe
                if (marcador) {
                    mapaEscuela.removeLayer(marcador);
                }
                
                // Agregar nuevo marcador
                marcador = L.marker([lat, lng]).addTo(mapaEscuela)
                    .bindPopup('📍 Nueva ubicación seleccionada')
                    .openPopup();
                
                console.log('✅ Nuevo marcador agregado');
            });
            
            // Forzar redimensionamiento del mapa
            setTimeout(() => {
                if (mapaEscuela) {
                    mapaEscuela.invalidateSize();
                    console.log('✅ Mapa redimensionado');
                }
            }, 100);
            
            // Marcar como inicializado
            mapaInicializado = true;
            console.log('🎉 Mapa interactivo inicializado correctamente');
            
        } catch (error) {
            console.error('❌ Error al crear el mapa:', error);
            // Mostrar mensaje de error en el contenedor del mapa
            document.getElementById('map').innerHTML = 
                '<div style="padding: 20px; text-align: center; color: #dc3545;">' +
                '<i class="fas fa-exclamation-triangle"></i><br>' +
                'Error al cargar el mapa. Por favor, recargue la página.' +
                '</div>';
        }
    }

    // Inicializar el mapa
    cargarLeaflet()
        .then(() => {
            // Usar jQuery si está disponible, si no usar vanilla JS
            if (typeof $ !== 'undefined') {
                $(document).ready(function() {
                    // Pequeño delay para asegurar que el DOM esté listo
                    setTimeout(crearMapa, 100);
                });
            } else {
                // Si no hay jQuery, usar vanilla JS
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(crearMapa, 100);
                    });
                } else {
                    setTimeout(crearMapa, 100);
                }
            }
        })
        .catch(error => {
            console.error('❌ Error inicializando el mapa:', error);
            document.getElementById('map').innerHTML = 
                '<div style="padding: 20px; text-align: center; color: #dc3545;">' +
                '<i class="fas fa-exclamation-triangle"></i><br>' +
                'No se pudo cargar el mapa. Verifique su conexión a internet.' +
                '</div>';
        });
}

// Función para destruir el mapa (útil si necesitas recargarlo)
function destruirMapaEscuela() {
    if (mapaEscuela) {
        mapaEscuela.remove();
        mapaEscuela = null;
    }
    if (marcador) {
        marcador = null;
    }
    mapaInicializado = false;
    console.log('🗑️ Mapa destruido');
}

// Hacer las funciones disponibles globalmente
window.initMapaEscuela = initMapaEscuela;
window.destruirMapaEscuela = destruirMapaEscuela;

// Auto-inicialización solo si no hay otra inicialización programada
if (document.readyState !== 'loading') {
    // Si el DOM ya está listo, verificar si hay un mapa en la página y no está inicializado
    if (document.getElementById('map') && !mapaInicializado) {
        console.log('🚀 Auto-inicializando mapa...');
        // Usar un timeout más largo para evitar conflictos
        setTimeout(initMapaEscuela, 1000);
    }
} else {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Inicializando mapa después de DOMContentLoaded...');
        if (document.getElementById('map') && !mapaInicializado) {
            setTimeout(initMapaEscuela, 1000);
        }
    });
}