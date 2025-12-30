<?php
/**
 * Test CSS - Verificación de estructura modularizada actual
 * Este archivo verifica los archivos CSS parciales que importa ged.css en la estructura nueva
 */

use yii\helpers\Html;

// Definir rutas base - CORREGIDO para Yii2
$cssDir = Yii::getAlias('@webroot') . '/css/';
$gedCssPath = $cssDir . 'ged.css';

// Lista EXACTA de archivos MODULARIZADOS NUEVOS que importa ged.css (según la estructura actual)
$cssPartialFiles = [
    '_variables.css',    # 1. Variables CSS globales
    '_base.css',         # 2. Estilos base y utilitarios  
    '_components.css',   # 3. Componentes reutilizables
    '_modules.css',      # 4. Estilos específicos de módulos
    '_navigation.css',   # 5. Navegación y menús
    '_responsive.css',   # 6. Media queries consolidados
    // Nota: reportes.css está separado pero no se importa en ged.css
];

// Agregar archivos adicionales que podrían existir pero no se importan
$additionalFiles = [
    'reportes.css'       # Estilos específicos de reportes (no se importa en ged.css)
];

// Verificar si ged.css existe
$gedCssExists = file_exists($gedCssPath);
$gedCssContent = $gedCssExists ? file_get_contents($gedCssPath) : '';

// Extraer importaciones reales de ged.css
$importsInGed = [];
$importsFound = 0;
if ($gedCssExists) {
    // Buscar @import url('...') en el contenido
    preg_match_all('/@import\s+url\([\'"]([^\'"]+\.css)[\'"]\)/', $gedCssContent, $matches);
    if (!empty($matches[1])) {
        $importsInGed = $matches[1];
        $importsFound = count($importsInGed);
    }
}

// Verificar archivos parciales MODULARIZADOS
$partialStatus = [];
foreach ($cssPartialFiles as $file) {
    $filePath = $cssDir . $file;
    $exists = file_exists($filePath);
    
    // Verificar si está referenciado en ged.css
    $referenced = false;
    if ($gedCssExists && $exists) {
        // Verificar si este archivo está en las importaciones reales
        $referenced = in_array($file, $importsInGed);
    }
    
    $partialStatus[$file] = [
        'exists' => $exists,
        'referenced' => $referenced,
        'path' => $filePath,
        'expected_in_ged' => true
    ];
}

// Verificar archivos adicionales (como reportes.css)
$additionalStatus = [];
foreach ($additionalFiles as $file) {
    $filePath = $cssDir . $file;
    $exists = file_exists($filePath);
    
    $additionalStatus[$file] = [
        'exists' => $exists,
        'referenced' => false, // Por defecto no se importan en ged.css
        'path' => $filePath,
        'expected_in_ged' => false
    ];
}

// Contar estadísticas de archivos MODULARIZADOS
$totalFiles = count($cssPartialFiles);
$existingFiles = count(array_filter($partialStatus, fn($s) => $s['exists']));
$referencedFiles = count(array_filter($partialStatus, fn($s) => $s['exists'] && $s['referenced']));

// Calcular porcentajes
$completenessPercent = $totalFiles > 0 ? round(($existingFiles / $totalFiles) * 100) : 0;
$referencedPercent = $existingFiles > 0 ? round(($referencedFiles / $existingFiles) * 100) : 0;

// Verificar si ged.css tiene las importaciones correctas
$expectedImports = [
    "_variables.css",
    "_base.css", 
    "_components.css",
    "_modules.css",
    "_navigation.css",
    "_responsive.css"
];

$missingImports = array_diff($expectedImports, $importsInGed);
$extraImports = array_diff($importsInGed, $expectedImports);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test CSS - Verificación de estructura modularizada</title>
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
        
        h3 {
            color: #2c3e50;
            margin-top: 20px;
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
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .summary-card {
            background: #e8f4fc;
            border: 1px solid #b3e0ff;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .summary-number {
            font-size: 28px;
            font-weight: bold;
            color: #2980b9;
            display: block;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .summary-good {
            color: #28a745;
        }
        
        .summary-medium {
            color: #ffc107;
        }
        
        .summary-bad {
            color: #dc3545;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
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
            word-break: break-all;
        }
        
        .file-path {
            font-size: 12px;
            color: #6c757d;
            margin: 5px 0;
            word-break: break-all;
        }
        
        .file-status {
            font-size: 14px;
            font-weight: 500;
            margin: 8px 0;
        }
        
        .status-success-text {
            color: #28a745;
        }
        
        .status-warning-text {
            color: #ffc107;
        }
        
        .status-error-text {
            color: #dc3545;
        }
        
        .file-details {
            font-size: 12px;
            color: #6c757d;
            margin-top: 8px;
        }
        
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 12px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
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
        
        .structure {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-family: monospace;
        }
        
        .structure-item {
            margin: 5px 0;
            padding-left: 20px;
        }
        
        .folder {
            color: #007bff;
            font-weight: bold;
        }
        
        .file {
            color: #28a745;
        }
        
        .import-check {
            background: #f0f8ff;
            border: 1px solid #cce5ff;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        
        .import-good {
            color: #28a745;
            font-weight: bold;
        }
        
        .import-bad {
            color: #dc3545;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .file-grid {
                grid-template-columns: 1fr;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            pre {
                font-size: 10px;
                padding: 10px;
            }
        }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test CSS - Verificación de estructura MODULARIZADA</h1>
        <h3>📁 Estructura: Archivos CSS planos en /css/ (NO subdirectorios)</h3>
        
        <!-- Estado principal de ged.css -->
        <div class="status-box <?= $gedCssExists ? 'status-success' : 'status-error' ?>">
            <h2>📄 Archivo Principal: ged.css</h2>
            <p><strong>Ruta absoluta:</strong> <?= Html::encode($gedCssPath) ?></p>
            <p><strong>Estado:</strong> 
                <span class="<?= $gedCssExists ? 'status-success-text' : 'status-error-text' ?>">
                    <?= $gedCssExists ? '✅ EXISTE' : '❌ NO EXISTE' ?>
                </span>
            </p>
            
            <?php if ($gedCssExists): ?>
                <p><strong>Tamaño:</strong> <?= number_format(filesize($gedCssPath)) ?> bytes</p>
                <p><strong>Última modificación:</strong> <?= date('Y-m-d H:i:s', filemtime($gedCssPath)) ?></p>
                <p><strong>Importaciones encontradas:</strong> <?= $importsFound ?> archivo(s)</p>
                
                <?php if (!empty($importsInGed)): ?>
                    <h3>Importaciones detectadas en ged.css:</h3>
                    <ul>
                        <?php foreach ($importsInGed as $import): ?>
                            <li><code>@import url('<?= Html::encode($import) ?>');</code></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <!-- Verificación de orden de importaciones -->
                <div class="import-check">
                    <h4>✅ Verificación de orden de importaciones:</h4>
                    <p><strong>Orden esperado:</strong></p>
                    <ol>
                        <li><code>@import url('_variables.css');</code> # Variables CSS globales</li>
                        <li><code>@import url('_base.css');</code> # Estilos base y utilitarios</li>
                        <li><code>@import url('_components.css');</code> # Componentes reutilizables</li>
                        <li><code>@import url('_modules.css');</code> # Estilos específicos de módulos</li>
                        <li><code>@import url('_navigation.css');</code> # Navegación y menús</li>
                        <li><code>@import url('_responsive.css');</code> # Media queries consolidados</li>
                    </ol>
                    
                    <?php if (empty($missingImports) && empty($extraImports)): ?>
                        <p class="import-good">✅ Todas las importaciones están correctamente configuradas</p>
                    <?php else: ?>
                        <?php if (!empty($missingImports)): ?>
                            <p class="import-bad">❌ Faltan importaciones: 
                                <?php foreach ($missingImports as $missing): ?>
                                    <code><?= $missing ?></code> 
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($extraImports)): ?>
                            <p class="import-bad">❌ Importaciones no esperadas: 
                                <?php foreach ($extraImports as $extra): ?>
                                    <code><?= $extra ?></code> 
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p><strong>Error:</strong> El archivo ged.css no existe en la ruta especificada.</p>
                <p>Verifica que la ruta sea correcta y que el archivo esté en: <code>web/css/ged.css</code></p>
            <?php endif; ?>
        </div>
        
        <!-- Resumen de estadísticas -->
        <h2>📊 Resumen de archivos MODULARIZADOS</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-number"><?= $totalFiles ?></span>
                <span class="summary-label">Archivos esperados</span>
            </div>
            
            <div class="summary-card">
                <span class="summary-number <?= $existingFiles == $totalFiles ? 'summary-good' : ($existingFiles > 0 ? 'summary-medium' : 'summary-bad') ?>">
                    <?= $existingFiles ?>
                </span>
                <span class="summary-label">Archivos existentes</span>
            </div>
            
            <div class="summary-card">
                <span class="summary-number <?= $referencedFiles == $existingFiles ? 'summary-good' : ($referencedFiles > 0 ? 'summary-medium' : 'summary-bad') ?>">
                    <?= $referencedFiles ?>
                </span>
                <span class="summary-label">Referenciados en ged.css</span>
            </div>
            
            <div class="summary-card">
                <span class="summary-number <?= $completenessPercent == 100 ? 'summary-good' : ($completenessPercent > 50 ? 'summary-medium' : 'summary-bad') ?>">
                    <?= $completenessPercent ?>%
                </span>
                <span class="summary-label">Completitud</span>
            </div>
        </div>
        
        <!-- Estructura actual esperada -->
        <div class="structure">
            <h3>📁 Estructura ACTUAL de archivos CSS (MODULARIZADA):</h3>
            <div class="structure-item folder">web/css/</div>
            <div class="structure-item file">├── ged.css (archivo maestro de importaciones)</div>
            <div class="structure-item file">├── _variables.css (variables CSS globales)</div>
            <div class="structure-item file">├── _base.css (estilos base y utilitarios)</div>
            <div class="structure-item file">├── _components.css (componentes reutilizables)</div>
            <div class="structure-item file">├── _modules.css (estilos específicos de módulos)</div>
            <div class="structure-item file">├── _navigation.css (navegación y menús)</div>
            <div class="structure-item file">├── _responsive.css (media queries consolidados)</div>
            <div class="structure-item file">└── reportes.css (opcional - no se importa en ged.css)</div>
        </div>
        
        <!-- Lista detallada de archivos MODULARIZADOS -->
        <h2>📁 Archivos CSS MODULARIZADOS (importados por ged.css)</h2>
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
                <div class="<?= $cardClass ?>">
                    <div class="file-name"><?= Html::encode($file) ?></div>
                    <div class="file-path"><?= Html::encode($status['path']) ?></div>
                    
                    <div class="file-status">
                        <?php if (!$status['exists']): ?>
                            <span class="status-error-text">❌ NO EXISTE</span>
                        <?php elseif (!$status['referenced']): ?>
                            <span class="status-warning-text">⚠️ EXISTE pero NO REFERENCIADO en ged.css</span>
                        <?php else: ?>
                            <span class="status-success-text">✅ EXISTE y REFERENCIADO en ged.css</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($status['exists']): ?>
                        <div class="file-details">
                            <div>Tamaño: <?= number_format(filesize($status['path'])) ?> bytes</div>
                            <div>Modificado: <?= date('Y-m-d H:i:s', filemtime($status['path'])) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="file-details">
                            <div>Archivo no encontrado en la ruta especificada</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Archivos adicionales (no importados en ged.css) -->
        <?php if (!empty($additionalStatus)): ?>
            <h2>📁 Archivos CSS ADICIONALES (no importados en ged.css)</h2>
            <div class="file-grid">
                <?php foreach ($additionalStatus as $file => $status): ?>
                    <?php 
                    $cardClass = 'file-card ';
                    if (!$status['exists']) {
                        $cardClass .= 'error';
                    } else {
                        $cardClass .= 'warning'; // Warning porque no se importa
                    }
                    ?>
                    <div class="<?= $cardClass ?>">
                        <div class="file-name"><?= Html::encode($file) ?></div>
                        <div class="file-path"><?= Html::encode($status['path']) ?></div>
                        
                        <div class="file-status">
                            <?php if (!$status['exists']): ?>
                                <span class="status-error-text">❌ NO EXISTE</span>
                            <?php else: ?>
                                <span class="status-warning-text">⚠️ EXISTE pero NO se importa en ged.css</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($status['exists']): ?>
                            <div class="file-details">
                                <div>Tamaño: <?= number_format(filesize($status['path'])) ?> bytes</div>
                                <div>Modificado: <?= date('Y-m-d H:i:s', filemtime($status['path'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Contenido de ged.css (primeras líneas) -->
        <?php if ($gedCssExists): ?>
            <h2>📄 Contenido de ged.css (primeras 30 líneas)</h2>
            <pre><?php 
            $lines = explode("\n", $gedCssContent);
            $lineCount = count($lines);
            $showLines = min(30, $lineCount);
            
            for ($i = 0; $i < $showLines; $i++) {
                echo ($i + 1) . ': ' . Html::encode($lines[$i]) . "\n";
            }
            
            if ($lineCount > $showLines) {
                echo "\n... (" . ($lineCount - $showLines) . " líneas más)";
            }
            ?></pre>
        <?php endif; ?>
        
        <!-- Recomendaciones -->
        <div class="recommendations">
            <h3>💡 Recomendaciones para Yii2</h3>
            <ul>
                <?php if ($existingFiles < $totalFiles): ?>
                    <li><strong>Archivos faltantes:</strong> Crea los archivos CSS que no existen en la estructura modularizada.</li>
                <?php endif; ?>
                
                <?php if ($referencedFiles < $existingFiles): ?>
                    <li><strong>Archivos no referenciados:</strong> Verifica que todos los archivos existentes estén correctamente importados en ged.css con @import.</li>
                <?php endif; ?>
                
                <?php if ($gedCssExists && empty($importsInGed)): ?>
                    <li><strong>Sin importaciones:</strong> ged.css debe contener importaciones @import de todos los módulos.</li>
                <?php endif; ?>
                
                <li><strong>Configuración Yii2 AppAsset:</strong> En AppAsset.php, solo debes registrar ged.css:
<pre>public $css = [
    'css/ged.css',  // SOLO este archivo
];</pre>
                </li>
                <li><strong>Orden de importación:</strong> Asegúrate de que los estilos responsive siempre sean los últimos (después de _navigation.css).</li>
                <li><strong>Cache:</strong> Limpia el cache de Yii2 y del navegador después de hacer cambios: <code>yii cache/flush-all</code></li>
                <li><strong>Archivos opcionales:</strong> Si necesitas usar reportes.css, debes agregarlo a ged.css o incluirlo por separado.</li>
            </ul>
        </div>
        
        <!-- Información del servidor -->
        <h2>🖥️ Información del Entorno Yii2</h2>
        <div class="status-box">
            <p><strong>Directorio CSS:</strong> <?= Html::encode($cssDir) ?></p>
            <p><strong>ged.css existe:</strong> <?= $gedCssExists ? 'Sí' : 'No' ?></p>
            <p><strong>Archivos modularizados encontrados:</strong> <?= $existingFiles ?> de <?= $totalFiles ?></p>
            <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
            <p><strong>Yii2 Version:</strong> <?= Yii::getVersion() ?></p>
            <p><strong>Servidor Web:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></p>
            <p><strong>Tiempo de Ejecución:</strong> <?= round(microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)), 4) ?> segundos</p>
        </div>
        
        <!-- Debug info -->
        <div class="debug-info">
            <p><strong>Debug Info:</strong></p>
            <p>URL Accedida: <?= Yii::$app->request->url ?></p>
            <p>Ruta Base (@webroot): <?= Html::encode(Yii::getAlias('@webroot')) ?></p>
            <p>Ruta Web (@web): <?= Html::encode(Yii::getAlias('@web')) ?></p>
            <p>AppAsset Path: <?= Yii::getAlias('@web') . '/css/' ?></p>
        </div>
    </div>
</body>
</html>