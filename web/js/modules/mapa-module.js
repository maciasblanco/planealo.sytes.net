// web/js/modules/mapa-module.js
class MapaModule {
    constructor(tipo = 'seleccion') {
        this.tipo = tipo; // 'seleccion' o 'visualizacion'
        this.mapa = null;
        this.marcadores = [];
        this.mapaInicializado = false;
    }

    // Métodos para mapa de selección (de mapa-escuela.js)
    initMapaSeleccion() {
        console.log('🗺️ Inicializando mapa de selección...');
        
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            console.error('❌ No se encontró el elemento #map');
            return;
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

        // Crear mapa
        this.mapa = L.map('map').setView([defaultLat, defaultLng], 13);
        
        // Agregar capa de tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.mapa);

        // Evento al hacer clic en el mapa
        this.mapa.on('click', (e) => {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            // Actualizar inputs
            const latInput = document.getElementById('lat-input');
            const lngInput = document.getElementById('lng-input');
            if (latInput) latInput.value = lat.toFixed(6);
            if (lngInput) lngInput.value = lng.toFixed(6);
            
            // Agregar marcador
            this.agregarMarcador(lat, lng, '📍 Nueva ubicación seleccionada');
        });

        this.mapaInicializado = true;
        console.log('✅ Mapa de selección inicializado');
    }

    // Métodos para mapa de visualización (de mapa-escuelas-show.js)
    initMapaVisualizacion(escuelasData) {
        console.log('🗺️ Inicializando mapa de visualización...');
        
        if (!escuelasData || !Array.isArray(escuelasData)) {
            console.error('❌ No hay datos de escuelas');
            return;
        }

        const mapElement = document.getElementById('mapa-escuelas');
        if (!mapElement) {
            console.error('❌ No se encontró #mapa-escuelas');
            return;
        }

        // Centro de Venezuela por defecto
        this.mapa = L.map('mapa-escuelas').setView([8.0000, -66.0000], 6);
        
        // Capa base
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(this.mapa);

        // Agregar marcadores para cada escuela
        escuelasData.forEach(escuela => {
            if (escuela.lat && escuela.lng) {
                this.agregarMarcadorEscuela(escuela);
            }
        });

        this.mapaInicializado = true;
        console.log(`✅ Mapa de visualización inicializado con ${escuelasData.length} escuelas`);
    }

    agregarMarcador(lat, lng, popupText = '') {
        const marker = L.marker([lat, lng]).addTo(this.mapa);
        if (popupText) {
            marker.bindPopup(popupText).openPopup();
        }
        this.marcadores.push(marker);
        return marker;
    }

    agregarMarcadorEscuela(escuela) {
        const marker = L.marker([escuela.lat, escuela.lng])
            .bindPopup(`
                <div class="escuela-popup">
                    <h6>${escuela.nombre}</h6>
                    <p>${escuela.direccion || ''}</p>
                    ${escuela.telefono ? `<p>📞 ${escuela.telefono}</p>` : ''}
                </div>
            `);
        
        marker.addTo(this.mapa);
        this.marcadores.push(marker);
        return marker;
    }

    limpiarMapa() {
        if (this.mapa) {
            this.marcadores.forEach(marker => this.mapa.removeLayer(marker));
            this.marcadores = [];
        }
    }

    destruir() {
        if (this.mapa) {
            this.mapa.remove();
            this.mapa = null;
        }
        this.mapaInicializado = false;
    }
}

// Funciones globales para compatibilidad
window.inicializarMapaSeleccion = function() {
    const mapa = new MapaModule('seleccion');
    mapa.initMapaSeleccion();
    return mapa;
};

window.inicializarMapaVisualizacion = function(escuelasData) {
    const mapa = new MapaModule('visualizacion');
    mapa.initMapaVisualizacion(escuelasData);
    return mapa;
};