// web/js/ged-offcanvas.js
(function() {
    'use strict';
    
    class GedOffcanvasManager {
        constructor() {
            this.offcanvas = document.querySelector('.ged-offcanvas-sidebar');
            this.togglerBtn = document.querySelector('[data-bs-toggle="offcanvas"]');
            this.closeBtn = document.querySelector('.close-offcanvas');
            this.compactToggler = document.querySelector('.sidebar-toggler-pc');
            this.backdrop = document.querySelector('.ged-sidebar-backdrop');
            this.isMobile = window.innerWidth < 992;
            this.isCompact = false;
            this.isOpen = false;
            
            this.init();
        }
        
        init() {
            console.log('🔄 GedOffcanvasManager inicializando...');
            
            this.cacheElements();
            this.checkViewport();
            this.bindEvents();
            this.restorePreferences();
            this.initSubmenus();
            
            console.log('✅ Offcanvas Manager listo - ' + (this.isMobile ? 'Móvil' : 'Desktop'));
        }
        
        cacheElements() {
            // Elementos ya están cacheados en constructor
            if (!this.offcanvas) {
                console.warn('⚠️ No se encontró .ged-offcanvas-sidebar');
                return;
            }
        }
        
        checkViewport() {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth < 992;
            
            if (wasMobile !== this.isMobile) {
                this.adjustForViewport();
            }
            
            // Escuchar cambios de tamaño
            window.addEventListener('resize', () => {
                const newIsMobile = window.innerWidth < 992;
                if (newIsMobile !== this.isMobile) {
                    this.isMobile = newIsMobile;
                    this.adjustForViewport();
                }
            });
        }
        
        adjustForViewport() {
            if (this.isMobile) {
                this.prepareForMobile();
            } else {
                this.prepareForDesktop();
            }
        }
        
        prepareForMobile() {
            console.log('📱 Configurando offcanvas para móvil');
            
            // Ocultar offcanvas por defecto
            if (this.offcanvas) {
                this.offcanvas.classList.remove('open');
                this.isOpen = false;
            }
            
            // Mostrar toggler en navbar
            if (this.togglerBtn) {
                this.togglerBtn.style.display = 'flex';
            }
            
            // Ocultar compact toggler
            if (this.compactToggler) {
                this.compactToggler.style.display = 'none';
            }
            
            // Asegurar que body no tenga padding lateral
            document.body.style.paddingLeft = '0';
            document.body.classList.remove('sidebar-compact');
        }
        
        prepareForDesktop() {
            console.log('💻 Configurando offcanvas para desktop');
            
            // Siempre visible en desktop
            if (this.offcanvas) {
                this.offcanvas.classList.add('open');
                this.isOpen = true;
            }
            
            // Ocultar toggler en navbar
            if (this.togglerBtn) {
                this.togglerBtn.style.display = 'none';
            }
            
            // Mostrar compact toggler
            if (this.compactToggler) {
                this.compactToggler.style.display = 'flex';
            }
            
            // Ocultar backdrop
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
            
            // Restaurar scroll
            document.body.style.overflow = '';
            
            // Aplicar padding lateral
            this.applyDesktopPadding();
        }
        
        applyDesktopPadding() {
            if (this.isCompact) {
                document.body.style.paddingLeft = '70px';
                document.body.classList.add('sidebar-compact');
            } else {
                document.body.style.paddingLeft = '280px';
                document.body.classList.remove('sidebar-compact');
            }
        }
        
        bindEvents() {
            // Toggle para móvil
            if (this.togglerBtn) {
                this.togglerBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleMobile();
                });
            }
            
            // Cerrar menú
            if (this.closeBtn) {
                this.closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.hide();
                });
            }
            
            // Compact toggler para PC
            if (this.compactToggler) {
                this.compactToggler.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleCompact();
                });
            }
            
            // Cerrar con backdrop (móvil)
            if (this.backdrop) {
                this.backdrop.addEventListener('click', () => this.hide());
            }
            
            // Cerrar con Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen && this.isMobile) {
                    this.hide();
                }
            });
            
            // Cerrar al hacer clic en enlace (solo móvil)
            document.addEventListener('click', (e) => {
                if (this.isMobile && this.isOpen) {
                    const link = e.target.closest('a.menu-link:not(.has-children)');
                    if (link) {
                        setTimeout(() => this.hide(), 300);
                    }
                }
            });
            
            // Prevenir cierre al hacer clic dentro del offcanvas
            if (this.offcanvas) {
                this.offcanvas.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }
        }
        
        initSubmenus() {
            // Usar Bootstrap Collapse nativo para submenús
            const submenuToggles = document.querySelectorAll('.menu-link.has-children');
            
            submenuToggles.forEach(toggle => {
                // Prevenir navegación cuando tiene hijos
                if (toggle.getAttribute('href') === '#') {
                    toggle.addEventListener('click', (e) => {
                        if (this.isMobile) {
                            e.preventDefault();
                            
                            // Cerrar otros submenús del mismo nivel
                            const level = toggle.getAttribute('data-level');
                            const parent = toggle.closest('.offcanvas-menu');
                            
                            if (parent) {
                                parent.querySelectorAll(`.menu-link.has-children[data-level="${level}"]`).forEach(otherToggle => {
                                    if (otherToggle !== toggle && otherToggle.getAttribute('aria-expanded') === 'true') {
                                        const target = otherToggle.getAttribute('data-bs-target');
                                        if (target) {
                                            const submenu = document.querySelector(target);
                                            if (submenu && bootstrap.Collapse) {
                                                new bootstrap.Collapse(submenu, { toggle: false }).hide();
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    });
                }
            });
        }
        
        restorePreferences() {
            // Restaurar preferencia compact desde localStorage
            const savedCompact = localStorage.getItem('ged-sidebar-compact');
            if (savedCompact === 'true' && !this.isMobile) {
                setTimeout(() => {
                    this.toggleCompact(true);
                }, 100);
            }
        }
        
        // ✅ MÉTODOS PÚBLICOS
        toggleMobile() {
            if (!this.isMobile) return;
            
            if (this.isOpen) {
                this.hide();
            } else {
                this.show();
            }
        }
        
        show() {
            if (!this.offcanvas) return;
            
            this.offcanvas.classList.add('open');
            this.isOpen = true;
            
            if (this.backdrop) {
                this.backdrop.classList.add('show');
            }
            
            // Bloquear scroll en móvil
            if (this.isMobile) {
                document.body.style.overflow = 'hidden';
            }
            
            // Enfocar primer elemento para accesibilidad
            setTimeout(() => {
                const firstLink = this.offcanvas.querySelector('a');
                if (firstLink) firstLink.focus();
            }, 100);
            
            console.log('📱 Offcanvas abierto');
        }
        
        hide() {
            if (!this.offcanvas) return;
            
            this.offcanvas.classList.remove('open');
            this.isOpen = false;
            
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
            
            // Restaurar scroll
            document.body.style.overflow = '';
            
            // Cerrar todos los submenús (solo móvil)
            if (this.isMobile) {
                document.querySelectorAll('.collapse.show').forEach(collapse => {
                    if (bootstrap.Collapse) {
                        new bootstrap.Collapse(collapse, { toggle: false }).hide();
                    }
                });
            }
            
            // Devolver foco al toggler
            if (this.togglerBtn && this.isMobile) {
                this.togglerBtn.focus();
            }
            
            console.log('📱 Offcanvas cerrado');
        }
        
        toggleCompact(forceState = null) {
            if (this.isMobile) return;
            
            if (forceState !== null) {
                this.isCompact = forceState;
            } else {
                this.isCompact = !this.isCompact;
            }
            
            if (this.offcanvas) {
                this.offcanvas.classList.toggle('compact', this.isCompact);
            }
            
            this.applyDesktopPadding();
            
            // Guardar preferencia
            localStorage.setItem('ged-sidebar-compact', this.isCompact);
            
            console.log('💻 Modo compacto:', this.isCompact ? 'Activado' : 'Desactivado');
        }
        
        isOpen() {
            return this.isOpen;
        }
        
        update() {
            this.checkViewport();
        }
    }
    
    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', () => {
        // Crear instancia global
        window.GedOffcanvas = new GedOffcanvasManager();
        
        // ✅ Compatibilidad con jQuery (opcional)
        if (typeof jQuery !== 'undefined') {
            jQuery.fn.gedOffcanvas = function(action) {
                if (!window.GedOffcanvas) return this;
                
                if (action === 'toggle') {
                    window.GedOffcanvas.toggleMobile();
                } else if (action === 'show') {
                    window.GedOffcanvas.show();
                } else if (action === 'hide') {
                    window.GedOffcanvas.hide();
                } else if (action === 'toggleCompact') {
                    window.GedOffcanvas.toggleCompact();
                } else if (action === 'update') {
                    window.GedOffcanvas.update();
                }
                
                return this;
            };
        }
        
        // ✅ Exponer métodos globales
        window.toggleGedOffcanvas = function() {
            if (window.GedOffcanvas) {
                window.GedOffcanvas.toggleMobile();
            }
        };
        
        window.toggleGedCompact = function() {
            if (window.GedOffcanvas) {
                window.GedOffcanvas.toggleCompact();
            }
        };
        
        console.log('🚀 GedOffcanvasManager inicializado globalmente');
    });
    
})();