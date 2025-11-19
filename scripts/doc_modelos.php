<?php
$output = "# MÓDULOS DEL SISTEMA\n\n";
$modulesDir = 'modules';
$modules = scandir($modulesDir);

foreach ($modules as $module) {
    if ($module !== '.' && $module !== '..' && is_dir($modulesDir . '/' . $module)) {
        $output .= "## 🏗️ MÓDULO: " . strtoupper($module) . "\n\n";
        
        // Controllers del módulo
        $controllersPath = $modulesDir . '/' . $module . '/controllers';
        if (is_dir($controllersPath)) {
            $controllers = scandir($controllersPath);
            $output .= "### 🎮 Controladores:\n";
            foreach ($controllers as $controller) {
                if (pathinfo($controller, PATHINFO_EXTENSION) === 'php') {
                    $output .= "- `{$controller}`\n";
                }
            }
            $output .= "\n";
        }
        
        // Vistas del módulo
        $viewsPath = $modulesDir . '/' . $module . '/views';
        if (is_dir($viewsPath)) {
            $viewCount = countViews($viewsPath);
            $output .= "### 👁️ Vistas: $viewCount archivos\n\n";
        }
        
        $output .= "---\n\n";
    }
}

file_put_contents('docs/modulos_detallados.md', $output);

function countViews($dir) {
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