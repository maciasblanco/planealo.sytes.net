// ged-offcanvas.js - Módulo OffCanvas Sidebar para GED
// Versión: 3.0.0 - Extraído del sistema GED

// ==================================================
// OFF-CANVAS SIDEBAR CON LAZY LOADING
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
                    <div class="text-center py-4">
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
// INICIALIZACIÓN GLOBAL
// ==================================================

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.OffCanvasSidebar = OffCanvasSidebar;
}

// Inicialización automática si hay un toggler presente
document.addEventListener('DOMContentLoaded', function() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler && typeof OffCanvasSidebar !== 'undefined') {
        setTimeout(() => {
            if (!window.gedOffcanvas) {
                window.gedOffcanvas = new OffCanvasSidebar();
                window.gedOffcanvas.init();
                console.log('✅ ged-offcanvas.js inicializado automáticamente');
            }
        }, 500);
    }
});

// ==================================================
// FUNCIONES DE UTILIDAD
// ==================================================

if (typeof window !== 'undefined') {
    // Función para recargar el menú manualmente
    window.reloadOffCanvasMenu = function() {
        if (window.gedOffcanvas) {
            window.gedOffcanvas.loadMobileMenu();
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
    
    // Debug function
    window.debugOffCanvas = function() {
        console.group('🐛 DEBUG GED OFFCANVAS');
        console.log('Instancia:', window.gedOffcanvas);
        console.log('Estado:', window.gedOffcanvas ? 
            `Abierto: ${window.gedOffcanvas.isOpen}, Móvil: ${window.gedOffcanvas.isMobile}, Menú cargado: ${window.gedOffcanvas.menuLoaded}` : 
            'No inicializado');
        console.log('Elementos:', {
            sidebar: document.querySelector('.ged-offcanvas-sidebar'),
            backdrop: document.querySelector('.ged-sidebar-backdrop')
        });
        console.groupEnd();
    };
}

// Compatibilidad con módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OffCanvasSidebar;
}