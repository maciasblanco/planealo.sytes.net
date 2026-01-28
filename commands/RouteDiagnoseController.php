<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class RouteDiagnoseController extends Controller
{
    public function actionIndex()
    {
        echo "=== DIAGNÓSTICO BÁSICO DE RUTAS MDM/ADMIN ===\n\n";
        
        // 1. Verificar si existe el modelo Route
        if (!class_exists('mdm\admin\models\Route')) {
            echo "ERROR: mdm\admin\models\Route no existe\n";
            echo "Verifica que mdm/yii2-admin esté instalado correctamente.\n";
            return;
        }
        
        echo "OK: Clase Route encontrada\n";
        
        // 2. Intentar usar reflexión para acceder al método protegido
        try {
            $routeModel = new \mdm\admin\models\Route();
            $reflection = new \ReflectionClass($routeModel);
            $method = $reflection->getMethod('getAppRoutes');
            $method->setAccessible(true);
            
            $routes = $method->invoke($routeModel);
            
            echo "\n1. RUTAS DETECTADAS POR MDM/ADMIN: " . count($routes) . "\n";
            
            if (count($routes) === 0) {
                echo "   CRITICO: No se detectaron rutas automáticamente.\n";
                echo "   Esto explica por qué no aparecen en la interfaz.\n";
            } else {
                echo "   Ejemplos (primeras 10):\n";
                foreach (array_slice($routes, 0, 10) as $route) {
                    echo "     - $route\n";
                }
            }
            
            // 3. Verificar módulos específicos
            echo "\n2. RUTAS POR MÓDULO:\n";
            
            $modules = ['reportes', 'atletas', 'aportes', 'tienda', 'escuela_club', 'admin'];
            $foundAny = false;
            
            foreach ($modules as $module) {
                $moduleRoutes = array_filter($routes, function($r) use ($module) {
                    return strpos($r, "/$module/") === 0;
                });
                
                $count = count($moduleRoutes);
                if ($count > 0) {
                    $foundAny = true;
                    echo "   [$module] $count rutas\n";
                    if ($count <= 3) {
                        foreach ($moduleRoutes as $route) {
                            echo "     - $route\n";
                        }
                    }
                } else {
                    echo "   [$module] 0 rutas [NO]\n";
                }
            }
            
            if (!$foundAny) {
                echo "\n   ATENCION: ¡No se encontraron rutas de NINGÚN módulo!\n";
                echo "   Esto indica que el método getAppRoutes() no está funcionando.\n";
            }
            
            // 4. Verificar qué rutas están en auth_item (asignadas)
            echo "\n3. RUTAS EN AUTH_ITEM (ASIGNADAS):\n";
            
            $auth = Yii::$app->authManager;
            $assigned = $auth->getPermissions();
            $assignedRoutes = array_keys($assigned);
            
            echo "   Total en auth_item: " . count($assignedRoutes) . "\n";
            
            // Comparar
            $available = array_diff($routes, $assignedRoutes);
            echo "   Rutas disponibles (no en auth_item): " . count($available) . "\n";
            
            if (count($available) > 0) {
                echo "   Ejemplos de rutas disponibles:\n";
                foreach (array_slice($available, 0, 10) as $route) {
                    echo "     - $route\n";
                }
            } else {
                echo "   [PROBLEMA] Todas las rutas detectadas ya están en auth_item.\n";
                echo "   Esto es por qué no aparecen como 'disponibles' en la interfaz.\n";
            }
            
        } catch (\Exception $e) {
            echo "Error al obtener rutas: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== FIN DIAGNÓSTICO ===\n";
    }
    
    public function actionDeepDiagnose()
    {
        echo "=== DIAGNÓSTICO PROFUNDO: POR QUÉ MDM/ADMIN NO DETECTA RUTAS ===\n\n";
        
        // 1. Verificar estructura de módulos
        echo "1. ESTRUCTURA DE MÓDULOS EN YII:\n";
        $this->printModulesTree(Yii::$app, 0);
        
        // 2. Verificar namespaces de controladores
        echo "\n2. NAMESPACES DE CONTROLADORES:\n";
        $this->checkControllerNamespaces();
        
        // 3. Intentar detectar rutas manualmente
        echo "\n3. DETECCIÓN MANUAL DE RUTAS:\n";
        $this->manualRouteDetection();
        
        // 4. Probar el método getAppRoutes() de mdm/admin
        echo "\n4. PRUEBA DEL MÉTODO GETAPPROUTES():\n";
        $this->testMdmGetAppRoutes();
        
        // 5. Verificar configuración de mdm/admin
        echo "\n5. CONFIGURACIÓN MDM/ADMIN:\n";
        $this->checkMdmConfig();
        
        echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
    }
    
    private function printModulesTree($module, $level)
    {
        $indent = str_repeat("  ", $level);
        echo $indent . "• " . $module->id . "\n";
        
        if (isset($module->controllerNamespace)) {
            echo $indent . "  Namespace: " . $module->controllerNamespace . "\n";
        }
        
        if ($module->hasProperty('modules')) {
            foreach ($module->modules as $id => $child) {
                echo $indent . "  └── Submódulo: $id\n";
                if (is_array($child) && isset($child['class'])) {
                    echo $indent . "      Clase: " . $child['class'] . "\n";
                }
            }
        }
        
        // Recursivamente procesar submódulos
        foreach ($module->getModules() as $id => $childModule) {
            if ($childModule instanceof \yii\base\Module) {
                $this->printModulesTree($childModule, $level + 1);
            }
        }
    }
    
    private function checkControllerNamespaces()
    {
        $app = Yii::$app;
        $modules = $app->getModules();
        
        foreach ($modules as $id => $config) {
            $module = $app->getModule($id);
            
            if ($module && isset($module->controllerNamespace)) {
                echo "  [$id] Namespace: {$module->controllerNamespace}\n";
                
                // Verificar si existe el directorio
                $alias = '@' . str_replace('\\', '/', $module->controllerNamespace);
                $path = Yii::getAlias($alias, false);
                
                if ($path && is_dir($path)) {
                    echo "       ✓ Directorio existe: $path\n";
                    
                    // Contar controladores
                    $controllers = glob($path . '/*Controller.php');
                    echo "       ✓ Controladores encontrados: " . count($controllers) . "\n";
                    
                    foreach ($controllers as $controller) {
                        echo "         - " . basename($controller) . "\n";
                    }
                } else {
                    echo "       ✗ Directorio NO existe: $alias\n";
                }
            } else {
                echo "  [$id] ✗ No tiene controllerNamespace definido\n";
            }
        }
    }
    
    private function manualRouteDetection()
    {
        echo "  Método 1: Usando Yii::\$app->urlManager->rules\n";
        $rules = Yii::$app->urlManager->rules;
        echo "    Reglas configuradas: " . count($rules) . "\n";
        
        echo "\n  Método 2: Escaneando controladores manualmente\n";
        $allRoutes = [];
        
        // Escanear módulo principal
        $this->scanModuleForRoutes(Yii::$app, '', $allRoutes);
        
        // Escanear módulos registrados
        foreach (Yii::$app->getModules() as $id => $module) {
            $moduleObj = Yii::$app->getModule($id);
            if ($moduleObj) {
                $this->scanModuleForRoutes($moduleObj, $id . '/', $allRoutes);
            }
        }
        
        echo "    Rutas encontradas manualmente: " . count($allRoutes) . "\n";
        
        if (count($allRoutes) > 0) {
            echo "    Ejemplos (primeras 15):\n";
            foreach (array_slice($allRoutes, 0, 15) as $route) {
                echo "      - $route\n";
            }
        }
        
        return $allRoutes;
    }
    
    private function scanModuleForRoutes($module, $prefix, &$routes)
    {
        if (!isset($module->controllerNamespace)) {
            return;
        }
        
        $namespace = $module->controllerNamespace;
        $alias = '@' . str_replace('\\', '/', $namespace);
        $path = Yii::getAlias($alias, false);
        
        if (!$path || !is_dir($path)) {
            return;
        }
        
        $files = glob($path . '/*Controller.php');
        
        foreach ($files as $file) {
            $controllerName = basename($file, 'Controller.php');
            $controllerClass = $namespace . '\\' . basename($file, '.php');
            
            if (!class_exists($controllerClass)) {
                continue;
            }
            
            $reflection = new \ReflectionClass($controllerClass);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                if (strpos($method->name, 'action') === 0 && $method->name !== 'actions') {
                    $actionName = substr($method->name, 6);
                    $route = $prefix . $this->camel2id($controllerName) . '/' . $this->camel2id($actionName);
                    $routes[] = '/' . $route;
                }
            }
        }
    }
    
    private function camel2id($name)
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '-$1', $name));
    }
    
    private function testMdmGetAppRoutes()
    {
        if (!class_exists('mdm\admin\models\Route')) {
            echo "  ✗ Clase mdm\\admin\\models\\Route no existe\n";
            return;
        }
        
        try {
            $routeModel = new \mdm\admin\models\Route();
            $reflection = new \ReflectionClass($routeModel);
            
            // Verificar si existe el método
            if (!$reflection->hasMethod('getAppRoutes')) {
                echo "  ✗ El método getAppRoutes() no existe\n";
                return;
            }
            
            $method = $reflection->getMethod('getAppRoutes');
            $method->setAccessible(true);
            $routes = $method->invoke($routeModel);
            
            echo "  ✓ Método getAppRoutes() ejecutado\n";
            echo "    Rutas devueltas: " . count($routes) . "\n";
            
            if (count($routes) > 0) {
                echo "    Ejemplos:\n";
                foreach (array_slice($routes, 0, 10) as $route) {
                    echo "      - $route\n";
                }
            } else {
                echo "    ⚠️ El método NO encontró ninguna ruta\n";
                
                // Debug adicional: ver qué hace internamente
                echo "\n    Debug del proceso interno:\n";
                $this->debugMdmProcess();
            }
            
        } catch (\Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    private function debugMdmProcess()
    {
        // Este es un hack para ver qué está haciendo mdm/admin internamente
        $routeModel = new \mdm\admin\models\Route();
        $reflection = new \ReflectionClass($routeModel);
        
        // Obtener propiedad 'module'
        if ($reflection->hasProperty('_module')) {
            $prop = $reflection->getProperty('_module');
            $prop->setAccessible(true);
            $module = $prop->getValue($routeModel);
            echo "    - Propiedad _module: " . ($module ? $module->id : 'NULL') . "\n";
        }
    }
    
    private function checkMdmConfig()
    {
        $configFile = Yii::getAlias('@app/config/web.php');
        if (file_exists($configFile)) {
            $config = require $configFile;
            
            echo "  Configuración en web.php:\n";
            
            // Buscar configuración de mdm/admin
            if (isset($config['modules']['admin'])) {
                echo "    ✓ Módulo 'admin' configurado\n";
                
                // Verificar si tiene el parámetro 'controllerMap'
                if (isset($config['modules']['admin']['controllerMap'])) {
                    echo "    ✓ Tiene controllerMap configurado\n";
                } else {
                    echo "    ⚠️ No tiene controllerMap configurado\n";
                }
            } else {
                echo "    ✗ No hay módulo 'admin' en la configuración\n";
            }
            
            // Buscar configuración de otros módulos
            echo "\n    Módulos registrados:\n";
            foreach ($config['modules'] ?? [] as $name => $moduleConfig) {
                echo "      - $name\n";
                if (is_array($moduleConfig) && isset($moduleConfig['controllerNamespace'])) {
                    echo "        Namespace: {$moduleConfig['controllerNamespace']}\n";
                }
            }
        } else {
            echo "  ✗ Archivo web.php no encontrado\n";
        }
    }
    
    public function actionClean()
    {
        echo "=== LIMPIAR RUTAS DE AUTH_ITEM ===\n\n";
        
        echo "ADVERTENCIA: Esto eliminará permisos de rutas de la base de datos.\n";
        echo "Solo deberías hacer esto si estás seguro.\n\n";
        
        echo "Módulos que se limpiarán:\n";
        echo "1. /reportes/\n";
        echo "2. /atletas/\n";
        echo "3. /aportes/\n";
        echo "4. /tienda/\n";
        echo "5. /escuela_club/\n\n";
        
        echo "¿Continuar? (s/n): ";
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        if(trim($line) != 's'){
            echo "Operación cancelada.\n";
            exit;
        }
        fclose($handle);
        
        $auth = Yii::$app->authManager;
        $modules = ['/reportes/', '/atletas/', '/aportes/', '/tienda/', '/escuela_club/'];
        
        foreach ($modules as $module) {
            echo "\nLimpiando $module ...\n";
            
            // Buscar permisos que comiencen con este módulo
            $permissions = $auth->getPermissions();
            $deleted = 0;
            
            foreach ($permissions as $permission) {
                if (strpos($permission->name, $module) === 0) {
                    try {
                        $auth->remove($permission);
                        $deleted++;
                        echo "  Eliminado: {$permission->name}\n";
                    } catch (\Exception $e) {
                        echo "  Error eliminando {$permission->name}: " . $e->getMessage() . "\n";
                    }
                }
            }
            
            echo "  Total eliminados: $deleted\n";
        }
        
        echo "\n=== LIMPIEZA COMPLETADA ===\n";
        echo "Ahora recarga /admin/route/index y haz clic en Refresh.\n";
        echo "Deberías ver rutas en la columna 'Disponibles'.\n";
    }
    
    public function actionFixMdmDetection()
    {
        echo "=== SOLUCIÓN: REPARAR DETECCIÓN DE RUTAS MDM/ADMIN ===\n\n";
        
        echo "Este comando intentará reparar la detección automática de rutas.\n\n";
        
        // Paso 1: Obtener rutas manualmente
        $allRoutes = [];
        $this->scanModuleForRoutes(Yii::$app, '', $allRoutes);
        
        foreach (Yii::$app->getModules() as $id => $module) {
            $moduleObj = Yii::$app->getModule($id);
            if ($moduleObj) {
                $this->scanModuleForRoutes($moduleObj, $id . '/', $allRoutes);
            }
        }
        
        echo "Rutas detectadas manualmente: " . count($allRoutes) . "\n\n";
        
        if (count($allRoutes) === 0) {
            echo "ERROR: No se encontraron rutas. Revisa la configuración de módulos.\n";
            return;
        }
        
        // Paso 2: Crear un Route personalizado
        echo "Creando Route personalizado para mdm/admin...\n";
        
        $routeClass = <<<'PHP'
<?php
namespace app\components;

use Yii;
use yii\base\BaseObject;

class CustomRoute extends BaseObject
{
    public static function getAppRoutes($module = null, $prefix = '')
    {
        if ($module === null) {
            $module = Yii::$app;
        }
        
        $routes = [];
        
        // Escanear controladores del módulo
        self::scanControllers($module, $prefix, $routes);
        
        // Escanear submódulos
        foreach ($module->getModules() as $id => $child) {
            if (($child = $module->getModule($id)) !== null) {
                $childPrefix = $prefix . $id . '/';
                self::getAppRoutes($child, $childPrefix);
            }
        }
        
        return $routes;
    }
    
    private static function scanControllers($module, $prefix, &$routes)
    {
        if (!isset($module->controllerNamespace)) {
            return;
        }
        
        $namespace = $module->controllerNamespace;
        $alias = '@' . str_replace('\\', '/', $namespace);
        $path = Yii::getAlias($alias, false);
        
        if (!$path || !is_dir($path)) {
            return;
        }
        
        $files = glob($path . '/*Controller.php');
        
        foreach ($files as $file) {
            $controllerName = basename($file, 'Controller.php');
            $controllerClass = $namespace . '\\' . basename($file, '.php');
            
            if (!class_exists($controllerClass)) {
                continue;
            }
            
            $reflection = new \ReflectionClass($controllerClass);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                if (strpos($method->name, 'action') === 0 && $method->name !== 'actions') {
                    $actionName = substr($method->name, 6);
                    $route = $prefix . self::camel2id($controllerName) . '/' . self::camel2id($actionName);
                    $routes[] = '/' . $route;
                }
            }
        }
    }
    
    private static function camel2id($name)
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '-$1', $name));
    }
}
PHP;

        $filePath = Yii::getAlias('@app/components/CustomRoute.php');
        if (file_put_contents($filePath, $routeClass)) {
            echo "✓ CustomRoute creado en: $filePath\n\n";
        } else {
            echo "✗ Error al crear CustomRoute\n";
        }
        
        // Paso 3: Crear controlador Route personalizado para mdm/admin
        echo "Creando RouteController personalizado...\n";
        
        $controllerClass = <<<'PHP'
<?php
namespace app\controllers;

use Yii;
use mdm\admin\components\Helper;
use app\components\CustomRoute;

class RouteController extends \mdm\admin\controllers\RouteController
{
    public function actionIndex()
    {
        // Usar nuestro CustomRoute para obtener rutas
        $routes = CustomRoute::getAppRoutes();
        
        // Filtrar rutas ya asignadas
        $assignedRoutes = Helper::getAssignedRoutes(Yii::$app->user->id);
        
        return $this->render('index', [
            'routes' => $routes,
            'assignedRoutes' => $assignedRoutes,
        ]);
    }
    
    public function actionRefresh()
    {
        // Simplemente redirigir al index
        return $this->redirect(['index']);
    }
}
PHP;

        $controllerPath = Yii::getAlias('@app/controllers/RouteController.php');
        if (file_put_contents($controllerPath, $controllerClass)) {
            echo "✓ RouteController creado en: $controllerPath\n\n";
        } else {
            echo "✗ Error al crear RouteController\n";
        }
        
        // Paso 4: Actualizar configuración
        echo "Actualizando configuración de web.php...\n";
        
        $configFile = Yii::getAlias('@app/config/web.php');
        if (file_exists($configFile)) {
            $config = require $configFile;
            
            // Agregar controllerMap para el módulo admin
            if (isset($config['modules']['admin'])) {
                $config['modules']['admin']['controllerMap']['route'] = 'app\controllers\RouteController';
                
                // Guardar la configuración
                $newConfig = "<?php\n\nreturn " . var_export($config, true) . ";\n";
                if (file_put_contents($configFile, $newConfig)) {
                    echo "✓ Configuración actualizada\n";
                } else {
                    echo "⚠️ Error al actualizar configuración\n";
                }
            } else {
                echo "⚠️ No se encontró módulo admin en la configuración\n";
            }
        } else {
            echo "✗ Archivo web.php no encontrado\n";
        }
        
        echo "\n=== SOLUCIÓN APLICADA ===\n";
        echo "Ahora recarga /admin/route/index y haz clic en Refresh.\n";
        echo "Deberías ver todas las rutas detectadas.\n";
    }
    
    public function actionQuickFix()
    {
        echo "=== SOLUCIÓN RÁPIDA ===\n\n";
        
        // Crear rutas manualmente en auth_item
        echo "Creando permisos para rutas manualmente...\n";
        
        $auth = Yii::$app->authManager;
        
        // Rutas comunes que deberían existir
        $commonRoutes = [
            // Módulo reportes
            '/reportes/default/index',
            '/reportes/default/create',
            '/reportes/default/view',
            '/reportes/default/update',
            '/reportes/default/delete',
            
            // Módulo atletas
            '/atletas/default/index',
            '/atletas/default/create',
            '/atletas/default/view',
            '/atletas/default/update',
            '/atletas/default/delete',
            
            // Módulo aportes
            '/aportes/default/index',
            '/aportes/default/create',
            '/aportes/default/view',
            '/aportes/default/update',
            '/aportes/default/delete',
            
            // Módulo tienda
            '/tienda/default/index',
            '/tienda/default/create',
            '/tienda/default/view',
            '/tienda/default/update',
            '/tienda/default/delete',
            
            // Módulo escuela_club
            '/escuela_club/default/index',
            '/escuela_club/default/create',
            '/escuela_club/default/view',
            '/escuela_club/default/update',
            '/escuela_club/default/delete',
        ];
        
        $created = 0;
        foreach ($commonRoutes as $route) {
            if (!$auth->getPermission($route)) {
                try {
                    $permission = $auth->createPermission($route);
                    $auth->add($permission);
                    $created++;
                    echo "  ✓ Creado: $route\n";
                } catch (\Exception $e) {
                    echo "  ✗ Error: $route - " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "\nTotal creados: $created\n";
        echo "\nAhora estas rutas aparecerán en la gestión de permisos.\n";
    }
}