<?php

namespace app\components;

use yii\rbac\Rule;
use app\models\AtletasRegistro;
use app\models\RegistroRepresentantes;

class RepresentedAporteRule extends Rule
{
    public $name = 'isRepresentedAporte';

    public function execute($user, $item, $params)
    {
        // Verificar si hay atleta en los parámetros
        if (!isset($params['atleta'])) {
            \Yii::error('Parámetro atleta no encontrado en regla RepresentedAporteRule');
            return false;
        }

        $atleta = $params['atleta'];
        $atletaId = null;
        
        // Obtener ID del atleta según el tipo de parámetro
        if ($atleta instanceof AtletasRegistro) {
            $atletaId = $atleta->id;
        } elseif (is_array($atleta) && isset($atleta['id'])) {
            $atletaId = $atleta['id'];
        } elseif (is_numeric($atleta)) {
            $atletaId = $atleta;
        } else {
            return false;
        }
        
        // Buscar el representante del usuario actual
        $representante = RegistroRepresentantes::find()
            ->where(['user_id' => $user])
            ->one();
            
        if (!$representante) {
            return false;
        }
        
        // Buscar el atleta y verificar si este representante es su representante
        $atletaModel = AtletasRegistro::findOne($atletaId);
        return $atletaModel && $atletaModel->id_representante == $representante->id;
    }
}