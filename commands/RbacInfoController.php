<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

class RbacInfoController extends Controller
{
    public function actionRoles()
    {
        $auth = Yii::$app->authManager;
        
        echo Console::renderColoredString("%g=== ROLES DEL SISTEMA ===%n\n\n");
        
        $roles = $auth->getRoles();
        
        foreach ($roles as $role) {
            echo Console::renderColoredString("%y" . str_pad($role->name, 20) . "%n - {$role->description}\n");
            
            // Obtener permisos de este rol
            $permisos = $auth->getPermissionsByRole($role->name);
            
            if (empty($permisos)) {
                echo "   " . Console::renderColoredString("%r❌ SIN PERMISOS%n\n");
            } else {
                foreach ($permisos as $permiso) {
                    echo "   ├─ " . Console::renderColoredString("%c{$permiso->name}%n") . " - {$permiso->description}\n";
                }
            }
            echo "\n";
        }
        
        echo Console::renderColoredString("%g=== RESUMEN ===%n\n");
        echo "Total roles: " . count($roles) . "\n";
        
        // Contar permisos totales
        $permisos = $auth->getPermissions();
        echo "Total permisos: " . count($permisos) . "\n";
    }
    
    public function actionPermissions()
    {
        $auth = Yii::$app->authManager;
        
        echo Console::renderColoredString("%g=== TODOS LOS PERMISOS ===%n\n\n");
        
        $permisos = $auth->getPermissions();
        $permisosPorModulo = [];
        
        foreach ($permisos as $permiso) {
            // Extraer módulo del nombre del permiso
            $partes = explode('_', $permiso->name);
            $modulo = $partes[0] ?? 'general';
            
            if (!isset($permisosPorModulo[$modulo])) {
                $permisosPorModulo[$modulo] = [];
            }
            
            $permisosPorModulo[$modulo][] = $permiso;
        }
        
        // Mostrar por módulo
        foreach ($permisosPorModulo as $modulo => $permisosModulo) {
            echo Console::renderColoredString("%b📦 MÓDULO: " . strtoupper($modulo) . "%n\n");
            
            foreach ($permisosModulo as $permiso) {
                // Verificar qué roles tienen este permiso
                $roles = $auth->getRolesByPermission($permiso->name);
                $rolesStr = empty($roles) ? 
                    Console::renderColoredString("%r[NINGUNO]%n") : 
                    implode(', ', array_keys($roles));
                
                echo "   " . str_pad($permiso->name, 25) . " - " . 
                     str_pad($permiso->description, 40) . 
                     " → Roles: {$rolesStr}\n";
            }
            echo "\n";
        }
    }
    
    public function actionUsers()
    {
        echo Console::renderColoredString("%g=== USUARIOS Y SUS ROLES ===%n\n\n");
        
        $auth = Yii::$app->authManager;
        
        $usuarios = (new \yii\db\Query())
            ->select(['id', 'username', 'email', 'status'])
            ->from('seguridad.user')
            ->orderBy('id')
            ->all();
            
        foreach ($usuarios as $usuario) {
            $statusText = $usuario['status'] == 10 ? '✅ Activo' : '❌ Inactivo';
            
            echo Console::renderColoredString("%y👤 ID: {$usuario['id']} - {$usuario['username']}%n ({$usuario['email']}) - {$statusText}\n");
            
            $roles = $auth->getRolesByUser($usuario['id']);
            
            if (empty($roles)) {
                echo "   " . Console::renderColoredString("%r❌ SIN ROLES ASIGNADOS%n\n");
            } else {
                foreach ($roles as $rol) {
                    echo "   ├─ " . Console::renderColoredString("%g{$rol->name}%n") . " - {$rol->description}\n";
                    
                    // Opcional: Mostrar permisos directos del rol
                    $permisos = $auth->getPermissionsByRole($rol->name);
                    if (!empty($permisos)) {
                        echo "   │  Permisos: " . count($permisos) . "\n";
                    }
                }
            }
            echo "\n";
        }
    }
    
    public function actionMenuPermissions()
    {
        echo Console::renderColoredString("%g=== MENÚS Y SUS PERMISOS ===%n\n\n");
        
        $menus = (new \yii\db\Query())
            ->select(['id', 'name', 'route', 'permission', 'parent'])
            ->from('seguridad.menu')
            ->where(['active' => true])
            ->orderBy(['parent' => SORT_ASC, '"order"' => SORT_ASC])
            ->all();
            
        $auth = Yii::$app->authManager;
        
        echo Console::renderColoredString("%b📊 Menús principales (nivel 1):%n\n");
        foreach ($menus as $menu) {
            if (empty($menu['parent'])) {
                $this->printMenuInfo($menu, $auth);
            }
        }
        
        echo Console::renderColoredString("%b📊 Submenús (nivel 2):%n\n");
        foreach ($menus as $menu) {
            if (!empty($menu['parent'])) {
                $this->printMenuInfo($menu, $auth, 1);
            }
        }
    }
    
    private function printMenuInfo($menu, $auth, $nivel = 0)
    {
        $indent = str_repeat('   ', $nivel);
        
        echo $indent . Console::renderColoredString("%y📋 {$menu['name']}%n");
        echo " (Ruta: {$menu['route']})\n";
        
        if (empty($menu['permission'])) {
            echo $indent . "   " . Console::renderColoredString("%c🔓 PÚBLICO - Sin permiso requerido%n\n");
        } else {
            $permiso = $auth->getPermission($menu['permission']);
            
            if ($permiso) {
                echo $indent . "   Permiso requerido: " . 
                     Console::renderColoredString("%g{$menu['permission']}%n") . 
                     " - {$permiso->description}\n";
                
                // Qué roles tienen este permiso
                $roles = $auth->getRolesByPermission($menu['permission']);
                if (!empty($roles)) {
                    $rolesNombres = [];
                    foreach ($roles as $rol) {
                        $rolesNombres[] = $rol->name;
                    }
                    echo $indent . "   Roles con acceso: " . implode(', ', $rolesNombres) . "\n";
                } else {
                    echo $indent . "   " . Console::renderColoredString("%r⚠️  NINGÚN ROL TIENE ESTE PERMISO%n\n");
                }
            } else {
                echo $indent . "   " . 
                     Console::renderColoredString("%r❌ PERMISO NO EXISTE EN RBAC: {$menu['permission']}%n\n");
            }
        }
        echo "\n";
    }
    
    public function actionAll()
    {
        $this->actionRoles();
        echo "\n" . str_repeat("=", 80) . "\n\n";
        $this->actionPermissions();
        echo "\n" . str_repeat("=", 80) . "\n\n";
        $this->actionUsers();
        echo "\n" . str_repeat("=", 80) . "\n\n";
        $this->actionMenuPermissions();
    }
}