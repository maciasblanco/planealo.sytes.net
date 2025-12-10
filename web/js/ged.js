// js/ged.js - Sistema GED - VERSIÓN 4.1 CON PADDING MÍNIMO
// Versión: 4.1.0 - Padding máximo de 1vh en todos los elementos
// Fecha: [Fecha actual]

// ==================================================
// MÓDULOS DEL SISTEMA - CON PADDING MÍNIMO
// ==================================================

class GEDSystem {
    constructor() {
        this.isMobile = this.checkIsMobile();
        this.isDesktop = !this.isMobile;
        this.navbarHeight = this.getNavbarHeight();
        this.sidebarWidth = this.isDesktop ? this.calculateSidebarWidth() : 0;
        this.compactMode = false;
        
        // Variables de padding
        this.minPadding = 10; // Mínimo 10px
        this.maxPaddingVH = 0.01; // Máximo 1vh (1% de la altura de la ventana)
        
        // Elementos del DOM
        this.navbar = null;
        this.body = document.body;
        this.html = document.documentElement;
        this.mainContent = null;
        this.sidebar = null;
        
        // Controladores
        this.debouncedResize = this.debounce(() => this.handleResize(), 250);
        this.mutationObserver = null;
        
        this.modules = {};
        this.init();
    }
    
    // ✅ CALCULAR ANCHO DEL SIDEBAR BASADO EN PADDING MÍNIMO
    calculateSidebarWidth() {
        if (this.isMobile) return 0;
        
        // Calcular padding actual
        const padding = this.calculatePadding();
        
        // Si está en modo compacto, reducirlo a la mitad
        if (this.compactMode) {
            return Math.max(padding * 0.5, 50); // Mínimo 50px en compacto
        }
        
        // Máximo 220px, mínimo padding calculado
        return Math.min(padding, 220);
    }
    
    // ✅ CALCULAR PADDING BASADO EN 1vh MÁXIMO
    calculatePadding() {
        // 1vh de la altura de la ventana, pero mínimo 10px
        const vhPadding = window.innerHeight * this.maxPaddingVH;
        return Math.max(vhPadding, this.minPadding);
    }
    
    // ✅ VERIFICAR SI ES MÓVIL
    checkIsMobile() {
        return window.innerWidth < 992;
    }
    
    // ✅ OBTENER ALTURA DEL NAVBAR (REDUCIDA)
    getNavbarHeight() {
        const navbar = document.querySelector('.navbar-contextual');
        if (!navbar) return this.isMobile ? 60 : 150; // Reducido
        
        if (this.isMobile) {
            if (window.innerWidth < 576) return 50; // Reducido
            if (window.innerWidth < 768) return 55; // Reducido
            return 60; // Reducido
        } else {
            return Math.min(window.innerHeight * 0.2, 150); // Reducido, máximo 150px
        }
    }
    
    // ✅ INICIALIZACIÓN PRINCIPAL
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    // ✅ CONFIGURACIÓN COMPLETA DEL SISTEMA
    setup() {
        console.log(`🚀 Sistema GED v4.1 inicializando - Padding máximo: 1vh (${this.calculatePadding().toFixed(1)}px)`);
        
        try {
            // Cachear elementos DOM
            this.cacheElements();
            
            // Aplicar correcciones de padding inmediatamente
            this.applyMinimalPadding();
            
            // Inicializar todos los módulos
            this.modules = {
                navbar: new NavbarManager(),
                sidebar: new OffCanvasSidebar(),
                landing: new LandingPageManager(),
                search: new SchoolSearch(),
                components: new ComponentsManager()
            };
            
            // Inicializar cada módulo
            Object.values(this.modules).forEach(module => module.init());
            
            // Configurar observadores y eventos
            this.setupObservers();
            this.bindEvents();
            
            // Aplicar correcciones iniciales del body
            this.applyBodyCorrections();
            
            // Forzar recálculo después de la carga completa
            setTimeout(() => {
                this.forceMinimalPaddingRecalculation();
                this.applyBodyCorrections();
                this.fixOverflowIssues();
            }, 500);
            
            console.log('✅ Sistema GED v4.1 completamente inicializado con padding mínimo');
            
        } catch (error) {
            console.error('❌ Error crítico en inicialización del sistema:', error);
            this.showCriticalError('Error al inicializar el sistema GED');
        }
    }
    
    // ✅ CACHEAR ELEMENTOS DOM IMPORTANTES
    cacheElements() {
        this.navbar = document.querySelector('.navbar-contextual');
        this.mainContent = document.querySelector('.main-content-wrapper');
        this.sidebar = document.querySelector('.ged-offcanvas-sidebar');
        this.offcanvasWrapper = document.querySelector('.ged-offcanvas-wrapper');
    }
    
    // ✅ APLICAR PADDING MÍNIMO A TODOS LOS ELEMENTOS
    applyMinimalPadding() {
        console.log('🔧 Aplicando padding mínimo (máximo 1vh)...');
        
        const padding = this.calculatePadding();
        const sidebarWidth = this.calculateSidebarWidth();
        
        // ✅ FIX 1: Body y HTML SIN PADDING LATERAL
        this.html.style.paddingLeft = '0';
        this.html.style.paddingRight = '0';
        
        this.body.style.paddingLeft = '0';
        this.body.style.paddingRight = '0';
        this.body.style.boxSizing = 'border-box';
        
        // ✅ FIX 2: Sidebar con ancho reducido
        if (this.sidebar && this.isDesktop) {
            this.sidebar.style.width = `${sidebarWidth}px`;
            this.sidebar.style.transition = 'width 0.3s ease';
        }
        
        // ✅ FIX 3: Navbar con padding mínimo lateral
        if (this.navbar) {
            this.navbar.style.paddingLeft = `${padding}px`;
            this.navbar.style.paddingRight = `${padding}px`;
            this.navbar.style.boxSizing = 'border-box';
        }
        
        // ✅ FIX 4: Main content con padding mínimo
        if (this.mainContent) {
            this.mainContent.style.paddingLeft = `${padding}px`;
            this.mainContent.style.paddingRight = `${padding}px`;
            
            // Solo en desktop, agregar padding extra para el sidebar
            if (this.isDesktop) {
                this.mainContent.style.paddingLeft = `${padding + sidebarWidth}px`;
            }
        }
        
        // ✅ FIX 5: Containers fluidos con padding mínimo
        document.querySelectorAll('.container-fluid, .container').forEach(container => {
            container.style.paddingLeft = `${padding}px`;
            container.style.paddingRight = `${padding}px`;
            container.style.boxSizing = 'border-box';
        });
        
        // ✅ FIX 6: Aplicar padding mínimo a secciones principales
        document.querySelectorAll('section, .section, .content-section, .main-section').forEach(section => {
            section.style.paddingLeft = `${padding}px`;
            section.style.paddingRight = `${padding}px`;
        });
        
        console.log(`✅ Padding mínimo aplicado: ${padding}px (${(padding/window.innerHeight*100).toFixed(1)}vh)`);
        console.log(`✅ Ancho sidebar: ${sidebarWidth}px (${this.compactMode ? 'compacto' : 'normal'})`);
    }
    
    // ✅ FORZAR RECÁLCULO DE PADDING MÍNIMO
    forceMinimalPaddingRecalculation() {
        try {
            // Recalcular valores
            const padding = this.calculatePadding();
            const sidebarWidth = this.calculateSidebarWidth();
            
            // Aplicar a todos los elementos
            this.applyMinimalPadding();
            
            // Actualizar sidebar si existe
            if (this.sidebar && this.isDesktop) {
                this.sidebar.style.width = `${sidebarWidth}px`;
            }
            
            console.log(`🔄 Padding mínimo recalculado: ${padding}px, Sidebar: ${sidebarWidth}px`);
        } catch (error) {
            console.error('Error en forceMinimalPaddingRecalculation:', error);
        }
    }
    
    // ✅ CONFIGURAR OBSERVADORES DE CAMBIOS
    setupObservers() {
        // Observar cambios en el sidebar (para modo compacto)
        if (this.sidebar) {
            this.mutationObserver = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        this.handleSidebarChange();
                    }
                });
            });
            
            this.mutationObserver.observe(this.sidebar, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
        
        // Observar cambios en el body para detectar overflow
        const bodyObserver = new MutationObserver(() => {
            setTimeout(() => this.fixOverflowIssues(), 100);
        });
        
        bodyObserver.observe(this.body, {
            childList: true,
            subtree: true,
            attributes: true
        });
    }
    
    // ✅ MANEJAR CAMBIOS EN EL SIDEBAR
    handleSidebarChange() {
        if (this.isDesktop) {
            this.compactMode = this.sidebar.classList.contains('compact');
            console.log(`🔄 Modo sidebar cambiado a: ${this.compactMode ? 'compacto' : 'normal'}`);
            this.forceMinimalPaddingRecalculation();
        }
    }
    
    // ✅ VINCULAR EVENTOS
    bindEvents() {
        window.addEventListener('resize', this.debouncedResize);
        
        // Evento personalizado para cambios de padding
        window.addEventListener('ged:paddingchange', () => {
            this.forceMinimalPaddingRecalculation();
        });
    }
    
    // ✅ APLICAR CORRECCIONES AL BODY
    applyBodyCorrections() {
        try {
            this.navbarHeight = this.getNavbarHeight();
            
            // Ajustar padding-top del body para el navbar
            this.body.style.paddingTop = `${this.navbarHeight}px`;
            
            // Ajustar altura mínima de elementos main
            const mainElements = document.querySelectorAll('main#main, .main-container');
            mainElements.forEach(main => {
                main.style.marginTop = '0';
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
            
            if (newIsMobile !== this.isMobile) {
                this.isMobile = newIsMobile;
                this.isDesktop = !newIsMobile;
                console.log(`🔄 Cambio de modo: ${this.isMobile ? 'Móvil' : 'Escritorio'}`);
                
                // Reaplicar todas las correcciones
                this.applyMinimalPadding();
                this.applyBodyCorrections();
                
                // Notificar a los módulos del cambio
                if (this.modules.sidebar) {
                    this.modules.sidebar.handleViewportChange(this.isMobile);
                }
            }
            
            // Recalcular alturas y padding
            this.navbarHeight = this.getNavbarHeight();
            
            // Recalcular padding mínimo
            this.forceMinimalPaddingRecalculation();
            this.applyBodyCorrections();
            
            // Verificar overflow después de resize
            setTimeout(() => this.fixOverflowIssues(), 100);
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
            
            if (difference > 5) { // Tolerancia de 5px
                console.warn(`⚠️ Overflow detectado: Body ${bodyWidth}px > Viewport ${viewportWidth}px (Diff: ${difference}px)`);
                
                // Reducir padding si hay overflow significativo
                if (difference > 20) {
                    const currentPadding = this.calculatePadding();
                    const reduction = Math.min(difference * 0.5, currentPadding * 0.3);
                    const newPadding = Math.max(currentPadding - reduction, 5);
                    
                    // Aplicar nuevo padding reducido
                    document.querySelectorAll('.container-fluid, .container, .navbar-contextual, .main-content-wrapper')
                        .forEach(el => {
                            const currentLeft = parseInt(el.style.paddingLeft) || 0;
                            if (currentLeft > newPadding) {
                                el.style.paddingLeft = `${newPadding}px`;
                            }
                        });
                    
                    console.log(`✅ Padding reducido a ${newPadding}px para corregir overflow`);
                }
                
                // Forzar hide de overflow horizontal
                this.body.style.overflowX = 'hidden';
                this.html.style.overflowX = 'hidden';
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
            top: 0;
            left: 0;
            right: 0;
            background: #dc3545;
            color: white;
            padding: 10px 15px;
            text-align: center;
            z-index: 9999;
            font-weight: bold;
            font-size: 0.9rem;
        `;
        errorDiv.textContent = `⚠️ ${message}. Por favor, recarga la página.`;
        document.body.appendChild(errorDiv);
    }
    
    // ✅ MÉTODOS PÚBLICOS PARA CONTROL EXTERNO
    forceWidthFix() {
        this.forceMinimalPaddingRecalculation();
        console.log('🔄 Full width fix aplicado manualmente');
    }
    
    checkOverflow() {
        const bodyWidth = this.body.offsetWidth;
        const viewportWidth = window.innerWidth;
        const difference = bodyWidth - viewportWidth;
        
        console.log(`📏 Análisis de ancho:
          Body: ${bodyWidth}px
          Viewport: ${viewportWidth}px
          Diferencia: ${difference}px
          ${difference > 5 ? '⚠️ HAY OVERFLOW' : '✅ SIN OVERFLOW'}
          Padding actual: ${this.calculatePadding()}px
        `);
        
        return {
            bodyWidth,
            viewportWidth,
            difference,
            hasOverflow: difference > 5,
            currentPadding: this.calculatePadding()
        };
    }
    
    // ✅ ACTUALIZAR CONFIGURACIÓN DE PADDING
    updatePaddingConfig(minPx = 10, maxVH = 0.01) {
        this.minPadding = minPx;
        this.maxPaddingVH = maxVH;
        this.forceMinimalPaddingRecalculation();
        console.log(`🔧 Configuración de padding actualizada: mínimo ${minPx}px, máximo ${maxVH*100}vh`);
    }
    
    // ✅ OBTENER ESTADO ACTUAL
    getCurrentState() {
        return {
            isMobile: this.isMobile,
            isDesktop: this.isDesktop,
            compactMode: this.compactMode,
            navbarHeight: this.navbarHeight,
            sidebarWidth: this.sidebarWidth,
            currentPadding: this.calculatePadding(),
            minPadding: this.minPadding,
            maxPaddingVH: this.maxPaddingVH
        };
    }
}

// ==================================================
// NAVBAR MANAGER - OPTIMIZADO PARA PADDING MÍNIMO
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
            
            // ✅ FORZAR ANCHO COMPLETO CON PADDING MÍNIMO
            this.forceMinimalWidth();
            
            // ✅ Ocultar navbar-collapse en móviles si es necesario
            if (this.isMobile) {
                this.hideNavbarCollapseOnMobile();
            }
            
            this.stabilizeNavbar();
            this.initNavbarEscuelaSelector();
            
            console.log('✅ NavbarManager inicializado - Móvil:', this.isMobile);
        } catch (error) {
            console.error('Error en NavbarManager.init:', error);
        }
    }
    
    forceMinimalWidth() {
        try {
            const minimalStyle = `
                width: 100vw !important;
                max-width: 100vw !important;
                min-width: 100vw !important;
                left: 0 !important;
                right: 0 !important;
                box-sizing: border-box !important;
                padding-left: 10px !important;
                padding-right: 10px !important;
            `;
            
            // Aplicar al navbar
            if (this.navbar) {
                this.navbar.style.cssText += minimalStyle;
            }
            
            // Aplicar a elementos internos
            const elements = [
                '.navbar-collapse',
                '.navbar-container',
                '.navbar-menu-section',
                '.navbar-brand-section'
            ];
            
            elements.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    element.style.cssText += 'width: 100% !important; max-width: 100% !important; box-sizing: border-box !important;';
                });
            });
        } catch (error) {
            console.error('Error en forceMinimalWidth:', error);
        }
    }
    
    hideNavbarCollapseOnMobile() {
        try {
            const navbarCollapse = document.getElementById('navbarGedCollapse');
            if (navbarCollapse) {
                navbarCollapse.style.display = 'none';
                navbarCollapse.classList.remove('show');
                console.log('✅ Navbar-collapse ocultado en móvil');
            }
        } catch (error) {
            console.error('Error en hideNavbarCollapseOnMobile:', error);
        }
    }
    
    stabilizeNavbar() {
        try {
            const criticalStyles = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1030 !important;
                width: 100vw !important;
                transform: none !important;
            `;
            this.navbar.style.cssText += criticalStyles;
        } catch (error) {
            console.error('Error en stabilizeNavbar:', error);
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
// OFF-CANVAS SIDEBAR CON PADDING MÍNIMO
// ==================================================

class OffCanvasSidebar {
    constructor() {
        this.isOpen = false;
        this.isMobile = window.innerWidth < 992;
        this.menuLoaded = false;
        this.sidebar = null;
        this.backdrop = null;
        this.sidebarNav = null;
    }
    
    init() {
        try {
            this.createOffCanvas();
            this.bindEvents();
            console.log('✅ Off-Canvas Sidebar inicializado - Móvil:', this.isMobile);
        } catch (error) {
            console.error('Error en OffCanvasSidebar.init:', error);
        }
    }
    
    createOffCanvas() {
        try {
            if (document.querySelector('.ged-offcanvas-sidebar')) {
                this.sidebar = document.querySelector('.ged-offcanvas-sidebar');
                this.backdrop = document.querySelector('.ged-sidebar-backdrop');
                this.sidebarNav = this.sidebar.querySelector('.sidebar-nav');
                return;
            }

            const sidebar = document.createElement('div');
            sidebar.className = 'ged-offcanvas-sidebar';
            sidebar.innerHTML = `
                <div class="sidebar-header">
                    <button class="close-sidebar" aria-label="Cerrar menú">✕</button>
                    <span>Menú Principal</span>
                </div>
                <nav class="sidebar-nav" aria-label="Navegación principal">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando menú...</span>
                        </div>
                        <p class="text-muted mt-2">Cargando menú...</p>
                    </div>
                </nav>
            `;
            
            const backdrop = document.createElement('div');
            backdrop.className = 'ged-sidebar-backdrop';
            
            document.body.appendChild(sidebar);
            document.body.appendChild(backdrop);
            
            this.sidebar = sidebar;
            this.backdrop = backdrop;
            this.sidebarNav = this.sidebar.querySelector('.sidebar-nav');
        } catch (error) {
            console.error('Error en createOffCanvas:', error);
        }
    }
    
    loadMobileMenu() {
        try {
            if (this.menuLoaded) return;
            
            console.log('📱 Cargando menú específico para móvil...');
            
            if (typeof $ !== 'undefined') {
                this.loadMobileMenuViaAJAX();
            } else {
                setTimeout(() => {
                    this.loadRealMenu();
                }, 100);
            }
        } catch (error) {
            console.error('Error en loadMobileMenu:', error);
            this.loadFallbackMenu();
        }
    }
    
    loadMobileMenuViaAJAX() {
        try {
            $.ajax({
                url: '/site/mobile-menu',
                type: 'GET',
                data: {
                    _csrf: $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    console.log('✅ Menú móvil cargado via AJAX');
                    this.sidebarNav.innerHTML = response;
                    this.adaptMenuForOffCanvas(this.sidebarNav);
                    this.menuLoaded = true;
                },
                error: (xhr, status, error) => {
                    console.error('❌ Error cargando menú móvil via AJAX:', error);
                    console.log('🔄 Intentando cargar menú desde navbar...');
                    this.loadRealMenu();
                }
            });
        } catch (error) {
            console.error('Error en loadMobileMenuViaAJAX:', error);
            this.loadRealMenu();
        }
    }
    
    loadRealMenu() {
        try {
            console.log('🔄 Cargando menú real desde navbar...');
            
            const realMenu = document.querySelector('.navbar-nav');
            
            if (!realMenu) {
                console.warn('❌ No se encontró el menú real en el navbar');
                this.loadFallbackMenu();
                return;
            }
            
            const clonedMenu = realMenu.cloneNode(true);
            this.sidebarNav.innerHTML = '';
            this.sidebarNav.appendChild(clonedMenu);
            this.adaptMenuForOffCanvas(this.sidebarNav);
            this.menuLoaded = true;
            
            console.log('✅ Menú real cargado y adaptado correctamente');
        } catch (error) {
            console.error('Error en loadRealMenu:', error);
            this.loadFallbackMenu();
        }
    }
    
    loadFallbackMenu() {
        try {
            console.log('🔄 Cargando menú de respaldo...');
            
            this.sidebarNav.innerHTML = `
                <ul class="sidebar-menu">
                    <li class="menu-item">
                        <a href="/" class="menu-link">Inicio</a>
                    </li>
                    <li class="menu-item has-children">
                        <a href="#" class="menu-link">
                            Sistema
                            <span class="submenu-indicator">›</span>
                        </a>
                        <ul class="submenu">
                            <li class="menu-item">
                                <a href="/ged/default/index" class="menu-link">Seleccionar Escuela</a>
                            </li>
                            <li class="menu-item">
                                <a href="/site/login" class="menu-link">Iniciar Sesión</a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-divider"></li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">Ayuda</a>
                    </li>
                </ul>
            `;
            
            this.adaptMenuForOffCanvas(this.sidebarNav);
            this.menuLoaded = true;
            console.log('✅ Menú de respaldo cargado');
        } catch (error) {
            console.error('Error en loadFallbackMenu:', error);
        }
    }
    
    adaptMenuForOffCanvas(menuElement) {
        try {
            let mainMenu = menuElement.querySelector('.navbar-nav, .sidebar-menu');
            if (!mainMenu) return;
            
            if (mainMenu.classList.contains('navbar-nav')) {
                this.convertBootstrapToMobileMenu(mainMenu);
            }
            
            this.addMobileMenuEvents(menuElement);
            console.log('✅ Menú adaptado correctamente para móvil');
        } catch (error) {
            console.error('Error en adaptMenuForOffCanvas:', error);
        }
    }
    
    convertBootstrapToMobileMenu(menuElement) {
        try {
            const dropdowns = menuElement.querySelectorAll('.dropdown, .dropdown-submenu');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('dropdown', 'dropdown-submenu');
                dropdown.classList.add('has-children');
                
                const toggle = dropdown.querySelector('.dropdown-toggle');
                if (toggle) {
                    toggle.classList.remove('dropdown-toggle');
                    toggle.removeAttribute('data-bs-toggle');
                    toggle.removeAttribute('aria-expanded');
                    
                    if (!toggle.querySelector('.submenu-indicator')) {
                        const indicator = document.createElement('span');
                        indicator.className = 'submenu-indicator';
                        indicator.textContent = '›';
                        toggle.appendChild(indicator);
                    }
                }
                
                const menu = dropdown.querySelector('.dropdown-menu');
                if (menu) {
                    menu.classList.remove('dropdown-menu');
                    menu.classList.add('submenu');
                    menu.style.display = 'none';
                }
            });
            
            const navItems = menuElement.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.classList.remove('nav-item');
                item.classList.add('menu-item');
            });
            
            const navLinks = menuElement.querySelectorAll('.nav-link, .dropdown-item');
            navLinks.forEach(link => {
                link.classList.remove('nav-link', 'dropdown-item');
                link.classList.add('menu-link');
                
                if (link.getAttribute('href') === '#' && link.parentElement.classList.contains('has-children')) {
                    link.style.cursor = 'pointer';
                }
            });
            
            menuElement.classList.remove('navbar-nav');
            menuElement.classList.add('sidebar-menu');
        } catch (error) {
            console.error('Error en convertBootstrapToMobileMenu:', error);
        }
    }
    
    addMobileMenuEvents(menuElement) {
        try {
            const menuItems = menuElement.querySelectorAll('.has-children > .menu-link');
            menuItems.forEach(menuItem => {
                menuItem.replaceWith(menuItem.cloneNode(true));
            });
            
            const refreshedMenuItems = menuElement.querySelectorAll('.has-children > .menu-link');
            refreshedMenuItems.forEach(menuItem => {
                menuItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleSubmenu(menuItem.parentElement);
                });
            });
            
            const normalLinks = menuElement.querySelectorAll('.menu-item:not(.has-children) > .menu-link');
            normalLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (this.isMobile) {
                        setTimeout(() => this.close(), 300);
                    }
                });
            });
        } catch (error) {
            console.error('Error en addMobileMenuEvents:', error);
        }
    }
    
    toggleSubmenu(parentItem) {
        try {
            const submenu = parentItem.querySelector('.submenu');
            if (!submenu) return;
            
            const isCurrentlyOpen = submenu.style.display === 'block';
            const indicator = parentItem.querySelector('.submenu-indicator');
            
            const siblings = parentItem.parentElement.querySelectorAll('.has-children');
            siblings.forEach(sibling => {
                if (sibling !== parentItem) {
                    const siblingSubmenu = sibling.querySelector('.submenu');
                    const siblingIndicator = sibling.querySelector('.submenu-indicator');
                    if (siblingSubmenu) siblingSubmenu.style.display = 'none';
                    if (siblingIndicator) siblingIndicator.style.transform = 'rotate(0deg)';
                    sibling.classList.remove('open');
                }
            });
            
            if (isCurrentlyOpen) {
                submenu.style.display = 'none';
                if (indicator) indicator.style.transform = 'rotate(0deg)';
                parentItem.classList.remove('open');
            } else {
                submenu.style.display = 'block';
                if (indicator) indicator.style.transform = 'rotate(90deg)';
                parentItem.classList.add('open');
            }
        } catch (error) {
            console.error('Error en toggleSubmenu:', error);
        }
    }
    
    bindEvents() {
        try {
            this.interceptBootstrapToggler();
            
            const closeButton = this.sidebar.querySelector('.close-sidebar');
            if (closeButton) {
                closeButton.addEventListener('click', () => this.close());
            }
            
            if (this.backdrop) {
                this.backdrop.addEventListener('click', () => this.close());
            }
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) this.close();
            });
        } catch (error) {
            console.error('Error en bindEvents:', error);
        }
    }
    
    interceptBootstrapToggler() {
        try {
            const navbarToggler = document.querySelector('.navbar-toggler');
            if (!navbarToggler) {
                console.warn('❌ No se encontró el navbar toggler');
                return;
            }
            
            const originalOnClick = navbarToggler.onclick;
            
            navbarToggler.addEventListener('click', (e) => {
                if (this.isMobile) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    if (this.isOpen) {
                        this.close();
                    } else {
                        this.open();
                    }
                    return false;
                }
                
                if (originalOnClick) originalOnClick.call(navbarToggler, e);
            });
            
            console.log('✅ Toggler interceptado correctamente');
        } catch (error) {
            console.error('Error en interceptBootstrapToggler:', error);
        }
    }
    
    open() {
        try {
            if (this.isOpen) return;
            
            if (!this.menuLoaded) {
                this.loadMobileMenu();
                this.menuLoaded = true;
            }
            
            this.isOpen = true;
            this.sidebar.classList.add('open');
            this.backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
            this.sidebar.setAttribute('tabindex', '-1');
            this.sidebar.focus();
            
            console.log('✅ Off-Canvas abierto correctamente');
        } catch (error) {
            console.error('Error en open:', error);
        }
    }
    
    close() {
        try {
            if (!this.isOpen) return;
            
            this.isOpen = false;
            this.sidebar.classList.remove('open');
            this.backdrop.classList.remove('show');
            document.body.style.overflow = '';
            this.closeAllSubmenus();
            
            console.log('✅ Off-Canvas cerrado correctamente');
        } catch (error) {
            console.error('Error en close:', error);
        }
    }
    
    closeAllSubmenus() {
        try {
            const submenus = this.sidebar.querySelectorAll('.submenu');
            const parentItems = this.sidebar.querySelectorAll('.has-children');
            
            submenus.forEach(submenu => submenu.style.display = 'none');
            parentItems.forEach(item => {
                item.classList.remove('open');
                const indicator = item.querySelector('.submenu-indicator');
                if (indicator) indicator.style.transform = 'rotate(0deg)';
            });
        } catch (error) {
            console.error('Error en closeAllSubmenus:', error);
        }
    }
    
    handleViewportChange(isMobile) {
        this.isMobile = isMobile;
        console.log('🔄 Off-Canvas cambió a modo:', this.isMobile ? 'Móvil' : 'Escritorio');
        
        if (!this.isMobile && this.isOpen) {
            this.close();
        }
    }
}

// ==================================================
// SCHOOL SEARCH MANAGER (SIN CAMBIOS SIGNIFICATIVOS)
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
                console.error('jQuery no está cargado. SchoolSearch desactivado.');
                return;
            }
            
            if (!document.querySelector('#schoolSearch')) return;
            
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
// COMPONENTS MANAGER (SIN CAMBIOS SIGNIFICATIVOS)
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
// LANDING PAGE MANAGER (SIN CAMBIOS SIGNIFICATIVOS)
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
            console.log('🚀 Landing Page Manager inicializado');
            this.cargarProductos();
            this.renderizarProductos();
            this.actualizarTotalVendidos();
            this.setupEventListeners();
            this.mostrarBannerTiendas();
            this.initAnimaciones();
            this.initMarketplace();
            console.log('✅ LandingPageManager listo');
        } catch (error) {
            console.error('Error en LandingPageManager.init:', error);
        }
    }

    getElement(id) {
        try {
            if (!this.cachedElements[id]) {
                this.cachedElements[id] = document.getElementById(id);
            }
            return this.cachedElements[id];
        } catch (error) {
            console.error(`Error obteniendo elemento ${id}:`, error);
            return null;
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
                const contenedor = this.getElement(`productos-${categoria}`);
                if (!contenedor) {
                    console.warn(`Contenedor no encontrado: productos-${categoria}`);
                    continue;
                }

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
            
            const totalElement = this.getElement('total-productos-vendidos');
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
            
            const accederBtn = this.getElement('btn-acceder-sistema');
            if (accederBtn) this.enhanceAccederButton(accederBtn);
            
            const marketplaceBtn = this.getElement('btn-marketplace');
            if (marketplaceBtn) this.enhanceMarketplaceButton(marketplaceBtn);
            
            const logo = this.getElement('ged-main-logo');
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
            const contador = this.getElement('contador-carrito');
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
            let notificacion = this.getElement('notificacion-carrito');
            
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
                this.cachedElements['notificacion-carrito'] = notificacion;
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
            const banner = this.getElement('banner-tiendas-patrocinadas');
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
// INICIALIZACIÓN GLOBAL DEL SISTEMA
// ==================================================

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (!window.gedSystem) {
            window.gedSystem = new GEDSystem();
            console.log('🚀 Sistema GED v4.1 completamente inicializado');
        }
    }, 100);
    
    // Inicializar Landing Page Manager si es necesario
    if (document.querySelector('.landing-page')) {
        setTimeout(() => {
            if (typeof window.landingPageManager !== 'undefined') {
                console.log('✅ Landing Page Manager ya está cargado');
            } else if (typeof LandingPageManager !== 'undefined') {
                window.landingPageManager = new LandingPageManager();
                console.log('✅ Landing Page Manager inicializado correctamente');
            } else {
                console.error('❌ LandingPageManager no está definido');
            }
        }, 500);
    }
});

// ==================================================
// FUNCIONES DE DEBUG Y UTILIDADES
// ==================================================

function debugGEDSystem() {
    console.group('🐛 DEBUG GED SYSTEM v4.1 - PADDING MÍNIMO');
    console.log('GED System:', window.gedSystem);
    console.log('Estado:', window.gedSystem?.getCurrentState());
    console.log('Módulos cargados:', Object.keys(window.gedSystem?.modules || {}));
    
    const bodyWidth = document.body.offsetWidth;
    const viewportWidth = window.innerWidth;
    console.log(`📏 Body: ${bodyWidth}px, Viewport: ${viewportWidth}px, Diff: ${bodyWidth - viewportWidth}px`);
    
    // Verificar padding de elementos clave
    const elements = ['.navbar-contextual', '.main-content-wrapper', '.container-fluid', 'body'];
    elements.forEach(selector => {
        const el = document.querySelector(selector);
        if (el) {
            const style = window.getComputedStyle(el);
            console.log(`${selector}: padding-left=${style.paddingLeft}, width=${el.offsetWidth}px`);
        }
    });
    
    console.groupEnd();
}

window.debugGEDSystem = debugGEDSystem;

// ✅ FUNCIONES GLOBALES DE CONTROL
window.forceWidthFix = function() {
    if (window.gedSystem) {
        window.gedSystem.forceWidthFix();
        console.log('🔄 Full width fix ejecutado manualmente');
    }
};

window.checkOverflow = function() {
    if (window.gedSystem) {
        return window.gedSystem.checkOverflow();
    }
    return null;
};

window.updatePaddingConfig = function(minPx = 10, maxVH = 0.01) {
    if (window.gedSystem) {
        window.gedSystem.updatePaddingConfig(minPx, maxVH);
    }
};

window.getSystemState = function() {
    if (window.gedSystem) {
        return window.gedSystem.getCurrentState();
    }
    return null;
};

window.reloadOffCanvasMenu = function() {
    if (window.gedSystem && window.gedSystem.modules.sidebar) {
        window.gedSystem.modules.sidebar.loadMobileMenu();
        console.log('🔄 Menú del off-canvas recargado manualmente');
    }
};

window.forceNavbarRecalculation = function() {
    if (window.gedSystem) {
        window.gedSystem.forceMinimalPaddingRecalculation();
    }
};

window.limpiarCarrito = function() {
    sessionStorage.removeItem('ged-carrito');
    if (window.landingPageManager) {
        window.landingPageManager.carrito = [];
        window.landingPageManager.actualizarContadorCarrito();
    }
    console.log('🧹 Carrito limpiado');
};

function debugLandingPage() {
    console.group('🐛 DEBUG LANDING PAGE - CON PRODUCTOS');
    console.log('Landing Page Manager:', window.landingPageManager);
    console.log('Productos cargados:', window.landingPageManager?.productos);
    console.log('Carrito:', window.landingPageManager?.carrito);
    console.log('Total vendidos:', window.landingPageManager?.totalVendidos);
    console.groupEnd();
}

window.debugLandingPage = debugLandingPage;

// Modo desarrollo automático
if (window.location.href.indexOf('localhost') > -1 || window.location.href.indexOf('debug') > -1) {
    setTimeout(() => {
        debugGEDSystem();
        console.log('🔧 Modo desarrollo activo - Debug functions disponibles');
        console.log('ℹ️  Usa debugGEDSystem() para ver estado completo');
        console.log('ℹ️  Usa updatePaddingConfig(minPx, maxVH) para ajustar padding');
    }, 2000);
}

// Evento personalizado para notificar que el sistema está listo
window.dispatchEvent(new CustomEvent('ged:ready', { 
    detail: { 
        version: '4.1.0', 
        features: ['minimal-padding', 'full-width-fix', 'responsive-sidebar'],
        paddingConfig: {
            minPx: 10,
            maxVH: 0.01
        }
    } 
}));

// Export para Node.js (si es necesario)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { 
        GEDSystem, 
        LandingPageManager, 
        OffCanvasSidebar, 
        NavbarManager, 
        SchoolSearch, 
        ComponentsManager 
    };
}