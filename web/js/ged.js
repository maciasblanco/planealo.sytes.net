// js/ged.js - Sistema GED - VERSIÓN 4.5 CORRECCIÓN FINAL
// Versión: 4.5.0 - Corrección completa de OffCanvas
// Fecha: 15/01/2024

// ==================================================
// MÓDULOS DEL SISTEMA - CON CONTROL DE INICIALIZACIÓN
// ==================================================

class GEDSystem {
    constructor() {
        // Evitar inicialización múltiple
        if (window.gedSystem) {
            console.warn('⚠️ Sistema GED ya está inicializado');
            return window.gedSystem;
        }
        
        this.isMobile = this.checkIsMobile();
        this.isDesktop = !this.isMobile;
        this.navbarHeight = this.getNavbarHeight();
        this.sidebarWidth = 0;
        this.compactMode = false;
        
        // Variables de padding optimizadas
        this.minPadding = 10;
        this.maxPaddingVH = 0.015;
        
        // Elementos del DOM
        this.navbar = null;
        this.body = document.body;
        this.html = document.documentElement;
        this.mainContent = null;
        
        // Controladores
        this.debouncedResize = this.debounce(() => this.handleResize(), 250);
        this.mutationObserver = null;
        
        // Estado del sistema
        this.currentPage = this.detectCurrentPage();
        this.modules = {};
        this._widthConfigApplied = false;
        this._initialized = false;
        
        // Marcar como inicializado globalmente
        window.gedSystem = this;
        
        this.init();
    }
    
    // ✅ DETECTAR PÁGINA ACTUAL
    detectCurrentPage() {
        const path = window.location.pathname;
        const bodyClasses = document.body.classList;
        
        if (path === '/' || path.includes('site/index') || 
            bodyClasses.contains('site-index') || 
            bodyClasses.contains('landing-page')) {
            return 'index';
        }
        
        if (path.includes('site/login')) return 'login';
        if (path.includes('site/signup')) return 'signup';
        if (path.includes('ged/default')) return 'ged';
        if (path.includes('tienda')) return 'tienda';
        
        return 'other';
    }
    
    // ✅ VERIFICAR SI ES MÓVIL
    checkIsMobile() {
        return window.innerWidth < 992;
    }
    
    // ✅ OBTENER ALTURA DEL NAVBAR (CORREGIDO)
    getNavbarHeight() {
        if (this.isMobile) {
            if (window.innerWidth < 576) return 60;
            if (window.innerWidth < 768) return 70;
            return 80;
        } else {
            // En escritorio: altura fija más consistente
            return 120;
        }
    }
    
    // ✅ INICIALIZACIÓN PRINCIPAL
    init() {
        if (this._initialized) {
            console.warn('⚠️ GEDSystem ya estaba inicializado');
            return;
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    // ✅ CONFIGURACIÓN COMPLETA DEL SISTEMA
    setup() {
        console.log(`🚀 Sistema GED v4.5 inicializando - Página: ${this.currentPage}, Modo: ${this.isMobile ? 'Móvil' : 'Escritorio'}`);
        
        try {
            // Aplicar parche para OffCanvas ANTES de cualquier otra inicialización
            this.applyOffCanvasPatch();
            
            // Cachear elementos DOM
            this.cacheElements();
            
            // Aplicar configuración de ancho completo inmediatamente
            this.applyFullWidthConfiguration();
            
            // Inicializar módulos base (siempre necesarios)
            this.modules = {
                navbar: new NavbarManager(),
                components: new ComponentsManager()
            };
            
            // ✅ INICIALIZAR MÓDULOS ESPECÍFICOS POR PÁGINA
            this.initializePageSpecificModules();
            
            // Configurar observadores y eventos
            this.setupObservers();
            this.bindEvents();
            
            // Aplicar correcciones iniciales
            this.applyBodyCorrections();
            
            // Verificación final
            setTimeout(() => {
                this.verifyWidth();
                this._initialized = true;
                this.logInitializationStatus();
            }, 300);
            
        } catch (error) {
            console.error('❌ Error crítico en inicialización del sistema:', error);
            this.showCriticalError('Error al inicializar el sistema GED');
        }
    }
    
    // ✅ APLICAR PARCHES PARA OFFCANVAS
    applyOffCanvasPatch() {
        console.log('🔧 Aplicando parches para OffCanvas...');
        
        // Parchear la clase OffCanvasSidebar si existe
        if (typeof window.OffCanvasSidebar !== 'undefined') {
            const OriginalOffCanvasSidebar = window.OffCanvasSidebar;
            
            // Crear clase parcheada
            class PatchedOffCanvasSidebar extends OriginalOffCanvasSidebar {
                constructor() {
                    super();
                    this._isMobile = window.innerWidth < 992;
                    console.log(`ℹ️ OffCanvasSidebar parcheado - Modo: ${this._isMobile ? 'Móvil' : 'Escritorio'}`);
                }
                
                loadMobileMenu() {
                    if (!this._isMobile) {
                        console.log('ℹ️ OffCanvas: loadMobileMenu deshabilitado en escritorio');
                        return;
                    }
                    return super.loadMobileMenu();
                }
                
                open() {
                    if (!this._isMobile) {
                        console.warn('⚠️ OffCanvas: open() deshabilitado en escritorio');
                        return;
                    }
                    return super.open();
                }
                
                init() {
                    if (!this._isMobile) {
                        console.log('ℹ️ OffCanvas: init() deshabilitado en escritorio');
                        return;
                    }
                    return super.init();
                }
                
                handleViewportChange(isMobile) {
                    this._isMobile = isMobile;
                    console.log(`ℹ️ OffCanvas cambió a modo: ${isMobile ? 'Móvil' : 'Escritorio'}`);
                    return super.handleViewportChange(isMobile);
                }
            }
            
            // Reemplazar la clase global
            window.OffCanvasSidebar = PatchedOffCanvasSidebar;
            console.log('✅ Clase OffCanvasSidebar parcheada globalmente');
            
            // Parchear instancia existente si existe
            if (window.gedOffcanvas && window.gedOffcanvas instanceof OriginalOffCanvasSidebar) {
                const originalProto = Object.getPrototypeOf(window.gedOffcanvas);
                
                // Crear nuevo prototipo con métodos parcheados
                const patchedProto = Object.create(originalProto);
                
                patchedProto.loadMobileMenu = function() {
                    if (!this._isMobile) {
                        console.log('ℹ️ gedOffcanvas: loadMobileMenu deshabilitado en escritorio');
                        return;
                    }
                    return originalProto.loadMobileMenu.call(this);
                };
                
                patchedProto.open = function() {
                    if (!this._isMobile) {
                        console.warn('⚠️ gedOffcanvas: open() deshabilitado en escritorio');
                        return;
                    }
                    return originalProto.open.call(this);
                };
                
                patchedProto.init = function() {
                    if (!this._isMobile) {
                        console.log('ℹ️ gedOffcanvas: init() deshabilitado en escritorio');
                        return;
                    }
                    return originalProto.init.call(this);
                };
                
                Object.setPrototypeOf(window.gedOffcanvas, patchedProto);
                window.gedOffcanvas._isMobile = window.innerWidth < 992;
                console.log('✅ Instancia gedOffcanvas parcheada');
            }
        } else {
            console.log('ℹ️ OffCanvasSidebar no encontrado, omitiendo parche');
        }
    }
    
    // ✅ INICIALIZAR MÓDULOS ESPECÍFICOS POR PÁGINA
    initializePageSpecificModules() {
        console.log(`📄 Inicializando módulos para página: ${this.currentPage}`);
        
        // Módulos que dependen de jQuery (solo si jQuery está disponible)
        if (typeof window.jQuery !== 'undefined') {
            this.modules.search = new SchoolSearch();
        }
        
        // LandingPageManager SOLO en página de inicio
        if (this.currentPage === 'index') {
            this.modules.landing = new LandingPageManager();
        }
        
        // Inicializar cada módulo
        Object.values(this.modules).forEach(module => {
            if (module && typeof module.init === 'function') {
                try {
                    module.init();
                } catch (moduleError) {
                    console.warn(`⚠️ Error al inicializar módulo ${module.constructor?.name || 'desconocido'}:`, moduleError);
                }
            }
        });
    }
    
    // ✅ CACHEAR ELEMENTOS DOM IMPORTANTES
    cacheElements() {
        this.navbar = document.querySelector('.navbar-contextual');
        this.mainContent = document.querySelector('.main-content-wrapper');
    }
    
    // ✅ APLICAR CONFIGURACIÓN DE ANCHO COMPLETO
    applyFullWidthConfiguration() {
        // Evitar aplicaciones múltiples
        if (this._widthConfigApplied && !this.isMobile) return;
        
        const padding = this.calculatePadding();
        console.log('🔧 Aplicando configuración de ancho completo...');
        
        // ✅ EN PC: SIN PADDING LATERAL EN BODY/HTML
        if (this.isDesktop) {
            // Body y HTML al 100% sin padding lateral
            this.html.style.paddingLeft = '0';
            this.html.style.paddingRight = '0';
            this.html.style.overflowX = 'hidden';
            this.html.style.width = '100vw';
            
            this.body.style.paddingLeft = '0';
            this.body.style.paddingRight = '0';
            this.body.style.marginLeft = '0';
            this.body.style.marginRight = '0';
            this.body.style.overflowX = 'hidden';
            this.body.style.width = '100vw';
            
            // Navbar al 100% con padding mínimo
            if (this.navbar) {
                this.navbar.style.width = '100vw';
                this.navbar.style.paddingLeft = `${padding}px`;
                this.navbar.style.paddingRight = `${padding}px`;
                this.navbar.style.boxSizing = 'border-box';
                this.navbar.style.left = '0';
                this.navbar.style.right = '0';
            }
            
            // Main content sin padding lateral forzado
            if (this.mainContent) {
                this.mainContent.style.paddingLeft = '0';
                this.mainContent.style.paddingRight = '0';
                this.mainContent.style.marginLeft = '0';
                this.mainContent.style.marginRight = '0';
                this.mainContent.style.width = '100%';
            }
            
            this._widthConfigApplied = true;
        } 
        // ✅ EN MÓVIL: CONFIGURACIÓN NORMAL
        else {
            if (this.navbar) {
                this.navbar.style.paddingLeft = `${padding}px`;
                this.navbar.style.paddingRight = `${padding}px`;
            }
            
            if (this.mainContent) {
                this.mainContent.style.paddingLeft = `${padding}px`;
                this.mainContent.style.paddingRight = `${padding}px`;
            }
        }
        
        console.log(`✅ Configuración aplicada: ${this.isDesktop ? 'PC (ancho completo)' : 'Móvil'} - Padding: ${padding}px`);
    }
    
    // ✅ CALCULAR PADDING DINÁMICO
    calculatePadding() {
        if (this.isDesktop) {
            return Math.max(15, this.minPadding);
        } else {
            const vhPadding = window.innerHeight * this.maxPaddingVH;
            return Math.max(vhPadding, this.minPadding);
        }
    }
    
    // ✅ VERIFICAR ANCHO DEL SISTEMA
    verifyWidth() {
        const bodyWidth = this.body.offsetWidth;
        const viewportWidth = window.innerWidth;
        const difference = Math.abs(bodyWidth - viewportWidth);
        
        const isCorrect = difference < 5;
        console.log(`📏 Verificación de ancho: Body ${bodyWidth}px, Viewport ${viewportWidth}px, Diferencia ${difference}px - ${isCorrect ? '✅ Correcto' : '⚠️ Desbordamiento'}`);
        
        return isCorrect;
    }
    
    // ✅ LOG DE ESTADO DE INICIALIZACIÓN
    logInitializationStatus() {
        console.log('📊 RESUMEN DE INICIALIZACIÓN:');
        console.log(`  • Página: ${this.currentPage}`);
        console.log(`  • Modo: ${this.isMobile ? 'Móvil' : 'Escritorio'}`);
        console.log(`  • Módulos cargados: ${Object.keys(this.modules).join(', ')}`);
        console.log(`  • Navbar height: ${this.navbarHeight}px`);
        console.log(`  • Padding actual: ${this.calculatePadding()}px`);
        console.log(`  • Sistema inicializado: ${this._initialized ? '✅ Sí' : '❌ No'}`);
        console.log('✅ Sistema GED v4.5 completamente inicializado');
    }
    
    // ✅ CONFIGURAR OBSERVADORES DE CAMBIOS
    setupObservers() {
        // Observar cambios en el DOM para ajustar ancho
        const observer = new MutationObserver(() => {
            setTimeout(() => {
                if (!this._widthConfigApplied || this.isMobile) {
                    this.applyFullWidthConfiguration();
                }
                this.fixOverflowIssues();
            }, 100);
        });
        
        observer.observe(this.body, {
            childList: true,
            subtree: true,
            attributes: false
        });
    }
    
    // ✅ VINCULAR EVENTOS
    bindEvents() {
        window.addEventListener('resize', this.debouncedResize);
        
        // Eventos personalizados
        window.addEventListener('ged:updateLayout', () => {
            this.applyFullWidthConfiguration();
        });
    }
    
    // ✅ APLICAR CORRECCIONES AL BODY
    applyBodyCorrections() {
        try {
            this.navbarHeight = this.getNavbarHeight();
            
            // Ajustar padding-top del body para el navbar fijo
            this.body.style.paddingTop = `${this.navbarHeight}px`;
            
            // Ajustar altura mínima de elementos main
            const mainElements = document.querySelectorAll('main#main, .main-container');
            mainElements.forEach(main => {
                main.style.minHeight = `calc(100vh - ${this.navbarHeight}px)`;
                main.style.boxSizing = 'border-box';
            });
            
            console.log(`✅ Correcciones body aplicadas - Navbar height: ${this.navbarHeight}px`);
        } catch (error) {
            console.error('Error en applyBodyCorrections:', error);
        }
    }
    
    // ✅ MANEJAR CAMBIOS DE TAMAÑO
    handleResize() {
        try {
            const newIsMobile = this.checkIsMobile();
            const changed = newIsMobile !== this.isMobile;
            
            this.isMobile = newIsMobile;
            this.isDesktop = !newIsMobile;
            
            if (changed) {
                console.log(`🔄 Cambio de modo: ${this.isMobile ? 'Móvil' : 'Escritorio'}`);
                
                // Resetear configuración de ancho
                this._widthConfigApplied = false;
                
                // Recalcular alturas
                this.navbarHeight = this.getNavbarHeight();
                
                // Reaplicar configuración completa
                this.applyFullWidthConfiguration();
                this.applyBodyCorrections();
                
                // Actualizar estado de OffCanvas si existe
                if (window.gedOffcanvas && typeof window.gedOffcanvas.handleViewportChange === 'function') {
                    window.gedOffcanvas.handleViewportChange(this.isMobile);
                }
            }
            
            // Recalcular y ajustar
            this.applyFullWidthConfiguration();
            this.applyBodyCorrections();
            
            // Verificar overflow después de resize
            setTimeout(() => this.fixOverflowIssues(), 150);
            
        } catch (error) {
            console.error('Error en handleResize:', error);
        }
    }
    
    // ✅ ARREGLAR PROBLEMAS DE OVERFLOW
    fixOverflowIssues() {
        try {
            const bodyWidth = this.body.offsetWidth;
            const viewportWidth = window.innerWidth;
            const difference = bodyWidth - viewportWidth;
            
            if (difference > 10) { // Tolerancia de 10px
                console.warn(`⚠️ Overflow detectado: Body ${bodyWidth}px > Viewport ${viewportWidth}px (Diff: ${difference}px)`);
                
                // Forzar hide de overflow horizontal
                this.body.style.overflowX = 'hidden';
                this.html.style.overflowX = 'hidden';
                
                // Reducir padding si hay overflow significativo
                if (difference > 30) {
                    console.log('🔄 Reduciendo padding para corregir overflow...');
                    this.minPadding = Math.max(5, this.minPadding - 5);
                    this.applyFullWidthConfiguration();
                }
            }
        } catch (error) {
            console.error('Error en fixOverflowIssues:', error);
        }
    }
    
    // ✅ DEBOUNCE PARA EVENTOS DE RESIZE
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // ✅ MOSTRAR ERROR CRÍTICO
    showCriticalError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            border-radius: 6px;
            z-index: 9999;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 400px;
        `;
        errorDiv.textContent = `⚠️ ${message}. Por favor, recarga la página.`;
        errorDiv.innerHTML += `<br><small style="opacity:0.8">Error en Sistema GED v4.5</small>`;
        document.body.appendChild(errorDiv);
        
        // Auto-eliminar después de 10 segundos
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 10000);
    }
    
    // ✅ MÉTODOS PÚBLICOS PARA CONTROL EXTERNO
    forceLayoutUpdate() {
        this._widthConfigApplied = false;
        this.applyFullWidthConfiguration();
        this.applyBodyCorrections();
        this.fixOverflowIssues();
        console.log('🔄 Layout actualizado manualmente');
    }
    
    checkLayout() {
        const bodyWidth = this.body.offsetWidth;
        const viewportWidth = window.innerWidth;
        const difference = bodyWidth - viewportWidth;
        
        const report = {
            bodyWidth,
            viewportWidth,
            difference,
            hasOverflow: difference > 10,
            currentMode: this.isMobile ? 'Móvil' : 'Escritorio',
            currentPage: this.currentPage,
            navbarHeight: this.navbarHeight,
            minPadding: this.minPadding,
            maxPaddingVH: this.maxPaddingVH,
            modulesLoaded: Object.keys(this.modules).filter(key => this.modules[key]),
            initialized: this._initialized
        };
        
        console.table(report);
        return report;
    }
    
    // ✅ ACTUALIZAR CONFIGURACIÓN DE PADDING
    updatePaddingConfig(minPx = 10, maxVH = 0.015) {
        this.minPadding = minPx;
        this.maxPaddingVH = maxVH;
        this._widthConfigApplied = false;
        this.applyFullWidthConfiguration();
        console.log(`🔧 Configuración actualizada: mínimo ${minPx}px, máximo ${maxVH*100}vh`);
    }
    
    // ✅ OBTENER ESTADO ACTUAL
    getCurrentState() {
        return {
            version: '4.5.0',
            isMobile: this.isMobile,
            isDesktop: this.isDesktop,
            currentPage: this.currentPage,
            navbarHeight: this.navbarHeight,
            minPadding: this.minPadding,
            maxPaddingVH: this.maxPaddingVH,
            viewportWidth: window.innerWidth,
            bodyWidth: this.body.offsetWidth,
            initialized: this._initialized,
            offCanvasPatched: typeof window.OffCanvasSidebar !== 'undefined'
        };
    }
}

// ==================================================
// NAVBAR MANAGER - CORREGIDO
// ==================================================

class NavbarManager {
    constructor() {
        this.navbar = null;
        this.isMobile = window.innerWidth < 992;
    }
    
    init() {
        try {
            this.navbar = document.querySelector('.navbar-contextual');
            
            if (!this.navbar) {
                console.warn('❌ Navbar contextual no encontrado');
                return;
            }
            
            // ✅ FORZAR ANCHO COMPLETO
            this.forceFullWidth();
            
            // ✅ Configuración específica por dispositivo
            if (this.isMobile) {
                this.configureForMobile();
            } else {
                this.configureForDesktop();
            }
            
            this.initNavbarEscuelaSelector();
            
            console.log('✅ NavbarManager inicializado');
        } catch (error) {
            console.error('Error en NavbarManager.init:', error);
        }
    }
    
    forceFullWidth() {
        try {
            // Estilos críticos para ancho completo
            const fullWidthStyles = `
                width: 100vw !important;
                max-width: 100vw !important;
                min-width: 100vw !important;
                left: 0 !important;
                right: 0 !important;
                box-sizing: border-box !important;
            `;
            
            // Aplicar al navbar
            this.navbar.style.cssText += fullWidthStyles;
        } catch (error) {
            console.error('Error en forceFullWidth:', error);
        }
    }
    
    configureForMobile() {
        try {
            // Ocultar elementos no esenciales en móvil
            const toHide = [
                '.navbar-social-section',
                '.school-info',
                '.user-info'
            ];
            
            toHide.forEach(selector => {
                const element = this.navbar.querySelector(selector);
                if (element) {
                    element.style.display = 'none';
                }
            });
            
            console.log('✅ Navbar configurado para móvil');
        } catch (error) {
            console.error('Error en configureForMobile:', error);
        }
    }
    
    configureForDesktop() {
        try {
            // Mostrar elementos de desktop
            const toShow = [
                '.navbar-social-section',
                '.school-info',
                '.user-info'
            ];
            
            toShow.forEach(selector => {
                const element = this.navbar.querySelector(selector);
                if (element) {
                    element.style.display = '';
                }
            });
            
            console.log('✅ Navbar configurado para escritorio');
        } catch (error) {
            console.error('Error en configureForDesktop:', error);
        }
    }
    
    initNavbarEscuelaSelector() {
        try {
            const escuelaSelect = document.getElementById('navbar-escuela-select');
            if (escuelaSelect) {
                escuelaSelect.addEventListener('change', function() {
                    const escuelaId = this.value;
                    if (escuelaId && escuelaId > 0) {
                        const escuelaNombre = this.options[this.selectedIndex].text;
                        window.location.href = '/ged/default/escuela?id=' + escuelaId + '&nombre=' + encodeURIComponent(escuelaNombre);
                    }
                });
                console.log('✅ Selector de escuelas del navbar inicializado');
            }
        } catch (error) {
            console.error('Error en initNavbarEscuelaSelector:', error);
        }
    }
}

// ==================================================
// SCHOOL SEARCH MANAGER - OPTIMIZADO
// ==================================================

class SchoolSearch {
    constructor() {
        this.searchTimeout = null;
        this.elements = {};
        this.selectors = {
            searchInput: '#schoolSearch',
            searchResults: '#schoolSearchResults',
            searchBtn: '#searchSchoolBtn',
            currentSchool: '#current-school',
            currentSchoolId: '#current-school-id'
        };
        this.urls = {
            search: '/ged/default/search-schools',
            setSchool: '/ged/default/set-school'
        };
    }
    
    init() {
        try {
            if (typeof window.jQuery === 'undefined') {
                console.warn('⚠️ jQuery no está cargado. SchoolSearch desactivado.');
                return;
            }
            
            if (!document.querySelector('#schoolSearch')) {
                console.log('ℹ️ SchoolSearch: No se encontró campo de búsqueda, omitiendo');
                return;
            }
            
            this.cacheElements();
            this.bindEvents();
            console.log('✅ SchoolSearch inicializado');
        } catch (error) {
            console.error('Error en SchoolSearch.init:', error);
        }
    }
    
    cacheElements() {
        try {
            for (const [key, selector] of Object.entries(this.selectors)) {
                this.elements[key] = $(selector);
            }
        } catch (error) {
            console.error('Error en cacheElements:', error);
        }
    }
    
    bindEvents() {
        try {
            const { searchInput, searchResults, searchBtn } = this.elements;
            if (searchInput.length === 0) return;
            
            searchInput.on('input', (e) => this.handleSearchInput(e.target.value.trim()));
            searchBtn.on('click', () => this.handleSearchClick());
            searchInput.on('keypress', (e) => {
                if (e.which === 13) this.handleEnterKey(e);
            });
            
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.school-search-container').length) {
                    this.hideResults();
                }
            });
        } catch (error) {
            console.error('Error en bindEvents:', error);
        }
    }
    
    handleSearchInput(query) {
        clearTimeout(this.searchTimeout);
        
        if (query.length < 2) {
            this.hideResults();
            return;
        }
        
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, 300);
    }
    
    handleSearchClick() {
        const query = this.elements.searchInput.val().trim();
        if (query.length >= 2) {
            this.performSearch(query);
        } else {
            this.elements.searchInput.focus();
        }
    }
    
    handleEnterKey(e) {
        const query = this.elements.searchInput.val().trim();
        if (query.length >= 2) {
            this.performSearch(query);
            e.preventDefault();
        }
    }
    
    performSearch(query) {
        try {
            this.showLoading();
            
            $.ajax({
                url: this.urls.search,
                type: 'GET',
                data: { 
                    q: query,
                    _csrf: $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => this.displayResults(response),
                error: (xhr, status, error) => {
                    console.error('Error en la búsqueda:', error);
                    this.showError('Error en la búsqueda');
                }
            });
        } catch (error) {
            console.error('Error en performSearch:', error);
            this.showError('Error en la búsqueda');
        }
    }
    
    showLoading() {
        this.elements.searchResults.html('<div class="search-result-item text-muted">Buscando...</div>').show();
    }
    
    showError(message) {
        this.elements.searchResults.html(`<div class="search-result-item text-danger">${message}</div>`).show();
    }
    
    displayResults(escuelas) {
        try {
            const { searchResults } = this.elements;
            searchResults.empty();
            
            if (!escuelas || escuelas.length === 0) {
                searchResults.append('<div class="search-result-item text-muted">No se encontraron escuelas</div>');
            } else {
                escuelas.forEach((escuela) => this.createResultItem(escuela));
            }
            
            searchResults.show();
        } catch (error) {
            console.error('Error en displayResults:', error);
        }
    }
    
    createResultItem(escuela) {
        try {
            const item = $('<div class="search-result-item"></div>');
            let escuelaInfo = `<div class="school-name">${escuela.nombre}</div><div class="school-id">ID: ${escuela.id}</div>`;
            
            if (escuela.direccion_administrativa) {
                escuelaInfo += `<div class="school-address text-muted">${escuela.direccion_administrativa}</div>`;
            }
            
            item.html(escuelaInfo);
            item.on('click', () => this.selectSchool(escuela));
            this.elements.searchResults.append(item);
        } catch (error) {
            console.error('Error en createResultItem:', error);
        }
    }
    
    selectSchool(escuela) {
        try {
            const originalHtml = this.elements.searchBtn.html();
            this.elements.searchBtn.html('<i class="bi bi-hourglass-split"></i>').prop('disabled', true);
            
            $.ajax({
                url: this.urls.setSchool,
                type: 'POST',
                data: {
                    schoolId: escuela.id,
                    schoolName: escuela.nombre,
                    _csrf: $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    if (response.success) {
                        this.updateUI(escuela);
                        this.showNotification('Escuela seleccionada: ' + escuela.nombre, 'success');
                        this.reloadPage();
                    } else {
                        this.showNotification('Error al seleccionar la escuela', 'error');
                    }
                },
                error: () => this.showNotification('Error de conexión', 'error'),
                complete: () => {
                    this.elements.searchBtn.html(originalHtml).prop('disabled', false);
                }
            });
        } catch (error) {
            console.error('Error en selectSchool:', error);
            this.showNotification('Error al seleccionar escuela', 'error');
        }
    }
    
    updateUI(escuela) {
        this.elements.currentSchool.text(escuela.nombre);
        this.elements.currentSchoolId.text('ID: ' + escuela.id).show();
        this.elements.searchInput.val('');
        this.hideResults();
    }
    
    hideResults() {
        this.elements.searchResults.hide().empty();
    }
    
    showNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" 
                 style="position: fixed; top: 20px; right: 20px; z-index: 10000;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(alert);
        
        setTimeout(() => {
            alert.alert('close');
        }, 3000);
    }
    
    reloadPage() {
        setTimeout(() => location.reload(), 800);
    }
}

// ==================================================
// COMPONENTS MANAGER - OPTIMIZADO
// ==================================================

class ComponentsManager {
    constructor() {
        this.tooltipsInitialized = false;
    }
    
    init() {
        try {
            this.initTooltips();
            this.initEscuelaSelector();
            this.initCarousel();
            console.log('✅ ComponentsManager inicializado');
        } catch (error) {
            console.error('Error en ComponentsManager.init:', error);
        }
    }
    
    initTooltips() {
        try {
            if (typeof bootstrap !== 'undefined' && !this.tooltipsInitialized) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
                this.tooltipsInitialized = true;
                console.log('✅ Tooltips inicializados');
            }
        } catch (error) {
            console.error('Error en initTooltips:', error);
        }
    }
    
    initEscuelaSelector() {
        try {
            if (typeof $ === 'undefined') return;
            
            $('.back-to-top').on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({scrollTop: 0}, 300);
            });
            
            $(window).on('scroll', function() {
                $('.back-to-top').toggleClass('active', $(this).scrollTop() > 300);
            });
            
            console.log('✅ Escuela selector inicializado');
        } catch (error) {
            console.error('Error en initEscuelaSelector:', error);
        }
    }
    
    initCarousel() {
        try {
            const carouselHero = document.getElementById('carouselHero');
            if (carouselHero && typeof bootstrap !== 'undefined') {
                new bootstrap.Carousel(carouselHero, {
                    interval: 5000,
                    ride: 'carousel',
                    wrap: true,
                    pause: 'hover'
                });
                console.log('✅ Carrusel Hero inicializado');
            }
        } catch (error) {
            console.error('Error en initCarousel:', error);
        }
    }
}

// ==================================================
// LANDING PAGE MANAGER - OPTIMIZADO
// ==================================================

class LandingPageManager {
    constructor() {
        this.productos = {};
        this.carrito = [];
        this.totalVendidos = 0;
        this.cachedElements = {};
        this.observer = null;
    }

    init() {
        try {
            // Verificar que estamos en una página de landing
            const hasLandingElements = document.querySelector('.landing-page, .site-index, #hero-carousel');
            if (!hasLandingElements) {
                console.log('ℹ️ LandingPageManager: No se detectó página de inicio, omitiendo inicialización');
                return;
            }
            
            console.log('🚀 Landing Page Manager inicializando...');
            
            // Solo cargar productos si hay contenedores
            this.checkProductContainers();
            
            // Inicializar solo las funcionalidades necesarias
            this.setupEventListeners();
            this.initAnimaciones();
            this.initMarketplace();
            
            console.log('✅ LandingPageManager listo');
        } catch (error) {
            console.error('Error en LandingPageManager.init:', error);
        }
    }

    // ✅ VERIFICAR CONTENEDORES DE PRODUCTOS
    checkProductContainers() {
        const categorias = ['vestimenta', 'alimentacion', 'implementos-deportivos', 'suplementos'];
        let hasContainers = false;
        
        categorias.forEach(categoria => {
            const contenedor = document.getElementById(`productos-${categoria}`);
            if (contenedor) {
                hasContainers = true;
            }
        });
        
        if (hasContainers) {
            this.cargarProductos();
            this.renderizarProductos();
            this.actualizarTotalVendidos();
            this.mostrarBannerTiendas();
        } else {
            console.log('ℹ️ LandingPageManager: No se encontraron contenedores de productos');
        }
    }

    cargarProductos() {
        try {
            this.productos = {
                vestimenta: [
                    { id: 1, nombre: 'Camiseta Deportiva', precio: 25, vendidos: 150 },
                    { id: 2, nombre: 'Pantalón Deportivo', precio: 35, vendidos: 120 },
                    { id: 3, nombre: 'Sudadera con Capucha', precio: 45, vendidos: 95 }
                ],
                alimentacion: [
                    { id: 4, nombre: 'Barra Energética', precio: 3, vendidos: 200 },
                    { id: 5, nombre: 'Bebida Isotónica', precio: 2, vendidos: 180 },
                    { id: 6, nombre: 'Snack Proteico', precio: 4, vendidos: 150 }
                ],
                'implementos-deportivos': [
                    { id: 7, nombre: 'Balón de Fútbol', precio: 20, vendidos: 80 },
                    { id: 8, nombre: 'Cuerda para Saltar', precio: 10, vendidos: 75 },
                    { id: 9, nombre: 'Banda Elástica', precio: 15, vendidos: 90 }
                ],
                suplementos: [
                    { id: 10, nombre: 'Proteína en Polvo', precio: 50, vendidos: 110 },
                    { id: 11, nombre: 'Multivitamínico', precio: 15, vendidos: 85 },
                    { id: 12, nombre: 'Creatina', precio: 30, vendidos: 70 }
                ]
            };
            console.log('✅ Productos cargados');
        } catch (error) {
            console.error('Error en cargarProductos:', error);
        }
    }

    renderizarProductos() {
        try {
            for (const categoria in this.productos) {
                const contenedor = document.getElementById(`productos-${categoria}`);
                if (!contenedor) continue;

                contenedor.innerHTML = '';
                this.productos[categoria].forEach(producto => {
                    const productoHTML = `
                        <div class="producto-card">
                            <h3>${producto.nombre}</h3>
                            <p class="precio">$${producto.precio}</p>
                            <p class="vendidos">${producto.vendidos} vendidos</p>
                            <button class="btn-agregar-carrito" 
                                    data-id="${producto.id}"
                                    data-nombre="${producto.nombre}"
                                    data-precio="${producto.precio}">
                                Agregar al carrito
                            </button>
                        </div>
                    `;
                    contenedor.insertAdjacentHTML('beforeend', productoHTML);
                });
            }
            console.log('✅ Productos renderizados');
        } catch (error) {
            console.error('Error en renderizarProductos:', error);
        }
    }

    actualizarTotalVendidos() {
        try {
            this.totalVendidos = 0;
            for (const categoria in this.productos) {
                this.productos[categoria].forEach(producto => {
                    this.totalVendidos += producto.vendidos;
                });
            }
            
            const totalElement = document.getElementById('total-productos-vendidos');
            if (totalElement) {
                totalElement.textContent = this.totalVendidos.toLocaleString();
            }
            console.log('✅ Total vendidos actualizado:', this.totalVendidos);
        } catch (error) {
            console.error('Error en actualizarTotalVendidos:', error);
        }
    }

    setupEventListeners() {
        try {
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-agregar-carrito')) {
                    const id = parseInt(e.target.dataset.id);
                    const nombre = e.target.dataset.nombre;
                    const precio = parseFloat(e.target.dataset.precio);
                    
                    this.agregarAlCarrito(id, nombre, precio);
                    
                    e.target.textContent = '✓ Agregado';
                    e.target.disabled = true;
                    setTimeout(() => {
                        e.target.textContent = 'Agregar al carrito';
                        e.target.disabled = false;
                    }, 1500);
                }
            });
            
            const accederBtn = document.getElementById('btn-acceder-sistema');
            if (accederBtn) this.enhanceAccederButton(accederBtn);
            
            const marketplaceBtn = document.getElementById('btn-marketplace');
            if (marketplaceBtn) this.enhanceMarketplaceButton(marketplaceBtn);
            
            const logo = document.getElementById('ged-main-logo');
            if (logo) this.addLogoAnimation(logo);
            
            if (typeof $ !== 'undefined') {
                $('#main-escuela-select').on('change', function() {
                    const escuelaId = $(this).val();
                    if (escuelaId && escuelaId > 0) {
                        const escuelaNombre = $(this).find('option:selected').text();
                        window.location.href = '/ged/default/escuela?id=' + escuelaId + '&nombre=' + encodeURIComponent(escuelaNombre);
                    }
                });

                $('#filtro-escuelas').on('input', function() {
                    const filtro = $(this).val().toLowerCase();
                    $('.escuela-item').each(function() {
                        const nombre = $(this).find('.school-name').text().toLowerCase();
                        $(this).toggle(nombre.includes(filtro));
                    });
                });

                $('#btn-limpiar-filtro').on('click', function() {
                    $('#filtro-escuelas').val('').trigger('input');
                });

                $('.school-card').hover(
                    function() { $(this).addClass('shadow-lg'); },
                    function() { $(this).removeClass('shadow-lg'); }
                );

                $('a[href^="#"]').on('click', function(event) {
                    const target = $(this).attr('href');
                    if (target && target !== '#' && $(target).length) {
                        event.preventDefault();
                        $('html, body').stop().animate({
                            scrollTop: $(target).offset().top - 100
                        }, 1000);
                    }
                });
            }
            
            console.log('✅ Event listeners configurados');
        } catch (error) {
            console.error('Error en setupEventListeners:', error);
        }
    }

    agregarAlCarrito(id, nombre, precio) {
        try {
            const productoExistente = this.carrito.find(p => p.id === id);
            
            if (productoExistente) {
                productoExistente.cantidad++;
            } else {
                this.carrito.push({ id, nombre, precio, cantidad: 1 });
            }
            
            this.actualizarContadorCarrito();
            this.mostrarNotificacion(`${nombre} agregado al carrito`);
            console.log('✅ Producto agregado al carrito:', nombre);
        } catch (error) {
            console.error('Error en agregarAlCarrito:', error);
        }
    }

    actualizarContadorCarrito() {
        try {
            const contador = document.getElementById('contador-carrito');
            if (contador) {
                const totalItems = this.carrito.reduce((sum, item) => sum + item.cantidad, 0);
                contador.textContent = totalItems;
                contador.style.display = totalItems > 0 ? 'block' : 'none';
            }
        } catch (error) {
            console.error('Error en actualizarContadorCarrito:', error);
        }
    }

    mostrarNotificacion(mensaje) {
        try {
            let notificacion = document.getElementById('notificacion-carrito');
            
            if (!notificacion) {
                notificacion = document.createElement('div');
                notificacion.id = 'notificacion-carrito';
                notificacion.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #4CAF50;
                    color: white;
                    padding: 15px 25px;
                    border-radius: 5px;
                    z-index: 1000;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    animation: slideIn 0.3s ease-out;
                `;
                document.body.appendChild(notificacion);
            }
            
            notificacion.textContent = mensaje;
            notificacion.style.display = 'block';
            
            setTimeout(() => {
                notificacion.style.display = 'none';
            }, 3000);
        } catch (error) {
            console.error('Error en mostrarNotificacion:', error);
        }
    }

    mostrarBannerTiendas() {
        try {
            const banner = document.getElementById('banner-tiendas-patrocinadas');
            if (banner) {
                banner.style.cssText = `
                    width: 60%;
                    margin: 20px auto;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-radius: 10px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                `;
                
                banner.innerHTML = `
                    <h2 style="color: #2c3e50; margin-bottom: 15px;">🏪 Tiendas Patrocinadas</h2>
                    <p style="color: #7f8c8d; margin-bottom: 20px;">
                        Descubre nuestras tiendas aliadas con los mejores productos deportivos y descuentos exclusivos.
                    </p>
                    <div style="display: flex; justify-content: space-around; flex-wrap: wrap;">
                        <div class="tienda" style="text-align: center; margin: 10px;">
                            <div style="background: #3498db; color: white; width: 60px; height: 60px; 
                                        line-height: 60px; border-radius: 50%; margin: 0 auto 10px;">🏀</div>
                            <p style="font-weight: bold;">Deportes Total</p>
                            <p style="font-size: 0.9em;">15% descuento</p>
                        </div>
                        <div class="tienda" style="text-align: center; margin: 10px;">
                            <div style="background: #2ecc71; color: white; width: 60px; height: 60px; 
                                        line-height: 60px; border-radius: 50%; margin: 0 auto 10px;">👟</div>
                            <p style="font-weight: bold;">Running Pro</p>
                            <p style="font-size: 0.9em;">Envío gratis</p>
                        </div>
                        <div class="tienda" style="text-align: center; margin: 10px;">
                            <div style="background: #e74c3c; color: white; width: 60px; height: 60px; 
                                        line-height: 60px; border-radius: 50%; margin: 0 auto 10px;">🥤</div>
                            <p style="font-weight: bold;">NutriSport</p>
                            <p style="font-size: 0.9em;">2x1 en suplementos</p>
                        </div>
                    </div>
                `;
                console.log('✅ Banner tiendas mostrado');
            }
        } catch (error) {
            console.error('Error en mostrarBannerTiendas:', error);
        }
    }
    
    initAnimaciones() {
        try {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                        
                        if (entry.target.classList.contains('feature-card')) {
                            setTimeout(() => {
                                entry.target.style.opacity = '1';
                                entry.target.style.transform = 'translateY(0)';
                            }, 100);
                        }
                        
                        if (entry.target.classList.contains('categoria-card')) {
                            setTimeout(() => {
                                entry.target.style.opacity = '1';
                                entry.target.style.transform = 'translateY(0)';
                            }, 200);
                        }
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.feature-card, .categoria-card').forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                this.observer.observe(element);
            });
            
            console.log('✅ Animaciones inicializadas');
        } catch (error) {
            console.error('Error en initAnimaciones:', error);
        }
    }
    
    initMarketplace() {
        try {
            if (!document.querySelector('.landing-page')) return;
            
            const marketplaceMenu = document.querySelector('.marketplace-nav');
            if (!marketplaceMenu) return;
            
            const dropdownToggles = marketplaceMenu.querySelectorAll('.dropdown-toggle');
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    if (window.innerWidth < 992) {
                        e.preventDefault();
                        const dropdownMenu = this.nextElementSibling;
                        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
                    }
                });
            });
            
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.marketplace-nav .dropdown')) {
                    marketplaceMenu.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                }
            });
            
            marketplaceMenu.querySelectorAll('a[href="#"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (window.innerWidth >= 992) e.preventDefault();
                });
            });
            
            document.querySelectorAll('.marketplace-nav .nav-link').forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.02)';
                });
                link.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });
            
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    const dropdowns = marketplaceMenu.querySelectorAll('.dropdown-menu');
                    if (window.innerWidth >= 992) {
                        dropdowns.forEach(menu => menu.style.display = '');
                    } else {
                        dropdowns.forEach(menu => menu.style.display = 'none');
                    }
                }, 250);
            });
            
            console.log('✅ Marketplace inicializado');
        } catch (error) {
            console.error('Error en initMarketplace:', error);
        }
    }
    
    enhanceAccederButton(button) {
        button.addEventListener('click', (e) => {
            const originalText = button.innerHTML;
            button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Accediendo...`;
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 1500);
        });
        
        button.addEventListener('mouseenter', () => {
            button.style.boxShadow = '0 15px 30px rgba(40, 167, 69, 0.3)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.boxShadow = '';
        });
    }
    
    enhanceMarketplaceButton(button) {
        button.addEventListener('click', (e) => {
            button.classList.add('pulse-animation');
            setTimeout(() => button.classList.remove('pulse-animation'), 500);
        });
        
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'scale(1.05) rotate(2deg)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.transform = '';
        });
    }
    
    addLogoAnimation(logo) {
        logo.addEventListener('mouseenter', () => {
            logo.style.transform = 'scale(1.1) rotate(5deg)';
            logo.style.filter = 'drop-shadow(0 8px 16px rgba(0,0,0,0.4))';
        });
        
        logo.addEventListener('mouseleave', () => {
            logo.style.transform = '';
            logo.style.filter = 'drop-shadow(0 4px 8px rgba(0,0,0,0.3))';
        });
        
        setTimeout(() => {
            logo.style.transition = 'transform 0.5s ease, filter 0.5s ease';
        }, 100);
    }
}

// ==================================================
// INICIALIZACIÓN GLOBAL CONTROLADA
// ==================================================

// Control de inicialización única
let gedSystemInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
    // Verificar si ya está inicializado
    if (gedSystemInitialized || window.gedSystem) {
        console.log('ℹ️ Sistema GED ya estaba inicializado, omitiendo');
        return;
    }
    
    // Inicializar con un pequeño delay para evitar conflictos
    setTimeout(() => {
        if (!gedSystemInitialized && !window.gedSystem) {
            try {
                new GEDSystem();
                gedSystemInitialized = true;
                console.log('🌐 Sistema GED v4.5 inicializado correctamente');
            } catch (error) {
                console.error('❌ Error al inicializar Sistema GED:', error);
            }
        }
    }, 100);
});

// ==================================================
// FUNCIONES DE DEBUG Y CONTROL
// ==================================================

if (typeof window !== 'undefined') {
    // Función de debug
    window.debugGED = function() {
        if (window.gedSystem) {
            console.group('🐛 DEBUG SISTEMA GED 4.5');
            console.log('Estado:', window.gedSystem.getCurrentState());
            console.log('Layout check:', window.gedSystem.checkLayout());
            console.groupEnd();
        } else {
            console.error('Sistema GED no inicializado');
        }
    };
    
    // Función para forzar actualización
    window.updateGEDLayout = function() {
        if (window.gedSystem) {
            window.gedSystem.forceLayoutUpdate();
        }
    };
    
    // Función para reiniciar el sistema (solo para desarrollo)
    window.restartGED = function() {
        if (window.gedSystem) {
            window.gedSystem._initialized = false;
            window.gedSystem._widthConfigApplied = false;
            gedSystemInitialized = false;
            window.gedSystem = null;
            
            setTimeout(() => {
                new GEDSystem();
                console.log('🔄 Sistema GED reiniciado manualmente');
            }, 100);
        }
    };
}

// ==================================================
// CORRECCIÓN DE EMERGENCIA PARA CARRUSEL
// ==================================================

function corregirCarruselUrgente() {
    const carrusel = document.getElementById('carouselHero');
    if (!carrusel) return;
    
    console.log('🚨 Aplicando corrección urgente al carrusel...');
    
    // Solo aplicar si estamos en escritorio
    const isDesktop = window.innerWidth >= 992;
    if (isDesktop) {
        const imagenes = document.querySelectorAll('#carouselHero .carousel-item img');
        imagenes.forEach(img => {
            img.style.width = '100vw';
            img.style.maxWidth = '100vw';
            img.style.objectFit = 'cover';
        });
    }
    
    console.log('✅ Corrección urgente aplicada al carrusel');
}

// Ejecutar solo una vez después de carga completa
let carruselCorregido = false;
window.addEventListener('load', function() {
    if (!carruselCorregido) {
        setTimeout(corregirCarruselUrgente, 500);
        carruselCorregido = true;
    }
});

// ==================================================
// FUNCIONES DE UTILIDAD
// ==================================================

// Verificar imágenes rotas
function checkBrokenImages() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        if (img.complete && img.naturalHeight === 0) {
            console.warn('⚠️ Imagen rota detectada:', img.src);
            // Reemplazar con imagen de respaldo
            img.style.backgroundColor = '#f8f9fa';
            img.style.padding = '20px';
        }
    });
}

// Ejecutar chequeo de imágenes
window.addEventListener('load', function() {
    setTimeout(checkBrokenImages, 1000);
});

// ==================================================
// COMPATIBILIDAD CON MÓDULOS EXISTENTES
// ==================================================

// Esto asegura compatibilidad con scripts antiguos que esperan gedSystem
if (typeof window !== 'undefined') {
    // Variable global para compatibilidad
    window.GED_SYSTEM_LOADED = true;
    window.GED_SYSTEM_VERSION = '4.5.0';
}

// Exportar para módulos (si es necesario)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        GEDSystem,
        NavbarManager,
        SchoolSearch,
        ComponentsManager,
        LandingPageManager
    };
}