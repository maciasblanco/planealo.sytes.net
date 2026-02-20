<?php
namespace app\components;

use Yii;
use yii\base\BaseObject;
use yii\base\Module;

class CustomRoute extends BaseObject
{
    /**
     * Obtiene todas las rutas de la aplicación.
     */
    public static function getAppRoutes($module = null, $prefix = '')
    {
        if ($module === null) {
            $module = Yii::$app;
        }

        $routes = [];
        self::scanControllers($module, $prefix, $routes);

        // Escanear submódulos forzando su carga
        foreach ($module->getModules() as $id => $childConfig) {
            // El segundo parámetro "true" fuerza la instanciación del módulo
            if (($child = $module->getModule($id, true)) !== null) {
                $childPrefix = $prefix . $id . '/';
                Yii::info("Escaneando submódulo: $id (prefijo: $childPrefix)", 'rbac');
                // MOD: Se capturan las rutas del submódulo y se fusionan
                $routes = array_merge($routes, self::getAppRoutes($child, $childPrefix));
            } else {
                Yii::warning("No se pudo cargar el módulo: $id", 'rbac');
            }
        }

        return $routes;
    }

    /**
     * Escanea recursivamente los controladores de un módulo.
     */
    private static function scanControllers($module, $prefix, &$routes, $basePath = null, $baseNamespace = null)
    {
        if (!isset($module->controllerNamespace)) {
            return;
        }

        if ($basePath === null) {
            $namespace = $module->controllerNamespace;
            $alias = '@' . str_replace('\\', '/', $namespace);
            $basePath = Yii::getAlias($alias, false);
            $baseNamespace = $namespace;
        }

        if (!$basePath || !is_dir($basePath)) {
            Yii::warning("Directorio no encontrado: $basePath", 'rbac');
            return;
        }

        Yii::info("Escaneando directorio: $basePath (namespace: $baseNamespace)", 'rbac');

        // Buscar archivos de controlador
        $files = glob($basePath . '/*Controller.php');
        foreach ($files as $file) {
            $controllerName = basename($file, 'Controller.php');
            $relativePath = substr(dirname($file), strlen($basePath));
            $relativePath = str_replace('/', '\\', trim($relativePath, '\\/'));
            $controllerClass = $baseNamespace . ($relativePath ? '\\' . $relativePath : '') . '\\' . basename($file, '.php');

            if (!class_exists($controllerClass)) {
                Yii::warning("No se pudo cargar la clase '$controllerClass' desde $file", 'rbac');
                continue;
            }

            try {
                $reflection = new \ReflectionClass($controllerClass);
                $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $method) {
                    if (strpos($method->name, 'action') === 0 && $method->name !== 'actions') {
                        $actionName = substr($method->name, 6);
                        $route = $prefix . self::camel2id($controllerName) . '/' . self::camel2id($actionName);
                        $routes[] = '/' . $route;
                        Yii::info("Ruta encontrada: /$route", 'rbac');
                    }
                }
            } catch (\Exception $e) {
                Yii::warning("Error al reflejar la clase: " . $e->getMessage(), 'rbac');
            }
        }

        // Escanear subdirectorios
        $subdirs = glob($basePath . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            self::scanControllers($module, $prefix, $routes, $subdir, $baseNamespace);
        }
    }

    private static function camel2id($name)
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '-$1', $name));
    }
}