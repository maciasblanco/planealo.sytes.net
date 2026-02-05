<?php
$output = "# CONTROLADORES DEL SISTEMA\n\n";

// Controladores principales
$output .= "## 🎮 CONTROLADORES PRINCIPALES\n\n";
$controllersDir = 'controllers';
if (is_dir($controllersDir)) {
    $controllers = scandir($controllersDir);
    foreach ($controllers as $controller) {
        if (pathinfo($controller, PATHINFO_EXTENSION) === 'php') {
            $output .= "### 🔹 " . basename($controller, '.php') . "\n\n";
            
            $content = file_get_contents($controllersDir . '/' . $controller);
            
            // Extraer información del controlador
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $output .= "**Clase:** `{$matches[1]}`\n\n";
            }
            
            // Contar acciones
            $actionCount = preg_match_all('/public\s+function\s+action(\w+)/', $content, $actionMatches);
            $output .= "**Acciones públicas:** $actionCount\n";
            
            // Listar acciones principales
            if ($actionCount > 0) {
                $output .= "**Acciones disponibles:**\n";
                foreach ($actionMatches[1] as $action) {
                    $output .= "- `{$action}`\n";
                }
            }
            
            // Buscar comportamientos comunes
            $behaviors = [];
            if (strpos($content, 'behaviors()') !== false) $behaviors[] = "Define behaviors";
            if (strpos($content, 'accessControl') !== false) $behaviors[] = "Control de acceso";
            if (strpos($content, 'verbs()') !== false) $behaviors[] = "Filtro de verbos HTTP";
            
            if (!empty($behaviors)) {
                $output .= "**Características:** " . implode(', ', $behaviors) . "\n";
            }
            
            $output .= "\n---\n\n";
        }
    }
}

// Controladores de módulos
$output .= "## 🏗️ CONTROLADORES EN MÓDULOS\n\n";
$modulesDir = 'modules';
if (is_dir($modulesDir)) {
    $modules = scandir($modulesDir);
    foreach ($modules as $module) {
        if ($module !== '.' && $module !== '..' && is_dir($modulesDir . '/' . $module)) {
            $moduleControllersDir = $modulesDir . '/' . $module . '/controllers';
            if (is_dir($moduleControllersDir)) {
                $output .= "### Módulo: `{$module}`\n\n";
                $controllers = scandir($moduleControllersDir);
                foreach ($controllers as $controller) {
                    if (pathinfo($controller, PATHINFO_EXTENSION) === 'php') {
                        $controllerName = basename($controller, '.php');
                        $output .= "- `{$controllerName}`\n";
                        
                        // Contar acciones rápidamente
                        $content = file_get_contents($moduleControllersDir . '/' . $controller);
                        $actionCount = preg_match_all('/public\s+function\s+action(\w+)/', $content);
                        $output .= "  - Acciones: $actionCount\n";
                    }
                }
                $output .= "\n";
            }
        }
    }
}

file_put_contents('docs/controladores_detallados.md', $output);
echo "✅ Documentación de controladores generada.\n";
?>