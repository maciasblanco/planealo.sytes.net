// ged-offcanvas.js - Módulo OffCanvas Sidebar para GED - VERSIÓN INTEGRADA CON MenuWidget.php
// Versión: 3.2.0 - Integración completa con MenuWidget.php y GED System
// Fecha: 16/01/2024

// ==================================================
// OFF-CANVAS SIDEBAR INTEGRADO CON MenuWidget.php
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
        
        // URLs para cargar el menú
        this.menuConfig = {
            ajaxUrl: '/site/get-mobile-menu', // Debes crear esta ruta en Yii2
            fallbackOnly: false,
            debugMode: true
        };
        
        console.log('🔧 OffCanvasSidebar inicializado - Integración MenuWidget');
    }
    
    init() {
        try {
            console.log('🔧 OffCanvasSidebar inicializando...');
            
            // Verificar Bootstrap y jQuery
            if (!this.checkDependencies()) {
                console.error('❌ Dependencias no cumplidas');
                return false;
            }
            
            this.createOffCanvas();
            this.bindEvents();
            
            // Precargar menú si estamos en móvil
            if (this.isMobile) {
                setTimeout(() => this.preloadMenu(), 500);
            }
            
            console.log('✅ Off-Canvas Sidebar inicializado - Móvil:', this.isMobile);
            return true;
        } catch (error) {
            console.error('❌ Error crítico en OffCanvasSidebar.init:', error);
            this.showErrorNotification('Error al inicializar menú móvil');
            return false;
        }
    }
    
    // ✅ VERIFICAR DEPENDENCIAS
    checkDependencies() {
        const checks = {
            'Bootstrap Offcanvas': typeof bootstrap !== 'undefined' && bootstrap.Offcanvas,
            'jQuery disponible': typeof $ !== 'undefined',
            'Body disponible': !!document.body
        };
        
        let allPassed = true;
        for (const [name, passed] of Object.entries(checks)) {
            if (!passed) {
                console.warn(`⚠️ Dependencia no cumplida: ${name}`);
                allPassed = false;
            }
        }
        
        return allPassed;
    }
    
    // ✅ CREAR ESTRUCTURA DEL OFFCANVAS
    createOffCanvas() {
        try {
            // Verificar si ya existe
            const existingSidebar = document.querySelector('#gedMobileMenuContainer');
            if (existingSidebar) {
                this.sidebar = existingSidebar;
                this.sidebarNav = this.sidebar.querySelector('.sidebar-nav');
                console.log('✅ OffCanvas ya existente, reutilizando');
                return;
            }

            // Crear estructura del offcanvas
            const sidebar = document.createElement('div');
            sidebar.id = 'gedMobileMenuContainer';
            sidebar.className = 'ged-offcanvas-sidebar offcanvas offcanvas-start';
            sidebar.tabIndex = -1;
            sidebar.setAttribute('aria-labelledby', 'offcanvasLabel');
            
            sidebar.innerHTML = `
                <div class="offcanvas-header bg-primary text-white">
                    <h5 class="offcanvas-title" id="offcanvasLabel">
                        <i class="fas fa-bars me-2"></i>
                        Menú del Sistema
                    </h5>
                    <button type="button" class="btn-close btn-close-white" 
                            data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <nav class="sidebar-nav" aria-label="Navegación principal">
                        <!-- Menú se cargará aquí dinámicamente -->
                        <div class="menu-loading text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando menú...</span>
                            </div>
                            <p class="text-muted mt-2">Cargando menú...</p>
                        </div>
                    </nav>
                </div>
                <div class="offcanvas-footer bg-light border-top py-2 px-3">
                    <small class="text-muted">GED System v4.6</small>
                </div>
            `;
            
            // Crear toggler si no existe
            this.ensureNavbarToggler();
            
            document.body.appendChild(sidebar);
            
            this.sidebar = sidebar;
            this.sidebarNav = this.sidebar.querySelector('.sidebar-nav');
            
            // Inicializar Bootstrap Offcanvas
            this.bootstrapOffcanvas = new bootstrap.Offcanvas(sidebar);
            
            console.log('✅ OffCanvas creado exitosamente con Bootstrap');
        } catch (error) {
            console.error('❌ Error en createOffCanvas:', error);
            this.createEmergencyOffCanvas();
        }
    }
    
    // ✅ GARANTIZAR QUE EXISTA EL TOGGLER
    ensureNavbarToggler() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;
        
        // Buscar toggler existente
        let toggler = navbar.querySelector('.navbar-toggler');
        
        if (!toggler) {
            // Crear toggler si no existe
            toggler = document.createElement('button');
            toggler.className = 'navbar-toggler';
            toggler.type = 'button';
            toggler.setAttribute('data-bs-toggle', 'offcanvas');
            toggler.setAttribute('data-bs-target', '#gedMobileMenuContainer');
            toggler.setAttribute('aria-controls', 'gedMobileMenuContainer');
            toggler.setAttribute('aria-label', 'Toggle navigation');
            toggler.innerHTML = '<span class="navbar-toggler-icon"></span>';
            
            // Agregar al navbar
            const navbarCollapse = navbar.querySelector('.navbar-collapse');
            if (navbarCollapse) {
                navbar.insertBefore(toggler, navbarCollapse);
            } else {
                navbar.appendChild(toggler);
            }
            
            console.log('✅ Toggler creado dinámicamente');
        }
        
        this.navbarToggler = toggler;
    }
    
    // ✅ CARGAR MENÚ DINÁMICAMENTE DESDE MenuWidget.php
    async loadMobileMenu() {
        try {
            if (this.menuLoaded) return;
            
            console.log('📱 Cargando menú específico para móvil...');
            
            if (!this.sidebarNav) {
                console.error('❌ Contenedor de menú no encontrado');
                this.loadFallbackMenu();
                return;
            }
            
            // Mostrar estado de carga
            this.showLoadingState();
            
            // Intentar cargar via AJAX
            const success = await this.loadMenuViaAJAX();
            
            if (!success) {
                // Fallback 1: Intentar obtener menú del navbar si existe
                this.loadMenuFromExistingNavbar();
            }
            
            this.menuLoaded = true;
        } catch (error) {
            console.error('❌ Error en loadMobileMenu:', error);
            this.loadFallbackMenu();
        }
    }
    
    // ✅ PRECARGAR MENÚ (para mejor UX)
    preloadMenu() {
        if (!this.menuLoaded && this.isMobile) {
            console.log('⚡ Precargando menú móvil...');
            this.loadMobileMenu();
        }
    }
    
    // ✅ CARGAR MENÚ VIA AJAX (Integración con MenuWidget.php)
    async loadMenuViaAJAX() {
        return new Promise((resolve) => {
            if (!window.$) {
                console.warn('⚠️ jQuery no disponible para AJAX');
                resolve(false);
                return;
            }
            
            console.log('🌐 Intentando cargar menú dinámico...');
            
            // Intenta varias rutas posibles
            const possibleUrls = [
                '/site/mobile-menu',
                '/site/get-mobile-menu',
                '/menu/widget',
                window.location.origin + '/site/get-mobile-menu'
            ];
            
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            // Función para intentar una URL
            const tryUrl = (urlIndex) => {
                if (urlIndex >= possibleUrls.length) {
                    console.warn('⚠️ Todas las rutas AJAX fallaron');
                    resolve(false);
                    return;
                }
                
                const url = possibleUrls[urlIndex];
                console.log(`🔍 Intentando ruta: ${url}`);
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'html',
                    data: csrfToken ? { _csrf: csrfToken } : {},
                    timeout: 3000,
                    success: (response) => {
                        console.log(`✅ Menú cargado desde: ${url}`);
                        this.processMenuHTML(response);
                        resolve(true);
                    },
                    error: (xhr, status, error) => {
                        console.warn(`❌ Error en ${url}: ${status}`);
                        // Intentar siguiente URL
                        tryUrl(urlIndex + 1);
                    }
                });
            };
            
            // Comenzar con la primera URL
            tryUrl(0);
        });
    }
    
    // ✅ CARGAR MENÚ DESDE NAVBAR EXISTENTE
    loadMenuFromExistingNavbar() {
        try {
            console.log('🔄 Buscando menú existente en navbar...');
            
            // Buscar el menú principal en varias ubicaciones posibles
            const menuSelectors = [
                '.navbar-nav',
                '#navbar-menu',
                '.main-navigation',
                'nav .nav',
                '[role="navigation"] ul'
            ];
            
            let sourceMenu = null;
            for (const selector of menuSelectors) {
                const element = document.querySelector(selector);
                if (element && element.children.length > 0) {
                    sourceMenu = element;
                    console.log(`✅ Menú encontrado con selector: ${selector}`);
                    break;
                }
            }
            
            if (sourceMenu && this.sidebarNav) {
                // Clonar el menú
                const clonedMenu = sourceMenu.cloneNode(true);
                
                // Limpiar clases de Bootstrap que puedan interferir
                this.cleanBootstrapClasses(clonedMenu);
                
                this.sidebarNav.innerHTML = '';
                this.sidebarNav.appendChild(clonedMenu);
                
                // Adaptar para offcanvas
                this.adaptMenuForOffCanvas(this.sidebarNav);
                
                console.log('✅ Menú cargado desde navbar existente');
                return true;
            }
            
            console.warn('⚠️ No se encontró menú en navbar');
            return false;
            
        } catch (error) {
            console.error('❌ Error en loadMenuFromExistingNavbar:', error);
            return false;
        }
    }
    
    // ✅ CARGAR MENÚ DE RESERVA (fallback principal)
    loadFallbackMenu() {
        try {
            console.log('🔄 Cargando menú de reserva...');
            
            if (!this.sidebarNav) {
                console.error('❌ Contenedor de menú no disponible');
                this.loadSimpleMenu();
                return;
            }
            
            // Usar el HTML generado por MenuWidget.php (debería estar disponible)
            const menuWidgetHTML = this.getMenuWidgetHTML();
            
            if (menuWidgetHTML) {
                this.sidebarNav.innerHTML = menuWidgetHTML;
                this.adaptMenuForOffCanvas(this.sidebarNav);
                console.log('✅ Menú de reserva cargado desde HTML estático');
            } else {
                // Fallback a menú estático
                this.loadStaticMenu();
            }
            
        } catch (error) {
            console.error('❌ Error en loadFallbackMenu:', error);
            this.loadSimpleMenu();
        }
    }
    
    // ✅ OBTENER HTML DE MenuWidget.php (si está disponible)
    getMenuWidgetHTML() {
        try {
            // Buscar menú offcanvas oculto en el DOM
            const hiddenMenu = document.querySelector('#offcanvas-menu-template, .mobile-menu-template');
            if (hiddenMenu && hiddenMenu.innerHTML) {
                return hiddenMenu.innerHTML;
            }
            
            // Buscar script con template
            const scriptTemplate = document.querySelector('script[type="text/template"][data-menu]');
            if (scriptTemplate) {
                return scriptTemplate.textContent;
            }
            
            return null;
        } catch (error) {
            console.error('Error en getMenuWidgetHTML:', error);
            return null;
        }
    }
    
    // ✅ CARGAR MENÚ ESTÁTICO (fallback secundario)
    loadStaticMenu() {
        try {
            if (!this.sidebarNav) return;
            
            this.sidebarNav.innerHTML = `
                <div class="mobile-menu-container">
                    <div class="menu-header mb-3">
                        <h6 class="text-primary mb-2">
                            <i class="fas fa-sitemap me-2"></i>Navegación Principal
                        </h6>
                    </div>
                    
                    <ul class="nav flex-column mobile-menu">
                        <li class="nav-item">
                            <a class="nav-link active" href="/">
                                <i class="fas fa-home fa-fw me-2"></i>Inicio
                            </a>
                        </li>
                        
                        <li class="nav-item menu-divider">
                            <small class="text-muted">GED System</small>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/ged/default/index">
                                <i class="fas fa-school fa-fw me-2"></i>Seleccionar Escuela
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/ged/escuela/listar">
                                <i class="fas fa-list fa-fw me-2"></i>Listar Escuelas
                            </a>
                        </li>
                        
                        <li class="nav-item menu-divider">
                            <small class="text-muted">Tienda</small>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/tienda/marketplace">
                                <i class="fas fa-store fa-fw me-2"></i>Marketplace
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/tienda/producto/create">
                                <i class="fas fa-box fa-fw me-2"></i>Inventario
                            </a>
                        </li>
                        
                        <li class="nav-item menu-divider">
                            <small class="text-muted">Cuenta</small>
                        </li>
                        
                        ${this.getUserMenuItems()}
                    </ul>
                </div>
            `;
            
            console.log('✅ Menú estático cargado');
            
        } catch (error) {
            console.error('Error en loadStaticMenu:', error);
        }
    }
    
    // ✅ OBTENER ITEMS DE MENÚ SEGÚN ESTADO DEL USUARIO
    getUserMenuItems() {
        // Esta función debería sincronizarse con la lógica de MenuWidget.php
        // Por ahora, devolvemos un menú básico
        
        const isGuest = true; // Esto debería detectarse dinámicamente
        
        if (isGuest) {
            return `
                <li class="nav-item">
                    <a class="nav-link" href="/site/login">
                        <i class="fas fa-sign-in-alt fa-fw me-2"></i>Iniciar Sesión
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/site/signup">
                        <i class="fas fa-user-plus fa-fw me-2"></i>Registrarse
                    </a>
                </li>
            `;
        } else {
            return `
                <li class="nav-item">
                    <a class="nav-link" href="/site/mi-cuenta">
                        <i class="fas fa-user-cog fa-fw me-2"></i>Mi Cuenta
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/site/logout" data-method="post">
                        <i class="fas fa-sign-out-alt fa-fw me-2"></i>Cerrar Sesión
                    </a>
                </li>
            `;
        }
    }
    
    // ✅ CARGAR MENÚ SIMPLE (último fallback)
    loadSimpleMenu() {
        try {
            console.log('🔄 Cargando menú simple de emergencia...');
            
            if (!this.sidebarNav) {
                console.error('❌ Contenedor de menú no disponible');
                return;
            }
            
            this.sidebarNav.innerHTML = `
                <div class="simple-menu p-3">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Menú simplificado</strong>
                        <p class="mb-0 small">No se pudo cargar el menú completo.</p>
                    </div>
                    <div class="list-group">
                        <a href="/" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i>Inicio
                        </a>
                        <a href="/ged/default/index" class="list-group-item list-group-item-action">
                            <i class="fas fa-school me-2"></i>GED Sistema
                        </a>
                        <a href="/tienda/marketplace" class="list-group-item list-group-item-action">
                            <i class="fas fa-store me-2"></i>Tienda
                        </a>
                        <a href="/site/login" class="list-group-item list-group-item-action">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </a>
                    </div>
                </div>
            `;
            
            console.log('✅ Menú simple de emergencia cargado');
            
        } catch (error) {
            console.error('❌ Error crítico en loadSimpleMenu:', error);
            this.showErrorState();
        }
    }
    
    // ✅ PROCESAR HTML DEL MENÚ
    processMenuHTML(html) {
        if (!this.sidebarNav) return;
        
        try {
            this.sidebarNav.innerHTML = html;
            
            // Verificar si el HTML tiene contenido válido
            if (this.sidebarNav.children.length === 0 || 
                (this.sidebarNav.children.length === 1 && 
                 this.sidebarNav.children[0].classList.contains('menu-loading'))) {
                console.warn('⚠️ HTML del menú vacío, usando fallback');
                this.loadFallbackMenu();
                return;
            }
            
            // Adaptar para offcanvas
            this.adaptMenuForOffCanvas(this.sidebarNav);
            
            console.log('✅ Menú procesado y adaptado correctamente');
            
        } catch (error) {
            console.error('❌ Error en processMenuHTML:', error);
            this.loadFallbackMenu();
        }
    }
    
    // ✅ LIMPIAR CLASES DE BOOTSTRAP
    cleanBootstrapClasses(element) {
        // Limpiar clases que puedan interferir con el offcanvas
        const classesToRemove = [
            'dropdown-menu', 'dropdown-toggle', 'dropdown-item',
            'navbar-nav', 'nav-item', 'nav-link'
        ];
        
        classesToRemove.forEach(className => {
            const elements = element.querySelectorAll(`.${className}`);
            elements.forEach(el => {
                el.classList.remove(className);
            });
        });
        
        // Convertir a estructura de menú móvil
        element.classList.add('mobile-menu', 'nav', 'flex-column');
    }
    
    // ✅ ADAPTAR MENÚ PARA OFFCANVAS
    adaptMenuForOffCanvas(menuElement) {
        try {
            // Agregar eventos para submenús
            const submenuToggles = menuElement.querySelectorAll('[data-bs-toggle="dropdown"], .has-children > a');
            
            submenuToggles.forEach(toggle => {
                // Reemplazar evento de dropdown de Bootstrap por nuestro propio
                toggle.addEventListener('click', (e) => {
                    if (this.isMobile) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const parent = toggle.closest('.has-children, .dropdown');
                        if (parent) {
                            this.toggleMobileSubmenu(parent);
                        }
                    }
                });
            });
            
            // Agregar eventos para cerrar el offcanvas al hacer clic en enlaces
            const menuLinks = menuElement.querySelectorAll('a:not([data-bs-toggle="dropdown"])');
            menuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (this.isMobile && this.bootstrapOffcanvas) {
                        setTimeout(() => {
                            this.bootstrapOffcanvas.hide();
                        }, 300);
                    }
                });
            });
            
            console.log('✅ Menú adaptado para offcanvas');
            
        } catch (error) {
            console.error('Error en adaptMenuForOffCanvas:', error);
        }
    }
    
    // ✅ ALTERNAR SUBMENÚ MÓVIL
    toggleMobileSubmenu(parentItem) {
        const submenu = parentItem.querySelector('.dropdown-menu, .submenu');
        if (!submenu) return;
        
        const isOpen = submenu.style.display === 'block' || 
                      submenu.classList.contains('show');
        
        // Cerrar otros submenús al mismo nivel
        const siblings = parentItem.parentElement.querySelectorAll('.has-children, .dropdown');
        siblings.forEach(sibling => {
            if (sibling !== parentItem) {
                const siblingSubmenu = sibling.querySelector('.dropdown-menu, .submenu');
                if (siblingSubmenu) {
                    siblingSubmenu.style.display = 'none';
                    siblingSubmenu.classList.remove('show');
                    sibling.classList.remove('show');
                }
            }
        });
        
        if (isOpen) {
            submenu.style.display = 'none';
            submenu.classList.remove('show');
            parentItem.classList.remove('show');
        } else {
            submenu.style.display = 'block';
            submenu.classList.add('show');
            parentItem.classList.add('show');
        }
    }
    
    // ✅ MOSTRAR ESTADO DE CARGA
    showLoadingState() {
        if (this.sidebarNav) {
            this.sidebarNav.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando menú...</span>
                    </div>
                    <p class="text-muted mt-3">Cargando menú de navegación...</p>
                    <small class="text-muted">Sincronizando con MenuWidget.php</small>
                </div>
            `;
        }
    }
    
    // ✅ MOSTRAR ESTADO DE ERROR
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
    
    // ✅ CREAR OFFCANVAS DE EMERGENCIA
    createEmergencyOffCanvas() {
        try {
            const emergencyHTML = `
                <div class="offcanvas offcanvas-start" tabindex="-1" id="emergencyMobileMenu">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">Menú de Emergencia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                    </div>
                    <div class="offcanvas-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Usando menú de emergencia
                        </div>
                        <div class="list-group">
                            <a href="/" class="list-group-item list-group-item-action">Inicio</a>
                            <a href="/ged/default/index" class="list-group-item list-group-item-action">GED</a>
                            <a href="/tienda/marketplace" class="list-group-item list-group-item-action">Tienda</a>
                        </div>
                    </div>
                </div>
            `;
            
            const container = document.createElement('div');
            container.innerHTML = emergencyHTML;
            document.body.appendChild(container.firstElementChild);
            
            this.sidebar = document.getElementById('emergencyMobileMenu');
            this.sidebarNav = this.sidebar.querySelector('.offcanvas-body');
            
            console.log('✅ OffCanvas de emergencia creado');
            
        } catch (error) {
            console.error('❌ Error crítico al crear offcanvas de emergencia:', error);
        }
    }
    
    // ✅ VINCULAR EVENTOS
    bindEvents() {
        try {
            // Vincular evento para cargar menú al abrir
            if (this.sidebar) {
                this.sidebar.addEventListener('show.bs.offcanvas', () => {
                    if (!this.menuLoaded) {
                        this.loadMobileMenu();
                    }
                });
            }
            
            // Escuchar cambios de viewport
            window.addEventListener('resize', () => {
                const newIsMobile = window.innerWidth < 992;
                if (newIsMobile !== this.isMobile) {
                    this.isMobile = newIsMobile;
                    console.log(`🔄 Modo cambiado a: ${this.isMobile ? 'Móvil' : 'Escritorio'}`);
                }
            });
            
            console.log('✅ Eventos vinculados correctamente');
            
        } catch (error) {
            console.error('Error en bindEvents:', error);
        }
    }
    
    // ✅ ABRIR OFFCANVAS PROGRAMÁTICAMENTE
    open() {
        if (this.bootstrapOffcanvas) {
            this.bootstrapOffcanvas.show();
            if (!this.menuLoaded) {
                this.loadMobileMenu();
            }
        }
    }
    
    // ✅ CERRAR OFFCANVAS PROGRAMÁTICAMENTE
    close() {
        if (this.bootstrapOffcanvas) {
            this.bootstrapOffcanvas.hide();
        }
    }
    
    // ✅ MOSTRAR NOTIFICACIÓN DE ERROR
    showErrorNotification(message) {
        try {
            const notification = document.createElement('div');
            notification.className = 'alert alert-warning alert-dismissible fade show';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 350px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            notification.innerHTML = `
                <strong>OffCanvas Error</strong>
                <div class="small">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
            
        } catch (error) {
            console.error('Error en showErrorNotification:', error);
        }
    }
    
    // ✅ MÉTODOS PÚBLICOS
    reloadMenu() {
        console.log('🔄 Recargando menú manualmente...');
        this.menuLoaded = false;
        this.loadMobileMenu();
    }
    
    getStatus() {
        return {
            isOpen: this.bootstrapOffcanvas ? this.bootstrapOffcanvas._isShown : false,
            isMobile: this.isMobile,
            menuLoaded: this.menuLoaded,
            elements: {
                sidebar: !!this.sidebar,
                sidebarNav: !!this.sidebarNav,
                navbarToggler: !!this.navbarToggler
            }
        };
    }
}

// ==================================================
// INICIALIZACIÓN GLOBAL
// ==================================================

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.OffCanvasSidebar = OffCanvasSidebar;
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Inicializando OffCanvas automáticamente...');
    
    // Esperar a que Bootstrap esté disponible
    const waitForBootstrap = setInterval(() => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            clearInterval(waitForBootstrap);
            
            if (!window.gedOffcanvas) {
                try {
                    window.gedOffcanvas = new OffCanvasSidebar();
                    const success = window.gedOffcanvas.init();
                    
                    if (success) {
                        console.log('✅ ged-offcanvas.js inicializado automáticamente');
                    }
                } catch (error) {
                    console.error('❌ Error al inicializar ged-offcanvas:', error);
                }
            } else {
                console.log('ℹ️ gedOffcanvas ya estaba inicializado');
            }
        }
    }, 100);
});

// ==================================================
// FUNCIONES GLOBALES DE UTILIDAD
// ==================================================

if (typeof window !== 'undefined') {
    // Función para recargar el menú
    window.reloadOffCanvasMenu = function() {
        if (window.gedOffcanvas) {
            window.gedOffcanvas.reloadMenu();
        }
    };
    
    // Función para debug
    window.debugOffCanvas = function() {
        console.group('🐛 DEBUG GED OFFCANVAS');
        if (window.gedOffcanvas) {
            const status = window.gedOffcanvas.getStatus();
            console.log('Estado:', status);
            console.log('Elementos en DOM:', {
                sidebar: document.querySelector('#gedMobileMenuContainer'),
                toggler: document.querySelector('.navbar-toggler')
            });
        }
        console.groupEnd();
    };
}

// Compatibilidad con módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OffCanvasSidebar;
}

// ==================================================
// ESTILOS DINÁMICOS PARA MEJOR VISUALIZACIÓN
// ==================================================

(function injectOffCanvasStyles() {
    if (!document.getElementById('ged-offcanvas-styles')) {
        const style = document.createElement('style');
        style.id = 'ged-offcanvas-styles';
        style.textContent = `
            /* Estilos para el offcanvas de GED */
            #gedMobileMenuContainer {
                max-width: 300px;
            }
            
            /* Mejoras para el menú móvil */
            .mobile-menu .nav-link {
                padding: 0.75rem 1rem;
                border-radius: 0.375rem;
                margin-bottom: 0.125rem;
            }
            
            .mobile-menu .nav-link:hover {
                background-color: rgba(0, 0, 0, 0.05);
            }
            
            .mobile-menu .nav-link.active {
                background-color: #0d6efd;
                color: white;
            }
            
            .menu-divider {
                padding: 0.5rem 1rem;
                margin-top: 0.5rem;
                border-top: 1px solid #dee2e6;
            }
            
            /* Submenús móviles */
            .mobile-menu .dropdown-menu {
                border: none;
                box-shadow: none;
                background-color: rgba(0, 0, 0, 0.02);
                padding-left: 1.5rem;
            }
            
            /* Animaciones */
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .mobile-menu-container {
                animation: fadeIn 0.3s ease-out;
            }
            
            /* Responsive */
            @media (max-width: 576px) {
                #gedMobileMenuContainer {
                    max-width: 280px;
                }
            }
        `;
        
        document.head.appendChild(style);
        console.log('✅ Estilos de OffCanvas inyectados');
    }
})();