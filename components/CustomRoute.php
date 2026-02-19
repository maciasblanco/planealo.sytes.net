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