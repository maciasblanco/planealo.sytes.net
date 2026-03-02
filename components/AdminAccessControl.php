<?php

namespace app\components;

use Yii;
use mdm\admin\components\AccessControl;

/**
 * Extiende el AccessControl de mdm\admin para permitir acceso total al superusuario (ID 1).
 */
class AdminAccessControl extends AccessControl
{
    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        // Si el usuario es el superusuario (ID 1), se permite el acceso sin más verificaciones
        if (Yii::$app->user->id == 1) {
            return true;
        }

        // Para el resto de usuarios, se aplica la lógica normal del componente original
        return parent::beforeAction($action);
    }
}