/**
 * navbar.js - Manejo de interacciones del navbar responsive
 * Versión: 1.0.0
 * Compatible: Bootstrap 5, Yii2, jQuery
 */

 (function() {
    'use strict';
    
    // ==================================================
    // CONFIGURACIÓN Y CONSTANTES
    // ==================================================
    const CONFIG = {
        mobileBreakpoint: 992,
        animationSpeed: 300,
        searchDebounceDelay: 300,
        escuelaSessionKey: 'ged_escuela_activa'
    };
    
    // ==================================================
    // CLASE PRINCIPAL - NavbarManager
    // ==================================================
    class NavbarManager {
        constructor() {
            this.navbar = null;
            this.toggler = null;
            this.collapse = null;
            this.isMobile = false;
            this.isCollapsed = true;
            this.searchTimeout = null;
            
            this.init();
        }
        
        // ==================================================
        // INICIALIZACIÓN
        // ==================================================
        init() {
            console.log('🔄 NavbarManager inicializando...');
            
            this.cacheElements();
            this.checkViewport();
            this.bindEvents();
            this.initDropdowns();
            this.initEscuelaSearch();
            
            console.log('✅ NavbarManager inicializado');
        }
        
        cacheElements() {
            this.navbar = document.querySelector('.navbar-contextual');
            this.toggler = document.querySelector('.navbar-toggler');
            this.collapse = document.querySelector('.navbar-collapse');
            this.backdrop = document.querySelector('.navbar-backdrop') || this.createBackdrop();
        }
        
        createBackdrop() {
            const backdrop = document.createElement('div');
            backdrop.className = 'navbar-backdrop';
            backdrop.style.cssText = `
                position: fixed;
                top: ${this.getNavbarHeight()}px;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1035;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            `;
            
            backdrop.addEventListener('click', () => this.hideMobileMenu());
            document.body.appendChild(backdrop);
            
            return backdrop;
        }
        
        getNavbarHeight() {
            if (window.innerWidth >= CONFIG.mobileBreakpoint) {
                return 180; // Desktop height
            }
            return this.navbar ? this.navbar.offsetHeight : 60;
        }
        
        // ==================================================
        // MANEJO DE VIEWPORT
        // ==================================================
        checkViewport() {
            this.isMobile = window.innerWidth < CONFIG.mobileBreakpoint;
            this.updateNavbarState();
            
            // Escuchar cambios de tamaño
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    const newIsMobile = window.innerWidth < CONFIG.mobileBreakpoint;
                    if (newIsMobile !== this.isMobile) {
                        this.isMobile = newIsMobile;
                        this.updateNavbarState();
                    }
                }, 250);
            });
        }
        
        updateNavbarState() {
            if (this.isMobile) {
                this.prepareForMobile();
            } else {
                this.prepareForDesktop();
            }
        }
        
        prepareForMobile() {
            console.log('📱 Modo móvil activado');
            
            // Asegurar que el menú esté colapsado
            if (this.collapse) {
                this.collapse.classList.remove('show');
                this.isCollapsed = true;
            }
            
            // Ocultar backdrop
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
            
            // Ajustar altura del backdrop
            this.updateBackdropPosition();
        }
        
        prepareForDesktop() {
            console.log('💻 Modo escritorio activado');
            
            // Mostrar menú si estaba oculto
            if (this.collapse) {
                this.collapse.classList.add('show');
                this.isCollapsed = false;
            }
            
            // Ocultar backdrop
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
        }
        
        updateBackdropPosition() {
            if (this.backdrop && this.isMobile) {
                this.backdrop.style.top = `${this.getNavbarHeight()}px`;
            }
        }
        
        // ==================================================
        // MANEJO DE EVENTOS
        // ==================================================
        bindEvents() {
            // Toggler del navbar
            if (this.toggler) {
                this.toggler.addEventListener('click', (e) => this.toggleMobileMenu(e));
            }
            
            // Cerrar menú al hacer clic en enlaces (solo móvil)
            document.addEventListener('click', (e) => {
                if (this.isMobile && !this.isCollapsed) {
                    const link = e.target.closest('a');
                    if (link && link.getAttribute('href') !== '#') {
                        setTimeout(() => this.hideMobileMenu(), 300);
                    }
                }
            });
            
            // Cerrar menú con tecla Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isMobile && !this.isCollapsed) {
                    this.hideMobileMenu();
                }
            });
            
            // Prevenir que el menú se cierre al hacer clic dentro
            if (this.collapse) {
                this.collapse.addEventListener('click', (e) => {
                    if (this.isMobile && e.target.closest('.dropdown-menu')) {
                        e.stopPropagation();
                    }
                });
            }
        }
        
        // ==================================================
        // MANEJO DEL MENÚ MÓVIL
        // ==================================================
        toggleMobileMenu(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            if (!this.isMobile) return;
            
            if (this.isCollapsed) {
                this.showMobileMenu();
            } else {
                this.hideMobileMenu();
            }
        }
        
        showMobileMenu() {
            console.log('📱 Mostrando menú móvil');
            
            if (this.collapse) {
                this.collapse.classList.add('show');
                this.isCollapsed = false;
            }
            
            if (this.backdrop) {
                this.backdrop.classList.add('show');
            }
            
            // Bloquear scroll
            document.body.style.overflow = 'hidden';
            
            // Enfocar primer elemento del menú para accesibilidad
            setTimeout(() => {
                const firstLink = this.collapse.querySelector('a');
                if (firstLink) firstLink.focus();
            }, 100);
        }
        
        hideMobileMenu() {
            console.log('📱 Ocultando menú móvil');
            
            if (this.collapse) {
                this.collapse.classList.remove('show');
                this.isCollapsed = true;
            }
            
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
            
            // Restaurar scroll
            document.body.style.overflow = '';
            
            // Devolver foco al toggler
            if (this.toggler) {
                this.toggler.focus();
            }
        }
        
        // ==================================================
        // DROPDOWNS MEJORADOS
        // ==================================================
        initDropdowns() {
            // Para escritorio: comportamiento Bootstrap normal
            // Para móvil: comportamiento personalizado
            if (this.isMobile) {
                this.setupMobileDropdowns();
            }
            
            // Detectar cambios entre móvil/desktop
            const observer = new ResizeObserver(entries => {
                for (let entry of entries) {
                    const isNowMobile = entry.contentRect.width < CONFIG.mobileBreakpoint;
                    if (isNowMobile !== this.isMobile) {
                        this.isMobile = isNowMobile;
                        if (this.isMobile) {
                            this.setupMobileDropdowns();
                        } else {
                            this.setupDesktopDropdowns();
                        }
                    }
                }
            });
            
            if (this.navbar) {
                observer.observe(this.navbar);
            }
        }
        
        setupMobileDropdowns() {
            console.log('🔄 Configurando dropdowns para móvil');
            
            // Deshabilitar comportamiento Bootstrap
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.setAttribute('data-bs-toggle', 'none');
                
                // Agregar handler personalizado
                toggle.addEventListener('click', (e) => {
                    if (!this.isMobile) return;
                    
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const dropdown = toggle.closest('.dropdown');
                    const isOpen = dropdown.classList.contains('show');
                    
                    // Cerrar otros dropdowns abiertos
                    document.querySelectorAll('.dropdown.show').forEach(d => {
                        if (d !== dropdown) d.classList.remove('show');
                    });
                    
                    // Toggle current dropdown
                    dropdown.classList.toggle('show', !isOpen);
                });
            });
            
            // Cerrar dropdowns al hacer clic fuera
            document.addEventListener('click', (e) => {
                if (this.isMobile && !e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown.show').forEach(d => {
                        d.classList.remove('show');
                    });
                }
            });
        }
        
        setupDesktopDropdowns() {
            console.log('🔄 Configurando dropdowns para escritorio');
            
            // Restaurar comportamiento Bootstrap
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.setAttribute('data-bs-toggle', 'dropdown');
            });
        }
        
        // ==================================================
        // BÚSQUEDA DE ESCUELAS
        // ==================================================
        initEscuelaSearch() {
            const searchInput = document.getElementById('schoolSearch');
            const searchBtn = document.getElementById('searchSchoolBtn');
            const resultsContainer = document.getElementById('schoolSearchResults');
            
            if (!searchInput) return;
            
            // Búsqueda al escribir (con debounce)
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    this.hideSearchResults();
                    return;
                }
                
                this.searchTimeout = setTimeout(() => {
                    this.performEscuelaSearch(query);
                }, CONFIG.searchDebounceDelay);
            });
            
            // Búsqueda al hacer clic en botón
            if (searchBtn) {
                searchBtn.addEventListener('click', () => {
                    const query = searchInput.value.trim();
                    if (query) {
                        this.performEscuelaSearch(query);
                    }
                });
            }
            
            // Cerrar resultados al hacer clic fuera
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && 
                    !(resultsContainer && resultsContainer.contains(e.target))) {
                    this.hideSearchResults();
                }
            });
        }
        
        performEscuelaSearch(query) {
            console.log(`🔍 Buscando escuela: ${query}`);
            
            // Mostrar loading
            const resultsContainer = document.getElementById('schoolSearchResults');
            if (resultsContainer) {
                resultsContainer.innerHTML = `
                    <div class="search-results-item text-center">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Buscando...</span>
                        </div>
                        <span class="ms-2">Buscando escuelas...</span>
                    </div>
                `;
                resultsContainer.classList.add('show');
            }
            
            // Simular búsqueda AJAX (reemplazar con llamada real)
            setTimeout(() => {
                this.showSearchResults([
                    { id: 1, nombre: 'Escuela Deportiva ' + query + ' 1' },
                    { id: 2, nombre: 'Escuela ' + query + ' de Alto Rendimiento' },
                    { id: 3, nombre: query + ' Sports Academy' }
                ]);
            }, 500);
        }
        
        showSearchResults(escuelas) {
            const resultsContainer = document.getElementById('schoolSearchResults');
            if (!resultsContainer) return;
            
            if (escuelas.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="search-results-item">
                        <span class="text-muted">No se encontraron escuelas</span>
                    </div>
                `;
                return;
            }
            
            let html = '';
            escuelas.forEach(escuela => {
                html += `
                    <div class="search-results-item" 
                         data-escuela-id="${escuela.id}"
                         data-escuela-nombre="${escuela.nombre}">
                        <strong>${escuela.nombre}</strong>
                        <small class="text-muted d-block">ID: ${escuela.id}</small>
                    </div>
                `;
            });
            
            resultsContainer.innerHTML = html;
            resultsContainer.classList.add('show');
            
            // Agregar event listeners a los resultados
            resultsContainer.querySelectorAll('.search-results-item').forEach(item => {
                item.addEventListener('click', () => {
                    const id = item.dataset.escuelaId;
                    const nombre = item.dataset.escuelaNombre;
                    this.selectEscuela(id, nombre);
                });
            });
        }
        
        hideSearchResults() {
            const resultsContainer = document.getElementById('schoolSearchResults');
            if (resultsContainer) {
                resultsContainer.classList.remove('show');
            }
        }
        
        selectEscuela(id, nombre) {
            console.log(`🏫 Escuela seleccionada: ${nombre} (ID: ${id})`);
            
            // Actualizar UI
            const currentSchool = document.getElementById('current-school');
            const currentSchoolId = document.getElementById('current-school-id');
            
            if (currentSchool) currentSchool.textContent = nombre;
            if (currentSchoolId) currentSchoolId.textContent = `ID: ${id}`;
            
            // Ocultar resultados
            this.hideSearchResults();
            
            // Guardar en sesión (simulado)
            sessionStorage.setItem(CONFIG.escuelaSessionKey, JSON.stringify({ id, nombre }));
            
            // Mostrar confirmación
            this.showToast('Escuela seleccionada correctamente', 'success');
            
            // Disparar evento personalizado
            document.dispatchEvent(new CustomEvent('escuela-seleccionada', {
                detail: { id, nombre }
            }));
        }
        
        // ==================================================
        // UTILIDADES
        // ==================================================
        showToast(message, type = 'info') {
            // Crear toast simple
            const toast = document.createElement('div');
            toast.className = `navbar-toast navbar-toast-${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : '#007bff'};
                color: white;
                padding: 10px 20px;
                border-radius: 4px;
                z-index: 9999;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // ==================================================
        // API PÚBLICA
        // ==================================================
        toggle() {
            this.toggleMobileMenu();
        }
        
        show() {
            this.showMobileMenu();
        }
        
        hide() {
            this.hideMobileMenu();
        }
        
        isMobileView() {
            return this.isMobile;
        }
        
        update() {
            this.checkViewport();
            this.updateBackdropPosition();
        }
    }
    
    // ==================================================
    // INICIALIZACIÓN GLOBAL
    // ==================================================
    document.addEventListener('DOMContentLoaded', () => {
        // Crear instancia global
        window.GEDNavbar = new NavbarManager();
        
        // Agregar estilos CSS dinámicos
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            .navbar-toast {
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-size: 14px;
                font-weight: 500;
            }
            
            .navbar-toast-success {
                background: #28a745 !important;
            }
            
            .navbar-toast-info {
                background: #17a2b8 !important;
            }
            
            .navbar-toast-warning {
                background: #ffc107 !important;
                color: #212529 !important;
            }
            
            .navbar-toast-error {
                background: #dc3545 !important;
            }
        `;
        document.head.appendChild(style);
        
        // Compatibilidad con jQuery (opcional)
        if (typeof jQuery !== 'undefined') {
            jQuery.fn.navbar = function(action) {
                if (!window.GEDNavbar) return this;
                
                if (action === 'toggle') {
                    window.GEDNavbar.toggle();
                } else if (action === 'show') {
                    window.GEDNavbar.show();
                } else if (action === 'hide') {
                    window.GEDNavbar.hide();
                } else if (action === 'update') {
                    window.GEDNavbar.update();
                }
                
                return this;
            };
        }
    });
    
    // ==================================================
    // EXPORTACIÓN PARA MÓDULOS
    // ==================================================
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = NavbarManager;
    }
    
})();