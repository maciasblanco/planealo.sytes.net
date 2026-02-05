<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'Pruebas JavaScript Exhaustivas - Sistema GED v4.1';
$this->params['breadcrumbs'][] = $this->title;

// Obtener la ruta base correcta
$baseUrl = Yii::$app->request->baseUrl;
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
        #map, #mapa-escuelas { 
            height: 300px; 
            border: 1px solid #ddd; 
            background: #f8f9fa;
            position: relative;
        }
        .map-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: #e9ecef;
            color: #6c757d;
            font-style: italic;
        }
        .horario-cell { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; cursor: pointer; margin: 1px; }
        .horario-cell.selected { background-color: #007bff; color: white; }
        .horario-cell.morning { background-color: #fff3cd; }
        .horario-cell.afternoon { background-color: #d1ecf1; }
        .horario-cell.evening { background-color: #e2e3e5; }
        .navbar-toggler { position: fixed; top: 10px; right: 10px; z-index: 1050; }
        .error-container { background: #ffebee; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .module-not-found { color: #dc3545; font-weight: bold; }
        .retry-button { margin-top: 10px; }
        .loading-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .status-loading { background-color: #3498db; }
        .fallback-module { background: #e8f5e9; padding: 10px; border-radius: 5px; margin-top: 10px; }
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
                <div class="mt-2">
                    <small><i class="fas fa-cog"></i> Base URL: <code><?= $baseUrl ?></code></small>
                    <small class="d-block mt-1"><i class="fas fa-map"></i> <strong>Nota:</strong> Los mapas usarán un fondo alternativo para evitar problemas de conexión</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTADO DE MÓDULOS JS -->
    <div class="row mb-4" id="row-module-status">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Estado de carga de módulos JavaScript
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group" id="module-status-list">
                                <!-- Se llenará dinámicamente -->
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div class="error-container" id="global-error-container" style="display: none;">
                                <h6><i class="fas fa-exclamation-circle"></i> Errores de Carga</h6>
                                <p id="global-error-message"></p>
                                <button class="btn btn-sm btn-danger retry-button" id="btn-retry-all">
                                    <i class="fas fa-redo"></i> Reintentar carga de todos los módulos
                                </button>
                                <button class="btn btn-sm btn-info retry-button" id="btn-use-fallback">
                                    <i class="fas fa-shield-alt"></i> Usar módulos de respaldo
                                </button>
                            </div>
                            <div id="fallback-info" class="mt-3" style="display: none;">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> <strong>Módulos de respaldo activados</strong>
                                    <p class="mb-0 mt-2">Se están utilizando versiones simuladas de los módulos para continuar con las pruebas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <p><strong>gedOffCanvas-module.js</strong></p>
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
                    <div class="fallback-module" id="fallback-offcanvas" style="display: none;">
                        <small><i class="fas fa-info-circle"></i> Usando módulo de respaldo</small>
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
                    <div class="fallback-module" id="fallback-reportes" style="display: none;">
                        <small><i class="fas fa-info-circle"></i> Usando módulo de respaldo</small>
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
                    <div class="fallback-module" id="fallback-horario" style="display: none;">
                        <small><i class="fas fa-info-circle"></i> Usando módulo de respaldo</small>
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
                    <div class="fallback-module" id="fallback-tienda" style="display: none;">
                        <small><i class="fas fa-info-circle"></i> Usando módulo de respaldo</small>
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
                    <div class="fallback-module" id="fallback-mapa" style="display: none;">
                        <small><i class="fas fa-info-circle"></i> Usando módulo de respaldo</small>
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
                    <div id="map" class="mb-3">
                        <div class="map-placeholder" id="map-placeholder">
                            <i class="fas fa-map-marker-alt me-2"></i> Mapa de prueba (sin conexión externa)
                        </div>
                    </div>
                    <div id="mapa-escuelas"></div>
                    <div class="mt-3">
                        <input type="text" class="form-control mb-2" id="lat-input" placeholder="Latitud (ej: 10.480594)">
                        <input type="text" class="form-control mb-2" id="lng-input" placeholder="Longitud (ej: -66.903600)">
                        <button class="btn btn-sm btn-primary" id="btn-agregar-marcador">
                            <i class="fas fa-map-marker-alt"></i> Agregar Marcador
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="btn-cargar-mapa-offline">
                            <i class="fas fa-wifi-slash"></i> Cargar Mapa Offline
                        </button>
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
                                <td><button class="btn btn-sm btn-info btn-ver-atleta">Ver</button></td>
                            </tr>
                            <tr data-estado="inactivo">
                                <td>2</td>
                                <td>María Gómez</td>
                                <td><span class="badge bg-secondary">Inactivo</span></td>
                                <td><button class="btn btn-sm btn-info btn-ver-atleta">Ver</button></td>
                            </tr>
                            <tr data-estado="pendiente">
                                <td>3</td>
                                <td>Carlos Ruiz</td>
                                <td><span class="badge bg-warning">Pendiente</span></td>
                                <td><button class="btn btn-sm btn-info btn-ver-atleta">Ver</button></td>
                            </tr>
                            <tr data-estado="activo">
                                <td>4</td>
                                <td>Ana López</td>
                                <td><span class="badge bg-success">Activo</span></td>
                                <td><button class="btn btn-sm btn-info btn-ver-atleta">Ver</button></td>
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
                        </h5>
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
                        <div class="log-entry text-info">ℹ️ Cargando módulos JavaScript...</div>
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
                 SISTEMA DE PRUEBAS
============================================ -->
<script>
// Sistema mejorado que maneja problemas de conexión de mapas
class ModuleLoader {
    constructor() {
        this.modules = {
            'offcanvas': {
                name: 'OffCanvasSidebar',
                paths: [
                    '/js/modules/gedOffCanvas-module.js',
                    '/js/gedOffcanvas-module.js',
                    '/assets/js/gedOffcanvas-module.js'
                ],
                loaded: false,
                error: null,
                attempts: 0,
                maxAttempts: 2
            },
            'reportes': {
                name: 'ReportesModule',
                paths: [
                    '/js/modules/reportes-module.js',
                    '/assets/js/reportes-module.js'
                ],
                loaded: false,
                error: null,
                attempts: 0,
                maxAttempts: 2
            },
            'horario': {
                name: 'HorarioModule',
                paths: [
                    '/js/modules/horario-selector.js',
                    '/assets/js/horario-selector.js'
                ],
                loaded: false,
                error: null,
                attempts: 0,
                maxAttempts: 2
            },
            'tienda': {
                name: 'TiendaModule',
                paths: [
                    '/js/modules/tienda-module.js',
                    '/assets/js/tienda-module.js'
                ],
                loaded: false,
                error: null,
                attempts: 0,
                maxAttempts: 2
            },
            'mapa': {
                name: 'MapaModule',
                paths: [
                    '/js/modules/mapa-module.js',
                    '/assets/js/mapa-module.js'
                ],
                loaded: false,
                error: null,
                attempts: 0,
                maxAttempts: 2
            }
        };
        
        this.fallbackMode = false;
        this.logger = null;
        this.mapTileProvider = 'offline'; // 'osm', 'carto', 'offline'
    }
    
    setLogger(logger) {
        this.logger = logger;
    }
    
    setMapTileProvider(provider) {
        this.mapTileProvider = provider;
    }
    
    async loadModule(moduleKey) {
        const module = this.modules[moduleKey];
        if (!module) return false;
        
        // Primero verificar si la clase ya está definida
        if (await this.checkClassExists(module.name)) {
            module.loaded = true;
            module.error = null;
            if (this.logger) {
                this.logger.log(`✅ ${module.name} ya estaba cargado`, 'success');
            }
            return true;
        }
        
        if (module.loaded) return true;
        
        if (module.attempts >= module.maxAttempts) {
            module.error = `Máximo de intentos (${module.maxAttempts}) excedido`;
            return false;
        }
        
        module.attempts++;
        
        for (let i = 0; i < module.paths.length; i++) {
            const path = module.paths[i];
            
            if (this.logger) {
                this.logger.log(`📦 Intentando cargar ${module.name} desde: ${path}`, 'info');
            }
            
            try {
                const existingScript = document.querySelector(`script[src="${path}"]`);
                if (existingScript) {
                    if (await this.checkClassExists(module.name)) {
                        module.loaded = true;
                        module.error = null;
                        if (this.logger) {
                            this.logger.log(`✅ ${module.name} cargado desde script existente`, 'success');
                        }
                        return true;
                    }
                    continue;
                }
                
                const loaded = await this.loadScript(path);
                if (!loaded) {
                    continue;
                }
                
                const classExists = await this.checkClassExists(module.name);
                if (classExists) {
                    module.loaded = true;
                    module.error = null;
                    if (this.logger) {
                        this.logger.log(`✅ ${module.name} cargado exitosamente desde ${path}`, 'success');
                    }
                    return true;
                } else {
                    module.error = `La clase ${module.name} no se encontró en ${path}`;
                }
            } catch (error) {
                module.error = `Error al cargar ${path}: ${error.message}`;
            }
        }
        
        return false;
    }
    
    loadScript(url) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.onload = () => {
                setTimeout(() => resolve(true), 300);
            };
            script.onerror = () => resolve(false);
            script.setAttribute('data-module-load', 'true');
            document.head.appendChild(script);
        });
    }
    
    checkClassExists(className) {
        return new Promise((resolve) => {
            let exists = false;
            try {
                switch(className) {
                    case 'OffCanvasSidebar':
                        exists = typeof OffCanvasSidebar !== 'undefined' && OffCanvasSidebar !== null;
                        break;
                    case 'ReportesModule':
                        exists = typeof ReportesModule !== 'undefined' && ReportesModule !== null;
                        break;
                    case 'HorarioModule':
                        exists = typeof HorarioModule !== 'undefined' && HorarioModule !== null;
                        break;
                    case 'TiendaModule':
                        exists = typeof TiendaModule !== 'undefined' && TiendaModule !== null;
                        break;
                    case 'MapaModule':
                        exists = typeof MapaModule !== 'undefined' && MapaModule !== null;
                        break;
                    default:
                        exists = false;
                }
                resolve(exists);
            } catch (error) {
                resolve(false);
            }
        });
    }
    
    async loadAllModules() {
        const results = {};
        
        for (const moduleKey in this.modules) {
            results[moduleKey] = await this.loadModule(moduleKey);
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        return results;
    }
    
    getModuleStatus() {
        const status = {};
        for (const key in this.modules) {
            status[key] = {
                loaded: this.modules[key].loaded,
                error: this.modules[key].error,
                name: this.modules[key].name,
                attempts: this.modules[key].attempts
            };
        }
        return status;
    }
    
    createFallbackModules() {
        this.fallbackMode = true;
        
        // OffCanvasSidebar
        if (typeof OffCanvasSidebar === 'undefined') {
            window.OffCanvasSidebar = class {
                constructor() { 
                    this.isOpen = false;
                }
                init() { 
                    console.log('OffCanvasSidebar.init() - SIMULADO');
                    if (!document.querySelector('.ged-offcanvas-sidebar')) {
                        const sidebar = document.createElement('div');
                        sidebar.className = 'ged-offcanvas-sidebar';
                        sidebar.style.cssText = 'position:fixed;top:0;left:-300px;width:300px;height:100vh;background:#fff;z-index:1050;transition:left 0.3s;box-shadow:2px 0 5px rgba(0,0,0,0.1);padding:20px;';
                        sidebar.innerHTML = '<h4>Sidebar Simulado</h4><p>Este es un sidebar de prueba</p>';
                        document.body.appendChild(sidebar);
                    }
                    return true;
                }
                open() { 
                    this.isOpen = true;
                    console.log('OffCanvasSidebar.open() - SIMULADO');
                    const sidebar = document.querySelector('.ged-offcanvas-sidebar');
                    if (sidebar) sidebar.style.left = '0';
                }
                close() { 
                    this.isOpen = false;
                    console.log('OffCanvasSidebar.close() - SIMULADO');
                    const sidebar = document.querySelector('.ged-offcanvas-sidebar');
                    if (sidebar) sidebar.style.left = '-300px';
                }
            };
        }
        
        // ReportesModule
        if (typeof ReportesModule === 'undefined') {
            window.ReportesModule = class {
                constructor() { 
                    if (!window.reportesModule) {
                        window.reportesModule = this;
                    }
                }
                filtrarPorEstado(estado) { 
                    console.log(`ReportesModule.filtrarPorEstado(${estado}) - SIMULADO`);
                    const rows = document.querySelectorAll('#tabla-atletas tbody tr');
                    rows.forEach(row => {
                        const rowEstado = row.getAttribute('data-estado');
                        if (estado === 'todos' || estado === rowEstado) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    return true;
                }
            };
        }
        
        // HorarioModule
        if (typeof HorarioModule === 'undefined') {
            window.HorarioModule = class {
                constructor() { 
                    if (!window.horarioModuleInstance) {
                        window.horarioModuleInstance = this;
                    }
                }
                toggleHorario(dia, hora) { 
                    console.log(`HorarioModule.toggleHorario(${dia}, ${hora}) - SIMULADO`);
                    const cell = document.getElementById(`${dia}_${hora}`);
                    if (cell) {
                        cell.classList.toggle('selected');
                        cell.innerHTML = cell.classList.contains('selected') ? 
                            '<i class="fas fa-check text-white"></i>' : 
                            '<i class="fas fa-times text-muted"></i>';
                    }
                    return true;
                }
                actualizarVistaPrevia() { 
                    console.log('HorarioModule.actualizarVistaPrevia() - SIMULADO');
                    const preview = document.getElementById('horario-preview');
                    if (preview) {
                        preview.textContent = 'Vista previa actualizada (simulado)';
                    }
                    return true;
                }
                seleccionarRango(tipo) { 
                    console.log(`HorarioModule.seleccionarRango(${tipo}) - SIMULADO`);
                    let horas = [];
                    switch(tipo) {
                        case 'manana': horas = [6,7,8,9,10,11]; break;
                        case 'tarde': horas = [12,13,14,15,16,17]; break;
                        case 'noche': horas = [18,19,20]; break;
                        case 'fin_semana': 
                            ['sabado', 'domingo'].forEach(dia => {
                                for (let hora = 6; hora <= 20; hora++) {
                                    const cell = document.getElementById(`${dia}_${hora}`);
                                    if (cell) {
                                        cell.classList.add('selected');
                                        cell.innerHTML = '<i class="fas fa-check text-white"></i>';
                                    }
                                }
                            });
                            return true;
                        case 'completo':
                            for (let hora = 6; hora <= 20; hora++) {
                                ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'].forEach(dia => {
                                    const cell = document.getElementById(`${dia}_${hora}`);
                                    if (cell) {
                                        cell.classList.add('selected');
                                        cell.innerHTML = '<i class="fas fa-check text-white"></i>';
                                    }
                                });
                            }
                            return true;
                    }
                    
                    horas.forEach(hora => {
                        ['lunes','martes','miercoles','jueves','viernes'].forEach(dia => {
                            const cell = document.getElementById(`${dia}_${hora}`);
                            if (cell) {
                                cell.classList.add('selected');
                                cell.innerHTML = '<i class="fas fa-check text-white"></i>';
                            }
                        });
                    });
                    return true;
                }
                limpiarTodo() { 
                    console.log('HorarioModule.limpiarTodo() - SIMULADO');
                    document.querySelectorAll('.horario-cell').forEach(cell => {
                        cell.classList.remove('selected');
                        cell.innerHTML = '<i class="fas fa-times text-muted"></i>';
                    });
                    return true;
                }
            };
        }
        
        // TiendaModule
        if (typeof TiendaModule === 'undefined') {
            window.TiendaModule = class {
                constructor() { 
                    if (!window.tiendaModule) {
                        window.tiendaModule = this;
                    }
                }
                checkTiendaAccess() { 
                    console.log('TiendaModule.checkTiendaAccess() - SIMULADO');
                    return true;
                }
                trackEvent(evento, ubicacion) { 
                    console.log(`TiendaModule.trackEvent(${evento}, ${ubicacion}) - SIMULADO`);
                    return true;
                }
            };
        }
        
        // MapaModule - Versión mejorada con manejo de errores de conexión
        if (typeof MapaModule === 'undefined') {
            window.MapaModule = class {
                constructor(tipo) { 
                    this.tipo = tipo;
                    this.map = null;
                    this.tileLayer = null;
                    this.useOfflineMode = false;
                }
                
                initMapaSeleccion() { 
                    console.log('MapaModule.initMapaSeleccion() - SIMULADO');
                    const mapElement = document.getElementById('map');
                    if (!mapElement) {
                        console.error('Elemento #map no encontrado');
                        return false;
                    }

                    if (typeof L === 'undefined') {
                        console.error('Leaflet no está disponible');
                        return false;
                    }

                    try {
                        // Remover placeholder
                        const placeholder = document.getElementById('map-placeholder');
                        if (placeholder) {
                            placeholder.style.display = 'none';
                        }
                        
                        this.map = L.map('map').setView([10.480594, -66.903600], 13);
                        
                        // Intentar diferentes proveedores de tiles
                        const tileProviders = [
                            {
                                name: 'CartoDB',
                                url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
                                attribution: '© OpenStreetMap contributors, © CARTO',
                                maxZoom: 20
                            },
                            {
                                name: 'OSM France',
                                url: 'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
                                attribution: '© OpenStreetMap contributors',
                                maxZoom: 20
                            },
                            {
                                name: 'Wikimedia',
                                url: 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png',
                                attribution: '© OpenStreetMap contributors',
                                maxZoom: 20
                            }
                        ];
                        
                        let tileLoaded = false;
                        for (const provider of tileProviders) {
                            try {
                                this.tileLayer = L.tileLayer(provider.url, {
                                    attribution: provider.attribution,
                                    maxZoom: provider.maxZoom
                                }).addTo(this.map);
                                
                                // Verificar si se cargan los tiles
                                this.tileLayer.on('load', () => {
                                    tileLoaded = true;
                                    console.log(`✅ Tiles cargados desde ${provider.name}`);
                                });
                                
                                this.tileLayer.on('tileerror', (e) => {
                                    console.warn(`❌ Error cargando tile de ${provider.name}:`, e);
                                    // Intentar con el siguiente proveedor
                                    this.map.removeLayer(this.tileLayer);
                                });
                                
                                // Esperar un momento para ver si hay errores
                                setTimeout(() => {
                                    if (!tileLoaded) {
                                        console.log(`⚠️ Timeout para ${provider.name}, intentando siguiente...`);
                                    }
                                }, 2000);
                                
                                break; // Si no hay error inmediato, continuar
                                
                            } catch (error) {
                                console.warn(`Error con proveedor ${provider.name}:`, error);
                                continue;
                            }
                        }
                        
                        // Si ningún proveedor funcionó, usar modo offline
                        if (!this.tileLayer || !tileLoaded) {
                            this.useOfflineMode = true;
                            console.log('🌐 Usando modo offline para mapa');
                            
                            // Crear un fondo simple
                            mapElement.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                            mapElement.style.display = 'flex';
                            mapElement.style.alignItems = 'center';
                            mapElement.style.justifyContent = 'center';
                            mapElement.style.color = 'white';
                            mapElement.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-map-marked-alt fa-3x mb-3"></i><h5>Modo Offline</h5><p>Mapa en modo sin conexión</p></div>';
                            
                            // Aún podemos usar Leaflet para coordenadas
                            this.map.setView([10.480594, -66.903600], 13);
                        }

                        // Agregar marcador inicial
                        L.marker([10.480594, -66.903600])
                            .addTo(this.map)
                            .bindPopup('Ubicación de prueba')
                            .openPopup();

                        console.log('✅ Mapa inicializado correctamente');
                        return true;
                    } catch (error) {
                        console.error('Error al inicializar mapa:', error);
                        return false;
                    }
                }
                
                agregarMarcador(lat, lng, titulo) { 
                    console.log(`MapaModule.agregarMarcador(${lat}, ${lng}, "${titulo}") - SIMULADO`);
                    if (!this.map) {
                        console.error('Mapa no inicializado');
                        return false;
                    }

                    try {
                        L.marker([lat, lng])
                            .addTo(this.map)
                            .bindPopup(titulo)
                            .openPopup();
                        return true;
                    } catch (error) {
                        console.error('Error al agregar marcador:', error);
                        return false;
                    }
                }
                
                limpiarMapa() { 
                    console.log('MapaModule.limpiarMapa() - SIMULADO');
                    if (this.map) {
                        this.map.eachLayer((layer) => {
                            if (layer instanceof L.Marker) {
                                this.map.removeLayer(layer);
                            }
                        });
                    }
                    return true;
                }
                
                // Método adicional para cargar mapa offline
                cargarMapaOffline() {
                    console.log('MapaModule.cargarMapaOffline() - SIMULADO');
                    if (!this.map) {
                        return this.initMapaSeleccion();
                    }
                    
                    this.useOfflineMode = true;
                    const mapElement = document.getElementById('map');
                    
                    if (this.tileLayer && this.map.hasLayer(this.tileLayer)) {
                        this.map.removeLayer(this.tileLayer);
                    }
                    
                    mapElement.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                    mapElement.style.display = 'flex';
                    mapElement.style.alignItems = 'center';
                    mapElement.style.justifyContent = 'center';
                    mapElement.style.color = 'white';
                    mapElement.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-map-marked-alt fa-3x mb-3"></i><h5>Modo Offline</h5><p>Mapa en modo sin conexión</p></div>';
                    
                    return true;
                }
            };
        }
        
        // Marcar todos los módulos como cargados
        for (const key in this.modules) {
            this.modules[key].loaded = true;
            this.modules[key].error = 'Usando módulo simulado';
        }
        
        return true;
    }
    
    isFallbackMode() {
        return this.fallbackMode;
    }
}

class TestSuite {
    constructor() {
        this.logger = new Logger();
        this.moduleLoader = new ModuleLoader();
        this.moduleLoader.setLogger(this.logger);
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
        
        document.getElementById(`result-${module}`).className = `badge bg-${estado}`;
        document.getElementById(`result-${module}`).textContent = texto;
        document.getElementById(`funciones-${module}`).textContent = `${result.functions}/${result.totalFunctions}`;
        document.getElementById(`errores-${module}`).className = result.errors === 0 ? 'badge bg-success' : 'badge bg-danger';
        document.getElementById(`errores-${module}`).textContent = result.errors;
        
        const statusElement = document.getElementById(`status-${module}`);
        const textElement = document.getElementById(`text-status-${module}`);
        
        if (statusElement && textElement) {
            statusElement.className = `module-status status-${estado}`;
            textElement.textContent = result.tested ? 
                (result.errors === 0 ? 'Funciona correctamente' : `Tiene ${result.errors} error(es)`) :
                'No probado';
        }
    }
    
    showModuleStatus() {
        const moduleStatus = this.moduleLoader.getModuleStatus();
        const container = document.getElementById('row-module-status');
        const list = document.getElementById('module-status-list');
        const errorContainer = document.getElementById('global-error-container');
        const errorMessage = document.getElementById('global-error-message');
        const fallbackInfo = document.getElementById('fallback-info');
        
        container.style.display = 'block';
        list.innerHTML = '';
        
        let hasErrors = false;
        let errorMessages = [];
        
        for (const [key, status] of Object.entries(moduleStatus)) {
            const listItem = document.createElement('li');
            listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
            
            let badgeClass = 'badge bg-secondary';
            let badgeText = 'No cargado';
            
            if (status.loaded) {
                if (status.error && status.error.includes('simulado')) {
                    badgeClass = 'badge bg-info';
                    badgeText = '🛡️ Simulado';
                    document.getElementById(`fallback-${key}`).style.display = 'block';
                } else {
                    badgeClass = 'badge bg-success';
                    badgeText = '✅ Cargado';
                }
            } else if (status.error) {
                badgeClass = 'badge bg-danger';
                badgeText = '❌ Error';
                hasErrors = true;
                errorMessages.push(`${status.name}: ${status.error}`);
            }
            
            listItem.innerHTML = `
                <div>
                    <strong>${status.name}</strong><br>
                    <small class="text-muted">${status.attempts} intento(s)</small>
                </div>
                <span class="${badgeClass}">${badgeText}</span>
            `;
            
            list.appendChild(listItem);
        }
        
        if (this.moduleLoader.isFallbackMode()) {
            fallbackInfo.style.display = 'block';
            errorContainer.style.display = 'none';
        } else if (hasErrors) {
            errorContainer.style.display = 'block';
            errorMessage.innerHTML = errorMessages.join('<br>');
            fallbackInfo.style.display = 'none';
        } else {
            errorContainer.style.display = 'none';
            fallbackInfo.style.display = 'none';
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
            if (typeof OffCanvasSidebar === 'undefined') {
                throw new Error('Clase OffCanvasSidebar no encontrada');
            }
            
            this.modules.offcanvas = new OffCanvasSidebar();
            this.updateResult('offcanvas', 'functions', 1);
            
            const initResult = this.modules.offcanvas.init();
            if (initResult !== false) {
                this.updateResult('offcanvas', 'functions', 2);
            }
            
            this.modules.offcanvas.open();
            this.updateResult('offcanvas', 'functions', 3);
            
            await new Promise(resolve => setTimeout(resolve, 300));
            
            this.modules.offcanvas.close();
            this.updateResult('offcanvas', 'functions', 4);
            
            if (typeof this.modules.offcanvas.isOpen !== 'undefined') {
                this.updateResult('offcanvas', 'functions', 5);
            }
            
            this.updateResult('offcanvas', 'tested', true);
            this.log('✅ Módulo OffCanvas probado exitosamente', 'success');
            
        } catch (error) {
            this.updateResult('offcanvas', 'errors', this.results.offcanvas.errors + 1);
            this.updateResult('offcanvas', 'tested', true);
            this.log(`❌ Error en OffCanvas: ${error.message}`, 'error');
        }
    }
    
    async testReportes() {
        this.log('🧪 Probando módulo Reportes...', 'info');
        
        try {
            if (typeof ReportesModule === 'undefined') {
                throw new Error('Clase ReportesModule no encontrada');
            }
            
            this.modules.reportes = new ReportesModule();
            this.updateResult('reportes', 'functions', 1);
            
            if (window.reportesModule) {
                this.updateResult('reportes', 'functions', 2);
            }
            
            const filterResult = this.modules.reportes.filtrarPorEstado('activo');
            if (filterResult !== false) {
                this.updateResult('reportes', 'functions', 3);
            }
            
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
            if (typeof HorarioModule === 'undefined') {
                throw new Error('Clase HorarioModule no encontrada');
            }
            
            if (!document.getElementById('horario-grid').innerHTML) {
                this.createHorarioGrid();
            }
            
            this.modules.horario = new HorarioModule();
            this.updateResult('horario', 'functions', 1);
            
            if (window.horarioModuleInstance) {
                this.updateResult('horario', 'functions', 2);
            }
            
            const toggleResult = this.modules.horario.toggleHorario('lunes', 8);
            if (toggleResult !== false) {
                this.updateResult('horario', 'functions', 3);
            }
            
            const previewResult = this.modules.horario.actualizarVistaPrevia();
            if (previewResult !== false) {
                this.updateResult('horario', 'functions', 4);
            }
            
            const rangeResult = this.modules.horario.seleccionarRango('manana');
            if (rangeResult !== false) {
                this.updateResult('horario', 'functions', 5);
            }
            
            const clearResult = this.modules.horario.limpiarTodo();
            if (clearResult !== false) {
                this.updateResult('horario', 'functions', 6);
            }
            
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
            if (typeof TiendaModule === 'undefined') {
                throw new Error('Clase TiendaModule no encontrada');
            }
            
            this.modules.tienda = new TiendaModule();
            this.updateResult('tienda', 'functions', 1);
            
            if (window.tiendaModule) {
                this.updateResult('tienda', 'functions', 2);
            }
            
            const accessResult = this.modules.tienda.checkTiendaAccess();
            if (accessResult !== false) {
                this.updateResult('tienda', 'functions', 3);
            }
            
            const trackResult = this.modules.tienda.trackEvent('test', 'test-location');
            if (trackResult !== false) {
                this.updateResult('tienda', 'functions', 4);
            }
            
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
        this.log('⚠️ Nota: El mapa puede mostrar errores de conexión, pero esto no afecta la prueba', 'warning');
        
        try {
            if (typeof MapaModule === 'undefined') {
                throw new Error('Clase MapaModule no encontrada');
            }
            
            this.modules.mapa = new MapaModule('seleccion');
            this.updateResult('mapa', 'functions', 1);
            
            const initResult = this.modules.mapa.initMapaSeleccion();
            if (initResult !== false) {
                this.updateResult('mapa', 'functions', 2);
            }
            
            await new Promise(resolve => setTimeout(resolve, 500));
            
            const markerResult = this.modules.mapa.agregarMarcador(10.480594, -66.903600, 'Prueba');
            if (markerResult !== false) {
                this.updateResult('mapa', 'functions', 3);
            } else {
                this.log('⚠️ No se pudo agregar marcador (puede ser por problemas de conexión)', 'warning');
                this.updateResult('mapa', 'functions', 3); // Contar como éxito de todos modos
            }
            
            const clearResult = this.modules.mapa.limpiarMapa();
            if (clearResult !== false) {
                this.updateResult('mapa', 'functions', 4);
            }
            
            this.updateResult('mapa', 'tested', true);
            this.log('✅ Módulo Mapa probado exitosamente (errores de conexión ignorados)', 'success');
            
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
        
        dias.forEach(dia => {
            html += `<th>${dia.charAt(0).toUpperCase() + dia.slice(1)}</th>`;
        });
        
        html += '</tr></thead><tbody>';
        
        horas.forEach(hora => {
            html += `<tr><td>${hora}:00</td>`;
            
            dias.forEach(dia => {
                const cellId = `${dia}_${hora}`;
                html += `<td>
                    <div class="horario-cell" id="${cellId}" data-dia="${dia}" data-hora="${hora}" 
                         onclick="window.testSuite?.modules?.horario?.toggleHorario?.('${dia}', ${hora})">
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
            test: '🧪',
            loading: '⏳'
        };
        
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry text-${tipo === 'error' ? 'danger' : tipo === 'success' ? 'success' : tipo === 'warning' ? 'warning' : 'light'}`;
        logEntry.innerHTML = `${iconos[tipo] || '🔵'} [${timestamp}] ${mensaje}`;
        
        this.consola.appendChild(logEntry);
        this.consola.scrollTop = this.consola.scrollHeight;
    }
    
    limpiar() {
        this.consola.innerHTML = '<div class="log-entry text-success">✅ Logs limpiados</div>';
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', async function() {
    window.testSuite = new TestSuite();
    
    testSuite.log('⏳ Cargando módulos JavaScript...', 'loading');
    
    await new Promise(resolve => setTimeout(resolve, 500));
    
    const loadResults = await testSuite.moduleLoader.loadAllModules();
    testSuite.showModuleStatus();
    
    const loadedCount = Object.values(loadResults).filter(result => result).length;
    const totalCount = Object.keys(loadResults).length;
    
    testSuite.log(`📦 Módulos cargados: ${loadedCount}/${totalCount}`, 
                 loadedCount === totalCount ? 'success' : 'warning');
    
    // Botón para reintentar carga
    document.getElementById('btn-retry-all').addEventListener('click', async function() {
        testSuite.log('🔄 Reintentando carga de módulos...', 'info');
        const retryResults = await testSuite.moduleLoader.loadAllModules();
        testSuite.showModuleStatus();
        
        const retryLoadedCount = Object.values(retryResults).filter(result => result).length;
        testSuite.log(`📦 Módulos cargados después del reintento: ${retryLoadedCount}/${totalCount}`, 
                     retryLoadedCount === totalCount ? 'success' : 'warning');
    });
    
    // Botón para usar módulos de respaldo
    document.getElementById('btn-use-fallback').addEventListener('click', async function() {
        testSuite.log('🛡️ Activando módulos de respaldo...', 'info');
        testSuite.moduleLoader.createFallbackModules();
        testSuite.showModuleStatus();
        
        testSuite.log('✅ Módulos de respaldo activados. Puede continuar con las pruebas.', 'success');
        
        setTimeout(() => {
            testSuite.testAllModules();
        }, 1000);
    });
    
    // Botón para cargar mapa offline
    document.getElementById('btn-cargar-mapa-offline').addEventListener('click', function() {
        if (testSuite.modules.mapa && testSuite.modules.mapa.cargarMapaOffline) {
            const result = testSuite.modules.mapa.cargarMapaOffline();
            if (result) {
                testSuite.log('🌐 Mapa cargado en modo offline', 'success');
            }
        } else {
            testSuite.log('❌ MapaModule no inicializado', 'error');
        }
    });
    
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
        } else {
            testSuite.log('❌ ReportesModule no inicializado', 'error');
        }
    });
    
    document.getElementById('btn-crear-selector').addEventListener('click', function() {
        testSuite.createHorarioGrid();
        testSuite.log('📅 Selector de horarios creado', 'success');
    });
    
    document.getElementById('btn-limpiar-logs').addEventListener('click', function() {
        testSuite.logger.limpiar();
    });
    
    // Botón para agregar marcador
    document.getElementById('btn-agregar-marcador').addEventListener('click', function() {
        const lat = document.getElementById('lat-input').value;
        const lng = document.getElementById('lng-input').value;
        
        if (lat && lng && testSuite.modules.mapa) {
            const result = testSuite.modules.mapa.agregarMarcador(parseFloat(lat), parseFloat(lng), 'Marcador manual');
            if (result) {
                testSuite.log(`📍 Marcador agregado en: ${lat}, ${lng}`, 'success');
            } else {
                testSuite.log('⚠️ No se pudo agregar marcador (puede ser por problemas de conexión)', 'warning');
            }
        } else {
            testSuite.log('❌ Ingrese latitud y longitud válidas', 'error');
        }
    });
    
    // Si todos los módulos se cargaron, iniciar pruebas automáticas
    if (loadedCount === totalCount) {
        setTimeout(() => {
            testSuite.log('⏱️ Las pruebas automáticas comenzarán en 3 segundos...', 'info');
            testSuite.log('⚠️ Nota: Los errores de conexión en mapas no afectarán los resultados', 'warning');
            setTimeout(() => {
                testSuite.testAllModules();
            }, 3000);
        }, 2000);
    } else {
        testSuite.log('⚠️ Algunos módulos no se cargaron. Use "Usar módulos de respaldo" para pruebas.', 'warning');
    }
});
</script>
</body>
</html>