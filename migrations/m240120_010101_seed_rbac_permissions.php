<?php

use yii\db\Migration;
use yii\rbac\Role;
use yii\rbac\Permission;

class m240120_010101_seed_rbac_permissions extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            echo "=== Verificando configuración RBAC existente ===\n";
            
            // 1. PRIMERO: Listar lo que ya existe
            $existingRoles = $auth->getRoles();
            $existingPermissions = $auth->getPermissions();
            
            echo "Roles existentes: " . count($existingRoles) . "\n";
            echo "Permisos existentes: " . count($existingPermissions) . "\n";
            
            // 2. DEFINIR PERMISOS (solo crear si no existen)
            $permisos = [
                // Módulo Atletas
                'acceder_atletas' => 'Acceder al módulo de Atletas',
                'crear_atleta' => 'Crear nuevo atleta',
                'editar_atleta' => 'Editar atleta',
                'eliminar_atleta' => 'Eliminar atleta',
                'ver_atleta' => 'Ver información de atleta',
                
                // Módulo Tienda/Marketplace
                'acceder_tienda' => 'Acceder al Marketplace',
                'comprar_producto' => 'Comprar productos',
                'vender_producto' => 'Vender productos',
                'gestionar_tienda' => 'Gestionar tienda propia',
                
                // Módulo Escuela/Club
                'acceder_escuela_club' => 'Acceder a Escuela/Club',
                'gestionar_escuela' => 'Gestionar escuela',
                'ver_escuela' => 'Ver información de escuela',
                
                // Módulo Reportes
                'acceder_reportes' => 'Acceder a reportes',
                'generar_reportes' => 'Generar reportes',
                'exportar_reportes' => 'Exportar reportes',
                
                // Módulo Aportes
                'acceder_aportes' => 'Acceder a aportes',
                'registrar_aporte' => 'Registrar aporte',
                'ver_aportes' => 'Ver aportes',
                
                // Permisos generales
                'acceder_admin' => 'Acceder al panel de administración',
                'gestionar_usuarios' => 'Gestionar usuarios',
                'gestionar_roles' => 'Gestionar roles y permisos',
            ];
            
            // Crear permisos (si no existen)
            foreach ($permisos as $nombre => $descripcion) {
                if (!$auth->getPermission($nombre)) {
                    echo "Creando permiso: $nombre\n";
                    $permiso = $auth->createPermission($nombre);
                    $permiso->description = $descripcion;
                    $auth->add($permiso);
                } else {
                    echo "Permiso ya existe: $nombre\n";
                }
            }
            
            // 3. DEFINIR ROLES (solo crear si no existen)
            $roles = [
                'administrador' => 'Administrador del sistema',
                'entrenador' => 'Entrenador/Instructor',
                'atleta' => 'Atleta/Deportista',
                'invitado' => 'Usuario invitado',
                'vendedor' => 'Vendedor en Marketplace',
                'director_escuela' => 'Director de Escuela/Club',
            ];
            
            $rolObjetos = [];
            foreach ($roles as $nombre => $descripcion) {
                if (!$auth->getRole($nombre)) {
                    echo "Creando rol: $nombre\n";
                    $rol = $auth->createRole($nombre);
                    $rol->description = $descripcion;
                    $auth->add($rol);
                    $rolObjetos[$nombre] = $rol;
                } else {
                    echo "Rol ya existe: $nombre\n";
                    $rolObjetos[$nombre] = $auth->getRole($nombre);
                }
            }
            
            // 4. ASIGNAR PERMISOS A ROLES
            
            // Administrador: TODOS los permisos
            if (isset($rolObjetos['administrador'])) {
                echo "Asignando permisos a Administrador...\n";
                foreach ($permisos as $nombre => $desc) {
                    $permiso = $auth->getPermission($nombre);
                    if ($permiso && !$auth->hasChild($rolObjetos['administrador'], $permiso)) {
                        $auth->addChild($rolObjetos['administrador'], $permiso);
                    }
                }
            }
            
            // Entrenador
            if (isset($rolObjetos['entrenador'])) {
                $permisosEntrenador = [
                    'acceder_atletas', 'crear_atleta', 'editar_atleta', 'ver_atleta',
                    'acceder_tienda', 'comprar_producto',
                    'acceder_escuela_club', 'ver_escuela',
                    'acceder_reportes', 'ver_aportes',
                ];
                
                echo "Asignando permisos a Entrenador...\n";
                foreach ($permisosEntrenador as $permisoNombre) {
                    $permiso = $auth->getPermission($permisoNombre);
                    if ($permiso && !$auth->hasChild($rolObjetos['entrenador'], $permiso)) {
                        $auth->addChild($rolObjetos['entrenador'], $permiso);
                    }
                }
            }
            
            // Atleta
            if (isset($rolObjetos['atleta'])) {
                $permisosAtleta = [
                    'acceder_tienda', 'comprar_producto',
                    'acceder_escuela_club', 'ver_escuela',
                    'ver_aportes',
                ];
                
                echo "Asignando permisos a Atleta...\n";
                foreach ($permisosAtleta as $permisoNombre) {
                    $permiso = $auth->getPermission($permisoNombre);
                    if ($permiso && !$auth->hasChild($rolObjetos['atleta'], $permiso)) {
                        $auth->addChild($rolObjetos['atleta'], $permiso);
                    }
                }
            }
            
            // Invitado
            if (isset($rolObjetos['invitado'])) {
                $permisosInvitado = [
                    'acceder_tienda', 'comprar_producto',
                ];
                
                echo "Asignando permisos a Invitado...\n";
                foreach ($permisosInvitado as $permisoNombre) {
                    $permiso = $auth->getPermission($permisoNombre);
                    if ($permiso && !$auth->hasChild($rolObjetos['invitado'], $permiso)) {
                        $auth->addChild($rolObjetos['invitado'], $permiso);
                    }
                }
            }
            
            // Vendedor
            if (isset($rolObjetos['vendedor'])) {
                $permisosVendedor = [
                    'acceder_tienda', 'vender_producto', 'gestionar_tienda',
                    'comprar_producto',
                ];
                
                echo "Asignando permisos a Vendedor...\n";
                foreach ($permisosVendedor as $permisoNombre) {
                    $permiso = $auth->getPermission($permisoNombre);
                    if ($permiso && !$auth->hasChild($rolObjetos['vendedor'], $permiso)) {
                        $auth->addChild($rolObjetos['vendedor'], $permiso);
                    }
                }
            }
            
            // Director de Escuela
            if (isset($rolObjetos['director_escuela'])) {
                $permisosDirector = [
                    'acceder_escuela_club', 'gestionar_escuela', 'ver_escuela',
                    'acceder_atletas', 'crear_atleta', 'editar_atleta', 'ver_atleta',
                    'acceder_reportes',
                ];
                
                echo "Asignando permisos a Director de Escuela...\n";
                foreach ($permisosDirector as $permisoNombre) {
                    $permiso = $auth->getPermission($permisoNombre);
                    if ($permiso && !$auth->hasChild($rolObjetos['director_escuela'], $permiso)) {
                        $auth->addChild($rolObjetos['director_escuela'], $permiso);
                    }
                }
            }
            
            // 5. ASIGNAR ROL ADMIN AL USUARIO 1 (si no está asignado)
            $adminRole = $auth->getRole('administrador');
            if ($adminRole && !$auth->getAssignment('administrador', 1)) {
                echo "Asignando rol Administrador al usuario ID 1\n";
                $auth->assign($adminRole, 1);
            }
            
            $transaction->commit();
            echo "=== Migración completada exitosamente ===\n";
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            echo "ERROR durante migración: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    public function safeDown()
    {
        echo "m240120_010101_seed_rbac_permissions no puede ser revertida.\n";
        echo "Use la interfaz de administración RBAC para eliminar roles/permisos manualmente.\n";
        return false;
    }
}