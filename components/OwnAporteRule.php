<?php

namespace app\components;

use yii\rbac\Rule;
use app\models\AtletasRegistro;

class OwnAporteRule extends Rule
{
    public $name = 'isOwnAporte';

    public function execute($user, $item, $params)
    {
        // Verificar si hay atleta en los parámetros
        if (!isset($params['atleta'])) {
            \Yii::error('Parámetro atleta no encontrado en regla OwnAporteRule');
            return false;
        }

        $atleta = $params['atleta'];
        
        // Si es instancia de AtletasRegistro, verificar user_id
        if ($atleta instanceof AtletasRegistro) {
            return isset($atleta->user_id) && $atleta->user_id == $user;
        }
        
        // Si es un array, verificar el user_id
        if (is_array($atleta) && isset($atleta['user_id'])) {
            return $atleta['user_id'] == $user;
        }
        
        // Si es un ID, buscar el atleta
        if (is_numeric($atleta)) {
            $atletaModel = AtletasRegistro::findOne($atleta);
            return $atletaModel && isset($atletaModel->user_id) && $atletaModel->user_id == $user;
        }
        
        return false;
    }
}