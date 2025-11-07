// js/ged.js - Sistema GED - JavaScript Unificado
// Fusiona: navbar-fixed, menu-mobile, components, school-search, escuela-selector, landingPageGed, navbar-menu

class GEDSystem {
    constructor() {
        this.isMobile = window.innerWidth < 992;
        this.menuOpen = false;
        this.init();
    }
    
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        console.log('Sistema GED inicializado - Menú estable');
        
        // Inicializar todos los módulos
        this.initNavbarFixed();
        this.initMenuMobile();
        this.initComponents();
        this.initSchoolSearch();
        this.initNavbarMenu();
        this.initEscuelaSelector();
        this.initLandingPage();
        
        // Aplicar solución z-index inmediatamente
        this.applyZIndexSolution();
        this.applyLayoutCorrections(); // ← NUEVA LINEA
        
        // Manejar cambios de tamaño
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }
    
    // ===== VERIFICACIÓN Y CORRECCIÓN AUTOMÁTICA =====
    applyLayoutCorrections() {
        console.log('🔧 Aplicando correcciones de layout...');
        
        // 1. Corregir body padding
        document.body.style.paddingTop = '30vh';
        
        // 2. Forzar visibilidad de menús
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(dropdown => {
            dropdown.style.cssText += `
                z-index: 100000 !important;
                position: absolute !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            `;
        });
        
        // 3. Eliminar overflow conflictivo
        const containers = document.querySelectorAll(`
            .navbar-container, .navbar-menu-section, .navbar-nav,
            .nav-item, .dropdown, .container-fluid, .container
        `);
        
        containers.forEach(container => {
            container.style.overflow = 'visible';
        });
        
        // 4. Corregir main content
        const main = document.querySelector('main#main');
        if (main) {
            main.style.marginTop = '0';
            main.style.minHeight = 'calc(100vh - 30vh)';
        }
        
        console.log('✅ Correcciones de layout aplicadas');
    }
    
    // ===== SOLUCIÓN Z-INDEX DINÁMICA =====
    applyZIndexSolution() {
        console.log('🎯 Aplicando solución z-index para menús');
        
        // Aplicar z-index a contenedores principales
        const containers = [
            '.navbar-menu-container',
            '.navbar-menu-section', 
            '.navbar-nav'
        ];
        
        containers.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.style.cssText += `
                    z-index: 99999 !important;
                    position: relative !important;
                    overflow: visible !important;
                `;
            });
        });

        // Aplicar z-index escalonado a dropdowns
        const dropdowns = document.querySelectorAll('.navbar-menu-container .dropdown-menu');
        dropdowns.forEach((dropdown, index) => {
            const zIndex = 100000 + (index * 10);
            dropdown.style.cssText += `
                z-index: ${zIndex} !important;
                position: absolute !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                overflow: visible !important;
                clip-path: none !important;
            `;
        });

        this.preventDropdownInterference();
    }
    
    preventDropdownInterference() {
        console.log('🛡️ Previniendo interferencia de z-index');
        
        const potentialInterferers = document.querySelectorAll(`
            .navbar-contextual, .navbar-main, header, .header,
            .container, .container-fluid, .row, .col, 
            [style*="position"], [style*="z-index"],
            .card, .modal, .popover, .tooltip
        `);
        
        potentialInterferers.forEach(element => {
            const style = window.getComputedStyle(element);
            const position = style.position;
            const zIndex = parseInt(style.zIndex) || 0;
            
            // REDUCIR Z-INDEX DE ELEMENTOS QUE PODRÍAN CUBRIR MENÚS
            if (position !== 'static' && zIndex > 1000) {
                if (zIndex >= 99999) {
                    element.style.zIndex = '1000';
                    console.log(`🔧 Ajustado z-index de ${element.tagName} a 1000`);
                }
            }
            
            // ASEGURAR QUE NO TIENEN OVERFLOW HIDDEN
            if (style.overflow === 'hidden') {
                element.style.overflow = 'visible';
            }
        });
        
        // ELIMINAR CUALQUIER CLIP-PATH QUE OCULTE CONTENIDO
        document.querySelectorAll('*').forEach(el => {
            const style = window.getComputedStyle(el);
            if (style.clipPath && style.clipPath !== 'none') {
                el.style.clipPath = 'none';
            }
        });
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
        this.removeAllScrollbars();
        this.forceLogoOnly();
        this.optimizeCarousel();
        this.optimizeMenuSpace();
        
        console.log('Navbar Fixed - Ancho 100% y sin barras de desplazamiento');
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
            '.container-fluid',
            '.navbar-menu-container',
            '.navbar-control-container',
            '.navbar-carousel-container'
        ];
        
        elementsToFullWidth.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.style.cssText += fullWidthStyle;
            });
        });
        
        document.body.style.width = '100%';
        document.body.style.maxWidth = '100%';
        document.documentElement.style.width = '100%';
        document.documentElement.style.maxWidth = '100%';
    }
    
    removeAllScrollbars() {
        const noScrollStyle = `
            overflow: hidden !important;
            overflow-x: hidden !important;
            overflow-y: hidden !important;
        `;
        
        const elementsToFix = [
            '.navbar-contextual',
            '.navbar-collapse',
            '.navbar-menu-container',
            '.navbar-control-container',
            '.navbar-carousel-container',
            '.navbar-nav',
            '.dropdown-menu'
        ];
        
        elementsToFix.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.style.cssText += noScrollStyle;
            });
        });
    }
    
    forceLogoOnly() {
        const brand = document.querySelector('.navbar-brand');
        if (brand) {
            brand.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '') {
                    node.remove();
                }
            });
            
            const children = brand.children;
            for (let i = 0; i < children.length; i++) {
                if (!children[i].classList.contains('navbar-brand-container')) {
                    children[i].style.display = 'none';
                }
            }
            
            brand.style.textIndent = '-9999px';
            brand.style.fontSize = '0';
            brand.style.lineHeight = '0';
            brand.style.color = 'transparent';
            brand.style.overflow = 'hidden';
        }
        
        const logo = document.querySelector('.navbar-logo');
        if (logo) {
            logo.style.visibility = 'visible';
            logo.style.opacity = '1';
            logo.style.display = 'block';
            
            if (logo.complete && logo.naturalHeight !== 0) {
                console.log('Logo cargado correctamente');
            } else {
                logo.src = '/img/logos/logoGed.png';
            }
        }
    }
    
    optimizeCarousel() {
        const carousel = document.getElementById('navbarCarousel');
        if (!carousel) return;
        
        const bootstrapCarousel = new bootstrap.Carousel(carousel, {
            interval: 5000,
            wrap: true,
            pause: 'hover'
        });
        
        carousel.style.overflow = 'hidden';
        this.setOptimalCarouselDimensions();
        this.preloadCarouselImages();
        
        window.addEventListener('resize', () => {
            setTimeout(() => this.setOptimalCarouselDimensions(), 100);
        });
        
        carousel.addEventListener('slid.bs.carousel', () => {
            setTimeout(() => this.setOptimalCarouselDimensions(), 50);
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
        carousel.style.overflow = 'hidden';
        
        const items = carousel.querySelectorAll('.carousel-item');
        items.forEach(item => {
            item.style.width = `${containerWidth}px`;
            item.style.height = `${Math.min(containerHeight * 0.95, containerHeight - 10)}px`;
            item.style.overflow = 'hidden';
        });
        
        const images = carousel.querySelectorAll('.carousel-image');
        images.forEach(img => {
            img.style.width = `${containerWidth}px`;
            img.style.height = `${Math.min(containerHeight * 0.95, containerHeight - 10)}px`;
            img.style.objectFit = 'cover';
            img.style.overflow = 'hidden';
        });
    }
    
    preloadCarouselImages() {
        const images = document.querySelectorAll('.carousel-image');
        images.forEach(img => {
            if (!img.complete) {
                img.onload = () => {
                    img.style.objectFit = 'cover';
                    img.style.objectPosition = 'center';
                    img.style.overflow = 'hidden';
                };
            }
            
            img.onerror = () => {
                console.warn('Error cargando imagen del carrusel');
                img.src = '/img/logos/logoGed.png';
                img.style.objectFit = 'contain';
                img.style.overflow = 'hidden';
            };
        });
    }
    
    optimizeMenuSpace() {
        const menuContainer = document.querySelector('.navbar-menu-container');
        const navItems = menuContainer?.querySelectorAll('.nav-item');
        
        if (!navItems || navItems.length === 0) return;
        
        const totalItems = navItems.length;
        console.log(`Optimizando espacio para ${totalItems} items del menú`);
        
        menuContainer.style.overflow = 'hidden';
        
        if (totalItems > 8) {
            navItems.forEach(item => {
                const link = item.querySelector('.nav-link');
                if (link) {
                    link.style.padding = '4px 8px';
                    link.style.fontSize = '0.8rem';
                }
            });
        }
        
        if (totalItems > 12) {
            navItems.forEach(item => {
                const link = item.querySelector('.nav-link');
                if (link) {
                    link.style.padding = '3px 6px';
                    link.style.fontSize = '0.75rem';
                }
            });
        }
        
        if (totalItems > 15) {
            navItems.forEach(item => {
                const link = item.querySelector('.nav-link');
                if (link) {
                    link.style.padding = '2px 4px';
                    link.style.fontSize = '0.7rem';
                }
            });
        }
        
        this.handleResponsiveWrap();
    }
    
    handleResponsiveWrap() {
        const menuContainer = document.querySelector('.navbar-menu-container');
        const nav = menuContainer?.querySelector('.nav, .navbar-nav');
        
        if (!nav) return;
        
        const checkWrap = () => {
            const width = window.innerWidth;
            
            if (width < 992) {
                nav.style.flexWrap = 'wrap';
                nav.style.justifyContent = 'center';
                nav.style.overflow = 'hidden';
            } else {
                nav.style.flexWrap = 'nowrap';
                nav.style.justifyContent = 'flex-start';
                nav.style.overflow = 'hidden';
            }
        };
        
        checkWrap();
        window.addEventListener('resize', checkWrap);
    }
    
    // ===== MENU MOBILE =====
    initMenuMobile() {
        if (window.innerWidth < 992) {
            this.setupMobileSubmenus();
        }
    }
    
    setupMobileSubmenus() {
        console.log('Configurando submenús para móviles');
        
        document.querySelectorAll('.dropdown-submenu > .dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', this.handleSubmenuClick.bind(this));
        });
    }
    
    handleSubmenuClick(e) {
        if (window.innerWidth < 992) {
            e.preventDefault();
            e.stopPropagation();
            
            const toggle = e.currentTarget;
            const submenu = toggle.nextElementSibling;
            
            if (submenu && submenu.classList.contains('dropdown-menu')) {
                this.closeSiblingSubmenus(toggle);
                
                if (submenu.classList.contains('show')) {
                    submenu.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                } else {
                    submenu.classList.add('show');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }
        }
    }
    
    closeSiblingSubmenus(currentToggle) {
        const parentItem = currentToggle.closest('.dropdown-submenu');
        if (parentItem && parentItem.parentElement) {
            const siblings = parentItem.parentElement.querySelectorAll('.dropdown-submenu');
            siblings.forEach(sibling => {
                if (sibling !== parentItem) {
                    const siblingMenu = sibling.querySelector('.dropdown-menu');
                    const siblingToggle = sibling.querySelector('.dropdown-toggle');
                    if (siblingMenu) siblingMenu.classList.remove('show');
                    if (siblingToggle) siblingToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
    }
    
    // ===== COMPONENTS =====
    initComponents() {
        console.log('Components inicializado');
        
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
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
        
        searchInput.on('focus', () => {
            this.showResultsIfAvailable();
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
    
    showResultsIfAvailable() {
        const query = this.schoolSearchElements.searchInput.val().trim();
        if (query.length >= 2 && this.schoolSearchElements.searchResults.children().length > 0) {
            this.schoolSearchElements.searchResults.show();
        }
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
    
    // ===== NAVBAR MENU =====
    initNavbarMenu() {
        console.log('NavbarMenu inicializado - Modo:', this.isMobile ? 'Móvil' : 'Escritorio');
        
        // Solo mantener la accesibilidad
        this.improveAccessibility();
    }
    
    improveAccessibility() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllDropdowns();
            }
        });
    }
    
    closeAllDropdowns() {
        document.querySelectorAll('.nav-item.dropdown.show, .dropdown-submenu.show').forEach(element => {
            element.classList.remove('show');
            const dropdownMenu = element.querySelector('.dropdown-menu');
            const dropdownToggle = element.querySelector('.dropdown-toggle');
            if (dropdownMenu) dropdownMenu.classList.remove('show');
            if (dropdownToggle) dropdownToggle.setAttribute('aria-expanded', 'false');
        });
    }
    
    // ===== ESCUELA SELECTOR =====
    initEscuelaSelector() {
        if (typeof $ === 'undefined') {
            console.error('jQuery no está cargado');
            return;
        }

        try {
            // Carrusel de noticias en navbar
            if ($('#carouselNav').length) {
                $('#carouselNav').carousel({
                    interval: 4000,
                    pause: 'hover'
                });
            }
            
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
            var target = $(this.getAttribute('href'));
            if (target.length) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000);
            }
        });
    }
    
    // ===== MANEJO DE RESIZE =====
    handleResize() {
        const newIsMobile = window.innerWidth < 992;
        if (newIsMobile !== this.isMobile) {
            this.isMobile = newIsMobile;
            console.log('Cambio de modo:', this.isMobile ? 'Móvil' : 'Escritorio');
        }
        
        setTimeout(() => {
            this.forceFullWidth();
            this.removeAllScrollbars();
            this.forceLogoOnly();
            this.setOptimalCarouselDimensions();
            this.optimizeMenuSpace();
            this.applyZIndexSolution(); // Re-aplicar z-index en resize
            this.applyLayoutCorrections(); // Re-aplicar correcciones de layout
        }, 100);
    }
}

// ==================================================
// GESTIÓN DE MENÚS MULTINIVEL - SOLUCIÓN DEFINITIVA
// ==================================================

class GEDMenuManager {
    constructor() {
        this.menuTimeout = null;
        this.init();
    }
    
    init() {
        this.cleanupConflicts();
        this.setupDesktopMenu();
        this.setupMobileMenu();
        this.preventBootstrapInterference();
        console.log('✅ GEDMenuManager inicializado correctamente - Sin conflictos');
    }
    
    setupDesktopMenu() {
        if (window.innerWidth < 992) return;
        
        console.log('🖥️ Configurando menú ESTABLE para escritorio');
        
        // Limpiar eventos anteriores COMPLETAMENTE
        $('.nav-item.dropdown, .dropdown-submenu').off('mouseenter mouseleave click');
        $('.dropdown-toggle').off('click');
        $('.dropdown-menu').off('mouseenter mouseleave');
        
        // SOLUCIÓN DEFINITIVA: Hover con timeout controlado
        $('.nav-item.dropdown, .dropdown-submenu').hover(
            // Mouse ENTER - Abrir inmediatamente
            function() {
                const $this = $(this);
                clearTimeout(window.gedMenuManager?.menuTimeout);
                
                $this.addClass('show');
                $this.find('.dropdown-menu:first').addClass('show');
                $this.find('.dropdown-toggle:first').attr('aria-expanded', 'true');
                
                window.gedMenuManager.menuOpen = true;
            },
            // Mouse LEAVE - Cerrar con delay controlado
            function() {
                const $this = $(this);
                
                window.gedMenuManager.menuTimeout = setTimeout(() => {
                    if (!window.gedMenuManager.isMouseInMenu($this)) {
                        $this.removeClass('show');
                        $this.find('.dropdown-menu').removeClass('show');
                        $this.find('.dropdown-toggle').attr('aria-expanded', 'false');
                        window.gedMenuManager.menuOpen = false;
                    }
                }, 300);
            }
        );
        
        // Mantener menú abierto cuando el mouse está en dropdowns
        $('.dropdown-menu').hover(
            function() {
                clearTimeout(window.gedMenuManager?.menuTimeout);
            },
            function() {
                const $this = $(this);
                window.gedMenuManager.menuTimeout = setTimeout(() => {
                    $this.closest('.dropdown, .dropdown-submenu').removeClass('show');
                    $this.removeClass('show');
                    $this.closest('.dropdown, .dropdown-submenu').find('.dropdown-toggle').attr('aria-expanded', 'false');
                    window.gedMenuManager.menuOpen = false;
                }, 300);
            }
        );
    }
    
    isMouseInMenu($element) {
        const menuElements = $element.find('.dropdown-menu').addBack();
        let isInMenu = false;
        
        menuElements.each(function() {
            if ($(this).is(':hover')) {
                isInMenu = true;
                return false;
            }
        });
        
        return isInMenu;
    }
    
    setupMobileMenu() {
        if (window.innerWidth >= 992) return;
        
        console.log('📱 Configurando menú para móvil');
        
        // Limpiar eventos anteriores
        $('.dropdown-toggle').off('click.ged-mobile');
        
        // Click para móvil - SOLO para móvil
        $('.dropdown-toggle').on('click.ged-mobile', function(e) {
            if (window.innerWidth < 992) {
                const $this = $(this);
                const $parent = $this.closest('.dropdown, .dropdown-submenu');
                const $menu = $parent.find('.dropdown-menu:first');
                
                // Cerrar otros menús
                $('.dropdown, .dropdown-submenu').not($parent).removeClass('show');
                $('.dropdown-menu').not($menu).removeClass('show');
                $('.dropdown-toggle').not($this).attr('aria-expanded', 'false');
                
                // Toggle este menú
                $parent.toggleClass('show');
                $menu.toggleClass('show');
                $this.attr('aria-expanded', $parent.hasClass('show'));
                
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        // Cerrar menús al hacer click fuera - SOLO móvil
        $(document).off('click.ged-menu-close').on('click.ged-menu-close', function(e) {
            if (window.innerWidth < 992 && !$(e.target).closest('.dropdown, .dropdown-submenu').length) {
                $('.dropdown, .dropdown-submenu').removeClass('show');
                $('.dropdown-menu').removeClass('show');
                $('.dropdown-toggle').attr('aria-expanded', 'false');
            }
        });
    }
    
    preventBootstrapInterference() {
        // Prevenir que Bootstrap interfiera con nuestros menús
        $(document).off('hide.bs.dropdown').on('hide.bs.dropdown', function(e) {
            if (window.innerWidth >= 992 && window.gedMenuManager?.menuOpen) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        // Prevenir clicks en dropdowns en escritorio
        $('.dropdown-toggle').off('click.bs.dropdown').on('click.bs.dropdown', function(e) {
            if (window.innerWidth >= 992) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    }
    
    cleanupConflicts() {
        console.log('🧹 Limpiando TODOS los conflictos de eventos');
        
        // Eliminar TODOS los event listeners conflictivos
        $('.nav-item.dropdown, .dropdown-submenu').off('mouseenter mouseleave click');
        $('.dropdown-toggle').off('click click.ged-mobile click.bs.dropdown');
        $('.dropdown-menu').off('mouseenter mouseleave');
        $(document).off('click.ged-menu-close hide.bs.dropdown');
        
        // Limpiar timeouts
        if (this.menuTimeout) {
            clearTimeout(this.menuTimeout);
            this.menuTimeout = null;
        }
    }
}

// ==================================================
// INICIALIZACIÓN GLOBAL DEL SISTEMA GED
// ==================================================

// Inicialización automática del sistema principal
document.addEventListener('DOMContentLoaded', () => {
    window.gedSystem = new GEDSystem();
});

// Inicialización del gestor de menús (con jQuery)
$(document).ready(function() {
    setTimeout(() => {
        if (!window.gedMenuManager) {
            window.gedMenuManager = new GEDMenuManager();
            console.log('🎯 SOLUCIÓN MENÚ GED - Inicializada correctamente');
        }
    }, 500);
    
    $(window).on('resize', function() {
        setTimeout(() => {
            if (window.gedMenuManager) {
                window.gedMenuManager.cleanupConflicts();
                window.gedMenuManager.init();
            }
        }, 150);
    });
});

window.addEventListener('resize', () => {
    if (window.gedSystem) {
        window.gedSystem.handleResize();
    }
});

if (window.gedSystem) {
    window.gedSystem.handleResize();
}

// Debug
function debugMenuInfo() {
    if (console && console.log) {
        console.group('🐛 DEBUG MENÚ GED - ESTADO ACTUAL');
        console.log('Navbar z-index:', $('.navbar-contextual').css('z-index'));
        console.log('Dropdowns encontrados:', $('.dropdown-menu').length);
        console.log('Submenús encontrados:', $('.dropdown-submenu').length);
        console.log('Menús abiertos:', $('.dropdown-menu.show').length);
        console.log('Modo actual:', window.innerWidth < 992 ? 'Móvil' : 'Escritorio');
        console.log('Menu open state:', window.gedMenuManager?.menuOpen);
        console.groupEnd();
    }
}

if (window.location.href.indexOf('debug') > -1) {
    setTimeout(debugMenuInfo, 1000);
}