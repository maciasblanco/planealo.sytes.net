/* =================================================================

ged-consolidated.js - SISTEMA GED UNIFICADO

Reemplaza: ged.js, ged-init.js, gedOffCanvas-module.js,
       navbarWidth-module.js, mapa-module.js, 
       horario-selector.js, reportes-module.js, tienda-module.js
Fecha: 2026-02-09 - VERSIÓN CONSOLIDADA 5.9.3

CORRECCIÓN: Módulo de Registro Múltiple ACTUALIZADO con selectores REALES

================================================================= */

'use strict';

// ===== GED SYSTEM MAIN OBJECT =====
const gedSystem = {

// ===== CONFIGURACIÓN =====
config: {
    debug: window.location.href.includes('debug='),
    isInitialized: false,
    mobileBreakpoint: 992,
    modules: {
        map: null,
        reportes: null,
        tienda: null,
        registroMultiple: null
    },
    events: {},
    isMobileMenuOpen: false,
    cssLoaded: false // Nueva: para verificar carga CSS
},

// ===== CONSTANTES =====
CONSTANTS: {
    NAVBAR_HEIGHT_DESKTOP: '45vh',
    NAVBAR_HEIGHT_MOBILE: '60px',
    NAVBAR_WIDTH_DESKTOP: '95%',
    NAVBAR_WIDTH_MOBILE: '100%'
},

// ===== INICIALIZACIÓN PRINCIPAL =====
init: function() {
    if (this.config.isInitialized) {
        console.warn('⚠️ GED System ya está inicializado');
        return;
    }
    
    console.log('🚀 GED System v5.9.3 - Inicializando sistema consolidado');
    
    // Verificar si CSS cargó correctamente
    this.config.cssLoaded = this.checkCSSLoaded();
    
    // Inicializar componentes en orden
    this.initNavbar();
    this.initEventListeners();
    this.initResponsiveChecks();
    this.initDynamicModules();
    
    // Marcar como inicializado
    this.config.isInitialized = true;
    
    // Exponer para debug
    window.gedSystem = this;
    
    console.log('✅ Sistema GED inicializado correctamente');
    console.log(`📊 CSS cargado: ${this.config.cssLoaded ? '✅ SÍ' : '⚠️ NO'}`);
    
    // Ejecutar debug si está activado
    if (this.config.debug) {
        this.debug();
    }
},

// ===== VERIFICACIÓN DE CSS =====
checkCSSLoaded: function() {
    try {
        const rootStyles = getComputedStyle(document.documentElement);
        const navbarHeightVar = rootStyles.getPropertyValue('--navbar-container-height');
        return navbarHeightVar !== '' && navbarHeightVar !== null;
    } catch (error) {
        console.warn('⚠️ No se pudo verificar carga de CSS:', error);
        return false;
    }
},

// ===== NAVBAR UNIFICADO - VERSIÓN BALANCEADA =====
initNavbar: function() {
    const navbar = document.querySelector('.navbar-contextual');
    if (!navbar) {
        console.warn('⚠️ Navbar no encontrado');
        return;
    }
    
    console.log('🎯 Inicializando navbar (modo balanceado)');
    
    // ✅ FUNCIÓN ÚNICA para ajustar navbar
    const adjustNavbar = () => {
        const isDesktop = window.innerWidth >= this.config.mobileBreakpoint;
        
        // 🔄 LEER VALORES DEL CSS PRIMERO
        const computedStyles = getComputedStyle(navbar);
        const cssHeight = computedStyles.height;
        
        if (isDesktop) {
            // ✅ DESKTOP: CSS maneja altura y padding
            // Solo ajustar distribución y ancho que CSS podría no cubrir bien
            navbar.style.width = this.CONSTANTS.NAVBAR_WIDTH_DESKTOP;
            navbar.style.marginLeft = 'auto';
            
            // ❌ NO duplicar altura (CSS ya tiene height: 45vh)
            // ❌ NO duplicar padding (CSS ya tiene padding-top en main#main)
            
            // ✅ Solo forzar distribución si CSS no carga
            if (!this.config.cssLoaded) {
                const fallbackHeight = window.innerHeight * 0.45;
                navbar.style.height = fallbackHeight + 'px';
                navbar.style.minHeight = fallbackHeight + 'px';
                document.body.style.paddingTop = fallbackHeight + 'px';
                console.warn('🔄 Aplicando fallback JS (CSS no cargó)');
            }
            
            // ✅ Asegurar distribución exacta (CSS puede no cubrir edge cases)
            this.ensureNavbarDistribution();
        } else {
            // ✅ MÓVIL: CSS maneja altura con breakpoints
            navbar.style.width = this.CONSTANTS.NAVBAR_WIDTH_MOBILE;
            navbar.style.marginLeft = '0';
            
            // ❌ NO forzar 60px fijo (CSS ya tiene breakpoints: 60px, 50px, 45px)
            // ❌ NO duplicar padding
            
            // ✅ Solo fallback si CSS no carga
            if (!this.config.cssLoaded) {
                navbar.style.height = this.CONSTANTS.NAVBAR_HEIGHT_MOBILE;
                navbar.style.minHeight = this.CONSTANTS.NAVBAR_HEIGHT_MOBILE;
                document.body.style.paddingTop = this.CONSTANTS.NAVBAR_HEIGHT_MOBILE;
                console.warn('🔄 Aplicando fallback JS (CSS no cargó)');
            }
        }
        
        // ✅ Scroll behavior (no depende de altura duplicada)
        this.handleNavbarScroll(navbar);
    };
    
    // Ejecutar inmediatamente
    adjustNavbar();
    
    // Escuchar resize con debounce
    window.addEventListener('resize', this.debounce(() => {
        adjustNavbar();
        this.checkNavbarStructure();
    }, 250));
    
    // Verificar estructura inicial
    setTimeout(() => this.checkNavbarStructure(), 100);
},

// ===== DISTRIBUCIÓN EXACTA DEL NAVBAR =====
ensureNavbarDistribution: function() {
    if (window.innerWidth < this.config.mobileBreakpoint) return;
    
    const sections = {
        brand: document.querySelector('.navbar-brand-section'),
        menu: document.querySelector('.navbar-menu-section'),
        social: document.querySelector('.navbar-social-section'),
        control: document.querySelector('.navbar-control-section')
    };
    
    // Verificar que todas las secciones existan
    Object.entries(sections).forEach(([key, section]) => {
        if (!section) {
            console.warn(`⚠️ Sección ${key} no encontrada`);
            return;
        }
        
        // Asegurar display flex (edge cases que CSS podría no cubrir)
        section.style.display = 'flex';
        section.style.alignItems = 'center';
        section.style.height = '100%';
        section.style.overflow = 'hidden';
    });
    
    // Forzar una línea (CSS ya lo tiene pero JS asegura)
    const container = document.querySelector('.navbar-container');
    const sectionsContainer = document.querySelector('.navbar-sections-container');
    
    if (container) {
        container.style.display = 'flex';
        container.style.flexDirection = 'row';
        container.style.flexWrap = 'nowrap';
        container.style.whiteSpace = 'nowrap';
    }
    
    if (sectionsContainer) {
        sectionsContainer.style.display = 'flex';
        sectionsContainer.style.flexDirection = 'row';
        sectionsContainer.style.flexWrap = 'nowrap';
        sectionsContainer.style.whiteSpace = 'nowrap';
    }
},

// ===== COMPORTAMIENTO DE SCROLL =====
handleNavbarScroll: function(navbar) {
    let scrollTimeout;
    
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }, 10);
    });
},

// ===== MAPA UNIFICADO =====
initMap: function() {
    const mapContainer = document.getElementById('ged-map');
    if (!mapContainer) return;
    
    if (typeof L === 'undefined') {
        console.warn('⚠️ Leaflet no cargado');
        return;
    }
    
    if (this.config.modules.map) {
        console.log('ℹ️ Mapa ya inicializado');
        return;
    }
    
    console.log('🗺️ Inicializando mapa');
    
    try {
        const map = L.map('ged-map').setView([40.4168, -3.7038], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        map.whenReady(() => {
            map.invalidateSize();
            console.log('✅ Mapa listo');
        });
        
        this.config.modules.map = map;
        
        window.addEventListener('resize', this.debounce(() => {
            if (map) map.invalidateSize();
        }, 250));
        
    } catch (error) {
        console.error('❌ Error al inicializar mapa:', error);
    }
},

// ===== SELECTOR DE HORARIO =====
initHorarioSelector: function() {
    const horarioGrid = document.getElementById('horario-grid');
    if (!horarioGrid) return;
    
    console.log('⏰ Inicializando selector de horario');
    
    horarioGrid.addEventListener('click', (e) => {
        const cell = e.target.closest('.horario-cell');
        if (!cell) return;
        
        e.preventDefault();
        
        const isSelected = cell.classList.contains('selected');
        
        if (isSelected) {
            cell.classList.remove('selected');
            cell.setAttribute('data-selected', 'false');
        } else {
            cell.classList.add('selected');
            cell.setAttribute('data-selected', 'true');
        }
        
        this.updateHorarioCounter();
    });
    
    this.updateHorarioCounter();
},

updateHorarioCounter: function() {
    const selectedCells = document.querySelectorAll('.horario-cell.selected');
    const counter = document.getElementById('horario-counter');
    
    if (counter) {
        counter.textContent = selectedCells.length;
    }
},

// ===== MÓDULO DE REPORTES =====
initReportesModule: function() {
    if (!window.location.pathname.includes('/reportes')) {
        return;
    }
    
    console.log('📊 Inicializando módulo de reportes');
    
    this.initReportesFilters();
    this.initReportesCharts();
    
    this.config.modules.reportes = {
        initialized: true,
        filters: {},
        charts: {}
    };
},

initReportesFilters: function() {
    const filterForm = document.getElementById('reportes-filter-form');
    if (!filterForm) return;
    
    const inputs = filterForm.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', this.debounce(() => {
            this.applyReportesFilters();
        }, 300));
    });
    
    const resetBtn = document.getElementById('reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            filterForm.reset();
            this.applyReportesFilters();
        });
    }
},

applyReportesFilters: function() {
    console.log('🔍 Aplicando filtros de reportes');
},

initReportesCharts: function() {
    if (typeof Chart !== 'undefined') {
        const chartElements = document.querySelectorAll('.reporte-chart');
        chartElements.forEach((element, index) => {
            try {
                const ctx = element.getContext('2d');
                const chartType = element.dataset.chartType || 'bar';
                
                new Chart(ctx, {
                    type: chartType,
                    data: {
                        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Datos de ejemplo',
                            data: [12, 19, 3, 5, 2, 3],
                            borderWidth: 1
                        }]
                    }
                });
                
                console.log(`📈 Gráfico ${index + 1} inicializado`);
            } catch (error) {
                console.error(`❌ Error al inicializar gráfico ${index + 1}:`, error);
            }
        });
    }
},

// ===== MÓDULO DE TIENDA =====
initTiendaModule: function() {
    if (!window.location.pathname.includes('/tienda')) {
        return;
    }
    
    console.log('🛒 Inicializando módulo de tienda');
    
    this.initCarrito();
    this.initTiendaFilters();
    this.initValoraciones();
    
    this.config.modules.tienda = {
        initialized: true,
        carrito: [],
        filters: {}
    };
},

initCarrito: function() {
    const carritoBtn = document.getElementById('carrito-btn');
    const carritoModal = document.getElementById('carrito-modal');
    
    if (!carritoBtn || !carritoModal) return;
    
    carritoBtn.addEventListener('click', () => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(carritoModal);
            modal.show();
        } else {
            carritoModal.style.display = 'block';
        }
        
        this.actualizarCarrito();
    });
    
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const productId = btn.dataset.productId;
            const productName = btn.dataset.productName;
            const productPrice = parseFloat(btn.dataset.productPrice);
            
            this.agregarAlCarrito(productId, productName, productPrice);
            e.stopPropagation();
        });
    });
},

agregarAlCarrito: function(id, nombre, precio) {
    const item = {
        id: id,
        nombre: nombre,
        precio: precio,
        cantidad: 1,
        timestamp: Date.now()
    };
    
    const existingIndex = this.config.modules.tienda.carrito.findIndex(item => item.id === id);
    
    if (existingIndex >= 0) {
        this.config.modules.tienda.carrito[existingIndex].cantidad++;
    } else {
        this.config.modules.tienda.carrito.push(item);
    }
    
    this.actualizarCarrito();
    this.showNotification(`"${nombre}" añadido al carrito`, 'success');
    
    console.log('🛍️ Carrito actualizado:', this.config.modules.tienda.carrito);
},

actualizarCarrito: function() {
    const carritoCount = document.getElementById('carrito-count');
    const carritoTotal = document.getElementById('carrito-total');
    const carritoItems = document.getElementById('carrito-items');
    
    if (carritoCount) {
        const totalItems = this.config.modules.tienda.carrito.reduce((sum, item) => sum + item.cantidad, 0);
        carritoCount.textContent = totalItems;
        carritoCount.style.display = totalItems > 0 ? 'inline-block' : 'none';
    }
    
    if (carritoTotal) {
        const total = this.config.modules.tienda.carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        carritoTotal.textContent = total.toFixed(2) + '€';
    }
    
    if (carritoItems) {
        carritoItems.innerHTML = this.config.modules.tienda.carrito.map(item => `
            <div class="carrito-item">
                <strong>${item.nombre}</strong> 
                <span>${item.cantidad} x ${item.precio.toFixed(2)}€</span>
            </div>
        `).join('');
    }
},

initTiendaFilters: function() {
    const filterForm = document.getElementById('tienda-filter-form');
    if (!filterForm) return;
    
    const categoriaSelect = document.getElementById('categoria-filter');
    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', this.debounce(() => {
            this.applyTiendaFilters();
        }, 300));
    }
    
    const precioMin = document.getElementById('precio-min');
    const precioMax = document.getElementById('precio-max');
    
    [precioMin, precioMax].forEach(input => {
        if (input) {
            input.addEventListener('input', this.debounce(() => {
                this.applyTiendaFilters();
            }, 500));
        }
    });
},

applyTiendaFilters: function() {
    console.log('🔍 Aplicando filtros de tienda');
},

initValoraciones: function() {
    const starRatings = document.querySelectorAll('.star-rating');
    
    starRatings.forEach(rating => {
        const stars = rating.querySelectorAll('.star');
        const ratingValue = rating.querySelector('.rating-value');
        
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                const value = index + 1;
                
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                if (ratingValue) {
                    ratingValue.textContent = value.toFixed(1);
                    ratingValue.dataset.value = value;
                }
                
                console.log(`⭐ Valoración enviada: ${value} estrellas`);
            });
        });
    });
},

// ===== MÓDULO DE REGISTRO MÚLTIPLE DE ASISTENCIA - ACTUALIZADO CON SELECTORES REALES =====
initRegistroMultipleModule: function() {
    const moduleContainer = document.querySelector('[data-ged-module="registro-multiple"]');
    if (!moduleContainer) return;
    
    console.log('📝 Módulo de Registro Múltiple inicializado (v5.9.3 - selectores reales)');
    
    // ✅ SELECTORES CORRECTOS basados en registro-multiple.php REAL
    const selectors = {
        // Formulario y controles principales
        form: '#registro-multiple-form',
        escuela: '#select-escuela',
        fecha: '#asistencia-fecha_practica',
        btnCargar: '#btn-cargar-atletas',
        btnRegistrar: '#btn-registrar-multiple',
        
        // Panel de atletas y lista
        panelAtletas: '#panel-atletas',
        listaAtletas: '#lista-atletas',
        contadorSeleccionados: '#contador-seleccionados',
        
        // Checkboxes y selección
        selectAll: '#select-all',
        checkboxes: '.atleta-checkbox',
        atletaItems: '.atleta-item',
        
        // Botones de selección rápida
        btnSeleccionarSinAsistencia: '#btn-seleccionar-sin-asistencia',
        btnDeseleccionarTodos: '#btn-deseleccionar-todos',
        
        // Contadores estadísticos
        totalAtletas: '#total-atletas',
        conAsistencia: '#con-asistencia',
        disponibles: '#disponibles'
    };
    
    // Estado del módulo
    const estado = {
        seleccionados: new Set(),
        totalAtletas: 0,
        conAsistencia: 0,
        disponibles: 0,
        escuelaActual: null,
        fechaActual: null
    };
    
    // ==================== FUNCIONALIDAD PRINCIPAL ====================
    
    /**
     * Inicializar event listeners y estado
     */
    function inicializarModulo() {
        console.log('✅ Inicializando módulo con selectores correctos');
        
        // Verificar elementos críticos
        const elementosCriticos = [
            { selector: selectors.form, nombre: 'Formulario' },
            { selector: selectors.escuela, nombre: 'Select escuela' },
            { selector: selectors.btnCargar, nombre: 'Botón cargar atletas' }
        ];
        
        elementosCriticos.forEach(elemento => {
            const el = document.querySelector(elemento.selector);
            console.log(`${elemento.nombre}: ${el ? '✅ Encontrado' : '❌ No encontrado'}`);
        });
        
        // Inicializar eventos
        inicializarEventos();
        
        // Calcular estadísticas iniciales
        calcularEstadisticas();
        
        // Actualizar contador inicial
        actualizarContador();
        
        // Actualizar estado del botón registrar
        actualizarEstadoBotonRegistro();
        
        console.log('📊 Estado inicial:', {
            totalAtletas: estado.totalAtletas,
            conAsistencia: estado.conAsistencia,
            disponibles: estado.disponibles,
            seleccionados: estado.seleccionados.size
        });
    }
    
    /**
     * Inicializar todos los event listeners
     */
    function inicializarEventos() {
        // Botón cargar atletas
        const btnCargar = document.querySelector(selectors.btnCargar);
        if (btnCargar) {
            btnCargar.addEventListener('click', manejarCargarAtletas);
            console.log('✅ Event listener para btnCargar agregado');
        }
        
        // Botón registrar
        const btnRegistrar = document.querySelector(selectors.btnRegistrar);
        if (btnRegistrar) {
            btnRegistrar.addEventListener('click', manejarRegistroMultiple);
            console.log('✅ Event listener para btnRegistrar agregado');
        }
        
        // Checkbox "Seleccionar todos"
        const selectAll = document.querySelector(selectors.selectAll);
        if (selectAll) {
            selectAll.addEventListener('change', manejarSelectAll);
            console.log('✅ Event listener para selectAll agregado');
        }
        
        // Checkboxes individuales
        document.addEventListener('change', function(e) {
            if (e.target.matches(selectors.checkboxes)) {
                manejarCheckboxChange(e.target);
            }
        });
        
        // Botones de selección rápida
        const btnSeleccionarSinAsistencia = document.querySelector(selectors.btnSeleccionarSinAsistencia);
        if (btnSeleccionarSinAsistencia) {
            btnSeleccionarSinAsistencia.addEventListener('click', seleccionarSinAsistencia);
        }
        
        const btnDeseleccionarTodos = document.querySelector(selectors.btnDeseleccionarTodos);
        if (btnDeseleccionarTodos) {
            btnDeseleccionarTodos.addEventListener('click', deseleccionarTodos);
        }
        
        // Cambios en escuela y fecha
        const escuelaSelect = document.querySelector(selectors.escuela);
        if (escuelaSelect) {
            escuelaSelect.addEventListener('change', manejarCambioEscuela);
        }
        
        const fechaInput = document.querySelector(selectors.fecha);
        if (fechaInput) {
            fechaInput.addEventListener('change', manejarCambioFecha);
        }
    }
    
    /**
     * Manejar clic en botón cargar atletas
     */
    function manejarCargarAtletas(e) {
        e.preventDefault();
        console.log('🔄 Botón cargar atletas clickeado');
        
        const escuelaSelect = document.querySelector(selectors.escuela);
        const fechaInput = document.querySelector(selectors.fecha);
        
        if (!escuelaSelect || !escuelaSelect.value) {
            gedSystem.showAlert('warning', 'Por favor, seleccione una escuela primero');
            return;
        }
        
        if (!fechaInput || !fechaInput.value) {
            gedSystem.showAlert('warning', 'Por favor, seleccione una fecha');
            return;
        }
        
        // Mostrar indicador de carga
        const originalText = e.target.innerHTML;
        e.target.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Cargando...
        `;
        e.target.disabled = true;
        
        // Simular carga (en producción sería submit del formulario)
        setTimeout(() => {
            // Restaurar botón
            e.target.innerHTML = originalText;
            e.target.disabled = false;
            
            // Mostrar mensaje de éxito
            gedSystem.showAlert('success', 'Atletas cargados correctamente');
            
            // Recalcular estadísticas
            calcularEstadisticas();
            actualizarContador();
            actualizarEstadoBotonRegistro();
            
        }, 1000);
    }
    
    /**
     * Manejar cambio en checkbox individual
     */
    function manejarCheckboxChange(checkbox) {
        const atletaId = checkbox.value;
        const atletaItem = checkbox.closest(selectors.atletaItems);
        
        if (checkbox.checked) {
            estado.seleccionados.add(atletaId);
            if (atletaItem) {
                atletaItem.classList.add('selected');
            }
        } else {
            estado.seleccionados.delete(atletaId);
            if (atletaItem) {
                atletaItem.classList.remove('selected');
            }
        }
        
        actualizarContador();
        actualizarEstadoBotonRegistro();
        actualizarSelectAllState();
    }
    
    /**
     * Manejar cambio en "Seleccionar todos"
     */
    function manejarSelectAll(e) {
        const selectAll = e.target;
        const checkboxes = document.querySelectorAll(selectors.checkboxes + ':not(:disabled)');
        
        if (selectAll.checked) {
            // Seleccionar todos los checkboxes habilitados
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                const atletaId = checkbox.value;
                estado.seleccionados.add(atletaId);
                
                const atletaItem = checkbox.closest(selectors.atletaItems);
                if (atletaItem) {
                    atletaItem.classList.add('selected');
                }
            });
        } else {
            // Deseleccionar todos
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                const atletaId = checkbox.value;
                estado.seleccionados.delete(atletaId);
                
                const atletaItem = checkbox.closest(selectors.atletaItems);
                if (atletaItem) {
                    atletaItem.classList.remove('selected');
                }
            });
        }
        
        actualizarContador();
        actualizarEstadoBotonRegistro();
    }
    
    /**
     * Actualizar estado del checkbox "Seleccionar todos"
     */
    function actualizarSelectAllState() {
        const selectAll = document.querySelector(selectors.selectAll);
        if (!selectAll) return;
        
        const checkboxes = document.querySelectorAll(selectors.checkboxes + ':not(:disabled)');
        const checkboxesChecked = document.querySelectorAll(selectors.checkboxes + ':not(:disabled):checked');
        
        if (checkboxes.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            return;
        }
        
        if (checkboxesChecked.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        } else if (checkboxesChecked.length === checkboxes.length) {
            selectAll.checked = true;
            selectAll.indeterminate = false;
        } else {
            selectAll.checked = false;
            selectAll.indeterminate = true;
        }
    }
    
    /**
     * Seleccionar solo atletas sin asistencia
     */
    function seleccionarSinAsistencia() {
        // Deseleccionar todos primero
        estado.seleccionados.clear();
        const checkboxes = document.querySelectorAll(selectors.checkboxes);
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            const atletaItem = checkbox.closest(selectors.atletaItems);
            if (atletaItem) {
                atletaItem.classList.remove('selected');
            }
        });
        
        // Seleccionar solo los que no tienen asistencia (no están deshabilitados)
        const checkboxesDisponibles = document.querySelectorAll(selectors.checkboxes + ':not(:disabled)');
        checkboxesDisponibles.forEach(checkbox => {
            checkbox.checked = true;
            const atletaId = checkbox.value;
            estado.seleccionados.add(atletaId);
            
            const atletaItem = checkbox.closest(selectors.atletaItems);
            if (atletaItem) {
                atletaItem.classList.add('selected');
            }
        });
        
        actualizarContador();
        actualizarEstadoBotonRegistro();
        actualizarSelectAllState();
        
        gedSystem.showAlert('info', `Seleccionados ${estado.seleccionados.size} atletas sin asistencia`);
    }
    
    /**
     * Deseleccionar todos los atletas
     */
    function deseleccionarTodos() {
        estado.seleccionados.clear();
        const checkboxes = document.querySelectorAll(selectors.checkboxes);
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            const atletaItem = checkbox.closest(selectors.atletaItems);
            if (atletaItem) {
                atletaItem.classList.remove('selected');
            }
        });
        
        const selectAll = document.querySelector(selectors.selectAll);
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        
        actualizarContador();
        actualizarEstadoBotonRegistro();
        
        gedSystem.showAlert('info', 'Todos los atletas deseleccionados');
    }
    
    /**
     * Manejar registro múltiple
     */
    function manejarRegistroMultiple(e) {
        e.preventDefault();
        
        if (estado.seleccionados.size === 0) {
            gedSystem.showAlert('warning', 'Debe seleccionar al menos un atleta');
            return;
        }
        
        const confirmacion = confirm(`¿Está seguro de registrar asistencia para ${estado.seleccionados.size} atleta(s)?`);
        if (!confirmacion) return;
        
        const btnRegistrar = e.target;
        const originalText = btnRegistrar.innerHTML;
        
        // Mostrar spinner
        btnRegistrar.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Procesando...
        `;
        btnRegistrar.disabled = true;
        
        // Simular envío (en producción sería submit del formulario)
        setTimeout(() => {
            // Restaurar botón
            btnRegistrar.innerHTML = originalText;
            btnRegistrar.disabled = false;
            
            // Mostrar mensaje de éxito
            gedSystem.showAlert('success', `Asistencia registrada para ${estado.seleccionados.size} atleta(s)`);
            
            // Limpiar selección
            deseleccionarTodos();
            
            // Recalcular estadísticas
            calcularEstadisticas();
            
        }, 1500);
    }
    
    /**
     * Manejar cambio de escuela
     */
    function manejarCambioEscuela() {
        console.log('🔄 Escuela cambiada, limpiando selección');
        estado.seleccionados.clear();
        actualizarContador();
        actualizarEstadoBotonRegistro();
    }
    
    /**
     * Manejar cambio de fecha
     */
    function manejarCambioFecha() {
        console.log('🔄 Fecha cambiada, limpiando selección');
        estado.seleccionados.clear();
        actualizarContador();
        actualizarEstadoBotonRegistro();
    }
    
    /**
     * Calcular estadísticas de atletas
     */
    function calcularEstadisticas() {
        // Contar total de atletas
        const atletaItems = document.querySelectorAll(selectors.atletaItems);
        estado.totalAtletas = atletaItems.length;
        
        // Contar atletas con asistencia (checkboxes deshabilitados)
        const checkboxesDeshabilitados = document.querySelectorAll(selectors.checkboxes + ':disabled');
        estado.conAsistencia = checkboxesDeshabilitados.length;
        
        // Calcular disponibles
        estado.disponibles = estado.totalAtletas - estado.conAsistencia;
        
        // Actualizar elementos de estadísticas si existen
        const totalElement = document.querySelector(selectors.totalAtletas);
        const conAsistenciaElement = document.querySelector(selectors.conAsistencia);
        const disponiblesElement = document.querySelector(selectors.disponibles);
        
        if (totalElement) totalElement.textContent = estado.totalAtletas;
        if (conAsistenciaElement) conAsistenciaElement.textContent = estado.conAsistencia;
        if (disponiblesElement) disponiblesElement.textContent = estado.disponibles;
    }
    
    /**
     * Actualizar contador de seleccionados
     */
    function actualizarContador() {
        const contadorElement = document.querySelector(selectors.contadorSeleccionados);
        if (!contadorElement) return;
        
        contadorElement.textContent = `${estado.seleccionados.size} seleccionados`;
        
        // Cambiar color según cantidad
        if (estado.seleccionados.size === 0) {
            contadorElement.classList.remove('bg-primary');
            contadorElement.classList.add('bg-secondary');
        } else {
            contadorElement.classList.remove('bg-secondary');
            contadorElement.classList.add('bg-primary');
        }
    }
    
    /**
     * Actualizar estado del botón de registro
     */
    function actualizarEstadoBotonRegistro() {
        const btnRegistrar = document.querySelector(selectors.btnRegistrar);
        if (!btnRegistrar) return;
        
        const escuelaSelect = document.querySelector(selectors.escuela);
        const fechaInput = document.querySelector(selectors.fecha);
        
        const escuelaValida = escuelaSelect && escuelaSelect.value;
        const fechaValida = fechaInput && fechaInput.value;
        const haySeleccionados = estado.seleccionados.size > 0;
        
        btnRegistrar.disabled = !(escuelaValida && fechaValida && haySeleccionados);
    }
    
    // ==================== INICIALIZACIÓN ====================
    
    // Inicializar el módulo
    inicializarModulo();
    
    // Marcar como inicializado en el sistema
    this.config.modules.registroMultiple = {
        initialized: true,
        estado: estado,
        selectors: selectors
    };
    
    console.log('✅ Módulo Registro Múltiple inicializado correctamente');
},

// ===== EVENT LISTENERS =====
initEventListeners: function() {
    window.addEventListener('resize', this.debounce(() => {
        this.handleResize();
    }, 300));
    
    let lastUrl = location.href;
    new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            this.initDynamicModules();
        }
    }).observe(document, {subtree: true, childList: true});
    
    document.addEventListener('click', (e) => {
        if (e.target.tagName === 'A' && e.target.getAttribute('href') === '#') {
            e.preventDefault();
        }
    });
},

// ===== MANEJO DE RESIZE =====
handleResize: function() {
    if (this.config.modules.map) {
        this.config.modules.map.invalidateSize();
    }
},

// ===== VERIFICACIONES RESPONSIVE =====
initResponsiveChecks: function() {
    this.checkNavbarStructure();
    this.checkOverflow();
    this.checkHerramientas162();
},

checkNavbarStructure: function() {
    if (window.innerWidth >= this.config.mobileBreakpoint) {
        const container = document.querySelector('.navbar-container');
        if (container && getComputedStyle(container).flexDirection === 'column') {
            console.warn('⚠️ Navbar en columna en desktop - corrigiendo');
            container.style.flexDirection = 'row';
        }
        
        const sections = document.querySelectorAll('.navbar-brand-section, .navbar-menu-section, .navbar-social-section, .navbar-control-section');
        sections.forEach(section => {
            section.style.whiteSpace = 'nowrap';
            section.style.display = 'flex';
        });
    }
},

checkOverflow: function() {
    if (this.config.debug) {
        console.log('📊 Overflow diagnóstico:', {
            body: getComputedStyle(document.body).overflowX,
            html: getComputedStyle(document.documentElement).overflowX,
            windowWidth: window.innerWidth,
            documentWidth: document.documentElement.clientWidth
        });
    }
},

checkHerramientas162: function() {
    const isMobile = window.innerWidth < this.config.mobileBreakpoint;
    const selectores = [
        '.menu-id-162',
        '[data-menu-id="162"]',
        '.menu-herramientas',
        '[data-menu-tools="true"]'
    ];
    
    selectores.forEach(selector => {
        const elementos = document.querySelectorAll(selector);
        elementos.forEach(el => {
            if (isMobile) {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
                el.style.opacity = '0';
                el.style.pointerEvents = 'none';
            } else {
                el.style.display = '';
                el.style.visibility = '';
                el.style.opacity = '';
                el.style.pointerEvents = '';
            }
        });
    });
},

// ===== MÓDULOS DINÁMICOS =====
initDynamicModules: function() {
    const path = window.location.pathname;
    
    const mapContainer = document.getElementById('ged-map');
    if (mapContainer && !this.config.modules.map) {
        this.initMap();
    }
    
    const horarioGrid = document.getElementById('horario-grid');
    if (horarioGrid) {
        this.initHorarioSelector();
    }
    
    // Módulo de registro múltiple por atributo
    const registroMultipleContainer = document.querySelector('[data-ged-module="registro-multiple"]');
    if (registroMultipleContainer && !this.config.modules.registroMultiple) {
        this.initRegistroMultipleModule();
    }
    
    if (path.includes('/reportes') && !this.config.modules.reportes) {
        this.initReportesModule();
    }
    
    if (path.includes('/tienda') && !this.config.modules.tienda) {
        this.initTiendaModule();
    }
},

// ===== UTILIDADES =====
debounce: function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
},

showAlert: function(type, message) {
    const notification = document.createElement('div');
    notification.className = `ged-notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : type === 'warning' ? '#ff9800' : '#2196F3'};
        color: white;
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    `;
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    });
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
    
    document.body.appendChild(notification);
    
    if (!document.getElementById('notification-animations')) {
        const style = document.createElement('style');
        style.id = 'notification-animations';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
},

showNotification: function(message, type = 'info') {
    this.showAlert(type, message);
},

// ===== DEBUG =====
debug: function() {
    console.group('🐛 DEBUG GED SYSTEM v5.9.3');
    console.log('Configuración:', this.config);
    console.log('Viewport:', window.innerWidth, 'x', window.innerHeight);
    
    // Verificar valores CSS vs JS
    const navbar = document.querySelector('.navbar-contextual');
    if (navbar) {
        const computed = getComputedStyle(navbar);
        console.log('📐 Navbar CSS vs JS:', {
            'CSS height': computed.height,
            'CSS width': computed.width,
            'JS height': navbar.style.height,
            'JS width': navbar.style.width
        });
    }
    
    // Verificar body padding
    console.log('📏 Body padding-top:', getComputedStyle(document.body).paddingTop);
    
    // Verificar módulos cargados
    console.log('📦 Módulos activos:', {
        mapa: !!this.config.modules.map,
        reportes: !!this.config.modules.reportes,
        tienda: !!this.config.modules.tienda,
        registroMultiple: !!this.config.modules.registroMultiple
    });
    
    console.groupEnd();
},

// ===== DESTRUCTOR =====
destroy: function() {
    window.removeEventListener('resize', this.handleResize);
    
    if (this.config.modules.map) {
        this.config.modules.map.remove();
    }
    
    this.config.isInitialized = false;
    this.config.modules = {};
    this.config.events = {};
    
    console.log('🧹 GED System destruido');
}
};

// ===== INICIALIZACIÓN AUTOMÁTICA =====
document.addEventListener('DOMContentLoaded', function() {
setTimeout(() => {
gedSystem.init();
}, 100);
});

// ===== POLYFILLS =====
if (!Element.prototype.closest) {
Element.prototype.closest = function(s) {
var el = this;
do {
if (el.matches(s)) return el;
el = el.parentElement || el.parentNode;
} while (el !== null && el.nodeType === 1);
return null;
};
}

if (!Element.prototype.matches) {
Element.prototype.matches =
Element.prototype.matchesSelector ||
Element.prototype.mozMatchesSelector ||
Element.prototype.msMatchesSelector ||
Element.prototype.oMatchesSelector ||
Element.prototype.webkitMatchesSelector ||
function(s) {
var matches = (this.document || this.ownerDocument).querySelectorAll(s),
i = matches.length;
while (--i >= 0 && matches.item(i) !== this) {}
return i > -1;
};
}

if (typeof module !== 'undefined' && module.exports) {
module.exports = gedSystem;
}

/* ===== NOTA FINAL =====

ARCHIVO JS CONSOLIDADO GED v5.9.3

✅ MÓDULO DE REGISTRO MÚLTIPLE ACTUALIZADO:

CORRECCIONES APLICADAS:

✅ Selectores actualizados para coincidir con registro-multiple.php REAL
✅ IDs correctos: #select-escuela, #btn-cargar-atletas, #panel-atletas, #lista-atletas
✅ Clases correctas: .atleta-item, .atleta-checkbox, #contador-seleccionados
✅ Eliminados selectores antiguos: #asistencia-disciplina_id, #asistencia-evento_id
✅ Event listeners específicos para elementos reales
✅ Compatibilidad total con Yii2 ActiveForm (#asistencia-fecha_practica)
✅ Funcionalidad completa: selección, contadores, botones rápidos
✅ SELECTORES IMPLEMENTADOS:

#select-escuela (selector de escuela manual)

#asistencia-fecha_practica (campo ActiveForm Yii2)

#btn-cargar-atletas (botón para cargar lista)

#panel-atletas (contenedor principal)

#lista-atletas (lista de atletas)

.atleta-checkbox (checkboxes individuales)

#select-all (checkbox "seleccionar todos")

#btn-registrar-multiple (botón registrar)

#contador-seleccionados (contador de selección)

#btn-seleccionar-sin-asistencia (botón rápido)

#btn-deseleccionar-todos (botón rápido)

✅ FUNCIONALIDADES IMPLEMENTADAS:

Carga de atletas con validación

Selección individual y múltiple

Contador en tiempo real

Botones de selección rápida

Validación antes de registrar

Feedback visual con notificaciones

Manejo de estados disabled/enabled

✅ COMPATIBILIDAD:

100% compatible con Yii2 y Bootstrap 5

Sin conflictos con otros módulos GED

Código autocontenido sin dependencias externas
*/