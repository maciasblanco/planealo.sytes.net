// mapa-escuelas-show.js

// Inicializar el mapa
function inicializarMapa() {
    // Centro de Venezuela por defecto
    const centerLat = 8.0000;
    const centerLng = -66.0000;
    const defaultZoom = 6;
    
    // Crear mapa
    const mapa = L.map('mapa-escuelas').setView([centerLat, centerLng], defaultZoom);
    
    // Capa base de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(mapa);
    
    // Grupo de marcadores para clustering
    const markers = L.markerClusterGroup({
        chunkedLoading: true,
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: true,
        zoomToBoundsOnClick: true
    });
    
    // Crear marcadores para cada escuela
    escuelasData.forEach(escuela => {
        const marker = L.marker([escuela.lat, escuela.lng]);
        
        // Icono personalizado según el tipo
        const iconColor = escuela.tipo === 'Escuela' ? 'green' : 'blue';
        const iconHtml = `<i class="fas fa-map-marker-alt fa-2x" style="color: ${iconColor}"></i>`;
        
        marker.setIcon(L.divIcon({
            html: iconHtml,
            iconSize: [30, 30],
            className: 'custom-marker-icon'
        }));
        
        // Tooltip (al pasar el cursor)
        marker.bindTooltip(`
            <div class="escuela-tooltip">
                <strong>${escuela.nombre}</strong><br>
                <small>${escuela.direccion}</small><br>
                <small><i class="fas fa-phone"></i> ${escuela.telefono}</small>
            </div>
        `, {
            permanent: false,
            direction: 'top',
            offset: [0, -10],
            className: 'escuela-tooltip-container'
        });
        
        // Popup (al hacer click)
        const popupContent = `
            <div class="escuela-popup">
                <div class="text-center mb-2">
                    <h6 class="font-weight-bold mb-1">${escuela.nombre}</h6>
                    <span class="badge badge-${escuela.tipo === 'Escuela' ? 'success' : 'info'}">${escuela.tipo}</span>
                </div>
                ${escuela.logo ? `<img src="${escuela.logo}" alt="Logo" class="img-fluid mb-2">` : ''}
                <div class="escuela-info">
                    <p class="mb-1"><strong><i class="fas fa-map-marker-alt"></i> Dirección:</strong></p>
                    <p class="mb-2">${escuela.direccion}</p>
                    
                    <p class="mb-1"><strong><i class="fas fa-phone"></i> Teléfono:</strong></p>
                    <p class="mb-2">${escuela.telefono}</p>
                    
                    ${escuela.email ? `
                    <p class="mb-1"><strong><i class="fas fa-envelope"></i> Email:</strong></p>
                    <p class="mb-2">${escuela.email}</p>
                    ` : ''}
                    
                    <div class="text-center mt-3">
                        <a href="${escuela.url}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        marker.bindPopup(popupContent, {
            maxWidth: 300,
            className: 'escuela-popup-container'
        });
        
        // Evento para resaltar en la lista
        marker.on('click', function() {
            // Resaltar en la lista
            $('.escuela-item').removeClass('active');
            $(`.escuela-item[data-id="${escuela.id}"]`).addClass('active');
            
            // Actualizar panel de información
            actualizarPanelInfo(escuela);
        });
        
        markers.addLayer(marker);
    });
    
    // Agregar marcadores al mapa
    mapa.addLayer(markers);
    
    // Configurar controles del mapa
    configurarControles(mapa, markers);
    
    return mapa;
}

// Configurar controles del mapa
function configurarControles(mapa, markers) {
    // Controles del mapa
    $('#zoom-in').on('click', function() {
        mapa.zoomIn();
    });
    
    $('#zoom-out').on('click', function() {
        mapa.zoomOut();
    });
    
    $('#reset-view').on('click', function() {
        mapa.setView([8.0000, -66.0000], 6);
    });
    
    $('#mi-ubicacion').on('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                mapa.setView([position.coords.latitude, position.coords.longitude], 13);
            }, function() {
                alert('No se pudo obtener su ubicación');
            });
        } else {
            alert('La geolocalización no es soportada por este navegador');
        }
    });
    
    // Filtro por tipo
    $('#filtro-tipo').on('change', function() {
        const tipo = $(this).val();
        markers.clearLayers();
        
        escuelasData.forEach(escuela => {
            if (tipo === 'todos' || 
                (tipo === 'escuela' && escuela.tipo === 'Escuela') ||
                (tipo === 'club' && escuela.tipo === 'Club')) {
                
                crearMarcador(escuela, markers);
            }
        });
    });
    
    // Búsqueda de escuelas
    $('#buscar-escuela').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.escuela-item').each(function() {
            const escuelaText = $(this).text().toLowerCase();
            if (escuelaText.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Click en items de la lista
    $('.escuela-item').on('click', function(e) {
        e.preventDefault();
        const lat = $(this).data('lat');
        const lng = $(this).data('lng');
        const id = $(this).data('id');
        
        // Centrar mapa en la escuela
        mapa.setView([lat, lng], 15);
        
        // Resaltar item
        $('.escuela-item').removeClass('active');
        $(this).addClass('active');
        
        // Encontrar y abrir popup del marcador
        markers.eachLayer(function(layer) {
            if (layer.getLatLng().lat === lat && layer.getLatLng().lng === lng) {
                layer.openPopup();
                
                // Encontrar datos de la escuela
                const escuela = escuelasData.find(e => e.id === id);
                if (escuela) {
                    actualizarPanelInfo(escuela);
                }
            }
        });
    });
}

// Crear marcador individual
function crearMarcador(escuela, markers) {
    const marker = L.marker([escuela.lat, escuela.lng]);
    const iconColor = escuela.tipo === 'Escuela' ? 'green' : 'blue';
    const iconHtml = `<i class="fas fa-map-marker-alt fa-2x" style="color: ${iconColor}"></i>`;
    
    marker.setIcon(L.divIcon({
        html: iconHtml,
        iconSize: [30, 30],
        className: 'custom-marker-icon'
    }));
    
    marker.bindTooltip(`
        <div class="escuela-tooltip">
            <strong>${escuela.nombre}</strong><br>
            <small>${escuela.direccion}</small><br>
            <small><i class="fas fa-phone"></i> ${escuela.telefono}</small>
        </div>
    `);
    
    const popupContent = `
        <div class="escuela-popup">
            <div class="text-center mb-2">
                <h6 class="font-weight-bold mb-1">${escuela.nombre}</h6>
                <span class="badge badge-${escuela.tipo === 'Escuela' ? 'success' : 'info'}">${escuela.tipo}</span>
            </div>
            ${escuela.logo ? `<img src="${escuela.logo}" alt="Logo" class="img-fluid mb-2">` : ''}
            <div class="escuela-info">
                <p class="mb-1"><strong><i class="fas fa-map-marker-alt"></i> Dirección:</strong></p>
                <p class="mb-2">${escuela.direccion}</p>
                <p class="mb-1"><strong><i class="fas fa-phone"></i> Teléfono:</strong></p>
                <p class="mb-2">${escuela.telefono}</p>
                ${escuela.email ? `<p class="mb-1"><strong><i class="fas fa-envelope"></i> Email:</strong></p>
                <p class="mb-2">${escuela.email}</p>` : ''}
                <div class="text-center mt-3">
                    <a href="${escuela.url}" class="btn btn-primary btn-sm" target="_blank">
                        <i class="fas fa-eye"></i> Ver Detalles
                    </a>
                </div>
            </div>
        </div>
    `;
    
    marker.bindPopup(popupContent, { maxWidth: 300 });
    
    marker.on('click', function() {
        $('.escuela-item').removeClass('active');
        $(`.escuela-item[data-id="${escuela.id}"]`).addClass('active');
        actualizarPanelInfo(escuela);
    });
    
    markers.addLayer(marker);
}

// Actualizar panel de información
function actualizarPanelInfo(escuela) {
    const infoHtml = `
        <div class="escuela-detalle">
            <h6 class="font-weight-bold text-primary">${escuela.nombre}</h6>
            <span class="badge badge-${escuela.tipo === 'Escuela' ? 'success' : 'info'} mb-2">${escuela.tipo}</span>
            
            ${escuela.logo ? `<img src="${escuela.logo}" alt="Logo" class="img-fluid rounded mb-2" style="max-height: 100px;">` : ''}
            
            <div class="escuela-info-detalle">
                <p class="mb-1"><strong><i class="fas fa-map-marker-alt"></i> Dirección:</strong></p>
                <p class="mb-2">${escuela.direccion}</p>
                
                <p class="mb-1"><strong><i class="fas fa-phone"></i> Teléfono:</strong></p>
                <p class="mb-2">${escuela.telefono}</p>
                
                ${escuela.email ? `
                <p class="mb-1"><strong><i class="fas fa-envelope"></i> Email:</strong></p>
                <p class="mb-2">${escuela.email}</p>
                ` : ''}
                
                <div class="text-center mt-3">
                    <a href="${escuela.url}" class="btn btn-primary btn-sm" target="_blank">
                        <i class="fas fa-eye"></i> Ver Detalles Completos
                    </a>
                </div>
            </div>
        </div>
    `;
    
    $('#info-panel').html(infoHtml);
}

// Cargar dependencias y inicializar mapa
function inicializarAplicacionMapa() {
    // Cargar Leaflet CSS si no está cargado
    if (!$('link[href*="leaflet"]').length) {
        $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />');
    }
    
    // Cargar Leaflet JS si no está cargado
    if (typeof L === 'undefined') {
        $.getScript('https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', function() {
            // Cargar plugin de clustering
            $.getScript('https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js', function() {
                // Cargar CSS del clustering
                $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />');
                $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />');
                
                inicializarMapa();
            });
        });
    } else {
        // Si Leaflet ya está cargado, inicializar directamente
        if (typeof L.markerClusterGroup !== 'undefined') {
            inicializarMapa();
        } else {
            // Cargar plugin de clustering
            $.getScript('https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js', function() {
                $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />');
                $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />');
                inicializarMapa();
            });
        }
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    inicializarAplicacionMapa();
});