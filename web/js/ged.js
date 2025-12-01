// js/ged.js - Sistema GED - JavaScript ACTUALIZADO para Navbar sin Carrusel
// Versión optimizada con nuevos porcentajes: Logo 15%, Menú 50%, Redes 15%, Control 20%
// MEJORAS: Menú móvil completamente funcional

class GEDSystem {
    constructor() {
        this.isMobile = this.checkIsMobile();
        this.menuOpen = false;
        this.navbarHeight = this.getNavbarHeight();
        this.init();
    }
    
    checkIsMobile() {
        return window.innerWidth < 992;
    }
    
    getNavbarHeight() {
        const navbar = document.querySelector('.navbar-contextual');
        if (!navbar) return this.isMobile ? 70 : 180;
        
        // Calcular altura basada en viewport y modo
        if (this.isMobile) {
            if (window.innerWidth < 576) return 55;
            if (window.innerWidth < 768) return 60;
            return 70;
        } else {
            // ✅ ALTURA REDUCIDA PARA NUEVO NAVBAR SIN CARRUSEL
            return window.innerHeight * 0.25; // 25vh en lugar de 30vh
        }
    }
    
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        console.log('🚀 Sistema GED inicializado - Modo:', this.isMobile ? 'Móvil' : 'Escritorio');
        
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
        
        // Forzar recálculo después de la carga completa
        setTimeout(() => {
            this.forceNavbarRecalculation();
            this.applyBodyCorrections();
        }, 500);
    }
    
    // ===== CORRECCIONES DE BODY Y LAYOUT =====
    applyBodyCorrections() {
        console.log('🔧 Aplicando correcciones de body y layout...');
        
        this.navbarHeight = this.getNavbarHeight();
        
        // ✅ PADDING REDUCIDO PARA NUEVO NAVBAR (25vh)
        document.body.style.paddingTop = this.navbarHeight + 'px';
        
        // Corregir main content
        const mainElements = document.querySelectorAll('main#main');
        mainElements.forEach(main => {
            main.style.marginTop = '0';
            main.style.minHeight = `calc(100vh - ${this.navbarHeight}px)`;
        });
        
        // Corregir contenedores principales
        const mainContainers = document.querySelectorAll('.main-container');
        mainContainers.forEach(container => {
            container.style.marginTop = '0';
            container.style.minHeight = `calc(100vh - ${this.navbarHeight}px)`;
        });
        
        console.log('✅ Correcciones aplicadas - Navbar height:', this.navbarHeight);
    }
    
    forceNavbarRecalculation() {
        const navbar = document.querySelector('.navbar-contextual');
        if (navbar) {
            // Forzar reflow para recalcular dimensiones
            navbar.style.display = 'none';
            void navbar.offsetHeight; // Trigger reflow
            navbar.style.display = '';
            
            console.log('🔄 Navbar recalculation forzado');
        }
    }
    
    // ===== OFF-CANVAS SIDEBAR =====
    initOffCanvasSidebar() {
        this.offCanvasSidebar = new OffCanvasSidebar();
    }
    
    // ===== NAVBAR FIXED - ACTUALIZADO SIN CARRUSEL =====
    initNavbarFixed() {
        this.navbar = document.querySelector('.navbar-contextual');
        
        if (!this.navbar) {
            console.warn('❌ Navbar contextual no encontrado');
            return;
        }
        
        this.forceFullWidth();
        this.stabilizeNavbar();
        
        // ✅ INICIALIZAR SELECTOR DE ESCUELAS DEL NAVBAR
        this.initNavbarEscuelaSelector();
        
        console.log('✅ Navbar Fixed - Configurado correctamente (sin carrusel)');
    }
    
    stabilizeNavbar() {
        // Aplicar estilos críticos para estabilizar el navbar
        const criticalStyles = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1030 !important;
            width: 100% !important;
            transform: none !important;
        `;
        
        this.navbar.style.cssText += criticalStyles;
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
    
    // ✅ NUEVO MÉTODO PARA SELECTOR DE ESCUELAS EN NAVBAR
    initNavbarEscuelaSelector() {
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
    }
    
    // ✅ MÉTODO OPTIMIZECAROUSEL VACÍO - CARRUSEL ELIMINADO
    optimizeCarousel() {
        console.log('✅ Carrusel eliminado del navbar - No se requiere optimización');
    }
    
    // ===== COMPONENTS =====
    initComponents() {
        console.log('🔧 Components inicializado');
        
        // Solo tooltips básicos si son necesarios
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }
    
    // ===== SCHOOL SEARCH =====
    initSchoolSearch() {
        if (!document.querySelector('#schoolSearch') || typeof $ === 'undefined') return;
        
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
        
        if (searchInput.length === 0) return;
        
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
            
            console.log('✅ Escuela selector inicializado correctamente');
            
        } catch (error) {
            console.error('Error en escuela selector:', error);
        }
    }
    
    // ===== LANDING PAGE =====
    initLandingPage() {
        if (typeof $ === 'undefined') return;
        
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

        // Carrusel automático (solo para landing page)
        if (typeof bootstrap !== 'undefined') {
            $('#carouselHero').carousel({
                interval: 3000,
                pause: 'hover'
            });
        }

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
    
    // ===== MANEJO DE RESIZE - ACTUALIZADO SIN CARRUSEL =====
    handleResize() {
        const newIsMobile = this.checkIsMobile();
        const oldNavbarHeight = this.navbarHeight;
        
        if (newIsMobile !== this.isMobile) {
            this.isMobile = newIsMobile;
            console.log('🔄 Cambio de modo:', this.isMobile ? 'Móvil' : 'Escritorio');
            
            // Reinicializar off-canvas si cambió el modo
            if (this.offCanvasSidebar) {
                this.offCanvasSidebar.handleViewportChange(this.isMobile);
            }
        }
        
        // Recalcular altura del navbar
        this.navbarHeight = this.getNavbarHeight();
        
        // Solo aplicar correcciones si cambió la altura
        if (this.navbarHeight !== oldNavbarHeight) {
            setTimeout(() => {
                this.forceFullWidth();
                this.applyBodyCorrections();
                this.forceNavbarRecalculation();
            }, 100);
        }
    }
}

// ==================================================
// OFF-CANVAS SIDEBAR - CON MENÚ MÓVIL MEJORADO
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
            
            // ✅ CARGAR MENÚ MÓVIL ESPECÍFICO
            this.loadMobileMenu();
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
        
        // ✅ CARGAR MENÚ MÓVIL ESPECÍFICO
        this.loadMobileMenu();
    }
    
    // ✅ NUEVO MÉTODO MEJORADO PARA CARGAR MENÚ MÓVIL
    loadMobileMenu() {
        console.log('📱 Cargando menú específico para móvil...');
        
        // Mostrar loading
        this.sidebarNav.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando menú...</span>
                </div>
                <p class="text-muted mt-2">Cargando menú...</p>
            </div>
        `;
        
        // Intentar cargar el menú móvil via AJAX
        if (typeof $ !== 'undefined') {
            this.loadMobileMenuViaAJAX();
        } else {
            // Fallback: cargar menú desde el navbar existente
            setTimeout(() => {
                this.loadRealMenu();
            }, 100);
        }
    }
    
    // ✅ CARGAR MENÚ MÓVIL VIA AJAX
    loadMobileMenuViaAJAX() {
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
            },
            error: (xhr, status, error) => {
                console.error('❌ Error cargando menú móvil via AJAX:', error);
                console.log('🔄 Intentando cargar menú desde navbar...');
                this.loadRealMenu();
            }
        });
    }
    
    // ✅ Cargar el menú real desde el navbar (fallback)
    loadRealMenu() {
        console.log('🔄 Cargando menú real desde navbar...');
        
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
    
    // ✅ Menú de respaldo si no se encuentra el real
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
        
        // Adaptar el menú de respaldo
        this.adaptMenuForOffCanvas(this.sidebarNav);
        console.log('✅ Menú de respaldo cargado');
    }
    
    // ✅ ADAPTAR MENÚ PARA OFF-CANVAS - MEJORADO
    adaptMenuForOffCanvas(menuElement) {
        console.log('🎨 Adaptando menú para off-canvas...');
        
        // Buscar el menú principal
        let mainMenu = menuElement.querySelector('.navbar-nav, .sidebar-menu');
        if (!mainMenu) {
            console.warn('❌ No se encontró el menú principal para adaptar');
            return;
        }
        
        // Convertir a estructura móvil si es necesario
        if (mainMenu.classList.contains('navbar-nav')) {
            this.convertBootstrapToMobileMenu(mainMenu);
        }
        
        // Agregar eventos para submenús colapsables
        this.addMobileMenuEvents(menuElement);
        
        console.log('✅ Menú adaptado correctamente para móvil');
    }
    
    // ✅ CONVERTIR MENÚ BOOTSTRAP A ESTRUCTURA MÓVIL
    convertBootstrapToMobileMenu(menuElement) {
        console.log('🔄 Convirtiendo menú Bootstrap a estructura móvil...');
        
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
            if (link.getAttribute('href') === '#' && link.parentElement.classList.contains('has-children')) {
                link.style.cursor = 'pointer';
            }
        });
        
        // Cambiar la clase principal a sidebar-menu
        menuElement.classList.remove('navbar-nav');
        menuElement.classList.add('sidebar-menu');
    }
    
    // ✅ AGREGAR EVENTOS PARA MENÚ MÓVIL
    addMobileMenuEvents(menuElement) {
        const menuItems = menuElement.querySelectorAll('.has-children > .menu-link');
        
        menuItems.forEach(menuItem => {
            // Remover eventos existentes para evitar duplicados
            menuItem.replaceWith(menuItem.cloneNode(true));
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
        
        // Agregar eventos para enlaces normales (cerrar sidebar)
        const normalLinks = menuElement.querySelectorAll('.menu-item:not(.has-children) > .menu-link');
        normalLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (this.isMobile) {
                    setTimeout(() => this.close(), 300);
                }
            });
        });
    }
    
    // ✅ ALTERNAR SUBMENÚ
    toggleSubmenu(parentItem) {
        const submenu = parentItem.querySelector('.submenu');
        if (!submenu) return;
        
        const isCurrentlyOpen = submenu.style.display === 'block';
        const indicator = parentItem.querySelector('.submenu-indicator');
        
        console.log(`🔄 ${isCurrentlyOpen ? 'Cerrando' : 'Abriendo'} submenú...`);
        
        // Cerrar todos los submenús del mismo nivel
        const siblings = parentItem.parentElement.querySelectorAll('.has-children');
        siblings.forEach(sibling => {
            if (sibling !== parentItem) {
                const siblingSubmenu = sibling.querySelector('.submenu');
                const siblingIndicator = sibling.querySelector('.submenu-indicator');
                if (siblingSubmenu) {
                    siblingSubmenu.style.display = 'none';
                }
                if (siblingIndicator) {
                    siblingIndicator.style.transform = 'rotate(0deg)';
                }
                sibling.classList.remove('open');
            }
        });
        
        // Alternar submenú actual
        if (isCurrentlyOpen) {
            submenu.style.display = 'none';
            if (indicator) {
                indicator.style.transform = 'rotate(0deg)';
            }
            parentItem.classList.remove('open');
        } else {
            submenu.style.display = 'block';
            if (indicator) {
                indicator.style.transform = 'rotate(90deg)';
            }
            parentItem.classList.add('open');
        }
    }
    
    bindEvents() {
        // Interceptar el toggler de Bootstrap para móviles
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
    
    closeAllSubmenus() {
        const submenus = this.sidebar.querySelectorAll('.submenu');
        const parentItems = this.sidebar.querySelectorAll('.has-children');
        
        submenus.forEach(submenu => {
            submenu.style.display = 'none';
        });
        
        parentItems.forEach(item => {
            item.classList.remove('open');
            const indicator = item.querySelector('.submenu-indicator');
            if (indicator) {
                indicator.style.transform = 'rotate(0deg)';
            }
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
    }
}

// ==================================================
// INICIALIZACIÓN GLOBAL MEJORADA
// ==================================================

// Inicialización automática del sistema principal
document.addEventListener('DOMContentLoaded', () => {
    // Pequeño delay para asegurar que Bootstrap esté cargado
    setTimeout(() => {
        if (!window.gedSystem) {
            window.gedSystem = new GEDSystem();
            console.log('🚀 Sistema GED completamente inicializado y estable');
        }
    }, 100);
});

// Manejo de resize global mejorado
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        if (window.gedSystem) {
            window.gedSystem.handleResize();
        }
    }, 250);
});

// Debug helper mejorado
function debugGEDSystem() {
    console.group('🐛 DEBUG GED SYSTEM - ESTADO COMPLETO');
    console.log('GED System:', window.gedSystem);
    console.log('Navbar Height:', window.gedSystem?.navbarHeight);
    console.log('Modo móvil:', window.gedSystem?.isMobile);
    console.log('OffCanvas Sidebar:', window.gedSystem?.offCanvasSidebar);
    console.log('Sidebar abierto:', window.gedSystem?.offCanvasSidebar?.isOpen);
    console.log('Toggler encontrado:', !!document.querySelector('.navbar-toggler'));
    console.log('Menú real encontrado:', !!document.querySelector('.navbar-nav'));
    console.log('Body padding-top:', document.body.style.paddingTop);
    
    const main = document.querySelector('main#main');
    console.log('Main min-height:', main?.style.minHeight);
    
    console.groupEnd();
}

// Exponer para debugging
window.debugGEDSystem = debugGEDSystem;

// Función para forzar recarga del menú (útil para desarrollo)
window.reloadOffCanvasMenu = function() {
    if (window.gedSystem && window.gedSystem.offCanvasSidebar) {
        window.gedSystem.offCanvasSidebar.loadMobileMenu();
        console.log('🔄 Menú del off-canvas recargado manualmente');
    }
};

// Función para forzar recálculo del navbar
window.forceNavbarRecalculation = function() {
    if (window.gedSystem) {
        window.gedSystem.forceNavbarRecalculation();
        window.gedSystem.applyBodyCorrections();
    }
};

// Auto-debug en desarrollo
if (window.location.href.indexOf('localhost') > -1 || window.location.href.indexOf('debug') > -1) {
    setTimeout(() => {
        debugGEDSystem();
        console.log('🔧 Modo desarrollo activo - Debug functions disponibles');
    }, 2000);
}

///////////////////////////////////////////////////////////////////////////////////////////
/////////// Para uso del Index Landpage - MEJORADO CON PRODUCTOS MÁS VENDIDOS /////////////
///////////////////////////////////////////////////////////////////////////////////////////

class LandingPageManager {
    constructor() {
        this.productos = {
            vestimenta: [],
            alimentacion: [],
            'implementos-deportivos': [],
            suplementos: []
        };
        this.init();
    }
    
    init() {
        console.log('🚀 Landing Page Manager inicializado');
        
        // Verificar si estamos en la landing page
        if (!document.querySelector('.landing-page')) return;
        
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        this.bindEvents();
        this.initAnimations();
        this.initProductosMasVendidos(); // ✅ Inicializar productos
        
        // Si hay un usuario autenticado, agregar funcionalidad extra
        const accederBtn = document.getElementById('btn-acceder-sistema');
        if (accederBtn) {
            this.enhanceAccederButton(accederBtn);
        }
        
        // Marketplace button enhancement
        const marketplaceBtn = document.getElementById('btn-marketplace');
        if (marketplaceBtn) {
            this.enhanceMarketplaceButton(marketplaceBtn);
        }
        
        // Logo animation
        const logo = document.getElementById('ged-main-logo');
        if (logo) {
            this.addLogoAnimation(logo);
        }
        
        console.log('✅ Landing Page completamente configurada');
    }
    
    // ✅ MÉTODO: Cargar productos (puedes reemplazar con datos reales de tu API)
    initProductosMasVendidos() {
        console.log('🛍️ Inicializando productos más vendidos...');
        
        // Cargar productos desde API o datos estáticos
        this.cargarProductos()
            .then(() => {
                // Renderizar productos en cada categoría
                this.renderizarProductosPorCategoria('vestimenta');
                this.renderizarProductosPorCategoria('alimentacion');
                this.renderizarProductosPorCategoria('implementos-deportivos');
                this.renderizarProductosPorCategoria('suplementos');
                
                // Actualizar total
                this.actualizarTotalProductos();
                
                // Inicializar interactividad
                this.initProductosInteractividad();
            })
            .catch(error => console.error('Error cargando productos:', error));
    }
    
    cargarProductos() {
        return new Promise((resolve) => {
            // Simulación de datos - en producción deberías usar fetch() a tu API
            this.productos = {
                vestimenta: [
                    { id: 1, nombre: 'Camiseta Deportiva', precio: 25, imagen: '/img/productos/camiseta.jpg', vendidos: 150 },
                    { id: 2, nombre: 'Pantalón Deportivo', precio: 35, imagen: '/img/productos/pantalon.jpg', vendidos: 120 },
                    { id: 3, nombre: 'Sudadera con Capucha', precio: 45, imagen: '/img/productos/sudadera.jpg', vendidos: 95 }
                ],
                alimentacion: [
                    { id: 4, nombre: 'Barra Energética', precio: 3, imagen: '/img/productos/barra.jpg', vendidos: 200 },
                    { id: 5, nombre: 'Bebida Isotónica', precio: 2, imagen: '/img/productos/bebida.jpg', vendidos: 180 },
                    { id: 6, nombre: 'Snack Proteico', precio: 4, imagen: '/img/productos/snack.jpg', vendidos: 150 }
                ],
                'implementos-deportivos': [
                    { id: 7, nombre: 'Balón de Fútbol', precio: 20, imagen: '/img/productos/balon.jpg', vendidos: 80 },
                    { id: 8, nombre: 'Cuerda para Saltar', precio: 10, imagen: '/img/productos/cuerda.jpg', vendidos: 75 },
                    { id: 9, nombre: 'Banda Elástica', precio: 15, imagen: '/img/productos/banda.jpg', vendidos: 90 }
                ],
                suplementos: [
                    { id: 10, nombre: 'Proteína en Polvo', precio: 50, imagen: '/img/productos/proteina.jpg', vendidos: 110 },
                    { id: 11, nombre: 'Multivitamínico', precio: 15, imagen: '/img/productos/vitaminas.jpg', vendidos: 85 },
                    { id: 12, nombre: 'Creatina', precio: 30, imagen: '/img/productos/creatina.jpg', vendidos: 70 }
                ]
            };
            
            console.log('✅ Productos cargados:', this.productos);
            resolve();
        });
    }
    
    // ✅ MÉTODO: Renderizar productos por categoría
    renderizarProductosPorCategoria(categoria) {
        const container = document.getElementById(`productos-${categoria}`);
        if (!container || !this.productos[categoria]) return;
        
        const productos = this.productos[categoria];
        
        // Ordenar por más vendidos
        productos.sort((a, b) => b.vendidos - a.vendidos);
        
        let productosHTML = '';
        
        productos.forEach((producto, index) => {
            const badgeClass = index === 0 ? 'badge-top' : 'badge-normal';
            
            productosHTML += `
                <div class="producto-item" data-id="${producto.id}">
                    <div class="producto-header">
                        <div class="producto-badge ${badgeClass}">
                            <i class="bi bi-fire"></i>
                            <span>${producto.vendidos} vendidos</span>
                        </div>
                        <div class="producto-imagen-placeholder">
                            ${producto.imagen ? 
                                `<img src="${producto.imagen}" alt="${producto.nombre}" class="img-fluid" 
                                      onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\\'bi bi-box-seam\\'></i>';">` :
                                `<i class="bi bi-box-seam"></i>`
                            }
                        </div>
                    </div>
                    <div class="producto-info">
                        <h5 class="producto-nombre">${producto.nombre}</h5>
                        <div class="producto-precio">
                            <span class="precio-actual">$${producto.precio}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary btn-agregar-carrito" 
                                data-id="${producto.id}"
                                data-nombre="${producto.nombre}"
                                data-precio="${producto.precio}">
                            <i class="bi bi-cart-plus"></i> Agregar
                        </button>
                    </div>
                </div>
            `;
        });
        
        // Agregar botón "Ver más"
        productosHTML += `
            <div class="text-center mt-3">
                <button class="btn btn-link btn-ver-mas" data-categoria="${categoria}">
                    <i class="bi bi-arrow-right-circle"></i> Ver todos los productos
                </button>
            </div>
        `;
        
        container.innerHTML = productosHTML;
    }
    
    // ✅ MÉTODO: Actualizar total de productos
    actualizarTotalProductos() {
        const totalElement = document.getElementById('total-productos');
        if (!totalElement) return;
        
        let total = 0;
        Object.values(this.productos).forEach(categoria => {
            categoria.forEach(producto => {
                total += producto.vendidos;
            });
        });
        
        totalElement.textContent = total.toLocaleString();
    }
    
    // ✅ MÉTODO: Inicializar interactividad de productos
    initProductosInteractividad() {
        // Evento para botones "Agregar al carrito"
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-agregar-carrito')) {
                const button = e.target.closest('.btn-agregar-carrito');
                const productoId = button.getAttribute('data-id');
                const productoNombre = button.getAttribute('data-nombre');
                const productoPrecio = button.getAttribute('data-precio');
                
                this.agregarAlCarrito({
                    id: productoId,
                    nombre: productoNombre,
                    precio: productoPrecio
                });
            }
            
            // Evento para botones "Ver más"
            if (e.target.closest('.btn-ver-mas')) {
                const button = e.target.closest('.btn-ver-mas');
                const categoria = button.getAttribute('data-categoria');
                this.mostrarModalCategoria(categoria);
            }
        });
        
        // Efecto hover en productos
        document.querySelectorAll('.producto-item').forEach(item => {
            item.addEventListener('mouseenter', () => {
                item.classList.add('producto-hover');
            });
            
            item.addEventListener('mouseleave', () => {
                item.classList.remove('producto-hover');
            });
        });
        
        // Cargar carrito existente
        this.cargarCarritoExistente();
    }
    
    // ✅ MÉTODO: Agregar al carrito
    agregarAlCarrito(producto) {
        console.log('🛒 Agregando producto al carrito:', producto);
        
        // Mostrar notificación
        this.mostrarNotificacion(`"${producto.nombre}" agregado al carrito`, 'success');
        
        // Animación de producto al carrito
        this.animarAgregarAlCarrito(producto.id);
        
        // Guardar en carrito local
        this.guardarEnCarritoLocal(producto);
        
        // Si existe Google Analytics, registrar evento
        if (typeof gtag !== 'undefined') {
            gtag('event', 'add_to_cart', {
                'event_category': 'productos',
                'event_label': producto.nombre,
                'value': parseFloat(producto.precio)
            });
        }
    }
    
    // ✅ MÉTODO: Animación al agregar al carrito
    animarAgregarAlCarrito(productoId) {
        const productoElement = document.querySelector(`.producto-item[data-id="${productoId}"]`);
        if (!productoElement) return;
        
        // Buscar icono del carrito en el navbar
        const iconoCarrito = document.querySelector('.navbar .bi-cart, .navbar .bi-cart3');
        if (!iconoCarrito) return;
        
        // Crear elemento flotante
        const flotante = document.createElement('div');
        flotante.className = 'producto-flotante';
        flotante.innerHTML = '<i class="bi bi-cart-check-fill"></i>';
        flotante.style.position = 'fixed';
        flotante.style.zIndex = '9999';
        
        // Posición inicial (desde el producto)
        const productoRect = productoElement.getBoundingClientRect();
        flotante.style.left = `${productoRect.left + productoRect.width / 2}px`;
        flotante.style.top = `${productoRect.top}px`;
        
        document.body.appendChild(flotante);
        
        // Posición final (hacia el carrito)
        const carritoRect = iconoCarrito.getBoundingClientRect();
        const finalX = carritoRect.left + carritoRect.width / 2;
        const finalY = carritoRect.top + carritoRect.height / 2;
        
        // Animación
        flotante.animate([
            { 
                transform: 'translate(0, 0) scale(1)', 
                opacity: 1 
            },
            { 
                transform: `translate(${finalX - productoRect.left - productoRect.width / 2}px, 
                                     ${finalY - productoRect.top}px) scale(0.5)`, 
                opacity: 0 
            }
        ], {
            duration: 800,
            easing: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)'
        });
        
        // Eliminar después de la animación
        setTimeout(() => {
            flotante.remove();
        }, 800);
    }
    
    // ✅ MÉTODO: Guardar en carrito local (sessionStorage)
    guardarEnCarritoLocal(producto) {
        try {
            let carrito = JSON.parse(sessionStorage.getItem('ged-carrito') || '[]');
            
            // Verificar si el producto ya está en el carrito
            const productoExistente = carrito.find(item => item.id == producto.id);
            
            if (productoExistente) {
                productoExistente.cantidad += 1;
            } else {
                carrito.push({
                    id: producto.id,
                    nombre: producto.nombre,
                    precio: producto.precio,
                    cantidad: 1
                });
            }
            
            sessionStorage.setItem('ged-carrito', JSON.stringify(carrito));
            
            // Actualizar contador del carrito
            this.actualizarContadorCarrito(carrito.length);
            
            console.log('✅ Producto guardado en carrito:', producto);
        } catch (error) {
            console.error('Error guardando en carrito:', error);
        }
    }
    
    // ✅ MÉTODO: Actualizar contador del carrito
    actualizarContadorCarrito(cantidad) {
        // Buscar o crear contador del carrito
        let contador = document.querySelector('.carrito-contador');
        const iconoCarrito = document.querySelector('.navbar .bi-cart, .navbar .bi-cart3');
        
        if (!contador && iconoCarrito) {
            // Crear contador si no existe
            contador = document.createElement('span');
            contador.className = 'carrito-contador';
            iconoCarrito.parentElement.appendChild(contador);
        }
        
        if (contador) {
            if (cantidad > 0) {
                contador.textContent = cantidad > 9 ? '9+' : cantidad.toString();
                contador.style.display = 'flex';
            } else {
                contador.style.display = 'none';
            }
        }
    }
    
    // ✅ MÉTODO: Mostrar notificación
    mostrarNotificacion(mensaje, tipo = 'info') {
        const tipos = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'info': 'alert-info',
            'warning': 'alert-warning'
        };
        
        const alertClass = tipos[tipo] || 'alert-info';
        
        // Eliminar notificaciones anteriores
        document.querySelectorAll('.ged-notificacion').forEach(el => el.remove());
        
        const notificacion = document.createElement('div');
        notificacion.className = `alert ${alertClass} alert-dismissible fade show ged-notificacion`;
        notificacion.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Agregar al DOM
        document.body.appendChild(notificacion);
        
        // Mostrar con animación
        setTimeout(() => {
            notificacion.classList.add('show');
        }, 10);
        
        // Auto-eliminar después de 3 segundos
        setTimeout(() => {
            notificacion.classList.remove('show');
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 3000);
    }
    
    // ✅ MÉTODO: Cargar carrito existente
    cargarCarritoExistente() {
        try {
            const carrito = JSON.parse(sessionStorage.getItem('ged-carrito') || '[]');
            this.actualizarContadorCarrito(carrito.length);
        } catch (error) {
            console.error('Error cargando carrito:', error);
        }
    }
    
    // ✅ MÉTODO: Mostrar modal de categoría
    mostrarModalCategoria(categoriaNombre) {
        console.log('📋 Mostrando modal para:', categoriaNombre);
        
        // Aquí podrías implementar un modal con todos los productos de la categoría
        // Por ahora, solo redirigir al marketplace
        const nombreFormateado = categoriaNombre.replace('-', ' ');
        this.mostrarNotificacion(`Redirigiendo a productos de ${nombreFormateado}...`, 'info');
        
        // Redirigir al marketplace con filtro de categoría
        setTimeout(() => {
            window.location.href = '/tienda/marketplace/index?categoria=' + categoriaNombre;
        }, 1500);
    }
    
    // Métodos existentes (mantenerlos igual)
    bindEvents() {
        // Smooth scroll para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId && targetId !== '#') {
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
        
        // Efecto hover mejorado para cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }
    
    initAnimations() {
        // Intersection Observer para animaciones al hacer scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    
                    // Animación específica para feature cards
                    if (entry.target.classList.contains('feature-card')) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, 100);
                    }
                    
                    // Animación para productos más vendidos
                    if (entry.target.classList.contains('categoria-card')) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, 200);
                    }
                }
            });
        }, observerOptions);
        
        // Observar elementos que queremos animar
        document.querySelectorAll('.feature-card, .categoria-card').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(element);
        });
    }
    
    enhanceAccederButton(button) {
        // Agregar funcionalidad especial al botón "Acceder al Sistema"
        button.addEventListener('click', (e) => {
            console.log('🔐 Accediendo al sistema de forma segura...');
            
            // Agregar efecto de carga
            const originalText = button.innerHTML;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Accediendo...
            `;
            button.disabled = true;
            
            // Simular tiempo de carga (en producción esto no sería necesario)
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 1500);
            
            // Registrar acceso en analytics (si está configurado)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'acceder_sistema', {
                    'event_category': 'landing_page',
                    'event_label': 'boton_acceder'
                });
            }
        });
        
        // Efecto hover especial
        button.addEventListener('mouseenter', () => {
            button.style.boxShadow = '0 15px 30px rgba(40, 167, 69, 0.3)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.boxShadow = '';
        });
    }
    
    enhanceMarketplaceButton(button) {
        // Agregar funcionalidad especial al botón "Marketplace"
        button.addEventListener('click', (e) => {
            console.log('🛒 Redirigiendo al Marketplace...');
            
            // Efecto visual
            button.classList.add('pulse-animation');
            
            // Registrar acceso a marketplace
            if (typeof gtag !== 'undefined') {
                gtag('event', 'acceder_marketplace', {
                    'event_category': 'landing_page',
                    'event_label': 'boton_marketplace'
                });
            }
            
            setTimeout(() => {
                button.classList.remove('pulse-animation');
            }, 500);
        });
        
        // Efecto hover especial para marketplace
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'scale(1.05) rotate(2deg)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.transform = '';
        });
    }
    
    addLogoAnimation(logo) {
        // Animación sutil para el logo
        logo.addEventListener('mouseenter', () => {
            logo.style.transform = 'scale(1.1) rotate(5deg)';
            logo.style.filter = 'drop-shadow(0 8px 16px rgba(0,0,0,0.4))';
        });
        
        logo.addEventListener('mouseleave', () => {
            logo.style.transform = '';
            logo.style.filter = 'drop-shadow(0 4px 8px rgba(0,0,0,0.3))';
        });
        
        // Animación inicial al cargar
        setTimeout(() => {
            logo.style.transition = 'transform 0.5s ease, filter 0.5s ease';
        }, 100);
    }
}

// Inicializar Landing Page Manager automáticamente
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si estamos en la landing page
    const isLandingPage = document.querySelector('.landing-page');
    
    if (isLandingPage) {
        // Inicializar el gestor de landing page
        window.landingPageManager = new LandingPageManager();
        
        console.log('🌐 Landing Page Manager activado con productos más vendidos');
    }
});

// Función para debug de landing page mejorada
function debugLandingPage() {
    console.group('🐛 DEBUG LANDING PAGE - CON PRODUCTOS');
    console.log('Landing Page Manager:', window.landingPageManager);
    console.log('Productos cargados:', window.landingPageManager?.productos);
    console.log('Elementos interactivos encontrados:');
    console.log('- Botón Acceder:', document.getElementById('btn-acceder-sistema'));
    console.log('- Botón Marketplace:', document.getElementById('btn-marketplace'));
    console.log('- Logo principal:', document.getElementById('ged-main-logo'));
    console.log('- Sección productos:', document.getElementById('productos-mas-vendidos'));
    console.log('- Cards de productos:', document.querySelectorAll('.producto-item').length);
    console.log('- Carrito en sesión:', sessionStorage.getItem('ged-carrito'));
    console.groupEnd();
}

// Exponer para debugging
window.debugLandingPage = debugLandingPage;

// Función auxiliar para limpiar carrito (útil para desarrollo)
window.limpiarCarrito = function() {
    sessionStorage.removeItem('ged-carrito');
    if (window.landingPageManager) {
        window.landingPageManager.actualizarContadorCarrito(0);
    }
    console.log('🧹 Carrito limpiado');
};