// ged-offcanvas.js - Módulo OffCanvas Sidebar para GED - VERSIÓN CORREGIDA
// Versión: 3.1.0 - Con manejo robusto de errores y verificación de elementos
// Fecha: 16/01/2024

// ==================================================
// OFF-CANVAS SIDEBAR CON LAZY LOADING - VERSIÓN MEJORADA
// ==================================================

class OffCanvasSidebar {
    constructor() {
        this.isOpen = false;
        this.isMobile = window.innerWidth < 992;
        this.menuLoaded = false;
        this.sidebar = null;
        this.backdrop = null;
        this.sidebarNav = null;
        this.navbarToggler = null;
        
        // Cache de elementos críticos
        this.requiredElements = {
            sidebarCreated: false,
            navbarMenuExists: false,
            mainMenuExists: false
        };
        
        // Niveles de fallback
        this.fallbackLevel = 0; // 0: AJAX, 1: Real Menu, 2: Fallback Menu, 3: Simple Menu
    }
    
    init() {
        try {
            console.log('🔧 OffCanvasSidebar inicializando...');
            
            // Verificar elementos críticos antes de proceder
            if (!this.checkPrerequisites()) {
                console.warn('⚠️ Prerrequisitos no cumplidos, creando elementos necesarios...');
                this.createRequiredElements();
            }
            
            this.createOffCanvas();
            this.bindEvents();
            console.log('✅ Off-Canvas Sidebar inicializado - Móvil:', this.isMobile);
            return true;
        } catch (error) {
            console.error('❌ Error crítico en OffCanvasSidebar.init:', error);
            this.showErrorNotification('Error al inicializar menú móvil');
            return false;
        }
    }
    
    // ✅ VERIFICAR PRERREQUISITOS
    checkPrerequisites() {
        const checks = {
            'Body disponible': !!document.body,
            'Document readyState': document.readyState !== 'loading',
            'jQuery disponible': typeof $ !== 'undefined',
            'Bootstrap Offcanvas': typeof bootstrap !== 'undefined' && bootstrap.Offcanvas,
            'Navbar toggler': !!document.querySelector('.navbar-toggler')
        };
        
        let allPassed = true;
        for (const [name, passed] of Object.entries(checks)) {
            if (!passed) {
                console.warn(`⚠️ Prerrequisito no cumplido: ${name}`);
                allPassed = false;
            }
        }
        
        return allPassed;
    }
    
    // ✅ CREAR ELEMENTOS REQUERIDOS SI NO EXISTEN
    createRequiredElements() {
        try {
            // Crear navbar toggler si no existe (solo para desarrollo/debug)
            if (!document.querySelector('.navbar-toggler') && document.querySelector('.navbar')) {
                const navbar = document.querySelector('.navbar');
                const toggler = document.createElement('button');
                toggler.className = 'navbar-toggler';
                toggler.setAttribute('type', 'button');
                toggler.setAttribute('data-bs-toggle', 'offcanvas');
                toggler.setAttribute('data-bs-target', '#offcanvasNavbar');
                toggler.setAttribute('aria-controls', 'offcanvasNavbar');
                toggler.setAttribute('aria-label', 'Toggle navigation');
                toggler.innerHTML = '<span class="navbar-toggler-icon"></span>';
                navbar.appendChild(toggler);
                console.log('✅ Navbar toggler creado dinámicamente');
            }
        } catch (error) {
            console.error('Error en createRequiredElements:', error);
        }
    }
    
    createOffCanvas() {
        try {
            // Verificar si ya existe
            if (document.querySelector('.ged-offcanvas-sidebar')) {
                this.sidebar = document.querySelector('.ged-offcanvas-sidebar');
                this.backdrop = document.querySelector('.ged-sidebar-backdrop');
                this.sidebarNav = this.sidebar.querySelector('.sidebar-nav');
                this.requiredElements.sidebarCreated = true;
                console.log('✅ OffCanvas ya existente, reutilizando');
                return;
            }

            // Crear contenedor principal
            const sidebar = document.createElement('div');
            sidebar.className = 'ged-offcanvas-sidebar';
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
            sidebar.setAttribute('aria-label', 'Menú de navegación móvil');
            
            sidebar.innerHTML = `
                <div class="sidebar-header">
                    <button class="close-sidebar" aria-label="Cerrar menú">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="sidebar-title">
                        <span>Menú Principal</span>
                        <small class="text-muted">GED System</small>
                    </div>
                </div>
                <div class="sidebar-body">
                    <nav class="sidebar-nav" aria-label="Navegación principal">
                        <div class="menu-loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando menú...</span>
                            </div>
                            <p class="text-muted mt-2">Cargando menú de navegación...</p>
                        </div>
                    </nav>
                </div>
                <div class="sidebar-footer">
                    <div class="sidebar-info">
                        <small class="text-muted">Sistema GED v4.5</small>
                    </div>
                </div>
            `;
            
            // Crear backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'ged-sidebar-backdrop';
            backdrop.setAttribute('aria-hidden', 'true');
            
            // Agregar al DOM
            document.body.appendChild(sidebar);
            document.body.appendChild(backdrop);
            
            // Asignar referencias
            this.sidebar = sidebar;
            this.backdrop = backdrop;
            this.sidebarNav = this.sidebar.querySelector('.sidebar-nav');
            this.requiredElements.sidebarCreated = true;
            
            console.log('✅ OffCanvas creado exitosamente');
        } catch (error) {
            console.error('❌ Error en createOffCanvas:', error);
            this.showErrorNotification('No se pudo crear el menú móvil');
        }
    }
    
    loadMobileMenu() {
        try {
            if (this.menuLoaded) return;
            
            console.log('📱 Cargando menú específico para móvil...');
            
            // Verificar que exista el contenedor
            if (!this.sidebarNav) {
                console.error('❌ Contenedor de menú no encontrado');
                this.loadSimpleMenu();
                return;
            }
            
            // Intentar cargar via AJAX primero
            if (typeof $ !== 'undefined') {
                this.loadMobileMenuViaAJAX();
            } else {
                // Si no hay jQuery, intentar menú real directamente
                setTimeout(() => {
                    this.loadRealMenu();
                }, 100);
            }
        } catch (error) {
            console.error('❌ Error en loadMobileMenu:', error);
            this.loadSimpleMenu();
        }
    }
    
    loadMobileMenuViaAJAX() {
        try {
            console.log('🌐 Intentando cargar menú via AJAX...');
            
            // Verificar si la ruta probablemente existe
            const testUrl = '/site/mobile-menu';
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            if (!csrfToken) {
                console.warn('⚠️ Token CSRF no encontrado, usando menú alternativo');
                this.loadRealMenu();
                return;
            }
            
            $.ajax({
                url: testUrl,
                type: 'GET',
                dataType: 'html',
                data: {
                    _csrf: csrfToken
                },
                timeout: 5000, // 5 segundos timeout
                beforeSend: () => {
                    this.showLoadingState();
                },
                success: (response) => {
                    console.log('✅ Menú móvil cargado via AJAX');
                    if (this.sidebarNav) {
                        this.sidebarNav.innerHTML = response;
                        this.adaptMenuForOffCanvas(this.sidebarNav);
                        this.menuLoaded = true;
                        this.fallbackLevel = 0;
                    }
                },
                error: (xhr, status, error) => {
                    console.warn(`⚠️ Error cargando menú via AJAX (${status}):`, error);
                    console.log('🔄 Intentando cargar menú real desde navbar...');
                    this.loadRealMenu();
                }
            });
        } catch (error) {
            console.error('❌ Error en loadMobileMenuViaAJAX:', error);
            this.loadRealMenu();
        }
    }
    
    loadRealMenu() {
        try {
            console.log('🔄 Buscando menú real en navbar...');
            
            if (!this.sidebarNav) {
                console.error('❌ Contenedor de menú no disponible');
                this.loadFallbackMenu();
                return;
            }
            
            // Buscar menú en varias ubicaciones posibles
            const menuSelectors = [
                '.navbar-nav',
                '#main-nav',
                '.main-menu',
                '.nav-menu',
                'nav ul',
                '.navigation'
            ];
            
            let realMenu = null;
            for (const selector of menuSelectors) {
                const element = document.querySelector(selector);
                if (element) {
                    realMenu = element;
                    console.log(`✅ Menú encontrado con selector: ${selector}`);
                    break;
                }
            }
            
            if (!realMenu) {
                console.warn('❌ No se encontró ningún menú en el navbar');
                this.loadFallbackMenu();
                return;
            }
            
            // Clonar el menú
            const clonedMenu = realMenu.cloneNode(true);
            this.sidebarNav.innerHTML = '';
            this.sidebarNav.appendChild(clonedMenu);
            this.adaptMenuForOffCanvas(this.sidebarNav);
            this.menuLoaded = true;
            this.fallbackLevel = 1;
            
            console.log('✅ Menú real cargado y adaptado correctamente');
        } catch (error) {
            console.error('❌ Error en loadRealMenu:', error);
            this.loadFallbackMenu();
        }
    }
    
    loadFallbackMenu() {
        try {
            console.log('🔄 Cargando menú de respaldo estructurado...');
            
            if (!this.sidebarNav) {
                console.error('❌ Contenedor de menú no disponible');
                this.loadSimpleMenu();
                return;
            }
            
            this.sidebarNav.innerHTML = `
                <ul class="sidebar-menu">
                    <li class="menu-item">
                        <a href="/" class="menu-link">
                            <i class="fas fa-home me-2"></i>
                            Inicio
                        </a>
                    </li>
                    <li class="menu-item has-children">
                        <a href="#" class="menu-link">
                            <i class="fas fa-cogs me-2"></i>
                            Sistema
                            <span class="submenu-indicator">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </a>
                        <ul class="submenu">
                            <li class="menu-item">
                                <a href="/ged/default/index" class="menu-link">
                                    <i class="fas fa-school me-2"></i>
                                    Seleccionar Escuela
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="/site/login" class="menu-link">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Iniciar Sesión
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="/site/signup" class="menu-link">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Registrarse
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item has-children">
                        <a href="#" class="menu-link">
                            <i class="fas fa-store me-2"></i>
                            Tienda
                            <span class="submenu-indicator">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </a>
                        <ul class="submenu">
                            <li class="menu-item">
                                <a href="/tienda" class="menu-link">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Marketplace
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="/tienda/vendedor/registro" class="menu-link">
                                    <i class="fas fa-user-tie me-2"></i>
                                    Ser Vendedor
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-divider"></li>
                    <li class="menu-item">
                        <a href="/site/contact" class="menu-link">
                            <i class="fas fa-envelope me-2"></i>
                            Contacto
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="/site/help" class="menu-link">
                            <i class="fas fa-question-circle me-2"></i>
                            Ayuda
                        </a>
                    </li>
                </ul>
            `;
            
            this.adaptMenuForOffCanvas(this.sidebarNav);
            this.menuLoaded = true;
            this.fallbackLevel = 2;
            console.log('✅ Menú de respaldo cargado exitosamente');
        } catch (error) {
            console.error('❌ Error en loadFallbackMenu:', error);
            this.loadSimpleMenu();
        }
    }
    
    loadSimpleMenu() {
        try {
            console.log('🔄 Cargando menú simple de emergencia...');
            
            if (!this.sidebarNav) {
                console.error('❌ Contenedor de menú no disponible');
                return;
            }
            
            this.sidebarNav.innerHTML = `
                <div class="simple-menu">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Menú simplificado - Sistema GED
                    </div>
                    <div class="list-group">
                        <a href="/" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i> Inicio
                        </a>
                        <a href="/ged/default/index" class="list-group-item list-group-item-action">
                            <i class="fas fa-school me-2"></i> Seleccionar Escuela
                        </a>
                        <a href="/tienda" class="list-group-item list-group-item-action">
                            <i class="fas fa-store me-2"></i> Tienda
                        </a>
                        <a href="/site/login" class="list-group-item list-group-item-action">
                            <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
                        </a>
                        <a href="/site/contact" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i> Contacto
                        </a>
                    </div>
                </div>
            `;
            
            this.menuLoaded = true;
            this.fallbackLevel = 3;
            console.log('✅ Menú simple de emergencia cargado');
        } catch (error) {
            console.error('❌ Error crítico en loadSimpleMenu:', error);
            this.showErrorState();
        }
    }
    
    showLoadingState() {
        if (this.sidebarNav) {
            this.sidebarNav.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando menú...</span>
                    </div>
                    <p class="text-muted mt-3">Cargando menú de navegación...</p>
                    <small class="text-muted">Por favor espere</small>
                </div>
            `;
        }
    }
    
    showErrorState() {
        if (this.sidebarNav) {
            this.sidebarNav.innerHTML = `
                <div class="text-center py-5">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar el menú
                    </div>
                    <button class="btn btn-primary mt-3" onclick="window.location.reload()">
                        <i class="fas fa-redo me-2"></i>
                        Recargar página
                    </button>
                </div>
            `;
        }
    }
    
    adaptMenuForOffCanvas(menuElement) {
        try {
            let mainMenu = menuElement.querySelector('.navbar-nav, .sidebar-menu, .simple-menu, .list-group');
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
                        indicator.innerHTML = '<i class="fas fa-chevron-right"></i>';
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
            // Refrescar eventos para submenús
            const menuItems = menuElement.querySelectorAll('.has-children > .menu-link');
            menuItems.forEach(menuItem => {
                // Clonar para limpiar eventos anteriores
                const newMenuItem = menuItem.cloneNode(true);
                menuItem.parentNode.replaceChild(newMenuItem, menuItem);
            });
            
            // Agregar nuevos eventos
            const refreshedMenuItems = menuElement.querySelectorAll('.has-children > .menu-link');
            refreshedMenuItems.forEach(menuItem => {
                menuItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleSubmenu(menuItem.parentElement);
                });
            });
            
            // Eventos para enlaces normales
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
            const indicator = parentItem.querySelector('.submenu-indicator i');
            
            // Cerrar otros submenús en el mismo nivel
            const siblings = parentItem.parentElement.querySelectorAll('.has-children');
            siblings.forEach(sibling => {
                if (sibling !== parentItem) {
                    const siblingSubmenu = sibling.querySelector('.submenu');
                    const siblingIndicator = sibling.querySelector('.submenu-indicator i');
                    if (siblingSubmenu) siblingSubmenu.style.display = 'none';
                    if (siblingIndicator) {
                        siblingIndicator.classList.remove('fa-chevron-down');
                        siblingIndicator.classList.add('fa-chevron-right');
                    }
                    sibling.classList.remove('open');
                }
            });
            
            if (isCurrentlyOpen) {
                submenu.style.display = 'none';
                if (indicator) {
                    indicator.classList.remove('fa-chevron-down');
                    indicator.classList.add('fa-chevron-right');
                }
                parentItem.classList.remove('open');
            } else {
                submenu.style.display = 'block';
                if (indicator) {
                    indicator.classList.remove('fa-chevron-right');
                    indicator.classList.add('fa-chevron-down');
                }
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
            
            // Cerrar con tecla Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) this.close();
            });
            
            // Cerrar al hacer clic fuera del sidebar
            document.addEventListener('click', (e) => {
                if (this.isOpen && this.sidebar && !this.sidebar.contains(e.target) && 
                    !e.target.classList.contains('navbar-toggler')) {
                    this.close();
                }
            });
            
            console.log('✅ Eventos vinculados correctamente');
        } catch (error) {
            console.error('Error en bindEvents:', error);
        }
    }
    
    interceptBootstrapToggler() {
        try {
            const navbarToggler = document.querySelector('.navbar-toggler');
            if (!navbarToggler) {
                console.warn('⚠️ No se encontró el navbar toggler, creando uno alternativo');
                this.createNavbarToggler();
                return;
            }
            
            // Guardar referencia original
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
                
                // En escritorio, mantener comportamiento original
                if (originalOnClick) originalOnClick.call(navbarToggler, e);
            }, true);
            
            this.navbarToggler = navbarToggler;
            console.log('✅ Toggler interceptado correctamente');
        } catch (error) {
            console.error('Error en interceptBootstrapToggler:', error);
        }
    }
    
    createNavbarToggler() {
        try {
            // Buscar navbar existente
            const navbar = document.querySelector('.navbar');
            if (!navbar) return;
            
            // Crear toggler
            const toggler = document.createElement('button');
            toggler.className = 'navbar-toggler ged-toggler';
            toggler.setAttribute('type', 'button');
            toggler.setAttribute('aria-label', 'Toggle navigation');
            toggler.innerHTML = '<span class="navbar-toggler-icon"></span>';
            
            // Agregar al navbar (al final para que sea visible)
            navbar.appendChild(toggler);
            
            // Vincular evento
            toggler.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.isOpen) {
                    this.close();
                } else {
                    this.open();
                }
            });
            
            this.navbarToggler = toggler;
            console.log('✅ Toggler creado dinámicamente');
        } catch (error) {
            console.error('Error en createNavbarToggler:', error);
        }
    }
    
    open() {
        try {
            if (this.isOpen) return;
            
            console.log('🔓 Abriendo Off-Canvas...');
            
            // Cargar menú si no está cargado
            if (!this.menuLoaded) {
                this.loadMobileMenu();
            }
            
            this.isOpen = true;
            this.sidebar.classList.add('open');
            this.backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = this.getScrollbarWidth() + 'px'; // Prevenir salto
            this.sidebar.setAttribute('tabindex', '-1');
            this.sidebar.focus();
            
            // Animar entrada
            this.sidebar.style.transform = 'translateX(0)';
            
            // Disparar evento personalizado
            window.dispatchEvent(new CustomEvent('ged:offcanvas:open'));
            
            console.log('✅ Off-Canvas abierto correctamente');
        } catch (error) {
            console.error('Error en open:', error);
            this.showErrorNotification('No se pudo abrir el menú');
        }
    }
    
    close() {
        try {
            if (!this.isOpen) return;
            
            console.log('🔒 Cerrando Off-Canvas...');
            
            this.isOpen = false;
            this.sidebar.classList.remove('open');
            this.backdrop.classList.remove('show');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            // Animar salida
            this.sidebar.style.transform = 'translateX(-100%)';
            
            this.closeAllSubmenus();
            
            // Devolver foco al toggler
            if (this.navbarToggler) {
                setTimeout(() => this.navbarToggler.focus(), 100);
            }
            
            // Disparar evento personalizado
            window.dispatchEvent(new CustomEvent('ged:offcanvas:close'));
            
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
                const indicator = item.querySelector('.submenu-indicator i');
                if (indicator) {
                    indicator.classList.remove('fa-chevron-down');
                    indicator.classList.add('fa-chevron-right');
                }
            });
        } catch (error) {
            console.error('Error en closeAllSubmenus:', error);
        }
    }
    
    handleViewportChange(isMobile) {
        this.isMobile = isMobile;
        console.log(`🔄 Off-Canvas cambió a modo: ${this.isMobile ? 'Móvil' : 'Escritorio'}`);
        
        // Cerrar offcanvas cuando cambiamos a escritorio
        if (!this.isMobile && this.isOpen) {
            this.close();
        }
        
        // Disparar evento
        window.dispatchEvent(new CustomEvent('ged:offcanvas:modechange', {
            detail: { isMobile: this.isMobile }
        }));
    }
    
    // ✅ FUNCIONES DE UTILIDAD
    getScrollbarWidth() {
        // Crear elemento para medir el ancho del scrollbar
        const outer = document.createElement('div');
        outer.style.visibility = 'hidden';
        outer.style.overflow = 'scroll';
        outer.style.msOverflowStyle = 'scrollbar';
        document.body.appendChild(outer);
        
        const inner = document.createElement('div');
        outer.appendChild(inner);
        
        const scrollbarWidth = (outer.offsetWidth - inner.offsetWidth);
        
        // Limpiar
        outer.parentNode.removeChild(outer);
        
        return scrollbarWidth;
    }
    
    showErrorNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'ged-notification alert alert-warning alert-dismissible fade show';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>
                    <strong>OffCanvas Error</strong>
                    <div class="small">${message}</div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
    
    // ✅ MÉTODOS PÚBLICOS PARA CONTROL EXTERNO
    reloadMenu() {
        console.log('🔄 Recargando menú manualmente...');
        this.menuLoaded = false;
        this.fallbackLevel = 0;
        this.loadMobileMenu();
    }
    
    getStatus() {
        return {
            isOpen: this.isOpen,
            isMobile: this.isMobile,
            menuLoaded: this.menuLoaded,
            fallbackLevel: this.fallbackLevel,
            elements: {
                sidebar: !!this.sidebar,
                backdrop: !!this.backdrop,
                sidebarNav: !!this.sidebarNav,
                navbarToggler: !!this.navbarToggler
            }
        };
    }
    
    destroy() {
        try {
            console.log('🗑️ Destruyendo OffCanvas...');
            
            // Remover elementos del DOM
            if (this.sidebar && this.sidebar.parentNode) {
                this.sidebar.parentNode.removeChild(this.sidebar);
            }
            
            if (this.backdrop && this.backdrop.parentNode) {
                this.backdrop.parentNode.removeChild(this.backdrop);
            }
            
            // Limpiar referencias
            this.sidebar = null;
            this.backdrop = null;
            this.sidebarNav = null;
            this.navbarToggler = null;
            this.isOpen = false;
            this.menuLoaded = false;
            
            console.log('✅ OffCanvas destruido correctamente');
        } catch (error) {
            console.error('Error en destroy:', error);
        }
    }
}

// ==================================================
// INICIALIZACIÓN GLOBAL MEJORADA
// ==================================================

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.OffCanvasSidebar = OffCanvasSidebar;
}

// Inicialización automática mejorada
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Inicializando OffCanvas automáticamente...');
    
    // Verificar condiciones antes de inicializar
    const shouldInitialize = () => {
        // Solo inicializar si hay toggler o estamos en móvil
        const hasToggler = document.querySelector('.navbar-toggler');
        const isMobile = window.innerWidth < 992;
        
        return hasToggler || isMobile;
    };
    
    if (shouldInitialize()) {
        setTimeout(() => {
            if (!window.gedOffcanvas) {
                try {
                    window.gedOffcanvas = new OffCanvasSidebar();
                    const success = window.gedOffcanvas.init();
                    
                    if (success) {
                        console.log('✅ ged-offcanvas.js inicializado automáticamente');
                        
                        // Escuchar cambios de viewport
                        window.addEventListener('resize', () => {
                            const newIsMobile = window.innerWidth < 992;
                            if (newIsMobile !== window.gedOffcanvas.isMobile) {
                                window.gedOffcanvas.handleViewportChange(newIsMobile);
                            }
                        });
                    } else {
                        console.warn('⚠️ ged-offcanvas.js inicializado con advertencias');
                    }
                } catch (error) {
                    console.error('❌ Error crítico al inicializar ged-offcanvas:', error);
                }
            } else {
                console.log('ℹ️ gedOffcanvas ya estaba inicializado');
            }
        }, 500); // Delay para asegurar que otros scripts hayan cargado
    } else {
        console.log('ℹ️ No se requiere inicialización de OffCanvas en esta página');
    }
});

// ==================================================
// FUNCIONES DE UTILIDAD GLOBALES
// ==================================================

if (typeof window !== 'undefined') {
    // Función para recargar el menú manualmente
    window.reloadOffCanvasMenu = function() {
        if (window.gedOffcanvas) {
            window.gedOffcanvas.reloadMenu();
            console.log('🔄 Menú del off-canvas recargado manualmente');
        }
    };
    
    // Función para abrir/cerrar manualmente
    window.toggleOffCanvas = function() {
        if (window.gedOffcanvas) {
            if (window.gedOffcanvas.isOpen) {
                window.gedOffcanvas.close();
            } else {
                window.gedOffcanvas.open();
            }
        }
    };
    
    // Debug function mejorada
    window.debugOffCanvas = function() {
        console.group('🐛 DEBUG GED OFFCANVAS - VERSIÓN MEJORADA');
        console.log('Instancia:', window.gedOffcanvas);
        
        if (window.gedOffcanvas) {
            const status = window.gedOffcanvas.getStatus();
            console.log('Estado:', status);
            console.log('Fallback level:', ['AJAX', 'Real Menu', 'Fallback Menu', 'Simple Menu'][status.fallbackLevel]);
        } else {
            console.log('Estado: No inicializado');
        }
        
        console.log('Elementos:', {
            sidebar: document.querySelector('.ged-offcanvas-sidebar'),
            backdrop: document.querySelector('.ged-sidebar-backdrop'),
            navbarToggler: document.querySelector('.navbar-toggler')
        });
        
        console.groupEnd();
    };
    
    // Función para forzar reconstrucción
    window.rebuildOffCanvas = function() {
        if (window.gedOffcanvas) {
            window.gedOffcanvas.destroy();
        }
        
        setTimeout(() => {
            window.gedOffcanvas = new OffCanvasSidebar();
            window.gedOffcanvas.init();
            console.log('🔨 OffCanvas reconstruido manualmente');
        }, 100);
    };
}

// Compatibilidad con módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OffCanvasSidebar;
}

// ==================================================
// ESTILOS DINÁMICOS (para emergencias)
// ==================================================

// Inyectar estilos básicos si no existen
(function injectBasicStyles() {
    if (!document.getElementById('ged-offcanvas-styles')) {
        const style = document.createElement('style');
        style.id = 'ged-offcanvas-styles';
        style.textContent = `
            .ged-offcanvas-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 300px;
                height: 100vh;
                background: white;
                z-index: 1050;
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                display: flex;
                flex-direction: column;
            }
            
            .ged-offcanvas-sidebar.open {
                transform: translateX(0);
            }
            
            .ged-sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.5);
                z-index: 1049;
                display: none;
            }
            
            .ged-sidebar-backdrop.show {
                display: block;
            }
            
            .sidebar-header {
                padding: 15px;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .close-sidebar {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #6c757d;
            }
            
            .sidebar-body {
                flex: 1;
                overflow-y: auto;
                padding: 15px;
            }
            
            .sidebar-footer {
                padding: 10px 15px;
                border-top: 1px solid #dee2e6;
                background: #f8f9fa;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .has-children .submenu {
                display: none;
                padding-left: 20px;
            }
            
            .has-children.open .submenu {
                display: block;
            }
            
            .submenu-indicator {
                margin-left: auto;
                transition: transform 0.3s ease;
            }
            
            .has-children.open .submenu-indicator {
                transform: rotate(90deg);
            }
        `;
        
        document.head.appendChild(style);
        console.log('✅ Estilos básicos inyectados para OffCanvas');
    }
})();