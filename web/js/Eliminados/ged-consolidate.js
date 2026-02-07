/* =================================================================
 * ged-consolidated.js - SISTEMA GED UNIFICADO
 * Reemplaza: ged.js, ged-init.js, gedOffCanvas-module.js, 
 *            navbarWidth-module.js, mapa-module.js, 
 *            horario-selector.js, reportes-module.js, tienda-module.js
 * Fecha: 2024-01-22 - VERSIÓN CONSOLIDADA 5.3
 * Correcciones: Menú móvil COMPLETAMENTE funcional con 2do y 3er nivel
 * ================================================================= */

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
            tienda: null
        },
        events: {},
        isMobileMenuOpen: false
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
        
        console.log('🚀 GED System v5.3 - Inicializando sistema consolidado');
        
        // Inicializar componentes en orden
        this.initNavbar();
        this.initMobileMenuSystem(); // NUEVO: Sistema de menú móvil
        this.initEventListeners();
        this.initResponsiveChecks();
        this.initDynamicModules();
        
        // Marcar como inicializado
        this.config.isInitialized = true;
        
        // Exponer para debug
        window.gedSystem = this;
        
        console.log('✅ Sistema GED inicializado correctamente');
        
        // Ejecutar debug si está activado
        if (this.config.debug) {
            this.debug();
        }
    },
    
    // ===== NAVBAR UNIFICADO =====
    initNavbar: function() {
        const navbar = document.querySelector('.navbar-contextual');
        if (!navbar) {
            console.warn('⚠️ Navbar no encontrado');
            return;
        }
        
        console.log('🎯 Inicializando navbar consolidado');
        
        // ✅ FUNCIÓN ÚNICA para ajustar navbar
        const adjustNavbar = () => {
            const isDesktop = window.innerWidth >= this.config.mobileBreakpoint;
            
            if (isDesktop) {
                // Desktop: 95% width, 45vh height
                const targetHeight = window.innerHeight * 0.45;
                navbar.style.width = this.CONSTANTS.NAVBAR_WIDTH_DESKTOP;
                navbar.style.marginLeft = 'auto';
                navbar.style.height = targetHeight + 'px';
                navbar.style.minHeight = targetHeight + 'px';
                document.body.style.paddingTop = targetHeight + 'px';
                
                // Asegurar distribución exacta
                this.ensureNavbarDistribution();
            } else {
                // Mobile: 100% width, altura fija
                navbar.style.width = this.CONSTANTS.NAVBAR_WIDTH_MOBILE;
                navbar.style.marginLeft = '0';
                navbar.style.height = this.CONSTANTS.NAVBAR_HEIGHT_MOBILE;
                navbar.style.minHeight = this.CONSTANTS.NAVBAR_HEIGHT_MOBILE;
                document.body.style.paddingTop = this.CONSTANTS.NAVBAR_HEIGHT_MOBILE;
            }
            
            // Scroll behavior
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
            
            // Asegurar display flex
            section.style.display = 'flex';
            section.style.alignItems = 'center';
            section.style.height = '100%';
            section.style.overflow = 'hidden';
        });
        
        // Forzar una línea
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
    
    // ===== SISTEMA DE MENÚ MÓVIL - SOLUCIÓN COMPLETA =====
    initMobileMenuSystem: function() {
        console.log('📱 Inicializando sistema de menú móvil');
        
        // Configurar offcanvas
        this.setupOffcanvas();
        
        // Preparar estructura inicial
        this.prepareMobileMenuStructure();
    },
    
    // ===== CONFIGURAR OFFCANVAS =====
    setupOffcanvas: function() {
        const offcanvasEl = document.getElementById('mobileMenuOffcanvas');
        if (!offcanvasEl) {
            console.log('ℹ️ Offcanvas no encontrado');
            return;
        }
        
        console.log('🔧 Configurando offcanvas móvil');
        
        // Escuchar cuando el offcanvas se abra
        offcanvasEl.addEventListener('show.bs.offcanvas', () => {
            console.log('📱 Offcanvas abriéndose');
            this.config.isMobileMenuOpen = true;
        });
        
        // Escuchar cuando el offcanvas esté COMPLETAMENTE abierto
        offcanvasEl.addEventListener('shown.bs.offcanvas', () => {
            console.log('✅ Offcanvas completamente abierto');
            this.config.isMobileMenuOpen = true;
            
            // Inicializar sistema de menú móvil
            setTimeout(() => {
                this.initializeMobileMenu();
            }, 100);
        });
        
        // Escuchar cuando el offcanvas se cierre
        offcanvasEl.addEventListener('hidden.bs.offcanvas', () => {
            console.log('❌ Offcanvas cerrado');
            this.config.isMobileMenuOpen = false;
        });
    },
    
    // ===== PREPARAR ESTRUCTURA DE MENÚ MÓVIL =====
    prepareMobileMenuStructure: function() {
        const offcanvasEl = document.getElementById('mobileMenuOffcanvas');
        if (!offcanvasEl) return;
        
        console.log('🏗️ Preparando estructura de menú móvil');
        
        // ✅ 1. PREPARAR DROPDOWNS DE 2do NIVEL
        const secondLevelMenus = offcanvasEl.querySelectorAll('.dropdown-menu .dropdown-menu');
        secondLevelMenus.forEach((menu, index) => {
            console.log(`🔧 Preparando 2do nivel ${index + 1}`);
            
            // Asegurar clases
            menu.classList.add('mobile-dropdown-level-2');
            
            // Forzar estilos básicos
            menu.style.cssText = `
                position: absolute !important;
                top: 0 !important;
                left: 100% !important;
                margin-left: 5px !important;
                width: 220px !important;
                max-width: 90vw !important;
                background: rgba(125, 60, 152, 0.98) !important;
                border: 1px solid rgba(255,255,255,0.3) !important;
                border-radius: 6px !important;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
                z-index: 1071 !important;
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                padding: 0 !important;
            `;
        });
        
        // ✅ 2. PREPARAR DROPDOWNS DE 3er NIVEL
        const thirdLevelMenus = offcanvasEl.querySelectorAll('.dropdown-menu .dropdown-menu .dropdown-menu');
        thirdLevelMenus.forEach((menu, index) => {
            console.log(`🔧 Preparando 3er nivel ${index + 1}`);
            
            // Asegurar clases
            menu.classList.add('mobile-dropdown-level-3');
            
            // Forzar estilos básicos
            menu.style.cssText = `
                position: absolute !important;
                top: 0 !important;
                left: 100% !important;
                margin-left: 5px !important;
                width: 200px !important;
                max-width: 90vw !important;
                background: rgba(125, 60, 152, 0.98) !important;
                border: 1px solid rgba(255,255,255,0.3) !important;
                border-radius: 6px !important;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
                z-index: 1072 !important;
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                padding: 0 !important;
            `;
        });
        
        console.log(`✅ Preparados: ${secondLevelMenus.length} 2do nivel, ${thirdLevelMenus.length} 3er nivel`);
    },
    
    // ===== INICIALIZAR MENÚ MÓVIL =====
    initializeMobileMenu: function() {
        if (!this.config.isMobileMenuOpen) return;
        
        console.log('🎯 Inicializando menú móvil interactivo');
        
        const offcanvasEl = document.getElementById('mobileMenuOffcanvas');
        if (!offcanvasEl) return;
        
        // ✅ 1. CONFIGURAR DELEGACIÓN DE EVENTOS ÚNICA
        this.setupMobileMenuEventDelegation(offcanvasEl);
        
        // ✅ 2. AGREGAR INDICADORES VISUALES
        this.addMobileMenuIndicators(offcanvasEl);
        
        console.log('✅ Menú móvil inicializado');
    },
    
    // ===== CONFIGURAR DELEGACIÓN DE EVENTOS PARA MENÚ MÓVIL =====
    setupMobileMenuEventDelegation: function(container) {
        console.log('🖱️ Configurando delegación de eventos para menú móvil');
        
        // Remover cualquier evento anterior
        container.removeEventListener('click', this.config.events.mobileMenuClick);
        
        // Crear handler de delegación
        const clickHandler = (e) => {
            // Solo procesar en móvil
            if (window.innerWidth >= this.config.mobileBreakpoint) return;
            
            // Buscar el elemento clickeado que sea un dropdown toggle
            let target = e.target;
            
            // Si se hizo clic en un ícono dentro del enlace
            if (target.tagName === 'I' || target.tagName === 'SPAN') {
                target = target.closest('a');
            }
            
            // Si se hizo clic en un enlace sin dropdown
            if (!target || !target.classList.contains('dropdown-toggle')) {
                // Si se hizo clic fuera de un dropdown, cerrar todos
                if (!target || !target.closest('.dropdown')) {
                    this.closeAllMobileDropdowns(container);
                }
                return;
            }
            
            // Prevenir comportamiento por defecto de Bootstrap
            e.preventDefault();
            e.stopPropagation();
            
            // Obtener el menú asociado
            const dropdownMenu = target.nextElementSibling;
            if (!dropdownMenu || !dropdownMenu.classList.contains('dropdown-menu')) {
                return;
            }
            
            console.log('🔄 Click en dropdown móvil');
            
            // Manejar el clic
            this.handleMobileMenuClick(target, dropdownMenu, container);
        };
        
        // Guardar referencia y asignar evento
        this.config.events.mobileMenuClick = clickHandler;
        container.addEventListener('click', clickHandler);
        
        console.log('✅ Delegación de eventos configurada');
    },
    
    // ===== MANEJAR CLIC EN MENÚ MÓVIL =====
    handleMobileMenuClick: function(toggle, dropdownMenu, container) {
        const isOpen = dropdownMenu.style.display === 'block';
        
        console.log(`🔄 Dropdown ${isOpen ? 'abierto' : 'cerrado'} → ${isOpen ? 'cerrando' : 'abriendo'}`);
        
        if (!isOpen) {
            // Cerrar otros dropdowns del mismo nivel
            this.closeOtherMobileDropdowns(container, dropdownMenu);
        }
        
        // Alternar estado
        if (isOpen) {
            // Cerrar este dropdown
            this.closeMobileDropdown(dropdownMenu);
            
            // También cerrar sus hijos
            this.closeChildDropdowns(dropdownMenu);
        } else {
            // Abrir este dropdown
            this.openMobileDropdown(toggle, dropdownMenu);
        }
    },
    
    // ===== ABRIR DROPDOWN MÓVIL =====
    openMobileDropdown: function(toggle, dropdownMenu) {
        // Mostrar dropdown
        dropdownMenu.style.display = 'block';
        dropdownMenu.style.opacity = '1';
        dropdownMenu.style.visibility = 'visible';
        
        // Actualizar estado del toggle
        toggle.setAttribute('aria-expanded', 'true');
        
        // Añadir clase activa
        toggle.classList.add('active');
        
        // Ajustar posición si es necesario
        this.adjustMobileDropdownPosition(dropdownMenu);
        
        console.log('✅ Dropdown abierto');
    },
    
    // ===== CERRAR DROPDOWN MÓVIL =====
    closeMobileDropdown: function(dropdownMenu) {
        // Ocultar dropdown
        dropdownMenu.style.display = 'none';
        dropdownMenu.style.opacity = '0';
        dropdownMenu.style.visibility = 'hidden';
        
        // Actualizar toggle asociado
        const toggle = dropdownMenu.previousElementSibling;
        if (toggle && toggle.classList.contains('dropdown-toggle')) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.classList.remove('active');
        }
        
        console.log('✅ Dropdown cerrado');
    },
    
    // ===== CERRAR OTROS DROPDOWNS MÓVILES =====
    closeOtherMobileDropdowns: function(container, currentMenu) {
        // Buscar todos los dropdowns abiertos
        const openDropdowns = container.querySelectorAll('.dropdown-menu[style*="display: block"]');
        
        openDropdowns.forEach(menu => {
            // No cerrar el actual
            if (menu === currentMenu) return;
            
            // No cerrar padres del actual
            if (currentMenu && menu.contains(currentMenu)) return;
            
            // Cerrar este dropdown
            this.closeMobileDropdown(menu);
        });
    },
    
    // ===== CERRAR TODOS LOS DROPDOWNS MÓVILES =====
    closeAllMobileDropdowns: function(container) {
        const openDropdowns = container.querySelectorAll('.dropdown-menu[style*="display: block"]');
        
        openDropdowns.forEach(menu => {
            this.closeMobileDropdown(menu);
        });
        
        console.log(`🚫 Cerrados ${openDropdowns.length} dropdowns`);
    },
    
    // ===== CERRAR DROPDOWNS HIJOS =====
    closeChildDropdowns: function(parentMenu) {
        const childDropdowns = parentMenu.querySelectorAll('.dropdown-menu[style*="display: block"]');
        
        childDropdowns.forEach(menu => {
            this.closeMobileDropdown(menu);
        });
    },
    
    // ===== AJUSTAR POSICIÓN DE DROPDOWN MÓVIL =====
    adjustMobileDropdownPosition: function(dropdownMenu) {
        if (window.innerWidth >= this.config.mobileBreakpoint) return;
        
        // Solo ajustar para dropdowns anidados (2do y 3er nivel)
        if (!dropdownMenu.classList.contains('mobile-dropdown-level-2') && 
            !dropdownMenu.classList.contains('mobile-dropdown-level-3')) {
            return;
        }
        
        const rect = dropdownMenu.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        
        // Si se sale por la derecha, mostrar a la izquierda
        if (rect.right > viewportWidth - 20) {
            dropdownMenu.style.left = 'auto';
            dropdownMenu.style.right = '100%';
            dropdownMenu.style.marginLeft = '0';
            dropdownMenu.style.marginRight = '5px';
        } else {
            // Mantener a la derecha
            dropdownMenu.style.left = '100%';
            dropdownMenu.style.right = 'auto';
            dropdownMenu.style.marginLeft = '5px';
            dropdownMenu.style.marginRight = '0';
        }
    },
    
    // ===== AGREGAR INDICADORES VISUALES =====
    addMobileMenuIndicators: function(container) {
        console.log('🎨 Agregando indicadores visuales para menú móvil');
        
        // Buscar todos los items que tienen submenú
        const dropdownToggles = container.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach(toggle => {
            const dropdownMenu = toggle.nextElementSibling;
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                // Agregar flecha indicadora si no existe
                if (!toggle.querySelector('.mobile-menu-arrow')) {
                    const arrow = document.createElement('span');
                    arrow.className = 'mobile-menu-arrow ms-2';
                    arrow.innerHTML = '›';
                    arrow.style.cssText = `
                        display: inline-block;
                        transform: rotate(90deg);
                        transition: transform 0.3s;
                    `;
                    toggle.appendChild(arrow);
                }
            }
        });
        
        console.log(`✅ ${dropdownToggles.length} indicadores agregados`);
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
        const isMobile = window.innerWidth < this.config.mobileBreakpoint;
        
        if (isMobile && this.config.isMobileMenuOpen) {
            // Reajustar posición de dropdowns si el offcanvas está abierto
            setTimeout(() => {
                this.adjustAllMobileDropdownPositions();
            }, 300);
        }
        
        if (this.config.modules.map) {
            this.config.modules.map.invalidateSize();
        }
    },
    
    // ===== AJUSTAR TODAS LAS POSICIONES DE DROPDOWNS MÓVILES =====
    adjustAllMobileDropdownPositions: function() {
        const offcanvasEl = document.getElementById('mobileMenuOffcanvas');
        if (!offcanvasEl) return;
        
        const dropdowns = offcanvasEl.querySelectorAll('.dropdown-menu[style*="display: block"]');
        dropdowns.forEach(dropdown => {
            this.adjustMobileDropdownPosition(dropdown);
        });
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
    
    showNotification: function(message, type = 'info') {
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
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
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
    
    // ===== DEBUG =====
    debug: function() {
        console.group('🐛 DEBUG GED SYSTEM v5.3');
        console.log('Configuración:', this.config);
        console.log('Viewport:', window.innerWidth, 'x', window.innerHeight);
        console.log('Offcanvas abierto:', this.config.isMobileMenuOpen);
        
        // Verificar estructura del offcanvas
        const offcanvas = document.getElementById('mobileMenuOffcanvas');
        if (offcanvas) {
            console.log('Offcanvas encontrado');
            
            // Contar dropdowns
            const dropdowns = offcanvas.querySelectorAll('.dropdown-menu');
            const level2 = offcanvas.querySelectorAll('.mobile-dropdown-level-2');
            const level3 = offcanvas.querySelectorAll('.mobile-dropdown-level-3');
            
            console.log(`📊 Dropdowns totales: ${dropdowns.length}`);
            console.log(`📊 2do nivel: ${level2.length}`);
            console.log(`📊 3er nivel: ${level3.length}`);
            
            // Verificar eventos
            const toggles = offcanvas.querySelectorAll('.dropdown-toggle');
            console.log(`🖱️ Dropdown toggles: ${toggles.length}`);
            
            toggles.forEach((toggle, i) => {
                const hasMenu = toggle.nextElementSibling && 
                              toggle.nextElementSibling.classList.contains('dropdown-menu');
                console.log(`Toggle ${i + 1}: Tiene menú = ${hasMenu ? '✅' : '❌'}`);
            });
        }
        
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
        this.config.isMobileMenuOpen = false;
        
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
 * ARCHIVO JS CONSOLIDADO GED v5.3
 * SOLUCIÓN COMPLETA PARA MENÚ MÓVIL:
 * 1. ✅ SISTEMA DELEGACIÓN DE EVENTOS único
 * 2. ✅ MANEJO COMPLETO de 2do y 3er nivel
 * 3. ✅ PREVENCIÓN de interferencia de Bootstrap
 * 4. ✅ INDICADORES VISUALES para items con submenú
 * 5. ✅ AJUSTE AUTOMÁTICO de posición
 * 6. ✅ CIERRE INTELIGENTE de otros dropdowns
 */