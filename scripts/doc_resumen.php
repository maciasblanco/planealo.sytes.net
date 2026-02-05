<?php
// Generar un resumen ejecutivo para contexto rápido
$resumen = "# 📊 RESUMEN EJECUTIVO - PLANEALO\n\n";
$resumen .= "**Generado automáticamente el:** " . date('Y-m-d H:i:s') . "\n\n";

// Estadísticas rápidas
$resumen .= "## 📈 ESTADÍSTICAS DEL PROYECTO\n\n";
$resumen .= "- **Modelos:** " . count(glob('models/*.php')) . " archivos\n";
$resumen .= "- **Controladores principales:** " . count(glob('controllers/*.php')) . " archivos\n";

// Contar controladores en módulos
$controllerCount = 0;
$modules = array_filter(scandir('modules'), function($item) {
    return $item !== '.' && $item !== '..' && is_dir('modules/' . $item);
});
foreach ($modules as $module) {
    $moduleControllers = glob("modules/{$module}/controllers/*.php");
    $controllerCount += count($moduleControllers);
}
$resumen .= "- **Controladores en módulos:** " . $controllerCount . " archivos\n";

$resumen .= "- **Módulos:** " . count($modules) . " módulos activos\n";
$resumen .= "- **Vistas:** Aprox. " . countViewsRecursive('views') . " archivos\n\n";

// Módulos activos
$resumen .= "## 🏗️ MÓDULOS ACTIVOS\n\n";
foreach ($modules as $module) {
    $resumen .= "- `{$module}`\n";
    
    // Información rápida del módulo
    $moduleControllers = glob("modules/{$module}/controllers/*.php");
    $moduleViews = countViewsRecursive("modules/{$module}/views");
    $resumen .= "  - Controladores: " . count($moduleControllers) . "\n";
    $resumen .= "  - Vistas: ~" . $moduleViews . "\n";
}

// Dependencias principales
if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);
    $resumen .= "\n## 📦 DEPENDENCIAS PRINCIPALES\n\n";
    if (isset($composer['require'])) {
        $resumen .= "**Producción:** " . count($composer['require']) . " paquetes\n";
        // Mostrar solo las más importantes
        $importantPackages = ['yiisoft/yii2', 'yiisoft/yii2-bootstrap', 'yiisoft/yii2-swiftmailer'];
        foreach ($importantPackages as $pkg) {
            if (isset($composer['require'][$pkg])) {
                $resumen .= "- `{$pkg}`: {$composer['require'][$pkg]}\n";
            }
        }
    }
}

// Estado del sistema
$resumen .= "\n## 🟢 ESTADO DEL SISTEMA\n\n";
$resumen .= "- " . (file_exists('runtime') ? '✅' : '❌') . " Directorio runtime\n";
$resumen .= "- " . (file_exists('vendor') ? '✅' : '❌') . " Dependencias vendor\n";
$resumen .= "- " . (file_exists('web/assets') ? '✅' : '❌') . " Assets web\n";
$resumen .= "- " . (file_exists('docker-compose.yml') ? '✅' : '❌') . " Docker configurado\n";

file_put_contents('docs/RESUMEN_EJECUTIVO.md', $resumen);

function countViewsRecursive($dir) {
    if (!is_dir($dir)) return 0;
    $count = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $count++;
        }
    }
    return $count;
}

echo "✅ Resumen ejecutivo generado.\n";
?>