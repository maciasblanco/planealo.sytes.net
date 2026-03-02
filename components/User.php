<?php

namespace app\components;

use Yii;

/**
 * Extiende el componente yii\web\User para otorgar todos los permisos al superusuario (ID 1).
 */
class User extends \yii\web\User
{
    /**
     * {@inheritdoc}
     */
    public function can($permissionName, $params = [], $allowCaching = true)
    {
        // Si el usuario actual es el superusuario (ID 1), se concede el permiso automáticamente
        if ($this->getId() == 1) {
            return true;
        }

        // Para el resto de usuarios, se delega al método original
        return parent::can($permissionName, $params, $allowCaching);
    }
}