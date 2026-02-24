<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use app\components\OwnAporteRule;
use app\components\RepresentedAporteRule;

class RbacController extends Controller
{
    /**
     * Inicializar RBAC completo
     */
    public function actionInit()
    {
        $auth = Yii::$app->authManager;
        
        // Limpiar todo primero
        $this->stdout("Limpiando RBAC anterior...\n", Console::FG_YELLOW);
        $auth->removeAll();
        
        // ========== CREAR REGLAS ==========
        $this->stdout("Creando reglas...\n", Console::FG_GREEN);
        
        $ownAporteRule = new OwnAporteRule();
        $auth->add($ownAporteRule);

        $representedAporteRule = new RepresentedAporteRule();
        $auth->add($representedAporteRule);

        // ========== CREAR PERMISOS ==========
        $this->stdout("Creando permisos...\n", Console::FG_GREEN);

        // Permisos existentes
        $viewOwnAportes = $auth->createPermission('viewOwnAportes');
        $viewOwnAportes->description = 'Ver sus propios aportes';
        $viewOwnAportes->ruleName = $ownAporteRule->name;
        $auth->add($viewOwnAportes);

        $viewOwnAsistencias = $auth->createPermission('viewOwnAsistencias');
        $viewOwnAsistencias->description = 'Ver sus propias asistencias';
        $viewOwnAsistencias->ruleName = $ownAporteRule->name;
        $auth->add($viewOwnAsistencias);

        $viewRepresentedAportes = $auth->createPermission('viewRepresentedAportes');
        $viewRepresentedAportes->description = 'Ver aportes de representados';
        $viewRepresentedAportes->ruleName = $representedAporteRule->name;
        $auth->add($viewRepresentedAportes);

        $viewRepresentedAsistencias = $auth->createPermission('viewRepresentedAsistencias');
        $viewRepresentedAsistencias->description = 'Ver asistencias de representados';
        $viewRepresentedAsistencias->ruleName = $representedAporteRule->name;
        $auth->add($viewRepresentedAsistencias);

        // ========== CREAR NUEVOS PERMISOS ESPECÍFICOS ==========
        $this->stdout("Creando nuevos permisos específicos...\n", Console::FG_GREEN);

        // Permiso para ver información personal del atleta
        $viewOwnInfo = $auth->createPermission('viewOwnInfo');
        $viewOwnInfo->description = 'Ver su propia información personal';
        $viewOwnInfo->ruleName = $ownAporteRule->name;
        $auth->add($viewOwnInfo);

        // Permiso para ver deudas propias
        $viewOwnDeudas = $auth->createPermission('viewOwnDeudas');
        $viewOwnDeudas->description = 'Ver sus propias deudas';
        $viewOwnDeudas->ruleName = $ownAporteRule->name;
        $auth->add($viewOwnDeudas);

        // Permiso para ver información de representados
        $viewRepresentedInfo = $auth->createPermission('viewRepresentedInfo');
        $viewRepresentedInfo->description = 'Ver información de atletas representados';
        $viewRepresentedInfo->ruleName = $representedAporteRule->name;
        $auth->add($viewRepresentedInfo);

        // Permiso para ver deudas de representados
        $viewRepresentedDeudas = $auth->createPermission('viewRepresentedDeudas');
        $viewRepresentedDeudas->description = 'Ver deudas de atletas representados';
        $viewRepresentedDeudas->ruleName = $representedAporteRule->name;
        $auth->add($viewRepresentedDeudas);

        // Permisos para cambio de contraseña y verificación de email
        $changeOwnPassword = $auth->createPermission('changeOwnPassword');
        $changeOwnPassword->description = 'Cambiar su propia contraseña';
        $auth->add($changeOwnPassword);

        $verifyOwnEmail = $auth->createPermission('verifyOwnEmail');
        $verifyOwnEmail->description = 'Verificar su propio correo electrónico';
        $auth->add($verifyOwnEmail);

        // NUEVO PERMISO PARA ACCESO AL MÓDULO GED
        $accessGedModule = $auth->createPermission('accessGedModule');
        $accessGedModule->description = 'Acceder al módulo principal GED';
        $auth->add($accessGedModule);

        // ========== CREAR ROLES ==========
        $this->stdout("Creando roles...\n", Console::FG_GREEN);

        $atleta = $auth->createRole('atleta');
        $auth->add($atleta);
        $auth->addChild($atleta, $viewOwnAportes);
        $auth->addChild($atleta, $viewOwnAsistencias);
        $auth->addChild($atleta, $viewOwnInfo);
        $auth->addChild($atleta, $viewOwnDeudas);
        $auth->addChild($atleta, $changeOwnPassword);
        $auth->addChild($atleta, $verifyOwnEmail);
        $auth->addChild($atleta, $accessGedModule);  // Asignar acceso al módulo GED

        $representante = $auth->createRole('representante');
        $auth->add($representante);
        $auth->addChild($representante, $viewRepresentedAportes);
        $auth->addChild($representante, $viewRepresentedAsistencias);
        $auth->addChild($representante, $viewRepresentedInfo);
        $auth->addChild($representante, $viewRepresentedDeudas);
        $auth->addChild($representante, $changeOwnPassword);
        $auth->addChild($representante, $verifyOwnEmail);
        $auth->addChild($representante, $accessGedModule);  // Asignar acceso al módulo GED

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $atleta);
        $auth->addChild($admin, $representante);

        // ========== ASIGNAR ROLES DE EJEMPLO ==========
        $this->stdout("Asignando roles de ejemplo...\n", Console::FG_GREEN);
        
        try {
            $auth->assign($admin, 1);
            $this->stdout("Rol 'admin' asignado al usuario ID 1\n", Console::FG_BLUE);
        } catch (\Exception $e) {
            $this->stdout("No se pudo asignar admin al usuario 1: " . $e->getMessage() . "\n", Console::FG_RED);
        }

        $this->stdout("✅ RBAC inicializado correctamente.\n", Console::FG_GREEN);
        $this->stdout("Reglas creadas: isOwnAporte, isRepresentedAporte\n", Console::FG_CYAN);
        $this->stdout("Roles creados: atleta, representante, admin\n", Console::FG_CYAN);
        $this->stdout("Permisos creados: viewOwnInfo, viewOwnDeudas, viewRepresentedInfo, viewRepresentedDeudas, changeOwnPassword, verifyOwnEmail, accessGedModule\n", Console::FG_CYAN);
    }

    /**
     * Limpiar solo las reglas corruptas
     */
    public function actionFixRules()
    {
        $auth = Yii::$app->authManager;
        
        $this->stdout("Buscando reglas corruptas...\n", Console::FG_YELLOW);
        
        try {
            $rules = $auth->getRules();
            $this->stdout("Reglas encontradas: " . count($rules) . "\n", Console::FG_CYAN);
            
            foreach ($rules as $rule) {
                $this->stdout(" - " . $rule->name . "\n", Console::FG_CYAN);
            }
        } catch (\Exception $e) {
            $this->stdout("❌ Error al leer reglas: " . $e->getMessage() . "\n", Console::FG_RED);
            $this->stdout("Intentando limpiar reglas corruptas...\n", Console::FG_YELLOW);
            
            // Limpiar reglas específicas
            $auth->remove($auth->getRule('isOwnAporte'));
            $auth->remove($auth->getRule('isRepresentedAporte'));
            
            $this->stdout("✅ Reglas corruptas eliminadas.\n", Console::FG_GREEN);
        }
    }

    /**
     * Verificar estado del RBAC
     */
    public function actionStatus()
    {
        $auth = Yii::$app->authManager;
        
        $this->stdout("=== ESTADO RBAC ===\n", Console::FG_BLUE);
        
        // Reglas
        try {
            $rules = $auth->getRules();
            $this->stdout("Reglas: " . count($rules) . "\n", Console::FG_CYAN);
            foreach ($rules as $rule) {
                $this->stdout("  - {$rule->name}\n", Console::FG_CYAN);
            }
        } catch (\Exception $e) {
            $this->stdout("❌ Error en reglas: " . $e->getMessage() . "\n", Console::FG_RED);
        }
        
        // Roles
        $roles = $auth->getRoles();
        $this->stdout("Roles: " . count($roles) . "\n", Console::FG_CYAN);
        foreach ($roles as $role) {
            $this->stdout("  - {$role->name}\n", Console::FG_CYAN);
        }
        
        // Permisos
        $permissions = $auth->getPermissions();
        $this->stdout("Permisos: " . count($permissions) . "\n", Console::FG_CYAN);
        foreach ($permissions as $permission) {
            $this->stdout("  - {$permission->name} ({$permission->description})\n", Console::FG_CYAN);
        }
    }
}