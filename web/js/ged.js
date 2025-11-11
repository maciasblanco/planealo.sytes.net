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