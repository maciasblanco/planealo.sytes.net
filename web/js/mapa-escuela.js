/**
 * Mapa interactivo para selección de ubicación de escuelas
 */

function initMapaEscuela() {
    console.log('🔍 Inicializando mapa para escuela...');
    
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
            const map = L.map('map').setView([defaultLat, defaultLng], 13);
            console.log('✅ Mapa creado');
            
            // Agregar capa de tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            console.log('✅ Capa de tiles agregada');
            
            let marker = null;
            
            // Si hay coordenadas existentes, agregar marcador
            if (currentLat && currentLng) {
                marker = L.marker([defaultLat, defaultLng]).addTo(map)
                    .bindPopup('📍 Ubicación actual de la escuela/club')
                    .openPopup();
                console.log('✅ Marcador existente agregado');
            }
            
            // Evento al hacer clic en el mapa
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                
                console.log('🖱️ Click en mapa - Coordenadas:', lat, lng);
                
                // Actualizar inputs
                $('#lat-input').val(lat.toFixed(6));
                $('#lng-input').val(lng.toFixed(6));
                
                // Remover marcador anterior si existe
                if (marker) {
                    map.removeLayer(marker);
                }
                
                // Agregar nuevo marcador
                marker = L.marker([lat, lng]).addTo(map)
                    .bindPopup('📍 Nueva ubicación seleccionada')
                    .openPopup();
                
                console.log('✅ Nuevo marcador agregado');
            });
            
            // Forzar redimensionamiento del mapa
            setTimeout(() => {
                map.invalidateSize();
                console.log('✅ Mapa redimensionado');
            }, 100);
            
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
            // Esperar a que jQuery esté listo si se usa
            if (typeof $ !== 'undefined') {
                $(document).ready(crearMapa);
            } else {
                // Si no hay jQuery, usar DOMContentLoaded
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', crearMapa);
                } else {
                    crearMapa();
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

// Hacer la función disponible globalmente
window.initMapaEscuela = initMapaEscuela;

// Auto-inicialización si el script se carga después de que el DOM está listo
if (document.readyState !== 'loading') {
    // Si el DOM ya está listo, verificar si hay un mapa en la página
    if (document.getElementById('map')) {
        console.log('🚀 Auto-inicializando mapa...');
        setTimeout(initMapaEscuela, 100);
    }
} else {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Inicializando mapa después de DOMContentLoaded...');
        setTimeout(initMapaEscuela, 100);
    });
}