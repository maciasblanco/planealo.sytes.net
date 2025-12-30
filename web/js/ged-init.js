// web/js/ged-init.js
// Inicialización global del sistema GED - VERSIÓN CORREGIDA

document.addEventListener('DOMContentLoaded', () => {
    console.log('📄 GED Init - Documento cargado, inicializando sistema...');
    
    // ✅ FUNCIÓN MEJORADA CON CARGA DINÁMICA DE MÓDULOS
    async function initializeOptionalModules(maxRetries = 3, retryDelay = 500) {
        const modulesToCheck = [
            // ✅ TIENDA PRIMERO (más crítico)
            { 
                name: 'TiendaModule', 
                globalVar: 'tiendaModule', 
                required: false,
                scriptPath: '/js/modules/tienda-module.js',
                classCheck: () => typeof TiendaModule !== 'undefined'
            },
            // ✅ OFF-CANVAS (siempre presente)
            { 
                name: 'OffCanvasSidebar', 
                globalVar: 'gedOffcanvas', 
                required: false,
                scriptPath: '/js/modules/gedOffCanvas-module.js',
                classCheck: () => typeof OffCanvasSidebar !== 'undefined'
            },
            // ✅ OTROS MÓDULOS
            { 
                name: 'ReportesModule', 
                globalVar: 'reportesModule', 
                required: false,
                scriptPath: '/js/modules/reportes-module.js',
                classCheck: () => typeof ReportesModule !== 'undefined'
            },
            { 
                name: 'HorarioModule', 
                globalVar: 'horarioModuleInstance', 
                required: false,
                scriptPath: '/js/modules/horario-selector.js',
                classCheck: () => typeof HorarioModule !== 'undefined'
            },
            { 
                name: 'MapaModule', 
                globalVar: 'mapaModule', 
                required: false,
                scriptPath: '/js/modules/mapa-module.js',
                classCheck: () => typeof MapaModule !== 'undefined'
            }
        ];
        
        let initializedCount = 0;
        
        // ✅ FUNCIÓN PARA CARGAR SCRIPT DINÁMICAMENTE
        const loadScript = (src) => {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = () => resolve(true);
                script.onerror = () => {
                    console.warn(`⚠️ No se pudo cargar script: ${src}`);
                    resolve(false);
                };
                document.head.appendChild(script);
            });
        };
        
        // ✅ INICIALIZAR CADA MÓDULO CON MANEJO DE ERRORES
        for (const module of modulesToCheck) {
            const tryInitialize = async (retryCount = 0) => {
                try {
                    // Paso 1: Verificar si la clase ya está disponible
                    let classAvailable = module.classCheck();
                    
                    // Paso 2: Si no está disponible y tiene scriptPath, cargarlo
                    if (!classAvailable && module.scriptPath) {
                        console.log(`📦 Cargando módulo ${module.name} desde: ${module.scriptPath}`);
                        const loaded = await loadScript(module.scriptPath);
                        
                        if (loaded) {
                            // Esperar un poco a que el script se ejecute
                            await new Promise(resolve => setTimeout(resolve, 100));
                            classAvailable = module.classCheck();
                        }
                    }
                    
                    // Paso 3: Intentar inicializar si la clase está disponible
                    if (classAvailable && !window[module.globalVar]) {
                        try {
                            window[module.globalVar] = new window[module.name]();
                            
                            // Si el módulo tiene método init, llamarlo
                            if (typeof window[module.globalVar].init === 'function') {
                                window[module.globalVar].init();
                            }
                            
                            console.log(`✅ Módulo ${module.name} inicializado`);
                            initializedCount++;
                            return; // Éxito, salir
                            
                        } catch (initError) {
                            console.warn(`⚠️ Error al instanciar ${module.name}:`, initError.message);
                            throw initError;
                        }
                    } 
                    else if (classAvailable && window[module.globalVar]) {
                        console.log(`ℹ️ Módulo ${module.name} ya estaba inicializado`);
                    }
                    else if (!classAvailable) {
                        console.log(`ℹ️ Módulo ${module.name} no está disponible en esta página`);
                    }
                    
                } catch (error) {
                    console.warn(`⚠️ No se pudo inicializar ${module.name} (intento ${retryCount + 1}/${maxRetries}):`, error.message);
                    
                    // Reintentar si aún hay intentos disponibles
                    if (retryCount < maxRetries - 1) {
                        const nextDelay = retryDelay * (retryCount + 1);
                        console.log(`⏳ Reintentando ${module.name} en ${nextDelay}ms...`);
                        
                        return new Promise(resolve => {
                            setTimeout(async () => {
                                const result = await tryInitialize(retryCount + 1);
                                resolve(result);
                            }, nextDelay);
                        });
                    } else if (module.required) {
                        console.error(`❌ No se pudo inicializar módulo requerido: ${module.name}`);
                    }
                }
            };
            
            await tryInitialize();
        }
        
        return initializedCount;
    }
    
    // ✅ INICIALIZAR EL SISTEMA PRINCIPAL
    setTimeout(() => {
        if (!window.gedSystem && typeof GEDSystem !== 'undefined') {
            try {
                window.gedSystem = new GEDSystem();
                console.log('🚀 Sistema GED v4.1 completamente inicializado');
                
                // Disparar evento de listo
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
                
                // ✅ INICIALIZAR MÓDULOS OPCIONALES CON RETARDO
                setTimeout(async () => {
                    console.log('🔄 Inicializando módulos opcionales...');
                    
                    try {
                        const initialized = await initializeOptionalModules();
                        console.log(`📊 Módulos opcionales inicializados: ${initialized}`);
                        
                        // ✅ VERIFICACIÓN ESPECIAL PARA TIENDA (debugging)
                        if (typeof TiendaModule !== 'undefined' && !window.tiendaModule) {
                            console.log('🔧 Inicialización especial para TiendaModule...');
                            try {
                                window.tiendaModule = new TiendaModule();
                                if (typeof window.tiendaModule.init === 'function') {
                                    window.tiendaModule.init();
                                }
                                console.log('✅ TiendaModule inicializado manualmente');
                            } catch (tiendaError) {
                                console.error('❌ Error manual en TiendaModule:', tiendaError);
                            }
                        }
                        
                    } catch (modulesError) {
                        console.error('❌ Error en inicialización de módulos:', modulesError);
                    }
                }, 800); // Delay mayor para asegurar carga
                
            } catch (error) {
                console.error('❌ Error al inicializar GEDSystem:', error);
            }
        } 
        else if (window.gedSystem) {
            console.log('ℹ️ Sistema GED ya estaba inicializado');
            
            // Aún así intentar inicializar módulos opcionales
            setTimeout(async () => {
                try {
                    const initialized = await initializeOptionalModules();
                    console.log(`📊 Módulos opcionales inicializados: ${initialized}`);
                } catch (error) {
                    console.error('❌ Error en módulos opcionales:', error);
                }
            }, 800);
        } 
        else {
            console.error('❌ Clase GEDSystem no encontrada');
        }
    }, 200); // Delay inicial aumentado
    
    // ✅ INICIALIZAR LANDING PAGE MANAGER SI ES NECESARIO
    if (document.querySelector('.landing-page')) {
        setTimeout(() => {
            if (typeof window.landingPageManager !== 'undefined') {
                console.log('✅ Landing Page Manager ya está cargado');
            } 
            else if (typeof LandingPageManager !== 'undefined') {
                try {
                    window.landingPageManager = new LandingPageManager();
                    window.landingPageManager.init();
                    console.log('✅ Landing Page Manager inicializado correctamente');
                } catch (error) {
                    console.error('❌ Error al inicializar LandingPageManager:', error);
                }
            } 
            else {
                console.log('ℹ️ LandingPageManager no está definido para esta página');
            }
        }, 1000);
    }
    
    // ✅ VERIFICACIÓN DE CARGA COMPLETA (para debugging)
    setTimeout(() => {
        console.log('🔍 VERIFICACIÓN FINAL DE MÓDULOS:');
        console.log('- GEDSystem:', typeof GEDSystem !== 'undefined' ? '✅' : '❌');
        console.log('- TiendaModule:', typeof TiendaModule !== 'undefined' ? '✅' : '❌');
        console.log('- OffCanvasSidebar:', typeof OffCanvasSidebar !== 'undefined' ? '✅' : '❌');
        console.log('- ReportesModule:', typeof ReportesModule !== 'undefined' ? '✅' : '❌');
        console.log('- HorarioModule:', typeof HorarioModule !== 'undefined' ? '✅' : '❌');
        console.log('- MapaModule:', typeof MapaModule !== 'undefined' ? '✅' : '❌');
        console.log('- LandingPageManager:', typeof LandingPageManager !== 'undefined' ? '✅' : '❌');
        
        // Verificar instancias
        console.log('📦 INSTANCIAS CREADAS:');
        console.log('- gedSystem:', window.gedSystem ? '✅' : '❌');
        console.log('- tiendaModule:', window.tiendaModule ? '✅' : '❌');
        console.log('- gedOffcanvas:', window.gedOffcanvas ? '✅' : '❌');
        console.log('- reportesModule:', window.reportesModule ? '✅' : '❌');
        console.log('- horarioModuleInstance:', window.horarioModuleInstance ? '✅' : '❌');
        console.log('- mapaModule:', window.mapaModule ? '✅' : '❌');
        console.log('- landingPageManager:', window.landingPageManager ? '✅' : '❌');
        
    }, 3000);
});

// ✅ FUNCIONES GLOBALES DE UTILIDAD
if (typeof window !== 'undefined') {
    // Función para forzar reinicialización de módulos
    window.reinitializeGEDModules = async function() {
        console.log('🔄 Reinicializando módulos GED...');
        
        // Limpiar instancias existentes
        const modules = ['tiendaModule', 'gedOffcanvas', 'reportesModule', 'horarioModuleInstance', 'mapaModule'];
        modules.forEach(moduleName => {
            if (window[moduleName]) {
                if (typeof window[moduleName].destroy === 'function') {
                    window[moduleName].destroy();
                }
                window[moduleName] = null;
            }
        });
        
        // Recrear la función de inicialización
        async function reinit() {
            const modulesToCheck = [
                { name: 'TiendaModule', globalVar: 'tiendaModule' },
                { name: 'OffCanvasSidebar', globalVar: 'gedOffcanvas' },
                { name: 'ReportesModule', globalVar: 'reportesModule' },
                { name: 'HorarioModule', globalVar: 'horarioModuleInstance' },
                { name: 'MapaModule', globalVar: 'mapaModule' }
            ];
            
            for (const module of modulesToCheck) {
                if (typeof window[module.name] !== 'undefined') {
                    try {
                        window[module.globalVar] = new window[module.name]();
                        if (typeof window[module.globalVar].init === 'function') {
                            window[module.globalVar].init();
                        }
                        console.log(`✅ ${module.name} reinicializado`);
                    } catch (error) {
                        console.error(`❌ Error reinicializando ${module.name}:`, error);
                    }
                }
            }
        }
        
        await reinit();
    };
    
    // Función para debug de módulos
    window.debugGEDModules = function() {
        console.group('🔍 DEBUG MÓDULOS GED');
        
        const checks = [
            { name: 'TiendaModule', global: 'tiendaModule', class: TiendaModule },
            { name: 'OffCanvasSidebar', global: 'gedOffcanvas', class: OffCanvasSidebar },
            { name: 'ReportesModule', global: 'reportesModule', class: ReportesModule },
            { name: 'HorarioModule', global: 'horarioModuleInstance', class: HorarioModule },
            { name: 'MapaModule', global: 'mapaModule', class: MapaModule }
        ];
        
        checks.forEach(check => {
            const hasClass = typeof check.class !== 'undefined';
            const hasInstance = window[check.global] !== undefined && window[check.global] !== null;
            const status = hasClass ? (hasInstance ? '✅' : '⚠️') : '❌';
            
            console.log(`${status} ${check.name}:`, {
                'Clase disponible': hasClass,
                'Instancia creada': hasInstance,
                'Tiene init': hasInstance && typeof window[check.global].init === 'function',
                'Tiene destroy': hasInstance && typeof window[check.global].destroy === 'function'
            });
        });
        
        console.groupEnd();
    };
}

// ✅ EXPORT PARA NODE.JS (SI ES NECESARIO)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { 
        GEDSystem, 
        LandingPageManager, 
        OffCanvasSidebar, 
        NavbarManager, 
        SchoolSearch, 
        ComponentsManager,
        TiendaModule,
        ReportesModule,
        HorarioModule,
        MapaModule
    };
}

// ✅ VERIFICACIÓN DE CARGA AUTOMÁTICA (solo en desarrollo)
if (window.location.href.includes('localhost') || window.location.href.includes('debug')) {
    setTimeout(() => {
        console.log('🔧 MODO DESARROLLO ACTIVO');
        console.log('ℹ️ Comandos disponibles:');
        console.log('  - debugGEDModules() - Ver estado de módulos');
        console.log('  - reinitializeGEDModules() - Reiniciar módulos');
        console.log('  - window.gedSystem.checkLayout() - Verificar layout');
    }, 4000);
}