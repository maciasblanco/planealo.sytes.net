<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.voleibol_evento".
 *
 * @property int $id
 * @property int $sesion_id
 * @property int $set_id
 * @property int $tipo_evento_id
 * @property int $atleta_id
 * @property string $created_at
 * @property int $created_by
 *
 * @property VoleibolSesion $sesion
 * @property VoleibolSet $set
 * @property VoleibolTipoEvento $tipoEvento
 * @property AtletasRegistro $atleta
 * @property User $creador
 */
class VoleibolEvento extends ActiveRecord
{
    public static function tableName()
    {
        return 'estadisticas.voleibol_evento';
    }

    public function rules()
    {
        return [
            [['sesion_id', 'set_id', 'tipo_evento_id', 'atleta_id', 'created_by'], 'required'],
            [['sesion_id', 'set_id', 'tipo_evento_id', 'atleta_id', 'created_by'], 'default', 'value' => null],
            [['sesion_id', 'set_id', 'tipo_evento_id', 'atleta_id', 'created_by'], 'integer'],
            [['sesion_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSesion::class, 'targetAttribute' => ['sesion_id' => 'id']],
            [['set_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSet::class, 'targetAttribute' => ['set_id' => 'id']],
            [['tipo_evento_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolTipoEvento::class, 'targetAttribute' => ['tipo_evento_id' => 'id']],
            [['atleta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sesion_id' => 'Sesión',
            'set_id' => 'Set',
            'tipo_evento_id' => 'Tipo Evento',
            'atleta_id' => 'Atleta',
            'created_at' => 'Registrado',
            'created_by' => 'Registrado por',
        ];
    }

    public function getSesion()
    {
        return $this->hasOne(VoleibolSesion::class, ['id' => 'sesion_id']);
    }

    public function getSet()
    {
        return $this->hasOne(VoleibolSet::class, ['id' => 'set_id']);
    }

    public function getTipoEvento()
    {
        return $this->hasOne(VoleibolTipoEvento::class, ['id' => 'tipo_evento_id']);
    }

    public function getAtleta()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'atleta_id']);
    }

    public function getCreador()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}