<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.voleibol_sesion_atleta".
 *
 * @property int $id
 * @property int $sesion_id
 * @property int $atleta_id
 * @property string $equipo
 * @property string $created_at
 *
 * @property VoleibolSesion $sesion
 * @property AtletasRegistro $atleta
 */
class VoleibolSesionAtleta extends ActiveRecord
{
    public static function tableName()
    {
        return 'estadisticas.voleibol_sesion_atleta';
    }

    public function rules()
    {
        return [
            [['sesion_id', 'atleta_id', 'equipo'], 'required'],
            [['sesion_id', 'atleta_id'], 'default', 'value' => null],
            [['sesion_id', 'atleta_id'], 'integer'],
            [['equipo'], 'string'],
            [['equipo'], 'in', 'range' => ['A', 'B']],
            [['sesion_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSesion::class, 'targetAttribute' => ['sesion_id' => 'id']],
            [['atleta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sesion_id' => 'Sesión',
            'atleta_id' => 'Atleta',
            'equipo' => 'Equipo',
            'created_at' => 'Agregado',
        ];
    }

    public function getSesion()
    {
        return $this->hasOne(VoleibolSesion::class, ['id' => 'sesion_id']);
    }

    public function getAtleta()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'atleta_id']);
    }
}