// mapa-escuelas-show.js

// Inicializar el mapa
function inicializarMapa() {
    console.log('🔍 Inicializando mapa...');
    
    // Verificar que el contenedor del mapa existe
    const mapaContainer = document.getElementById('mapa-escuelas');
    if (!mapaContainer) {
        console.error('❌ Elemento #mapa-escuelas no encontrado');
        mostrarError('No se pudo encontrar el contenedor del mapa');
        return null;
    }

    // Verificar que existen datos de escuelas
    if (typeof escuelasData === 'undefined' || !Array.isArray(escuelasData)) {
        console.error('❌ Variable escuelasData no definida o no es un array');
        mostrarError('No se encontraron datos de escuelas');
        return null;
    }

    console.log(`📊 Total de escuelas en datos: ${escuelasData.length}`);

    // Centro de Venezuela por defecto
    const centerLat = 8.0000;
    const centerLng = -66.0000;
    const defaultZoom = 6;
    
    try {
        // Crear mapa
        const mapa = L.map('mapa-escuelas').setView([centerLat, centerLng], defaultZoom);
        console.log('🗺️ Mapa creado correctamente');
        
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
        
        let marcadoresCreados = 0;
        let escuelasSinCoordenadas = 0;

        // Crear marcadores para cada escuela
        escuelasData.forEach((escuela, index) => {
            // Verificar que la escuela tiene coordenadas válidas
            if (!escuela.lat || !escuela.lng || 
                isNaN(parseFloat(escuela.lat)) || 
                isNaN(parseFloat(escuela.lng))) {
                console.warn(`⚠️ Escuela sin coordenadas válidas: ${escuela.nombre}`, escuela);
                escuelasSinCoordenadas++;
                return;
            }

            const lat = parseFloat(escuela.lat);
            const lng = parseFloat(escuela.lng);
            
            // Validar rango de coordenadas para Venezuela
            if (lat < 0 || lat > 15 || lng < -75 || lng > -59) {
                console.warn(`⚠️ Coordenadas fuera de rango para Venezuela: ${escuela.nombre}`, {lat, lng});
                escuelasSinCoordenadas++;
                return;
            }

            try {
                const marker = L.marker([lat, lng]);
                
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
                        ${escuela.logo ? `<img src="${escuela.logo}" alt="Logo" class="img-fluid mb-2" style="max-height: 80px;">` : ''}
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
                marcadoresCreados++;
                
                console.log(`✅ Marcador creado: ${escuela.nombre}`, {lat, lng});
                
            } catch (error) {
                console.error(`❌ Error creando marcador para ${escuela.nombre}:`, error);
            }
        });
        
        console.log(`📌 Marcadores creados: ${marcadoresCreados}, Sin coordenadas: ${escuelasSinCoordenadas}`);
        
        // Agregar marcadores al mapa
        if (marcadoresCreados > 0) {
            mapa.addLayer(markers);
            console.log('📌 Marcadores agregados al mapa correctamente');
            
            // Si hay marcadores, ajustar la vista para mostrarlos todos
            if (marcadoresCreados === 1) {
                // Si solo hay un marcador, centrar en él con zoom cercano
                const primeraEscuela = escuelasData.find(e => e.lat && e.lng);
                if (primeraEscuela) {
                    mapa.setView([primeraEscuela.lat, primeraEscuela.lng], 13);
                }
            } else if (marcadoresCreados > 1) {
                // Si hay múltiples marcadores, ajustar la vista para mostrarlos todos
                const group = new L.featureGroup(markers.getLayers());
                mapa.fitBounds(group.getBounds().pad(0.1));
            }
        } else {
            console.warn('⚠️ No se crearon marcadores. Mostrando vista por defecto de Venezuela.');
            mostrarAdvertencia('No se encontraron escuelas con coordenadas válidas para mostrar en el mapa.');
        }
        
        // Configurar controles del mapa
        configurarControles(mapa, markers);
        
        return mapa;
        
    } catch (error) {
        console.error('❌ Error crítico al inicializar el mapa:', error);
        mostrarError('Error al cargar el mapa: ' + error.message);
        return null;
    }
}

// Función para mostrar errores
function mostrarError(mensaje) {
    const infoPanel = document.getElementById('info-panel');
    if (infoPanel) {
        infoPanel.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Error:</strong> ${mensaje}
            </div>
        `;
    }
}

// Función para mostrar advertencias
function mostrarAdvertencia(mensaje) {
    const infoPanel = document.getElementById('info-panel');
    if (infoPanel) {
        infoPanel.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Advertencia:</strong> ${mensaje}
            </div>
        `;
    }
}

// ... (el resto de las funciones configurarControles, crearMarcador, actualizarPanelInfo se mantienen igual)

// Cargar dependencias y inicializar mapa
function inicializarAplicacionMapa() {
    console.log('🚀 Inicializando aplicación de mapa...');
    
    // Verificar que jQuery esté disponible
    if (typeof $ === 'undefined') {
        console.error('❌ jQuery no está disponible');
        mostrarError('Error de configuración: jQuery no está cargado');
        return;
    }
    
    // Cargar Leaflet CSS si no está cargado
    if (!$('link[href*="leaflet"]').length) {
        $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />');
        console.log('🌿 Leaflet CSS cargado');
    }
    
    // Cargar Leaflet JS si no está cargado
    if (typeof L === 'undefined') {
        console.log('📥 Cargando Leaflet...');
        $.getScript('https://unpkg.com/leaflet@1.7.1/dist/leaflet.js')
            .done(function() {
                console.log('✅ Leaflet cargado correctamente');
                cargarPluginClustering();
            })
            .fail(function(jqxhr, settings, exception) {
                console.error('❌ Error cargando Leaflet:', exception);
                mostrarError('No se pudo cargar el mapa. Error: ' + exception);
            });
    } else {
        console.log('✅ Leaflet ya está cargado');
        cargarPluginClustering();
    }
}

// Función para cargar el plugin de clustering
function cargarPluginClustering() {
    if (typeof L.markerClusterGroup !== 'undefined') {
        console.log('✅ Plugin de clustering ya está cargado');
        inicializarMapa();
    } else {
        console.log('📥 Cargando plugin de clustering...');
        
        // Cargar CSS del clustering
        if (!$('link[href*="MarkerCluster"]').length) {
            $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />');
            $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />');
        }
        
        $.getScript('https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js')
            .done(function() {
                console.log('✅ Plugin de clustering cargado correctamente');
                inicializarMapa();
            })
            .fail(function(jqxhr, settings, exception) {
                console.error('❌ Error cargando plugin de clustering:', exception);
                mostrarError('No se pudo cargar el agrupamiento de marcadores. Error: ' + exception);
                // Intentar inicializar sin clustering
                inicializarMapa();
            });
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    console.log('📄 Documento listo, verificando configuración...');
    console.log('Datos disponibles:', typeof escuelasData !== 'undefined' ? escuelasData : 'NO DEFINIDO');
    
    // Pequeño delay para asegurar que todo esté cargado
    setTimeout(function() {
        if (typeof inicializarAplicacionMapa === 'function') {
            inicializarAplicacionMapa();
        } else {
            console.error('❌ La función inicializarAplicacionMapa no está disponible');
            mostrarError('Error de configuración: Funciones del mapa no disponibles');
        }
    }, 500);
});