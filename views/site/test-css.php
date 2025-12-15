<?php
/**
 * Test CSS Directo con Verificación de Archivos Parciales
 * Este archivo verifica la carga de todos los CSS parciales al cargar ged.css
 */
use yii\helpers\Html;

// Definir rutas base
$cssDir = Yii::getAlias('@app/web/css/'); // Corregir ruta para Yii2
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