/* navbar.css - VERSIÓN CON PADDING REDUCIDO */

/* Variables específicas para navbar */
:root {
  --navbar-desktop-height: 180px;
  --navbar-mobile-height: 60px;
  --navbar-transition-speed: 0.3s;
  --primary-color: #6c3483;
  --text-color-light: #ffffff;
  --bg-dark: #343a40;
  --min-padding: max(1.5vh, 15px); /* Aumentar a 15px mínimo */

}

/* ✅ NAVBAR PRINCIPAL - PADDING MÍNIMO */
.navbar-contextual {
  width: 100vw !important;
  max-width: 100vw !important;
  min-width: 100vw !important;
  background: linear-gradient(135deg, var(--primary-color), #8e44ad) !important;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
  transition: all var(--navbar-transition-speed) ease !important;
  z-index: 1030 !important;
  padding-left: var(--min-padding) !important;
  padding-right: var(--min-padding) !important;
}

/* ✅ CONTENEDOR INTERNO */
.navbar-container {
  display: flex !important;
  flex-wrap: wrap !important;
  width: 100% !important;
  max-width: 100% !important;
  padding: 0 var(--min-padding) !important;
  margin: 0 auto !important;
}

/* ✅ SECCIONES DEL NAVBAR CON PADDING MÍNIMO */
.navbar-brand-section {
  flex: 0 0 15% !important;
  max-width: 15% !important;
  min-width: 180px !important; /* Reducido */
  display: flex !important;
  align-items: center !important;
  padding: 8px 0 !important; /* Reducido */
}

.navbar-menu-section {
  flex: 0 0 50% !important;
  max-width: 50% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 0 var(--min-padding) !important;
}

.navbar-social-section {
  flex: 0 0 15% !important;
  max-width: 15% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 0 var(--min-padding) !important;
}

.navbar-control-section {
  flex: 0 0 20% !important;
  max-width: 20% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: flex-end !important;
  padding: 0 var(--min-padding) !important;
}

/* ✅ LOGO REDUCIDO */
.navbar-logo {
  height: 45px !important; /* Reducido */
  max-height: 45px !important;
  width: auto !important;
  transition: transform 0.3s ease !important;
}

.navbar-logo:hover {
  transform: scale(1.05) !important;
}

/* ✅ MENÚ PRINCIPAL - COMPACTO */
.main-navigation {
  width: 100% !important;
  justify-content: center !important;
}

.main-navigation .nav-link {
  color: var(--text-color-light) !important;
  font-weight: 500 !important;
  padding: 8px 12px !important; /* Reducido */
  transition: all 0.3s ease !important;
  white-space: nowrap !important;
  font-size: 0.9rem !important; /* Reducido */
}

.main-navigation .nav-link:hover,
.main-navigation .nav-link.active {
  background: rgba(255, 255, 255, 0.1) !important;
  border-radius: 4px !important;
  transform: translateY(-1px) !important; /* Reducido */
}

/* ✅ ICONOS SOCIALES COMPACTOS */
.social-icons-vertical {
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important; /* Reducido */
}

.social-icon-circle {
  width: 30px !important; /* Reducido */
  height: 30px !important; /* Reducido */
  border-radius: 50% !important;
  background: rgba(255, 255, 255, 0.1) !important;
  color: white !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  transition: all 0.3s ease !important;
  text-decoration: none !important;
  font-size: 0.8rem !important; /* Reducido */
}

.social-icon-circle:hover {
  background: rgba(255, 255, 255, 0.2) !important;
  transform: translateY(-2px) scale(1.1) !important; /* Reducido */
}

/* ✅ CONTROL DE SESIÓN COMPACTO */
.session-controls {
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important; /* Reducido */
  width: 100% !important;
}

.session-controls .btn {
  min-width: 100px !important; /* Reducido */
  transition: all 0.3s ease !important;
  padding: 5px 10px !important; /* Reducido */
  font-size: 0.85rem !important; /* Reducido */
}

.session-controls .btn:hover {
  transform: translateY(-1px) !important; /* Reducido */
  box-shadow: 0 3px 6px rgba(0,0,0,0.2) !important; /* Reducido */
}

/* ✅ INFORMACIÓN DE ESCUELA COMPACTA */
.school-info {
  background: rgba(0, 0, 0, 0.2) !important;
  padding: 8px !important; /* Reducido */
  border-radius: 6px !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  font-size: 0.85rem !important; /* Reducido */
}

.escuela-activa-indicator {
  background: rgba(52, 152, 219, 0.2) !important;
  padding: 6px !important; /* Reducido */
  border-radius: 4px !important;
  border-left: 2px solid #3498db !important; /* Reducido */
}

/* ✅ BUSCADOR DE ESCUELAS COMPACTO */
.school-search-container {
  position: relative !important;
}

.search-results-dropdown {
  position: absolute !important;
  top: 100% !important;
  left: 0 !important;
  right: 0 !important;
  background: white !important;
  border: 1px solid #dee2e6 !important;
  border-radius: 4px !important;
  max-height: 180px !important; /* Reducido */
  overflow-y: auto !important;
  z-index: 1000 !important;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1) !important; /* Reducido */
}

.search-results-item {
  padding: 6px 10px !important; /* Reducido */
  cursor: pointer !important;
  border-bottom: 1px solid #f8f9fa !important;
  transition: background 0.2s ease !important;
  font-size: 0.85rem !important; /* Reducido */
}

.search-results-item:hover {
  background: #f8f9fa !important;
}

/* ✅ DROPDOWN DE SELECCIÓN COMPACTO */
.escuela-selector-dropdown {
  min-width: 200px !important; /* Reducido */
  max-height: 250px !important; /* Reducido */
  overflow-y: auto !important;
  background: white !important;
  border-radius: 6px !important; /* Reducido */
  box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important; /* Reducido */
  border: 1px solid #dee2e6 !important;
}

/* ✅ RESPONSIVE COMPACTO */
@media (max-width: 1199.98px) {
  .navbar-brand-section {
    min-width: 150px !important; /* Reducido */
  }
  
  .navbar-logo {
    height: 40px !important; /* Reducido */
  }
}

@media (max-width: 991.98px) {
  .navbar-contextual {
    min-height: var(--navbar-mobile-height) !important;
    padding-left: 10px !important; /* Reducido */
    padding-right: 10px !important; /* Reducido */
  }
  
  .navbar-container {
    padding: 0 8px !important; /* Reducido */
  }
  
  .navbar-brand-section {
    min-width: 120px !important; /* Reducido */
  }
  
  .navbar-logo {
    height: 35px !important; /* Reducido */
  }
}

@media (max-width: 767.98px) {
  .navbar-contextual {
    padding-left: 8px !important; /* Reducido */
    padding-right: 8px !important; /* Reducido */
  }
  
  .navbar-logo {
    height: 30px !important; /* Reducido */
  }
  
  .main-navigation .nav-link {
    padding: 6px 8px !important; /* Reducido */
    font-size: 0.8rem !important; /* Reducido */
  }
  
  .session-controls .btn {
    min-width: 80px !important; /* Reducido */
    padding: 4px 8px !important; /* Reducido */
    font-size: 0.8rem !important; /* Reducido */
  }
}

@media (max-width: 575.98px) {
  .navbar-contextual {
    padding-left: 5px !important; /* Reducido */
    padding-right: 5px !important; /* Reducido */
  }
  
  .navbar-logo {
    height: 25px !important; /* Reducido */
  }
  
  .navbar-brand-section {
    min-width: 100px !important; /* Reducido */
    padding: 8px var(--min-padding) !important; /* Horizontal también */
  }
}
<?php
/**
 * Test CSS Directo con Verificación de Archivos Parciales
 * Este archivo verifica la carga de todos los CSS parciales al cargar ged.css
 */

// Definir rutas base
$cssDir = __DIR__ . '/css/';
$gedCssPath = $cssDir . 'ged.css';

// Lista de archivos CSS parciales esperados
$cssPartialFiles = [
    // Archivos base
    'variables.css',
    'reset.css',
    'typography.css',
    
    // Componentes principales
    'layout.css',
    'navbar.css',
    'sidebar.css',
    'footer.css',
    
    // Componentes UI
    'buttons.css',
    'forms.css',
    'tables.css',
    'cards.css',
    'modals.css',
    
    // Páginas específicas
    'dashboard.css',
    'escuelas.css',
    'usuarios.css',
    'reportes.css',
    
    // Utilidades
    'utilities.css',
    'responsive.css',
    'animations.css'
];

// Verificar si ged.css existe
$gedCssExists = file_exists($gedCssPath);
$gedCssContent = $gedCssExists ? file_get_contents($gedCssPath) : '';

// Verificar archivos parciales
$partialStatus = [];
foreach ($cssPartialFiles as $file) {
    $filePath = $cssDir . $file;
    $exists = file_exists($filePath);
    
    // Verificar si está referenciado en ged.css
    $referenced = false;
    if ($gedCssExists && $exists) {
        $referenced = strpos($gedCssContent, $file) !== false || 
                      strpos($gedCssContent, str_replace('.css', '', $file)) !== false;
    }
    
    $partialStatus[$file] = [
        'exists' => $exists,
        'referenced' => $referenced,
        'path' => $filePath
    ];
}

// Función para mostrar icono de estado
function getStatusIcon($status, $referenced = false) {
    if (!$status) return '❌ No existe';
    if (!$referenced) return '⚠️ Existe pero no referenciado';
    return '✅ Cargado correctamente';
}

// Verificar si ged.css importa otros archivos
$importsInGed = [];
if ($gedCssExists) {
    // Buscar imports en el contenido
    preg_match_all('/@import\s+[\'"]([^\'"]+\.css)[\'"]/', $gedCssContent, $matches);
    if (!empty($matches[1])) {
        $importsInGed = $matches[1];
    }
    
    // Buscar comentarios sobre archivos incluidos
    preg_match_all('/\/\*\s*Incluye:\s*([^*]+)\s*\*\//', $gedCssContent, $commentMatches);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test CSS - Verificación de Archivos Parciales</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        h2 {
            color: #34495e;
            margin-top: 30px;
            padding-bottom: 5px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        
        .status-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        
        .status-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .file-card {
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .file-card.success {
            border-left: 4px solid #28a745;
        }
        
        .file-card.warning {
            border-left: 4px solid #ffc107;
        }
        
        .file-card.error {
            border-left: 4px solid #dc3545;
        }
        
        .file-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .file-status {
            font-size: 14px;
        }
        
        .summary {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 15px;
            background: #e8f4fc;
            border-radius: 6px;
            border: 1px solid #b3e0ff;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-number {
            font-size: 24px;
            font-weight: bold;
            color: #2980b9;
        }
        
        .summary-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 12px;
            margin-top: 20px;
        }
        
        .recommendations {
            background: #e8f6f3;
            border: 1px solid #a3e4d7;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .recommendations h3 {
            color: #148f77;
            margin-top: 0;
        }
        
        .recommendations ul {
            padding-left: 20px;
        }
        
        .recommendations li {
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .file-grid {
                grid-template-columns: 1fr;
            }
            
            .summary {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test CSS - Verificación de Archivos Parciales</h1>
        
        <!-- Estado principal de ged.css -->
        <div class="status-box <?php echo $gedCssExists ? 'status-success' : 'status-error'; ?>">
            <h2>Archivo Principal: ged.css</h2>
            <p><strong>Ruta:</strong> <?php echo htmlspecialchars($gedCssPath); ?></p>
            <p><strong>Estado:</strong> <?php echo $gedCssExists ? '✅ EXISTE' : '❌ NO EXISTE'; ?></p>
            <p><strong>Tamaño:</strong> <?php echo $gedCssExists ? filesize($gedCssPath) . ' bytes' : 'N/A'; ?></p>
            
            <?php if ($gedCssExists && !empty($importsInGed)): ?>
                <p><strong>Importa:</strong> <?php echo count($importsInGed); ?> archivo(s)</p>
                <ul>
                    <?php foreach ($importsInGed as $import): ?>
                        <li><?php echo htmlspecialchars($import); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <!-- Resumen de archivos parciales -->
        <?php
        $totalFiles = count($partialStatus);
        $existingFiles = count(array_filter($partialStatus, fn($s) => $s['exists']));
        $referencedFiles = count(array_filter($partialStatus, fn($s) => $s['exists'] && $s['referenced']));
        ?>
        
        <div class="summary">
            <div class="summary-item">
                <div class="summary-number"><?php echo $totalFiles; ?></div>
                <div class="summary-label">Archivos Parciales</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo $existingFiles; ?></div>
                <div class="summary-label">Existen</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo $referencedFiles; ?></div>
                <div class="summary-label">Referenciados</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo $existingFiles > 0 ? round(($referencedFiles / $existingFiles) * 100) : 0; ?>%</div>
                <div class="summary-label">Completitud</div>
            </div>
        </div>
        
        <!-- Lista detallada de archivos parciales -->
        <h2>📁 Archivos CSS Parciales</h2>
        <div class="file-grid">
            <?php foreach ($partialStatus as $file => $status): ?>
                <?php 
                $cardClass = 'file-card ';
                if (!$status['exists']) {
                    $cardClass .= 'error';
                } elseif (!$status['referenced']) {
                    $cardClass .= 'warning';
                } else {
                    $cardClass .= 'success';
                }
                ?>
                <div class="<?php echo $cardClass; ?>">
                    <div class="file-name"><?php echo htmlspecialchars($file); ?></div>
                    <div class="file-status"><?php echo getStatusIcon($status['exists'], $status['referenced']); ?></div>
                    <?php if ($status['exists']): ?>
                        <div><small>Tamaño: <?php echo filesize($status['path']); ?> bytes</small></div>
                        <div><small>Modificado: <?php echo date('Y-m-d H:i:s', filemtime($status['path'])); ?></small></div>
                    <?php else: ?>
                        <div><small>Ruta: <?php echo htmlspecialchars($status['path']); ?></small></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Contenido de ged.css (si existe) -->
        <?php if ($gedCssExists): ?>
            <h2>📄 Contenido de ged.css (primeras 50 líneas)</h2>
            <pre><?php 
            $lines = explode("\n", $gedCssContent);
            $firstLines = array_slice($lines, 0, 50);
            foreach ($firstLines as $i => $line) {
                echo ($i + 1) . ': ' . htmlspecialchars($line) . "\n";
            }
            if (count($lines) > 50) {
                echo "\n... (" . (count($lines) - 50) . " líneas más)";
            }
            ?></pre>
        <?php endif; ?>
        
        <!-- Recomendaciones -->
        <div class="recommendations">
            <h3>💡 Recomendaciones</h3>
            <ul>
                <li><strong>Para archivos existentes pero no referenciados:</strong> Agrega @import o referencia en ged.css</li>
                <li><strong>Para archivos que no existen:</strong> Verifica la ruta o crea el archivo CSS</li>
                <li><strong>Optimización:</strong> Considera combinar archivos CSS pequeños en uno solo</li>
                <li><strong>Cache:</strong> Configura cabeceras de cache apropiadas para CSS</li>
                <li><strong>Minificación:</strong> Minifica los archivos CSS en producción</li>
            </ul>
            
            <p><strong>Ejemplo de cómo referenciar en ged.css:</strong></p>
            <pre>/* ged.css - Archivo principal */
@import 'variables.css';
@import 'reset.css';
@import 'layout.css';
/* ... otros imports ... */</pre>
        </div>
        
        <!-- Información del servidor -->
        <h2>🖥️ Información del Entorno</h2>
        <div class="status-box">
            <p><strong>Directorio CSS:</strong> <?php echo htmlspecialchars($cssDir); ?></p>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Servidor Web:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></p>
            <p><strong>Tiempo de Ejecución:</strong> <?php echo round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4); ?> segundos</p>
        </div>
    </div>
</body>
</html>