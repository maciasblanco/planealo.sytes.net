<?php
/* @var $this yii\web\View */

$this->title = 'Prueba de Módulos JavaScript';
$this->params['breadcrumbs'][] = $this->title;

// Cargar los assets necesarios
use app\assets\AppAsset;
AppAsset::register($this);
?>

<div class="site-test-js">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">
                            <i class="fas fa-vial"></i> Prueba de Módulos JavaScript - Sistema GED v4.1
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Objetivo:</strong> Verificar que todos los módulos JavaScript estén funcionando correctamente.
                            Abre la consola del navegador (F12) para ver los mensajes de log.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 1: SISTEMA GED BÁSICO -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-cogs"></i> Sistema GED Principal</h4>
                    </div>
                    <div class="card-body">
                        <p>Verifica que el sistema principal esté funcionando:</p>
                        
                        <div class="mb-3">
                            <button class="btn btn-primary btn-sm" onclick="testGEDSystem()">
                                <i class="fas fa-play"></i> Probar Sistema GED
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="debugGEDSystem()">
                                <i class="fas fa-bug"></i> Debug Sistema
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="checkOverflow()">
                                <i class="fas fa-ruler"></i> Verificar Overflow
                            </button>
                        </div>
                        
                        <div class="alert alert-light">
                            <strong>Estado del sistema:</strong>
                            <div id="system-status" class="mt-2">
                                <span class="badge bg-secondary">No verificado</span>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Configuración de Padding:</h6>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">Mínimo (px)</span>
                                <input type="number" id="min-padding" class="form-control" value="10">
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">Máximo (vh)</span>
                                <input type="number" id="max-padding" class="form-control" value="0.01" step="0.01">
                            </div>
                            <button class="btn btn-outline-primary btn-sm" onclick="updatePadding()">
                                <i class="fas fa-sliders-h"></i> Actualizar Padding
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-bars"></i> Navbar y Sidebar</h4>
                    </div>
                    <div class="card-body">
                        <p>Prueba los componentes de navegación:</p>
                        
                        <div class="mb-3">
                            <button class="btn btn-primary btn-sm" onclick="testNavbar()">
                                <i class="fas fa-bars"></i> Toggle Navbar (Móvil)
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="testSidebar()">
                                <i class="fas fa-stream"></i> Toggle Sidebar
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="reloadOffCanvasMenu()">
                                <i class="fas fa-redo"></i> Recargar Menú
                            </button>
                        </div>
                        
                        <div class="alert alert-light">
                            <strong>Estado de navegación:</strong>
                            <div id="nav-status" class="mt-2">
                                <span class="badge bg-secondary">No verificado</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: MÓDULO DE HORARIOS -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-clock"></i> Módulo de Horarios</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Selector de Horarios</h5>
                                <p>Simulación del selector de horarios:</p>
                                
                                <div class="mb-3">
                                    <label class="form-label">Tipo de horario:</label>
                                    <select id="tipo-horario" class="form-select form-select-sm">
                                        <option value="">Seleccionar...</option>
                                        <option value="manana">Mañana (6-11 AM)</option>
                                        <option value="tarde">Tarde (12-5 PM)</option>
                                        <option value="noche">Noche (6-10 PM)</option>
                                        <option value="completo">Completo</option>
                                        <option value="fin_semana">Fin de semana</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <button class="btn btn-primary btn-sm" onclick="initHorarioModule()">
                                        <i class="fas fa-play"></i> Inicializar Módulo
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="testHorario()">
                                        <i class="fas fa-check"></i> Probar Selección
                                    </button>
                                </div>
                                
                                <div class="alert alert-light">
                                    <strong>Horarios seleccionados:</strong>
                                    <div id="horario-preview" class="mt-2 small">
                                        No se han seleccionado horarios
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Simulación de Grid</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Hora</th>
                                                <th>Lun</th>
                                                <th>Mar</th>
                                                <th>Mié</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php for($h = 8; $h <= 12; $h++): ?>
                                            <tr>
                                                <td><?= $h ?>:00</td>
                                                <td>
                                                    <div class="horario-cell text-center" 
                                                         data-dia="lunes" 
                                                         data-hora="<?= $h ?>"
                                                         style="cursor: pointer; padding: 5px; border: 1px solid #ddd;">
                                                        <i class="fas fa-times text-muted"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="horario-cell text-center" 
                                                         data-dia="martes" 
                                                         data-hora="<?= $h ?>"
                                                         style="cursor: pointer; padding: 5px; border: 1px solid #ddd;">
                                                        <i class="fas fa-times text-muted"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="horario-cell text-center" 
                                                         data-dia="miercoles" 
                                                         data-hora="<?= $h ?>"
                                                         style="cursor: pointer; padding: 5px; border: 1px solid #ddd;">
                                                        <i class="fas fa-times text-muted"></i>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <input type="hidden" id="horario-data" value="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: MÓDULO DE MAPAS -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="fas fa-map"></i> Módulo de Mapas</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Mapa de Selección</h5>
                                <p>Para pruebas de ubicación de escuelas:</p>
                                
                                <div class="mb-3">
                                    <label class="form-label">Latitud:</label>
                                    <input type="text" id="lat-input" class="form-control form-control-sm" value="10.480594">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Longitud:</label>
                                    <input type="text" id="lng-input" class="form-control form-control-sm" value="-66.903600">
                                </div>
                                
                                <div class="mb-3">
                                    <button class="btn btn-primary btn-sm" onclick="initMapaSeleccion()">
                                        <i class="fas fa-map-marker-alt"></i> Inicializar Mapa Selección
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="testMapaVisualizacion()">
                                        <i class="fas fa-eye"></i> Probar Mapa Visualización
                                    </button>
                                </div>
                                
                                <div class="alert alert-light">
                                    <div id="map" style="height: 200px; background-color: #f0f0f0; border: 1px solid #ddd;">
                                        <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                                            <i class="fas fa-map fa-3x"></i>
                                            <div class="ms-3">
                                                <h6>Mapa de selección</h6>
                                                <small>Haz clic en el botón para inicializar</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Mapa de Visualización</h5>
                                <p>Para mostrar múltiples escuelas:</p>
                                
                                <div class="mb-3">
                                    <button class="btn btn-primary btn-sm" onclick="testMapaEscuelas()">
                                        <i class="fas fa-school"></i> Cargar Escuelas de Prueba
                                    </button>
                                </div>
                                
                                <div class="alert alert-light">
                                    <div id="mapa-escuelas" style="height: 200px; background-color: #f0f0f0; border: 1px solid #ddd;">
                                        <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                                            <i class="fas fa-map-marked-alt fa-3x"></i>
                                            <div class="ms-3">
                                                <h6>Mapa de visualización</h6>
                                                <small>Haz clic en el botón para cargar datos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 4: MÓDULO DE REPORTES -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h4 class="mb-0"><i class="fas fa-chart-bar"></i> Módulo de Reportes</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Filtros de Reportes</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Filtrar por estado:</label>
                                    <select id="filtro-estado" class="form-select form-select-sm" onchange="filtrarPorEstado(this.value)">
                                        <option value="todos">Todos</option>
                                        <option value="activo">Activos</option>
                                        <option value="inactivo">Inactivos</option>
                                        <option value="pendiente">Pendientes</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <button class="btn btn-primary btn-sm" onclick="initReportesModule()">
                                        <i class="fas fa-play"></i> Inicializar Reportes
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="exportarTabla()">
                                        <i class="fas fa-file-export"></i> Exportar Tabla
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Tabla de Prueba</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered" id="tabla-atletas">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr data-estado="activo">
                                                <td>1</td>
                                                <td>Juan Pérez</td>
                                                <td><span class="badge bg-success">Activo</span></td>
                                            </tr>
                                            <tr data-estado="inactivo">
                                                <td>2</td>
                                                <td>María Gómez</td>
                                                <td><span class="badge bg-danger">Inactivo</span></td>
                                            </tr>
                                            <tr data-estado="pendiente">
                                                <td>3</td>
                                                <td>Carlos López</td>
                                                <td><span class="badge bg-warning">Pendiente</span></td>
                                            </tr>
                                            <tr data-estado="activo">
                                                <td>4</td>
                                                <td>Ana Rodríguez</td>
                                                <td><span class="badge bg-success">Activo</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 5: MÓDULO DE TIENDA -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-purple text-white">
                        <h4 class="mb-0"><i class="fas fa-store"></i> Módulo de Tienda</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Funcionalidades de Tienda</h5>
                                
                                <div class="mb-3">
                                    <button class="btn btn-primary btn-sm" onclick="testTiendaModule()">
                                        <i class="fas fa-play"></i> Inicializar Tienda
                                    </button>
                                    <button class="btn btn-success btn-sm" id="btn-marketplace">
                                        <i class="fas fa-shopping-cart"></i> Ir al Marketplace
                                    </button>
                                    <a href="#" class="btn btn-info btn-sm">
                                        <i class="fas fa-user-tie"></i> Registro Vendedor
                                    </a>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="alert alert-light">
                                        <strong>Eventos de tienda:</strong>
                                        <div id="tienda-events" class="mt-2 small">
                                            <div class="text-muted">No hay eventos registrados</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Productos de Prueba</h5>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <h6>Camiseta Deportiva</h6>
                                                <p class="text-success">$25.00</p>
                                                <button class="btn btn-outline-primary btn-sm btn-agregar-carrito"
                                                        data-id="1"
                                                        data-nombre="Camiseta Deportiva"
                                                        data-precio="25">
                                                    <i class="fas fa-cart-plus"></i> Agregar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <h6>Balón de Fútbol</h6>
                                                <p class="text-success">$20.00</p>
                                                <button class="btn btn-outline-primary btn-sm btn-agregar-carrito"
                                                        data-id="2"
                                                        data-nombre="Balón de Fútbol"
                                                        data-precio="20">
                                                    <i class="fas fa-cart-plus"></i> Agregar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Carrito de compras:</span>
                                        <span id="contador-carrito" class="badge bg-primary" style="display: none;">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 6: CONSOLA DE LOGS -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0"><i class="fas fa-terminal"></i> Consola de Logs</h4>
                    </div>
                    <div class="card-body bg-dark text-white">
                        <div id="console-log" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                            <div class="text-success">> Sistema de prueba listo. Abre la consola del navegador (F12) para más detalles.</div>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-light btn-sm" onclick="clearConsole()">
                                <i class="fas fa-trash"></i> Limpiar Consola
                            </button>
                            <button class="btn btn-outline-light btn-sm" onclick="runAllTests()">
                                <i class="fas fa-play-circle"></i> Ejecutar Todas las Pruebas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// CSS adicional para esta vista
$this->registerCss('
.bg-purple {
    background-color: #6f42c1 !important;
}

.horario-cell.selected {
    background-color: #007bff !important;
    color: white !important;
}

.horario-cell.morning {
    background-color: #fff3cd !important;
}

.horario-cell.afternoon {
    background-color: #d1ecf1 !important;
}

.horario-cell.evening {
    background-color: #f8d7da !important;
}

#console-log div {
    padding: 2px 5px;
    border-bottom: 1px solid #333;
}

#console-log .text-success { color: #28a745 !important; }
#console-log .text-warning { color: #ffc107 !important; }
#console-log .text-danger { color: #dc3545 !important; }
#console-log .text-info { color: #17a2b8 !important; }
');
?>

<?php
// JavaScript de prueba
$this->registerJs('
// Función para agregar logs a la consola visual
function addLog(message, type = "info") {
    const consoleDiv = document.getElementById("console-log");
    const logEntry = document.createElement("div");
    logEntry.className = "text-" + type;
    logEntry.textContent = "> " + message;
    consoleDiv.appendChild(logEntry);
    consoleDiv.scrollTop = consoleDiv.scrollHeight;
    
    // También mostrar en consola real
    const icon = type === "success" ? "✅" : type === "warning" ? "⚠️" : type === "danger" ? "❌" : "ℹ️";
    console.log(icon + " " + message);
}

// Limpiar consola visual
function clearConsole() {
    document.getElementById("console-log").innerHTML = "";
    addLog("Consola limpiada", "info");
}

// ===== PRUEBAS DEL SISTEMA GED =====
function testGEDSystem() {
    addLog("Probando sistema GED...", "info");
    
    if (typeof window.gedSystem !== "undefined") {
        const state = gedSystem.getCurrentState();
        document.getElementById("system-status").innerHTML = `
            <span class="badge bg-success">Sistema Activo</span>
            <div class="small mt-1">
                Modo: ${state.isMobile ? "Móvil" : "Escritorio"} | 
                Padding: ${state.currentPadding.toFixed(1)}px
            </div>
        `;
        addLog("✅ Sistema GED funcionando correctamente", "success");
    } else {
        document.getElementById("system-status").innerHTML = `<span class="badge bg-danger">Sistema NO cargado</span>`;
        addLog("❌ Sistema GED no está disponible", "danger");
    }
}

function updatePadding() {
    const minPx = parseFloat(document.getElementById("min-padding").value);
    const maxVH = parseFloat(document.getElementById("max-padding").value);
    
    if (window.updatePaddingConfig) {
        updatePaddingConfig(minPx, maxVH);
        addLog(`Padding configurado: mínimo ${minPx}px, máximo ${maxVH*100}vh`, "success");
    } else {
        addLog("Función updatePaddingConfig no disponible", "warning");
    }
}

// ===== PRUEBAS DE NAVBAR Y SIDEBAR =====
function testNavbar() {
    addLog("Probando navbar...", "info");
    const status = document.getElementById("nav-status");
    
    if (window.gedSystem && gedSystem.modules.navbar) {
        status.innerHTML = `<span class="badge bg-success">Navbar funcionando</span>`;
        addLog("✅ NavbarManager disponible", "success");
        
        // Simular toggle en móvil
        if (gedSystem.isMobile) {
            addLog("Simulando toggle navbar en modo móvil", "info");
        }
    } else {
        status.innerHTML = `<span class="badge bg-warning">Navbar no inicializado</span>`;
        addLog("⚠️ NavbarManager no disponible", "warning");
    }
}

function testSidebar() {
    addLog("Probando sidebar...", "info");
    
    if (window.gedSystem && gedSystem.modules.sidebar) {
        const sidebar = gedSystem.modules.sidebar;
        if (sidebar.isOpen) {
            sidebar.close();
            addLog("✅ Sidebar cerrado", "success");
        } else {
            sidebar.open();
            addLog("✅ Sidebar abierto", "success");
        }
    } else {
        addLog("⚠️ Sidebar no disponible", "warning");
    }
}

// ===== PRUEBAS DE HORARIOS =====
function initHorarioModule() {
    addLog("Inicializando módulo de horarios...", "info");
    
    if (typeof initHorarioModule === "function") {
        window.initHorarioModule();
        addLog("✅ Módulo de horarios inicializado", "success");
        
        // Configurar evento de cambio en selector
        $("#tipo-horario").off("change").on("change", function() {
            const tipo = $(this).val();
            if (tipo) {
                const instance = getHorarioModuleInstance();
                if (instance) {
                    instance.seleccionarRango(tipo);
                    addLog(`Rango seleccionado: ${tipo}`, "info");
                }
            }
        });
    } else {
        addLog("❌ Función initHorarioModule no disponible", "danger");
    }
}

function testHorario() {
    addLog("Probando selector de horarios...", "info");
    
    if (typeof getHorarioModuleInstance === "function") {
        const instance = getHorarioModuleInstance();
        if (instance) {
            const horarios = instance.getHorariosSeleccionados();
            addLog(`Horarios seleccionados: ${JSON.stringify(horarios)}`, "success");
        } else {
            addLog("⚠️ Instancia de horarios no disponible", "warning");
        }
    } else {
        addLog("❌ Módulo de horarios no cargado", "danger");
    }
}

// ===== PRUEBAS DE MAPAS =====
function initMapaSeleccion() {
    addLog("Inicializando mapa de selección...", "info");
    
    if (typeof inicializarMapaSeleccion === "function") {
        // Primero cargar Leaflet
        loadLeaflet().then(() => {
            const mapa = window.inicializarMapaSeleccion();
            if (mapa) {
                addLog("✅ Mapa de selección inicializado", "success");
            } else {
                addLog("❌ Error al inicializar mapa", "danger");
            }
        }).catch(error => {
            addLog("❌ Error cargando Leaflet: " + error.message, "danger");
        });
    } else {
        addLog("❌ Función inicializarMapaSeleccion no disponible", "danger");
    }
}

function testMapaVisualizacion() {
    addLog("Probando mapa de visualización...", "info");
    
    if (typeof inicializarMapaVisualizacion === "function") {
        const escuelasTest = [
            { nombre: "Escuela Test 1", lat: 10.480594, lng: -66.903600, direccion: "Caracas", telefono: "0212-1234567" },
            { nombre: "Escuela Test 2", lat: 10.500000, lng: -66.900000, direccion: "Caracas Centro", telefono: "0212-7654321" }
        ];
        
        loadLeaflet().then(() => {
            const mapa = window.inicializarMapaVisualizacion(escuelasTest);
            if (mapa) {
                addLog(`✅ Mapa de visualización con ${escuelasTest.length} escuelas`, "success");
            }
        }).catch(error => {
            addLog("❌ Error cargando Leaflet: " + error.message, "danger");
        });
    } else {
        addLog("❌ Función inicializarMapaVisualizacion no disponible", "danger");
    }
}

function testMapaEscuelas() {
    addLog("Cargando datos de prueba para mapa...", "info");
    
    // Datos de prueba para Venezuela
    const escuelasData = [
        { nombre: "Escuela Deportiva Caracas", lat: 10.480594, lng: -66.903600, direccion: "Caracas", telefono: "0212-1111111" },
        { nombre: "Academia Deportiva Valencia", lat: 10.162105, lng: -68.007685, direccion: "Valencia", telefono: "0241-2222222" },
        { nombre: "Club Deportivo Maracaibo", lat: 10.642707, lng: -71.612534, direccion: "Maracaibo", telefono: "0261-3333333" },
        { nombre: "Escuela Deportiva Barcelona", lat: 10.136259, lng: -64.686188, direccion: "Barcelona", telefono: "0281-4444444" }
    ];
    
    addLog(`Datos de prueba cargados: ${escuelasData.length} escuelas`, "success");
    
    // Actualizar UI
    const mapaDiv = document.getElementById("mapa-escuelas");
    mapaDiv.innerHTML = `
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="text-center">
                <i class="fas fa-check-circle fa-3x text-success"></i>
                <div class="mt-2">
                    <h6>Datos listos</h6>
                    <small>${escuelasData.length} escuelas cargadas</small>
                </div>
            </div>
        </div>
    `;
}

// Función auxiliar para cargar Leaflet
function loadLeaflet() {
    return new Promise((resolve, reject) => {
        if (typeof L !== "undefined") {
            resolve();
            return;
        }
        
        addLog("Cargando biblioteca Leaflet...", "info");
        
        // Cargar CSS
        if (!document.querySelector(\'link[href*="leaflet"]\')) {
            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
            document.head.appendChild(link);
        }
        
        // Cargar JS
        const script = document.createElement("script");
        script.src = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
        script.onload = () => {
            addLog("✅ Leaflet cargado correctamente", "success");
            setTimeout(resolve, 500);
        };
        script.onerror = () => {
            addLog("❌ Error cargando Leaflet", "danger");
            reject(new Error("Leaflet no se pudo cargar"));
        };
        document.head.appendChild(script);
    });
}

// ===== PRUEBAS DE REPORTES =====
function initReportesModule() {
    addLog("Inicializando módulo de reportes...", "info");
    
    if (document.querySelector(".reportes-container") || document.querySelector("#tabla-atletas")) {
        if (typeof ReportesModule !== "undefined") {
            window.reportesModule = new ReportesModule();
            addLog("✅ Módulo de reportes inicializado", "success");
        } else {
            addLog("❌ Clase ReportesModule no disponible", "danger");
        }
    } else {
        addLog("⚠️ No hay elementos de reportes en la página", "warning");
    }
}

function filtrarPorEstado(estado) {
    addLog(`Filtrando por estado: ${estado}`, "info");
    
    if (window.reportesModule && typeof reportesModule.filtrarPorEstado === "function") {
        reportesModule.filtrarPorEstado(estado);
        addLog(`✅ Tabla filtrada por: ${estado}`, "success");
    } else {
        addLog("⚠️ Módulo de reportes no inicializado", "warning");
    }
}

function exportarTabla() {
    addLog("Solicitando exportación de tabla...", "info");
    
    if (window.reportesModule && typeof reportesModule.exportarTabla === "function") {
        reportesModule.exportarTabla();
        addLog("✅ Función de exportación llamada", "success");
    } else {
        addLog("⚠️ Función de exportación no disponible", "warning");
    }
}

// ===== PRUEBAS DE TIENDA =====
function testTiendaModule() {
    addLog("Inicializando módulo de tienda...", "info");
    
    if (typeof TiendaModule !== "undefined") {
        window.tiendaModule = new TiendaModule();
        addLog("✅ Módulo de tienda inicializado", "success");
        
        // Configurar eventos de botones
        const btnMarketplace = document.getElementById("btn-marketplace");
        if (btnMarketplace) {
            btnMarketplace.onclick = function(e) {
                addLog("Evento: Click en marketplace", "info");
                e.preventDefault();
            };
        }
        
        // Configurar eventos de productos
        document.querySelectorAll(".btn-agregar-carrito").forEach(btn => {
            btn.onclick = function() {
                const nombre = this.getAttribute("data-nombre");
                const precio = this.getAttribute("data-precio");
                addLog(`Producto agregado: ${nombre} ($${precio})`, "success");
                
                // Actualizar contador
                const contador = document.getElementById("contador-carrito");
                let count = parseInt(contador.textContent) || 0;
                contador.textContent = count + 1;
                contador.style.display = "block";
                
                // Actualizar lista de eventos
                const eventsDiv = document.getElementById("tienda-events");
                const eventEntry = document.createElement("div");
                eventEntry.textContent = `➕ ${nombre} agregado al carrito`;
                eventEntry.className = "text-success";
                eventsDiv.appendChild(eventEntry);
            };
        });
    } else {
        addLog("❌ Clase TiendaModule no disponible", "danger");
    }
}

// ===== EJECUCIÓN DE TODAS LAS PRUEBAS =====
function runAllTests() {
    addLog("=== INICIANDO TODAS LAS PRUEBAS ===", "info");
    
    // Ejecutar pruebas en secuencia
    setTimeout(() => testGEDSystem(), 100);
    setTimeout(() => testNavbar(), 300);
    setTimeout(() => testSidebar(), 500);
    setTimeout(() => initHorarioModule(), 700);
    setTimeout(() => initReportesModule(), 900);
    setTimeout(() => testTiendaModule(), 1100);
    
    setTimeout(() => {
        addLog("=== PRUEBAS COMPLETADAS ===", "success");
        addLog("Revisa la consola del navegador para detalles completos", "info");
    }, 1500);
}

// Inicialización automática
document.addEventListener("DOMContentLoaded", function() {
    addLog("Página de prueba cargada", "success");
    addLog("Haz clic en los botones para probar cada módulo", "info");
    
    // Configurar tooltips de Bootstrap si están disponibles
    if (typeof bootstrap !== "undefined") {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[title]\'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Interceptar console.log para mostrar en la consola visual
const originalLog = console.log;
console.log = function(...args) {
    originalLog.apply(console, args);
    
    // Solo mostrar mensajes importantes
    const message = args.join(" ");
    if (message.includes("✅") || message.includes("❌") || message.includes("⚠️")) {
        const type = message.includes("✅") ? "success" : 
                    message.includes("❌") ? "danger" : 
                    message.includes("⚠️") ? "warning" : "info";
        addLog(message, type);
    }
};
');
?>

<!-- Incluir FontAwesome para íconos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">