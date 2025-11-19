<?php
// Generar un resumen ejecutivo para contexto rápido
$resumen = "# 📊 RESUMEN EJECUTIVO - PLANEALO\n\n";
$resumen .= "**Generado automáticamente el:** " . date('Y-m-d H:i:s') . "\n\n";

// Estadísticas rápidas
$resumen .= "## 📈 ESTADÍSTICAS DEL PROYECTO\n\n";
$resumen .= "- **Modelos:** " . count(glob('models/*.php')) . " archivos\n";
$resumen .= "- **Controladores:** " . count(glob('controllers/*.php')) . " archivos\n";
$resumen .= "- **Módulos:** " . count(array_filter(scandir('modules'), function($item) {
    return $item !== '.' && $item !== '..' && is_dir('modules/' . $item);
})) . " módulos activos\n";
$resumen .= "- **Vistas:** Aprox. " . countViewsRecursive('views') . " archivos\n\n";

// Módulos activos
$resumen .= "## 🏗️ MÓDULOS ACTIVOS\n\n";
$modules = array_filter(scandir('modules'), function($item) {
    return $item !== '.' && $item !== '..' && is_dir('modules/' . $item);
});

foreach ($modules as $module) {
    $resumen .= "- `{$module}`\n";
}

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
?>