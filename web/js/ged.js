// js/ged.js - Sistema GED - JavaScript con Menú Real en Off-Canvas

class GEDSystem {
    constructor() {
        this.isMobile = this.checkIsMobile();
        this.menuOpen = false;
        this.init();
    }
    
    checkIsMobile() {
        return window.innerWidth < 992;
    }
    
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        console.log('Sistema GED inicializado - Modo:', this.isMobile ? 'Móvil' : 'Escritorio');
        
        // Inicializar todos los módulos
        this.initNavbarFixed();
        this.initOffCanvasSidebar();
        this.initComponents();
        this.initSchoolSearch();
        this.initEscuelaSelector();
        this.initLandingPage();
        
        // Aplicar correcciones iniciales
        this.applyBodyCorrections();
        
        // Manejar cambios de tamaño
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }
    
    // ===== CORRECCIONES DE BODY Y LAYOUT =====
    applyBodyCorrections() {
        console.log('🔧 Aplicando correcciones de body y layout...');
        
        // Aplicar padding-top correcto al body según el viewport
        if (this.isMobile) {
            document.body.style.paddingTop = '70px';
        } else {
            document.body.style.paddingTop = '30vh';
        }
        
        // Corregir main content
        const main = document.querySelector('main#main');
        if (main) {
            main.style.marginTop = '0';
            main.style.minHeight = this.isMobile ? 'calc(100vh - 70px)' : 'calc(100vh - 30vh)';
        }
        
        console.log('✅ Correcciones de layout aplicadas');
    }
    
    // ===== OFF-CANVAS SIDEBAR =====
    initOffCanvasSidebar() {
        this.offCanvasSidebar = new OffCanvasSidebar();
    }
    
    // ===== NAVBAR FIXED =====
    initNavbarFixed() {
        this.navbar = document.querySelector('.navbar-contextual');
        this.carousel = document.getElementById('navbarCarousel');
        
        if (!this.navbar) {
            console.warn('Navbar contextual no encontrado');
            return;
        }
        
        this.forceFullWidth();
        this.optimizeCarousel();
        
        console.log('Navbar Fixed - Configurado');
    }
    
    forceFullWidth() {
        const fullWidthStyle = `
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        `;
        
        const elementsToFullWidth = [
            '.navbar-contextual',
            '.navbar-collapse',
            '.container-fluid'
        ];
        
        elementsToFullWidth.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.style.cssText += fullWidthStyle;
            });
        });
    }
    
    optimizeCarousel() {
        const carousel = document.getElementById('navbarCarousel');
        if (!carousel) return;
        
        const bootstrapCarousel = new bootstrap.Carousel(carousel, {
            interval: 5000,
            wrap: true,
            pause: 'hover'
        });
        
        this.setOptimalCarouselDimensions();
        
        window.addEventListener('resize', () => {
            setTimeout(() => this.setOptimalCarouselDimensions(), 100);
        });
    }
    
    setOptimalCarouselDimensions() {
        const carousel = document.getElementById('navbarCarousel');
        if (!carousel) return;
        
        const container = carousel.closest('.navbar-carousel-container');
        if (!container) return;
        
        const containerWidth = container.offsetWidth;
        const containerHeight = container.offsetHeight;
        
        carousel.style.width = `${containerWidth}px`;
        carousel.style.height = `${Math.min(containerHeight * 0.95, containerHeight - 10)}px`;
        
        const items = carousel.querySelectorAll('.carousel-item');
        items.forEach(item => {
            item.style.width = `${containerWidth}px`;
            item.style.height = `${Math.min(containerHeight * 0.95, containerHeight - 10)}px`;
        });
        
        const images = carousel.querySelectorAll('.carousel-image');
        images.forEach(img => {
            img.style.width = `${containerWidth}px`;
            img.style.height = `${Math.min(containerHeight * 0.95, containerHeight - 10)}px`;
            img.style.objectFit = 'cover';
        });
    }
    
    // ===== COMPONENTS =====
    initComponents() {
        console.log('Components inicializado');
        
        // Solo tooltips básicos si son necesarios
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // ===== SCHOOL SEARCH =====
    initSchoolSearch() {
        if (!document.querySelector('#schoolSearch')) return;
        
        this.schoolSearchSelectors = {
            searchInput: '#schoolSearch',
            searchResults: '#schoolSearchResults',
            searchBtn: '#searchSchoolBtn',
            currentSchool: '#current-school',
            currentSchoolId: '#current-school-id'
        };
        
        this.schoolSearchUrls = {
            search: '/ged/default/search-schools',
            setSchool: '/ged/default/set-school'
        };
        
        this.schoolSearchElements = {};
        this.searchTimeout = null;
        
        this.cacheSchoolSearchElements();
        this.bindSchoolSearchEvents();
        
        console.log('✅ Búsqueda de escuelas inicializada');
    }
    
    cacheSchoolSearchElements() {
        for (const [key, selector] of Object.entries(this.schoolSearchSelectors)) {
            this.schoolSearchElements[key] = $(selector);
        }
    }
    
    bindSchoolSearchEvents() {
        const { searchInput, searchResults, searchBtn } = this.schoolSearchElements;
        
        searchInput.on('input', (e) => {
            this.handleSearchInput(e.target.value.trim());
        });
        
        searchBtn.on('click', () => {
            this.handleSearchClick();
        });
        
        searchInput.on('keypress', (e) => {
            if (e.which === 13) {
                this.handleEnterKey(e);
            }
        });
        
        $(document).on('click', (e) => {
            if (!$(e.target).closest('.school-search-container').length) {
                this.hideResults();
            }
        });
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
        const query = this.schoolSearchElements.searchInput.val().trim();
        if (query.length >= 2) {
            this.performSearch(query);
        } else {
            this.schoolSearchElements.searchInput.focus();
        }
    }
    
    handleEnterKey(e) {
        const query = this.schoolSearchElements.searchInput.val().trim();
        if (query.length >= 2) {
            this.performSearch(query);
            e.preventDefault();
        }
    }
    
    performSearch(query) {
        this.showLoading();
        
        $.ajax({
            url: this.schoolSearchUrls.search,
            type: 'GET',
            data: { 
                q: query,
                _csrf: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                this.displayResults(response);
            },
            error: (xhr, status, error) => {
                console.error('Error en la búsqueda:', error);
                this.showError('Error en la búsqueda');
            }
        });
    }
    
    showLoading() {
        this.schoolSearchElements.searchResults.html('<div class="search-result-item text-muted">Buscando...</div>').show();
    }
    
    showError(message) {
        this.schoolSearchElements.searchResults.html(`<div class="search-result-item text-danger">${message}</div>`).show();
    }
    
    displayResults(escuelas) {
        const { searchResults } = this.schoolSearchElements;
        searchResults.empty();
        
        if (!escuelas || escuelas.length === 0) {
            searchResults.append(
                '<div class="search-result-item text-muted">No se encontraron escuelas</div>'
            );
        } else {
            escuelas.forEach((escuela) => {
                this.createResultItem(escuela);
            });
        }
        
        searchResults.show();
    }
    
    createResultItem(escuela) {
        const item = $('<div class="search-result-item"></div>');
        
        let escuelaInfo = `
            <div class="school-name">${escuela.nombre}</div>
            <div class="school-id">ID: ${escuela.id}</div>
        `;
        
        if (escuela.direccion_administrativa) {
            escuelaInfo += `<div class="school-address text-muted">${escuela.direccion_administrativa}</div>`;
        }
        
        item.html(escuelaInfo);
        
        item.on('click', () => {
            this.selectSchool({
                id: escuela.id,
                name: escuela.nombre
            });
        });
        
        this.schoolSearchElements.searchResults.append(item);
    }
    
    selectSchool(escuela) {
        const originalHtml = this.schoolSearchElements.searchBtn.html();
        this.schoolSearchElements.searchBtn.html('<i class="bi bi-hourglass-split"></i>').prop('disabled', true);
        
        $.ajax({
            url: this.schoolSearchUrls.setSchool,
            type: 'POST',
            data: {
                schoolId: escuela.id,
                schoolName: escuela.name,
                _csrf: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.success) {
                    this.updateUI(escuela);
                    this.showNotification('Escuela seleccionada: ' + escuela.name, 'success');
                    this.reloadPage();
                } else {
                    this.showNotification('Error al seleccionar la escuela', 'error');
                }
            },
            error: () => {
                this.showNotification('Error de conexión', 'error');
            },
            complete: () => {
                this.schoolSearchElements.searchBtn.html(originalHtml).prop('disabled', false);
            }
        });
    }
    
    updateUI(escuela) {
        this.schoolSearchElements.currentSchool.text(escuela.name);
        this.schoolSearchElements.currentSchoolId.text('ID: ' + escuela.id).show();
        this.schoolSearchElements.searchInput.val('');
        this.hideResults();
    }
    
    hideResults() {
        this.schoolSearchElements.searchResults.hide().empty();
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
        setTimeout(() => {
            location.reload();
        }, 800);
    }
    
    // ===== ESCUELA SELECTOR =====
    initEscuelaSelector() {
        if (typeof $ === 'undefined') {
            console.error('jQuery no está cargado');
            return;
        }

        try {
            // Smooth scroll para back to top
            $('.back-to-top').on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({scrollTop: 0}, 300);
            });
            
            // Mostrar/ocultar back to top
            $(window).on('scroll', function() {
                if ($(this).scrollTop() > 300) {
                    $('.back-to-top').addClass('active');
                } else {
                    $('.back-to-top').removeClass('active');
                }
            });
            
            console.log('Escuela selector inicializado correctamente');
            
        } catch (error) {
            console.error('Error en escuela selector:', error);
        }
    }
    
    // ===== LANDING PAGE =====
    initLandingPage() {
        // Selector principal de escuelas
        $('#main-escuela-select').on('change', function() {
            var escuelaId = $(this).val();
            if (escuelaId && escuelaId > 0) {
                var escuelaNombre = $(this).find('option:selected').text();
                window.location.href = '/ged/default/escuela?id=' + escuelaId + '&nombre=' + encodeURIComponent(escuelaNombre);
            }
        });

        // Filtro rápido de escuelas
        $('#filtro-escuelas').on('input', function() {
            var filtro = $(this).val().toLowerCase();
            $('.escuela-item').each(function() {
                var nombre = $(this).find('.school-name').text().toLowerCase();
                if (nombre.includes(filtro)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $('#btn-limpiar-filtro').on('click', function() {
            $('#filtro-escuelas').val('').trigger('input');
        });

        // Efectos hover mejorados
        $('.school-card').hover(
            function() {
                $(this).addClass('shadow-lg');
            },
            function() {
                $(this).removeClass('shadow-lg');
            }
        );

        // Carrusel automático
        $('#carouselHero').carousel({
            interval: 3000,
            pause: 'hover'
        });

        // Smooth scroll para navegación interna
        $('a[href^="#"]').on('click', function(event) {
            var target = $(this).attr('href');
            if (target && target !== '#' && $(target).length) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: $(target).offset().top - 100
                }, 1000);
            }
        });
    }
    
    // ===== MANEJO DE RESIZE =====
    handleResize() {
        const newIsMobile = this.checkIsMobile();
        if (newIsMobile !== this.isMobile) {
            this.isMobile = newIsMobile;
            console.log('Cambio de modo:', this.isMobile ? 'Móvil' : 'Escritorio');
            
            // Reinicializar off-canvas si cambió el modo
            if (this.offCanvasSidebar) {
                this.offCanvasSidebar.handleViewportChange(this.isMobile);
            }
        }
        
        setTimeout(() => {
            this.forceFullWidth();
            this.setOptimalCarouselDimensions();
            this.applyBodyCorrections();
        }, 100);
    }
}

// ==================================================
// OFF-CANVAS SIDEBAR - CON MENÚ REAL DE MenuWidget
// ==================================================

class OffCanvasSidebar {
    constructor() {
        this.isOpen = false;
        this.isMobile = window.innerWidth < 992;
        this.init();
    }
    
    init() {
        this.createOffCanvas();
        this.bindEvents();
        console.log('✅ Off-Canvas Sidebar inicializado - Móvil:', this.isMobile);
    }
    
    createOffCanvas() {
        // Solo crear si no existe
        if (document.querySelector('.ged-offcanvas-sidebar')) {
            this.sidebar = document.querySelector('.ged-offcanvas-sidebar');
            this.backdrop = document.querySelector('.ged-sidebar-backdrop');
            this.sidebarNav = this.sidebar.querySelector('.sidebar-nav');
            
            // **IMPORTANTE: Cargar el menú real si no está presente**
            this.loadRealMenu();
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
                <!-- El menú se cargará dinámicamente -->
            </nav>
        `;
        
        const backdrop = document.createElement('div');
        backdrop.className = 'ged-sidebar-backdrop';
        
        document.body.appendChild(sidebar);
        document.body.appendChild(backdrop);
        
        this.sidebar = sidebar;
        this.backdrop = backdrop;
        this.sidebarNav = this.sidebar.querySelector('.sidebar-nav');
        
        // **IMPORTANTE: Cargar el menú real**
        this.loadRealMenu();
        
        this.applyBodyCorrections();
    }
    
    // **NUEVO MÉTODO: Cargar el menú real desde el navbar**
    loadRealMenu() {
        console.log('🔄 Cargando menú real...');
        
        // Buscar el menú real en el navbar
        const realMenu = document.querySelector('.navbar-nav');
        
        if (!realMenu) {
            console.warn('❌ No se encontró el menú real en el navbar');
            this.loadFallbackMenu();
            return;
        }
        
        console.log('✅ Menú real encontrado, clonando...');
        
        // Clonar el menú real profundamente
        const clonedMenu = realMenu.cloneNode(true);
        
        // Limpiar el contenedor del sidebar
        this.sidebarNav.innerHTML = '';
        
        // Agregar el menú clonado
        this.sidebarNav.appendChild(clonedMenu);
        
        // Adaptar el menú para off-canvas
        this.adaptMenuForOffCanvas(this.sidebarNav);
        
        console.log('✅ Menú real cargado y adaptado correctamente');
    }
    
    // **NUEVO MÉTODO: Menú de respaldo si no se encuentra el real**
    loadFallbackMenu() {
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
        
        console.log('✅ Menú de respaldo cargado');
    }
    
    adaptMenuForOffCanvas(menuElement) {
        console.log('🎨 Adaptando menú para off-canvas...');
        
        // Convertir dropdowns de Bootstrap a menú simple COLABSABLE
        const dropdowns = menuElement.querySelectorAll('.dropdown, .dropdown-submenu');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('dropdown', 'dropdown-submenu');
            dropdown.classList.add('has-children');
            
            const toggle = dropdown.querySelector('.dropdown-toggle');
            if (toggle) {
                toggle.classList.remove('dropdown-toggle');
                toggle.removeAttribute('data-bs-toggle');
                toggle.removeAttribute('aria-expanded');
                // Agregar indicador de submenú si no existe
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
                
                // Aplicar recursivamente
                this.adaptMenuForOffCanvas(menu);
            }
        });
        
        // Limpiar clases de Bootstrap y agregar clases propias
        const navItems = menuElement.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.classList.remove('nav-item');
            item.classList.add('menu-item');
        });
        
        const navLinks = menuElement.querySelectorAll('.nav-link, .dropdown-item');
        navLinks.forEach(link => {
            link.classList.remove('nav-link', 'dropdown-item');
            link.classList.add('menu-link');
            
            // Asegurar que los enlaces tengan href válido
            if (link.getAttribute('href') === '#' && link.querySelector('.submenu-indicator')) {
                link.style.cursor = 'pointer';
            }
        });
        
        console.log('✅ Menú adaptado correctamente');
    }
    
    bindEvents() {
        // **IMPORTANTE: Interceptar el toggler de Bootstrap para móviles**
        this.interceptBootstrapToggler();
        
        // Cerrar sidebar
        const closeButton = this.sidebar.querySelector('.close-sidebar');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                this.close();
            });
        }
        
        // Cerrar con backdrop
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => {
                this.close();
            });
        }
        
        // Manejar submenús COLABSABLES
        if (this.sidebarNav) {
            this.sidebarNav.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && link.parentElement.classList.contains('has-children')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleSubmenu(link.parentElement);
                    return false;
                }
                
                // Cerrar sidebar al hacer clic en enlace normal (solo en móviles)
                if (this.isMobile && link && !link.parentElement.classList.contains('has-children')) {
                    setTimeout(() => this.close(), 300);
                }
            });
        }
        
        // Cerrar con tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    interceptBootstrapToggler() {
        const navbarToggler = document.querySelector('.navbar-toggler');
        if (!navbarToggler) {
            console.warn('❌ No se encontró el navbar toggler');
            return;
        }
        
        console.log('🎯 Interceptando toggler de Bootstrap...');
        
        // Guardar el evento original de Bootstrap
        const originalOnClick = navbarToggler.onclick;
        
        navbarToggler.addEventListener('click', (e) => {
            // Solo en móviles
            if (this.isMobile) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('📱 Toggler interceptado - Abriendo off-canvas');
                
                // Abrir nuestro off-canvas en lugar del colapso de Bootstrap
                if (this.isOpen) {
                    this.close();
                } else {
                    this.open();
                }
                return false;
            }
            
            // En escritorio, dejar que Bootstrap maneje el evento
            if (originalOnClick) {
                originalOnClick.call(navbarToggler, e);
            }
        });
        
        console.log('✅ Toggler interceptado correctamente');
    }
    
    applyBodyCorrections() {
        if (this.isMobile) {
            document.body.style.paddingTop = '70px';
        } else {
            document.body.style.paddingTop = '30vh';
        }
    }
    
    open() {
        if (this.isOpen) return;
        
        console.log('🚀 Abriendo off-canvas sidebar...');
        
        this.isOpen = true;
        this.sidebar.classList.add('open');
        this.backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Mover foco al sidebar para accesibilidad
        this.sidebar.setAttribute('tabindex', '-1');
        this.sidebar.focus();
        
        console.log('✅ Off-Canvas abierto correctamente');
    }
    
    close() {
        if (!this.isOpen) return;
        
        console.log('🚀 Cerrando off-canvas sidebar...');
        
        this.isOpen = false;
        this.sidebar.classList.remove('open');
        this.backdrop.classList.remove('show');
        document.body.style.overflow = '';
        
        // Cerrar todos los submenús
        this.closeAllSubmenus();
        
        console.log('✅ Off-Canvas cerrado correctamente');
    }
    
    toggleSubmenu(parentItem) {
        const submenu = parentItem.querySelector('.submenu');
        if (!submenu) return;
        
        const isCurrentlyOpen = submenu.style.display === 'block';
        
        console.log(`🔄 ${isCurrentlyOpen ? 'Cerrando' : 'Abriendo'} submenú...`);
        
        // Cerrar todos los submenús del mismo nivel
        const siblings = parentItem.parentElement.querySelectorAll('.has-children');
        siblings.forEach(sibling => {
            if (sibling !== parentItem) {
                const siblingSubmenu = sibling.querySelector('.submenu');
                if (siblingSubmenu) {
                    siblingSubmenu.style.display = 'none';
                    sibling.classList.remove('open');
                }
            }
        });
        
        // Alternar submenú actual
        if (isCurrentlyOpen) {
            submenu.style.display = 'none';
            parentItem.classList.remove('open');
        } else {
            submenu.style.display = 'block';
            parentItem.classList.add('open');
        }
    }
    
    closeAllSubmenus() {
        const submenus = this.sidebar.querySelectorAll('.submenu');
        const parentItems = this.sidebar.querySelectorAll('.has-children');
        
        submenus.forEach(submenu => {
            submenu.style.display = 'none';
        });
        
        parentItems.forEach(item => {
            item.classList.remove('open');
        });
        
        console.log('✅ Todos los submenús cerrados');
    }
    
    handleViewportChange(isMobile) {
        this.isMobile = isMobile;
        console.log('🔄 Off-Canvas cambió a modo:', this.isMobile ? 'Móvil' : 'Escritorio');
        
        // Si cambió a escritorio y el sidebar está abierto, cerrarlo
        if (!this.isMobile && this.isOpen) {
            this.close();
        }
        
        this.applyBodyCorrections();
    }
}

// ==================================================
// INICIALIZACIÓN GLOBAL MEJORADA
// ==================================================

// Inicialización automática del sistema principal
document.addEventListener('DOMContentLoaded', () => {
    // Pequeño delay para asegurar que Bootstrap esté cargado
    setTimeout(() => {
        window.gedSystem = new GEDSystem();
        console.log('🚀 Sistema GED completamente inicializado');
    }, 100);
});

// Manejo de resize global
window.addEventListener('resize', () => {
    if (window.gedSystem) {
        window.gedSystem.handleResize();
    }
});

// Debug helper
function debugGEDSystem() {
    console.group('🐛 DEBUG GED SYSTEM - MENÚ REAL');
    console.log('GED System:', window.gedSystem);
    console.log('OffCanvas Sidebar:', window.gedSystem?.offCanvasSidebar);
    console.log('Modo móvil:', window.gedSystem?.isMobile);
    console.log('Sidebar abierto:', window.gedSystem?.offCanvasSidebar?.isOpen);
    console.log('Toggler encontrado:', !!document.querySelector('.navbar-toggler'));
    console.log('Menú real encontrado:', !!document.querySelector('.navbar-nav'));
    console.log('Menú en sidebar:', document.querySelector('.ged-offcanvas-sidebar .sidebar-nav')?.children.length || 0, 'elementos');
    console.groupEnd();
}

// Exponer para debugging
window.debugGEDSystem = debugGEDSystem;

// Auto-debug en desarrollo
if (window.location.href.indexOf('localhost') > -1 || window.location.href.indexOf('debug') > -1) {
    setTimeout(debugGEDSystem, 2000);
}

// Función para forzar recarga del menú (útil para desarrollo)
window.reloadOffCanvasMenu = function() {
    if (window.gedSystem && window.gedSystem.offCanvasSidebar) {
        window.gedSystem.offCanvasSidebar.loadRealMenu();
        console.log('🔄 Menú del off-canvas recargado manualmente');
    }
};