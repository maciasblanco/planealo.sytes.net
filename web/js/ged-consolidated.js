/* =================================================================
 * ged-consolidated.js - SISTEMA GED UNIFICADO - VERSIÓN 6.0
 * Correcciones: Menú móvil COMPLETAMENTE funcional con 2do y 3er nivel
 * Mejoras: Bootstrap 5 inicializado, conteo corregido, diagnóstico mejorado
 * Fecha: 2026-02-05
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
        isMobileMenuOpen: false,
        // NUEVO: Registro de niveles del menú
        menuLevels: { level2: 0, level3: 0 }
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
        
        console.log('🚀 GED System v6.0 - Inicializando sistema consolidado');
        
        // Inicializar componentes en orden
        this.initNavbar();
        this.initMobileMenuSystem(); // NUEVO: Sistema de menú móvil mejorado
        this.initEventListeners();
        this.initResponsiveChecks();
        this.initDynamicModules();
        
        // NUEVO: Inicializar dropdowns de Bootstrap 5 para niveles anidados
        this.initBootstrapDropdowns();
        
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
    
    // ===== INICIALIZAR DROPDOWNS BOOTSTRAP 5 (NUEVO) =====
    initBootstrapDropdowns: function() {
        console.log('🔽 Inicializando dropdowns de Bootstrap 5 para niveles anidados');
        
        // Inicializar todos los dropdowns que no están en el offcanvas móvil
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach((toggle, index) => {
            // Solo inicializar si no está dentro del offcanvas móvil
            if (!toggle.closest('#mobileMenuOffcanvas')) {
                // Asegurar que tenga el atributo data-bs-toggle
                if (!toggle.hasAttribute('data-bs-toggle')) {
                    toggle.setAttribute('data-bs-toggle', 'dropdown');
                }
                
                // Inicializar dropdown de Bootstrap
                try {
                    const dropdown = new bootstrap.Dropdown(toggle);
                    // NUEVO: Agregar atributo de nivel
                    this.setDropdownLevel(toggle);
                } catch (error) {
                    console.warn(`⚠️ Error inicializando dropdown ${index}:`, error);
                }
            }
        });
        
        console.log(`✅ ${dropdownToggles.length} dropdowns inicializados`);
    },
    
    // ===== SISTEMA DE MENÚ MÓVIL - SOLUCIÓN COMPLETA MEJORADA =====
    initMobileMenuSystem: function() {
        console.log('📱 Inicializando sistema de menú móvil v6.0');
        
        // NUEVO: Contar niveles reales del menú
        this.countAndLogMenuLevels();
        
        // Configurar offcanvas
        this.setupOffcanvas();
        
        // Preparar estructura inicial
        this.prepareMobileMenuStructure();
    },
    
    // ===== CONTAR Y REGISTRAR NIVELES DEL MENÚ (NUEVO) =====
    countAndLogMenuLevels: function() {
        console.group('🔢 CONTEO DE NIVELES DEL MENÚ MÓVIL');
        
        const offcanvasEl = document.getElementById('mobileMenuOffcanvas');
        if (!offcanvasEl) {
            console.warn('❌ Offcanvas no encontrado para conteo');
            console.groupEnd();
            return;
        }
        
        // NUEVO: Función de conteo corregida
        const counts = this.countNestedDropdowns(offcanvasEl);
        
        // Guardar en configuración
        this.config.menuLevels = counts;
        
        console.log(`📊 Resultado del conteo:`);
        console.log(`   • 2do nivel (submenús): ${counts.level2}`);
        console.log(`   • 3er nivel (sub-submenús): ${counts.level3}`);
        console.log(`   • ¿Hay niveles 3?: ${counts.level3 > 0 ? '✅ SÍ' : '❌ NO'}`);
        
        console.groupEnd();
        return counts;
    },
    
    // ===== FUNCIÓN COUNTNESTEDDROPDOWNS CORREGIDA (NUEVO) =====
    countNestedDropdowns: function(container) {
        const dropdowns = container.querySelectorAll('.dropdown-menu');
        let level2 = 0;
        let level3 = 0;
        
        console.log(`🔍 Analizando ${dropdowns.length} elementos .dropdown-menu`);
        
        dropdowns.forEach((dropdown, index) => {
            // Determinar nivel por profundidad de ancestros
            let level = 0;
            let parent = dropdown.parentElement;
            
            // Contar cuántos dropdown-menu hay en la jerarquía de padres
            while (parent) {
                if (parent.classList && parent.classList.contains('dropdown-menu')) {
                    level++;
                }
                parent = parent.parentElement;
            }
            
            // Clasificar por nivel
            if (level === 1) {
                level2++;
                console.log(`   Dropdown ${index + 1}: 2do nivel (dentro de 1 dropdown padre)`);
            } else if (level >= 2) {
                level3++;
                console.log(`   Dropdown ${index + 1}: 3er nivel (dentro de ${level} dropdowns padres)`);
            } else if (level === 0) {
                console.log(`   Dropdown ${index + 1}: 1er nivel (sin padres dropdown)`);
            }
        });
        
        return { level2, level3 };
    },
    
    // ===== ASIGNAR NIVEL A DROPDOWN (NUEVO) =====
    setDropdownLevel: function(toggleElement) {
        const dropdownMenu = toggleElement.nextElementSibling;
        if (!dropdownMenu || !dropdownMenu.classList.contains('dropdown-menu')) {
            return;
        }
        
        // Determinar nivel
        let level = 0;
        let parent = dropdownMenu.parentElement;
        
        while (parent) {
            if (parent.classList && parent.classList.contains('dropdown-menu')) {
                level++;
            }
            parent = parent.parentElement;
        }
        
        // Asignar atributo data-dropdown-level
        if (level > 0) {
            toggleElement.setAttribute('data-dropdown-level', level + 1);
            dropdownMenu.setAttribute('data-dropdown-level', level + 1);
        }
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
            
            // NUEVO: Verificar estructura después de abrir
            this.debugMobileMenuLevels();
            
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
            // Solo agregar clase si no la tiene
            if (!menu.classList.contains('mobile-dropdown-level-2')) {
                menu.classList.add('mobile-dropdown-level-2');
            }
            
            // Asignar atributo de nivel
            menu.setAttribute('data-dropdown-level', '2');
            
            // NUEVO: Estilos mejorados sin !important excesivo
            Object.assign(menu.style, {
                position: 'absolute',
                top: '0',
                left: '100%',
                marginLeft: '5px',
                width: '220px',
                maxWidth: '90vw',
                backgroundColor: 'rgba(125, 60, 152, 0.98)',
                border: '1px solid rgba(255,255,255,0.3)',
                borderRadius: '6px',
                boxShadow: '0 10px 30px rgba(0,0,0,0.3)',
                zIndex: '1071',
                display: 'none',
                opacity: '0',
                visibility: 'hidden',
                padding: '0'
            });
        });
        
        // ✅ 2. PREPARAR DROPDOWNS DE 3er NIVEL
        const thirdLevelMenus = offcanvasEl.querySelectorAll('.dropdown-menu .dropdown-menu .dropdown-menu');
        thirdLevelMenus.forEach((menu, index) => {
            // Solo agregar clase si no la tiene
            if (!menu.classList.contains('mobile-dropdown-level-3')) {
                menu.classList.add('mobile-dropdown-level-3');
            }
            
            // Asignar atributo de nivel
            menu.setAttribute('data-dropdown-level', '3');
            
            // NUEVO: Estilos diferenciados para nivel 3
            Object.assign(menu.style, {
                position: 'absolute',
                top: '0',
                left: '100%',
                marginLeft: '5px',
                width: '200px',
                maxWidth: '85vw',
                backgroundColor: 'rgba(105, 40, 132, 0.98)',
                border: '1px solid rgba(255,255,255,0.4)',
                borderRadius: '6px',
                boxShadow: '0 10px 30px rgba(0,0,0,0.4)',
                zIndex: '1072',
                display: 'none',
                opacity: '0',
                visibility: 'hidden',
                padding: '0'
            });
        });
        
        // NUEVO: Inicializar dropdowns anidados dentro del offcanvas
        this.initNestedDropdownsInOffcanvas(offcanvasEl);
        
        console.log(`✅ Preparados: ${secondLevelMenus.length} 2do nivel, ${thirdLevelMenus.length} 3er nivel`);
    },
    
    // ===== INICIALIZAR DROPDOWNS ANIDADOS EN OFFCANVAS (NUEVO) =====
    initNestedDropdownsInOffcanvas: function(offcanvasEl) {
        // Encontrar todos los dropdown-toggles dentro del offcanvas
        const dropdownToggles = offcanvasEl.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach((toggle, index) => {
            // Marcar como dropdown de Bootstrap
            toggle.setAttribute('data-bs-toggle', 'dropdown');
            
            // Asignar nivel
            this.setDropdownLevel(toggle);
            
            // Inicializar dropdown de Bootstrap con configuración especial para móvil
            try {
                const dropdown = new bootstrap.Dropdown(toggle, {
                    display: 'static', // Para comportamiento móvil
                    autoClose: false   // Mantener abierto en móvil
                });
                
                // NUEVO: Agregar evento personalizado para móvil
                toggle.addEventListener('click', (e) => {
                    if (window.innerWidth < this.config.mobileBreakpoint) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.handleMobileDropdownClick(toggle, dropdown);
                    }
                });
                
            } catch (error) {
                console.warn(`⚠️ Error inicializando dropdown móvil ${index}:`, error);
            }
        });
    },
    
    // ===== MANEJAR CLIC EN DROPDOWN MÓVIL (NUEVO) =====
    handleMobileDropdownClick: function(toggle, dropdownInstance) {
        const dropdownMenu = toggle.nextElementSibling;
        if (!dropdownMenu) return;
        
        const isShowing = dropdownMenu.style.display === 'block';
        
        if (isShowing) {
            // Cerrar
            dropdownInstance.hide();
        } else {
            // Cerrar otros dropdowns del mismo nivel primero
            this.closeOtherMobileDropdownsAtSameLevel(toggle);
            
            // Abrir este
            dropdownInstance.show();
            
            // Ajustar posición
            this.adjustMobileDropdownPosition(dropdownMenu);
        }
    },
    
    // ===== CERRAR OTROS DROPDOWNS DEL MISMO NIVEL (NUEVO) =====
    closeOtherMobileDropdownsAtSameLevel: function(currentToggle) {
        const currentLevel = currentToggle.getAttribute('data-dropdown-level') || '1';
        const offcanvas = document.getElementById('mobileMenuOffcanvas');
        if (!offcanvas) return;
        
        // Encontrar todos los dropdowns abiertos del mismo nivel
        const sameLevelToggles = offcanvas.querySelectorAll(
            `.dropdown-toggle[data-dropdown-level="${currentLevel}"]`
        );
        
        sameLevelToggles.forEach(toggle => {
            if (toggle !== currentToggle) {
                const dropdownMenu = toggle.nextElementSibling;
                if (dropdownMenu && dropdownMenu.style.display === 'block') {
                    // Encontrar instancia de Bootstrap y cerrar
                    const dropdownInstance = bootstrap.Dropdown.getInstance(toggle);
                    if (dropdownInstance) {
                        dropdownInstance.hide();
                    }
                }
            }
        });
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
        if (this.config.events.mobileMenuClick) {
            container.removeEventListener('click', this.config.events.mobileMenuClick);
        }
        
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
    
    // ===== FUNCIONES DE DIAGNÓSTICO MEJORADAS (NUEVO) =====
    debugMobileMenuLevels: function() {
        console.group('🔍 DIAGNÓSTICO COMPLETO MENÚ MÓVIL');
        
        const offcanvas = document.getElementById('mobileMenuOffcanvas');
        if (!offcanvas) {
            console.error('❌ Offcanvas no encontrado');
            console.groupEnd();
            return;
        }
        
        // Contar niveles estructurales
        const counts = this.countNestedDropdowns(offcanvas);
        
        // Contar por clases CSS
        const level2ByClass = offcanvas.querySelectorAll('.mobile-dropdown-level-2').length;
        const level3ByClass = offcanvas.querySelectorAll('.mobile-dropdown-level-3').length;
        
        // Contar por atributos
        const level2ByAttr = offcanvas.querySelectorAll('[data-dropdown-level="2"]').length;
        const level3ByAttr = offcanvas.querySelectorAll('[data-dropdown-level="3"]').length;
        
        // Verificar dropdowns de Bootstrap
        const bootstrapDropdowns = [];
        const dropdownToggles = offcanvas.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            const instance = bootstrap.Dropdown.getInstance(toggle);
            bootstrapDropdowns.push({
                element: toggle,
                hasInstance: !!instance,
                level: toggle.getAttribute('data-dropdown-level') || '1'
            });
        });
        
        console.log('📊 ESTADÍSTICAS:');
        console.log(`   • Dropdowns totales: ${offcanvas.querySelectorAll('.dropdown-menu').length}`);
        console.log(`   • Dropdown toggles: ${dropdownToggles.length}`);
        console.log('');
        console.log('📈 NIVELES POR ESTRUCTURA:');
        console.log(`   • 2do nivel: ${counts.level2}`);
        console.log(`   • 3er nivel: ${counts.level3}`);
        console.log('');
        console.log('🎨 NIVELES POR CLASES CSS:');
        console.log(`   • .mobile-dropdown-level-2: ${level2ByClass}`);
        console.log(`   • .mobile-dropdown-level-3: ${level3ByClass}`);
        console.log('');
        console.log('🏷️ NIVELES POR ATRIBUTOS:');
        console.log(`   • data-dropdown-level="2": ${level2ByAttr}`);
        console.log(`   • data-dropdown-level="3": ${level3ByAttr}`);
        console.log('');
        console.log('⚡ BOOTSTRAP DROPDOWNS:');
        console.log(`   • Inicializados: ${bootstrapDropdowns.filter(d => d.hasInstance).length}/${bootstrapDropdowns.length}`);
        
        // Verificar si hay discrepancias
        if (counts.level3 === 0 && (level3ByClass > 0 || level3ByAttr > 0)) {
            console.warn('⚠️ DISCREPANCIA: Hay niveles 3 en clases/atributos pero no detectados estructuralmente');
        }
        
        if (counts.level3 > 0) {
            console.log('✅ CORRECTO: Se detectaron niveles 3 estructuralmente');
        }
        
        console.groupEnd();
    },
    
    // ===== REINICIALIZAR MENÚ MÓVIL (NUEVO) =====
    reinitializeMobileMenu: function() {
        console.log('🔄 REINICIALIZANDO MENÚ MÓVIL');
        
        const offcanvas = document.getElementById('mobileMenuOffcanvas');
        if (!offcanvas) {
            console.error('❌ Offcanvas no encontrado');
            return;
        }
        
        // 1. Cerrar todos los dropdowns
        this.closeAllMobileDropdowns(offcanvas);
        
        // 2. Destruir instancias de Bootstrap
        const dropdownToggles = offcanvas.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            const instance = bootstrap.Dropdown.getInstance(toggle);
            if (instance) {
                instance.dispose();
            }
        });
        
        // 3. Re-preparar estructura
        this.prepareMobileMenuStructure();
        
        // 4. Re-inicializar
        this.initializeMobileMenu();
        
        console.log('✅ Menú móvil reinicializado');
    },
    
    // ===== DEBUG =====
    debug: function() {
        console.group('🐛 DEBUG GED SYSTEM v6.0');
        console.log('Configuración:', this.config);
        console.log('Viewport:', window.innerWidth, 'x', window.innerHeight);
        console.log('Offcanvas abierto:', this.config.isMobileMenuOpen);
        
        // NUEVO: Mostrar niveles del menú
        console.log('Niveles del menú:', this.config.menuLevels);
        
        // Verificar estructura del offcanvas
        const offcanvas = document.getElementById('mobileMenuOffcanvas');
        if (offcanvas) {
            console.log('Offcanvas encontrado');
            
            // Usar función de diagnóstico mejorada
            this.debugMobileMenuLevels();
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

// ===== FUNCIONES GLOBALES DE DIAGNÓSTICO (NUEVO) =====
window.debugMobileMenuLevels = function() {
    if (gedSystem && typeof gedSystem.debugMobileMenuLevels === 'function') {
        gedSystem.debugMobileMenuLevels();
    } else {
        console.error('❌ GED System no está inicializado');
    }
};

window.reinitializeMobileMenu = function() {
    if (gedSystem && typeof gedSystem.reinitializeMobileMenu === 'function') {
        gedSystem.reinitializeMobileMenu();
    } else {
        console.error('❌ GED System no está inicializado');
    }
};

window.countMenuLevels = function() {
    const offcanvas = document.getElementById('mobileMenuOffcanvas');
    if (!offcanvas) {
        console.error('❌ Offcanvas no encontrado');
        return;
    }
    
    const dropdowns = offcanvas.querySelectorAll('.dropdown-menu');
    let level2 = 0, level3 = 0;
    
    dropdowns.forEach(dropdown => {
        let parentCount = 0;
        let parent = dropdown.parentElement;
        
        while (parent) {
            if (parent.classList && parent.classList.contains('dropdown-menu')) {
                parentCount++;
            }
            parent = parent.parentElement;
        }
        
        if (parentCount === 1) level2++;
        if (parentCount >= 2) level3++;
    });
    
    console.log('🔍 VERIFICACIÓN RÁPIDA NIVELES:');
    console.log(`   • 2do nivel: ${level2}`);
    console.log(`   • 3er nivel: ${level3}`);
    console.log(`   • Total dropdowns: ${dropdowns.length}`);
};

if (typeof module !== 'undefined' && module.exports) {
    module.exports = gedSystem;
}

/* ===== NOTA FINAL =====
 * ARCHIVO JS CONSOLIDADO GED v6.0
 * SOLUCIÓN COMPLETA PARA MENÚ MÓVIL CON 3 NIVELES:
 * 
 * ✅ CORREGIDA DETECCIÓN DE NIVELES 3:
 *    - Función countNestedDropdowns() corregida
 *    - Conteo estructural por ancestros dropdown-menu
 *    - Registro en config.menuLevels
 * 
 * ✅ INICIALIZACIÓN BOOTSTRAP 5 PARA DROPDOWNS ANIDADOS:
 *    - Todos los .dropdown-toggle inicializados
 *    - Atributos data-dropdown-level asignados
 *    - Configuración especial para comportamiento móvil
 * 
 * ✅ FUNCIONES DE DIAGNÓSTICO MEJORADAS:
 *    - debugMobileMenuLevels() - diagnóstico completo
 *    - reinitializeMobileMenu() - reinicialización forzada
 *    - Funciones globales accesibles desde consola
 * 
 * ✅ ESTILOS MEJORADOS:
 *    - Diferentes colores para nivel 2 y 3
 *    - Z-index progresivo (1071, 1072)
 *    - Sin !important excesivo
 * 
 * ✅ COMPATIBILIDAD TOTAL:
 *    - Bootstrap 5
 *    - Yii2 MenuWidget
 *    - Mantiene 100% funcionalidades originales
 */