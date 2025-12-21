<?php
use yii\helpers\Html;

/* @var $this yii\web\View */

$this->title = 'Pruebas JavaScript Exhaustivas - Sistema GED v4.1';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Leaflet para MapaModule -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body { padding-top: 20px; background-color: #f8f9fa; }
        .card { margin-bottom: 20px; }
        .log-entry { padding: 2px 0; border-bottom: 1px solid #333; font-size: 13px; }
        #consola-logs { height: 400px; overflow-y: auto; background: #1a1a1a; color: #00ff00; font-family: monospace; padding: 10px; }
        .module-status { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
        .status-success { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-error { background-color: #dc3545; }
        .status-default { background-color: #6c757d; }
        #map, #mapa-escuelas { height: 300px; border: 1px solid #ddd; }
        .horario-cell { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; cursor: pointer; margin: 1px; }
        .horario-cell.selected { background-color: #007bff; color: white; }
        .horario-cell.morning { background-color: #fff3cd; }
        .horario-cell.afternoon { background-color: #d1ecf1; }
        .horario-cell.evening { background-color: #e2e3e5; }
        .navbar-toggler { position: fixed; top: 10px; right: 10px; z-index: 1050; }
    </style>
</head>
<body>
<!-- Navbar Toggler de prueba -->
<button class="navbar-toggler btn btn-primary">
    <i class="fas fa-bars"></i>
</button>

<div class="container-fluid">
    <!-- HEADER -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="text-center mb-3">
                <i class="fas fa-vial"></i> Pruebas Exhaustivas JavaScript
            </h1>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Sistema GED v4.1</strong> - Prueba exhaustiva de los 5 módulos JavaScript principales
            </div>
        </div>
    </div>

    <!-- PANEL DE MÓDULOS -->
    <div class="row mb-4">
        <!-- MÓDULO 1: GED OFFCANVAS -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bars"></i> Módulo OffCanvas
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>gedOffcanvas-module.js</strong></p>
                    <p>Sidebar off-canvas responsive con lazy loading</p>
                    <div class="mb-3">
                        <span class="module-status status-default" id="status-offcanvas"></span>
                        Estado: <span id="text-status-offcanvas">No probado</span>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" id="btn-test-offcanvas">
                            <i class="fas fa-play"></i> Probar Módulo
                        </button>
                        <button class="btn btn-outline-success" id="btn-toggle-offcanvas">
                            <i class="fas fa-exchange-alt"></i> Toggle Sidebar
                        </button>
                        <button class="btn btn-sm btn-outline-dark" id="btn-reload-offcanvas">
                            <i class="fas fa-sync"></i> Recargar Menú
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- MÓDULO 2: REPORTES -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i> Módulo Reportes
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>reportes-module.js</strong></p>
                    <p>Generación y análisis de reportes</p>
                    <div class="mb-3">
                        <span class="module-status status-default" id="status-reportes"></span>
                        Estado: <span id="text-status-reportes">No probado</span>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" id="select-estado-reporte">
                            <option value="todos">Todos los estados</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                            <option value="pendiente">Pendiente</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-info" id="btn-test-reportes">
                            <i class="fas fa-play"></i> Probar Módulo
                        </button>
                        <button class="btn btn-outline-info" id="btn-filtrar-reportes">
                            <i class="fas fa-filter"></i> Filtrar por Estado
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- MÓDULO 3: HORARIOS -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> Módulo Horarios
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>horario-selector.js</strong></p>
                    <p>Selector visual de horarios interactivo</p>
                    <div class="mb-3">
                        <span class="module-status status-default" id="status-horario"></span>
                        Estado: <span id="text-status-horario">No probado</span>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" id="btn-test-horario">
                            <i class="fas fa-play"></i> Probar Módulo
                        </button>
                        <button class="btn btn-outline-primary" id="btn-crear-selector">
                            <i class="fas fa-plus"></i> Crear Selector
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- MÓDULO 4: TIENDA -->
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-store"></i> Módulo Tienda
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>tienda-module.js</strong></p>
                    <p>Funcionalidades de tienda y marketplace</p>
                    <div class="mb-3">
                        <span class="module-status status-default" id="status-tienda"></span>
                        Estado: <span id="text-status-tienda">No probado</span>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning" id="btn-test-tienda">
                            <i class="fas fa-play"></i> Probar Módulo
                        </button>
                        <button class="btn btn-outline-warning" id="btn-simular-marketplace">
                            <i class="fas fa-shopping-cart"></i> Simular Marketplace
                        </button>
                        <button class="btn btn-sm btn-outline-dark" id="btn-track-evento">
                            <i class="fas fa-chart-line"></i> Track Event
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- MÓDULO 5: MAPA -->
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-map"></i> Módulo Mapa
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>mapa-module.js</strong></p>
                    <p>Mapas interactivos para selección y visualización</p>
                    <div class="mb-3">
                        <span class="module-status status-default" id="status-mapa"></span>
                        Estado: <span id="text-status-mapa">No probado</span>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-dark" id="btn-test-mapa">
                            <i class="fas fa-play"></i> Probar Módulo
                        </button>
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-dark" id="btn-mapa-seleccion">
                                <i class="fas fa-map-marker-alt"></i> Mapa Selección
                            </button>
                            <button class="btn btn-outline-dark" id="btn-mapa-visualizacion">
                                <i class="fas fa-school"></i> Mapa Visualización
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ÁREAS DE PRUEBA -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-map"></i> Área de Prueba - Mapas
                    </h5>
                </div>
                <div class="card-body">
                    <div id="map" class="mb-3"></div>
                    <div id="mapa-escuelas"></div>
                    <div class="mt-3">
                        <input type="text" class="form-control mb-2" id="lat-input" placeholder="Latitud">
                        <input type="text" class="form-control mb-2" id="lng-input" placeholder="Longitud">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> Área de Prueba - Horarios
                    </h5>
                </div>
                <div class="card-body">
                    <div id="horario-grid" class="mb-3"></div>
                    <div id="horario-preview" class="alert alert-info"></div>
                    <input type="hidden" id="horario-data">
                    
                    <div class="mb-3">
                        <select class="form-select" id="tipo-horario">
                            <option value="">Seleccionar tipo de horario</option>
                            <option value="manana">Mañana (6am-12pm)</option>
                            <option value="tarde">Tarde (12pm-6pm)</option>
                            <option value="noche">Noche (6pm-10pm)</option>
                            <option value="completo">Completo</option>
                            <option value="fin_semana">Fin de semana</option>
                        </select>
                    </div>
                    
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-primary" id="select-all">Seleccionar Todo</button>
                        <button class="btn btn-outline-danger" id="clear-all">Limpiar Todo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE PRUEBA PARA REPORTES -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> Tabla de Prueba - Reportes
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped" id="tabla-atletas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-estado="activo">
                                <td>1</td>
                                <td>Juan Pérez</td>
                                <td><span class="badge bg-success">Activo</span></td>
                                <td><button class="btn btn-sm btn-info">Ver</button></td>
                            </tr>
                            <tr data-estado="inactivo">
                                <td>2</td>
                                <td>María Gómez</td>
                                <td><span class="badge bg-secondary">Inactivo</span></td>
                                <td><button class="btn btn-sm btn-info">Ver</button></td>
                            </tr>
                            <tr data-estado="pendiente">
                                <td>3</td>
                                <td>Carlos Ruiz</td>
                                <td><span class="badge bg-warning">Pendiente</span></td>
                                <td><button class="btn btn-sm btn-info">Ver</button></td>
                            </tr>
                            <tr data-estado="activo">
                                <td>4</td>
                                <td>Ana López</td>
                                <td><span class="badge bg-success">Activo</span></td>
                                <td><button class="btn btn-sm btn-info">Ver</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- CONSOLA DE LOGS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-terminal"></i> Consola de Logs
                        </h4>
                        <div>
                            <button class="btn btn-sm btn-light" id="btn-iniciar-todas">
                                <i class="fas fa-rocket"></i> Probar Todos los Módulos
                            </button>
                            <button class="btn btn-sm btn-light" id="btn-limpiar-logs">
                                <i class="fas fa-trash"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body bg-dark text-light p-0">
                    <div id="consola-logs" class="p-3">
                        <div class="log-entry text-success">✅ Sistema de pruebas listo</div>
                        <div class="log-entry text-info">ℹ️ Haz clic en "Probar Todos los Módulos" para comenzar</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RESULTADOS DE PRUEBAS -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-check"></i> Resultados de Pruebas
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Módulo</th>
                                <th>Estado</th>
                                <th>Funciones Probadas</th>
                                <th>Errores</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>OffCanvas</td>
                                <td><span id="result-offcanvas" class="badge bg-secondary">Pendiente</span></td>
                                <td><span id="funciones-offcanvas">0/5</span></td>
                                <td><span id="errores-offcanvas" class="badge bg-success">0</span></td>
                            </tr>
                            <tr>
                                <td>Reportes</td>
                                <td><span id="result-reportes" class="badge bg-secondary">Pendiente</span></td>
                                <td><span id="funciones-reportes">0/3</span></td>
                                <td><span id="errores-reportes" class="badge bg-success">0</span></td>
                            </tr>
                            <tr>
                                <td>Horarios</td>
                                <td><span id="result-horario" class="badge bg-secondary">Pendiente</span></td>
                                <td><span id="funciones-horario">0/6</span></td>
                                <td><span id="errores-horario" class="badge bg-success">0</span></td>
                            </tr>
                            <tr>
                                <td>Tienda</td>
                                <td><span id="result-tienda" class="badge bg-secondary">Pendiente</span></td>
                                <td><span id="funciones-tienda">0/4</span></td>
                                <td><span id="errores-tienda" class="badge bg-success">0</span></td>
                            </tr>
                            <tr>
                                <td>Mapa</td>
                                <td><span id="result-mapa" class="badge bg-secondary">Pendiente</span></td>
                                <td><span id="funciones-mapa">0/4</span></td>
                                <td><span id="errores-mapa" class="badge bg-success">0</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
                 DEPENDENCIAS
============================================ -->
<!-- jQuery (requerido por horario-selector.js) -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- Leaflet JS (requerido por mapa-module.js) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ============================================
                 MÓDULOS A PROBAR
============================================ -->
<!-- NOTA: Asegúrate de que estas rutas sean correctas en tu servidor -->
<script src="/web/js/gedOffcanvas-module.js"></script>
<script src="/web/js/modules/reportes-module.js"></script>
<script src="/web/js/modules/horario-selector.js"></script>
<script src="/web/js/tienda-module.js"></script>
<script src="/web/js/modules/mapa-module.js"></script>

<!-- ============================================
                 SISTEMA DE PRUEBAS
============================================ -->
<script>
class TestSuite {
    constructor() {
        this.logger = new Logger();
        this.results = {
            offcanvas: { tested: false, functions: 0, totalFunctions: 5, errors: 0 },
            reportes: { tested: false, functions: 0, totalFunctions: 3, errors: 0 },
            horario: { tested: false, functions: 0, totalFunctions: 6, errors: 0 },
            tienda: { tested: false, functions: 0, totalFunctions: 4, errors: 0 },
            mapa: { tested: false, functions: 0, totalFunctions: 4, errors: 0 }
        };
        
        this.modules = {
            offcanvas: null,
            reportes: null,
            horario: null,
            tienda: null,
            mapa: null
        };
    }
    
    log(mensaje, tipo = 'info') {
        this.logger.log(mensaje, tipo);
    }
    
    updateResult(module, key, value) {
        this.results[module][key] = value;
        this.updateUI(module);
    }
    
    updateUI(module) {
        const result = this.results[module];
        const estado = result.tested ? (result.errors === 0 ? 'success' : 'warning') : 'secondary';
        const texto = result.tested ? (result.errors === 0 ? '✅ Pasó' : '⚠️ Con errores') : 'Pendiente';
        
        // Actualizar badges
        document.getElementById(`result-${module}`).className = `badge bg-${estado}`;
        document.getElementById(`result-${module}`).textContent = texto;
        document.getElementById(`funciones-${module}`).textContent = `${result.functions}/${result.totalFunctions}`;
        document.getElementById(`errores-${module}`).className = result.errors === 0 ? 'badge bg-success' : 'badge bg-danger';
        document.getElementById(`errores-${module}`).textContent = result.errors;
        
        // Actualizar estado en tarjetas
        const statusElement = document.getElementById(`status-${module}`);
        const textElement = document.getElementById(`text-status-${module}`);
        
        if (statusElement && textElement) {
            statusElement.className = `module-status status-${estado}`;
            textElement.textContent = result.tested ? 
                (result.errors === 0 ? 'Funciona correctamente' : `Tiene ${result.errors} error(es)`) :
                'No probado';
        }
    }
    
    async testAllModules() {
        this.log('🚀 Iniciando pruebas exhaustivas de todos los módulos...', 'success');
        
        try {
            await this.testOffCanvas();
            await this.testReportes();
            await this.testHorario();
            await this.testTienda();
            await this.testMapa();
            
            // Resumen final
            const totalTests = Object.values(this.results).reduce((sum, r) => sum + r.functions, 0);
            const totalErrors = Object.values(this.results).reduce((sum, r) => sum + r.errors, 0);
            
            this.log(`📊 RESULTADOS FINALES: ${totalTests} funciones probadas, ${totalErrors} errores`, 
                    totalErrors === 0 ? 'success' : 'warning');
            
        } catch (error) {
            this.log(`❌ Error en pruebas: ${error.message}`, 'error');
        }
    }
    
    async testOffCanvas() {
        this.log('🧪 Probando módulo OffCanvas...', 'info');
        
        try {
            // Verificar que la clase existe
            if (typeof OffCanvasSidebar === 'undefined') {
                throw new Error('Clase OffCanvasSidebar no encontrada');
            }
            
            // Crear instancia
            this.modules.offcanvas = new OffCanvasSidebar();
            this.updateResult('offcanvas', 'functions', 1);
            
            // Probar init()
            this.modules.offcanvas.init();
            this.updateResult('offcanvas', 'functions', 2);
            
            // Probar crear elementos DOM
            const sidebarExists = document.querySelector('.ged-offcanvas-sidebar') !== null;
            if (!sidebarExists) {
                throw new Error('No se creó el sidebar en el DOM');
            }
            this.updateResult('offcanvas', 'functions', 3);
            
            // Probar open()
            this.modules.offcanvas.open();
            this.updateResult('offcanvas', 'functions', 4);
            
            // Probar close()
            setTimeout(() => {
                this.modules.offcanvas.close();
                this.updateResult('offcanvas', 'functions', 5);
                this.updateResult('offcanvas', 'tested', true);
                this.log('✅ Módulo OffCanvas probado exitosamente', 'success');
            }, 500);
            
        } catch (error) {
            this.updateResult('offcanvas', 'errors', this.results.offcanvas.errors + 1);
            this.updateResult('offcanvas', 'tested', true);
            this.log(`❌ Error en OffCanvas: ${error.message}`, 'error');
        }
    }
    
    async testReportes() {
        this.log('🧪 Probando módulo Reportes...', 'info');
        
        try {
            // Verificar que la clase existe
            if (typeof ReportesModule === 'undefined') {
                throw new Error('Clase ReportesModule no encontrada');
            }
            
            // Crear instancia
            this.modules.reportes = new ReportesModule();
            this.updateResult('reportes', 'functions', 1);
            
            // Verificar que se inicializó
            if (!window.reportesModule) {
                throw new Error('No se creó reportesModule global');
            }
            this.updateResult('reportes', 'functions', 2);
            
            // Probar filtrarPorEstado
            this.modules.reportes.filtrarPorEstado('activo');
            this.updateResult('reportes', 'functions', 3);
            
            this.updateResult('reportes', 'tested', true);
            this.log('✅ Módulo Reportes probado exitosamente', 'success');
            
        } catch (error) {
            this.updateResult('reportes', 'errors', this.results.reportes.errors + 1);
            this.updateResult('reportes', 'tested', true);
            this.log(`❌ Error en Reportes: ${error.message}`, 'error');
        }
    }
    
    async testHorario() {
        this.log('🧪 Probando módulo Horario...', 'info');
        
        try {
            // Verificar que la clase existe
            if (typeof HorarioModule === 'undefined') {
                throw new Error('Clase HorarioModule no encontrada');
            }
            
            // Crear HTML para el selector
            this.createHorarioGrid();
            
            // Crear instancia
            this.modules.horario = new HorarioModule();
            this.updateResult('horario', 'functions', 1);
            
            // Verificar inicialización
            if (!window.horarioModuleInstance) {
                throw new Error('No se creó horarioModuleInstance');
            }
            this.updateResult('horario', 'functions', 2);
            
            // Probar toggleHorario
            this.modules.horario.toggleHorario('lunes', 8);
            this.updateResult('horario', 'functions', 3);
            
            // Probar actualizarVistaPrevia
            this.modules.horario.actualizarVistaPrevia();
            this.updateResult('horario', 'functions', 4);
            
            // Probar seleccionarRango
            this.modules.horario.seleccionarRango('manana');
            this.updateResult('horario', 'functions', 5);
            
            // Probar limpiarTodo
            this.modules.horario.limpiarTodo();
            this.updateResult('horario', 'functions', 6);
            
            this.updateResult('horario', 'tested', true);
            this.log('✅ Módulo Horario probado exitosamente', 'success');
            
        } catch (error) {
            this.updateResult('horario', 'errors', this.results.horario.errors + 1);
            this.updateResult('horario', 'tested', true);
            this.log(`❌ Error en Horario: ${error.message}`, 'error');
        }
    }
    
    async testTienda() {
        this.log('🧪 Probando módulo Tienda...', 'info');
        
        try {
            // Verificar que la clase existe
            if (typeof TiendaModule === 'undefined') {
                throw new Error('Clase TiendaModule no encontrada');
            }
            
            // Crear instancia
            this.modules.tienda = new TiendaModule();
            this.updateResult('tienda', 'functions', 1);
            
            // Verificar que se inicializó
            if (!window.tiendaModule) {
                throw new Error('No se creó tiendaModule global');
            }
            this.updateResult('tienda', 'functions', 2);
            
            // Probar checkTiendaAccess
            this.modules.tienda.checkTiendaAccess();
            this.updateResult('tienda', 'functions', 3);
            
            // Probar trackEvent
            this.modules.tienda.trackEvent('test', 'test-location');
            this.updateResult('tienda', 'functions', 4);
            
            this.updateResult('tienda', 'tested', true);
            this.log('✅ Módulo Tienda probado exitosamente', 'success');
            
        } catch (error) {
            this.updateResult('tienda', 'errors', this.results.tienda.errors + 1);
            this.updateResult('tienda', 'tested', true);
            this.log(`❌ Error en Tienda: ${error.message}`, 'error');
        }
    }
    
    async testMapa() {
        this.log('🧪 Probando módulo Mapa...', 'info');
        
        try {
            // Verificar que la clase existe
            if (typeof MapaModule === 'undefined') {
                throw new Error('Clase MapaModule no encontrada');
            }
            
            // Crear instancia
            this.modules.mapa = new MapaModule('seleccion');
            this.updateResult('mapa', 'functions', 1);
            
            // Probar initMapaSeleccion
            this.modules.mapa.initMapaSeleccion();
            this.updateResult('mapa', 'functions', 2);
            
            // Probar agregarMarcador
            this.modules.mapa.agregarMarcador(10.480594, -66.903600, 'Prueba');
            this.updateResult('mapa', 'functions', 3);
            
            // Probar limpiarMapa
            this.modules.mapa.limpiarMapa();
            this.updateResult('mapa', 'functions', 4);
            
            this.updateResult('mapa', 'tested', true);
            this.log('✅ Módulo Mapa probado exitosamente', 'success');
            
        } catch (error) {
            this.updateResult('mapa', 'errors', this.results.mapa.errors + 1);
            this.updateResult('mapa', 'tested', true);
            this.log(`❌ Error en Mapa: ${error.message}`, 'error');
        }
    }
    
    createHorarioGrid() {
        const grid = document.getElementById('horario-grid');
        if (!grid) return;
        
        const dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        const horas = [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
        
        let html = '<table class="table table-bordered table-sm"><thead><tr><th>Hora</th>';
        
        // Encabezados de días
        dias.forEach(dia => {
            html += `<th>${dia.charAt(0).toUpperCase() + dia.slice(1)}</th>`;
        });
        
        html += '</tr></thead><tbody>';
        
        // Filas de horas
        horas.forEach(hora => {
            html += `<tr><td>${hora}:00</td>`;
            
            dias.forEach(dia => {
                html += `<td>
                    <div class="horario-cell" id="${dia}_${hora}" data-dia="${dia}" data-hora="${hora}">
                        <i class="fas fa-times text-muted"></i>
                    </div>
                </td>`;
            });
            
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        grid.innerHTML = html;
    }
}

class Logger {
    constructor() {
        this.consola = document.getElementById('consola-logs');
        this.log('✅ Sistema de pruebas inicializado', 'success');
    }
    
    log(mensaje, tipo = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const iconos = {
            info: '🔵',
            success: '✅',
            warning: '⚠️',
            error: '🚨',
            debug: '🐛',
            test: '🧪'
        };
        
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry text-${tipo === 'error' ? 'danger' : tipo === 'success' ? 'success' : tipo === 'warning' ? 'warning' : 'light'}`;
        logEntry.innerHTML = `${iconos[tipo] || '🔵'} [${timestamp}] ${mensaje}`;
        
        this.consola.appendChild(logEntry);
        this.consola.scrollTop = this.consola.scrollHeight;
        
        console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
    }
    
    limpiar() {
        this.consola.innerHTML = '<div class="log-entry text-success">✅ Logs limpiados</div>';
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    const testSuite = new TestSuite();
    
    // Botón para probar todos los módulos
    document.getElementById('btn-iniciar-todas').addEventListener('click', function() {
        testSuite.testAllModules();
    });
    
    // Botones individuales
    document.getElementById('btn-test-offcanvas').addEventListener('click', function() {
        testSuite.testOffCanvas();
    });
    
    document.getElementById('btn-test-reportes').addEventListener('click', function() {
        testSuite.testReportes();
    });
    
    document.getElementById('btn-test-horario').addEventListener('click', function() {
        testSuite.testHorario();
    });
    
    document.getElementById('btn-test-tienda').addEventListener('click', function() {
        testSuite.testTienda();
    });
    
    document.getElementById('btn-test-mapa').addEventListener('click', function() {
        testSuite.testMapa();
    });
    
    // Botones de funcionalidad específica
    document.getElementById('btn-toggle-offcanvas').addEventListener('click', function() {
        if (testSuite.modules.offcanvas) {
            if (testSuite.modules.offcanvas.isOpen) {
                testSuite.modules.offcanvas.close();
                testSuite.log('🔒 Sidebar cerrado', 'info');
            } else {
                testSuite.modules.offcanvas.open();
                testSuite.log('🔓 Sidebar abierto', 'info');
            }
        } else {
            testSuite.log('❌ OffCanvas no inicializado', 'error');
        }
    });
    
    document.getElementById('btn-filtrar-reportes').addEventListener('click', function() {
        const estado = document.getElementById('select-estado-reporte').value;
        if (testSuite.modules.reportes) {
            testSuite.modules.reportes.filtrarPorEstado(estado);
            testSuite.log(`📊 Filtrando por estado: ${estado}`, 'info');
        }
    });
    
    document.getElementById('btn-crear-selector').addEventListener('click', function() {
        testSuite.createHorarioGrid();
        testSuite.log('📅 Selector de horarios creado', 'success');
    });
    
    document.getElementById('btn-limpiar-logs').addEventListener('click', function() {
        testSuite.logger.limpiar();
    });
    
    // Iniciar pruebas automáticas después de 2 segundos
    setTimeout(() => {
        testSuite.log('⏱️ Las pruebas automáticas comenzarán en 3 segundos...', 'info');
        setTimeout(() => {
            testSuite.testAllModules();
        }, 3000);
    }, 2000);
});
</script>
</body>
</html>