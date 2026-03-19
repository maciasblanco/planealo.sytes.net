<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.voleibol_alineacion".
 *
 * @property int $id
 * @property int $sesion_id
 * @property int $set_id
 * @property string $equipo
 * @property int $atleta_id
 * @property int $posicion
 * @property string|null $created_at
 * @property int|null $created_by
 *
 * @property VoleibolSesion $sesion
 * @property VoleibolSet $set
 * @property AtletasRegistro $atleta
 * @property User $creador
 */
class VoleibolAlineacion extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'estadisticas.voleibol_alineacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sesion_id', 'set_id', 'equipo', 'atleta_id', 'posicion'], 'required'],
            [['sesion_id', 'set_id', 'atleta_id', 'posicion', 'created_by'], 'integer'],
            [['equipo'], 'string', 'max' => 1],
            [['equipo'], 'in', 'range' => ['A', 'B']],
            [['created_at'], 'safe'],
            [['sesion_id', 'set_id', 'equipo', 'posicion'], 'unique', 'targetAttribute' => ['sesion_id', 'set_id', 'equipo', 'posicion']],
            [['sesion_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSesion::class, 'targetAttribute' => ['sesion_id' => 'id']],
            [['set_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSet::class, 'targetAttribute' => ['set_id' => 'id']],
            [['atleta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sesion_id' => 'Sesión',
            'set_id' => 'Set',
            'equipo' => 'Equipo',
            'atleta_id' => 'Atleta',
            'posicion' => 'Posición (1-6)',
            'created_at' => 'Creado',
            'created_by' => 'Creado por',
        ];
    }

    /**
     * Gets query for [[Sesion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSesion()
    {
        return $this->hasOne(VoleibolSesion::class, ['id' => 'sesion_id']);
    }

    /**
     * Gets query for [[Set]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSet()
    {
        return $this->hasOne(VoleibolSet::class, ['id' => 'set_id']);
    }

    /**
     * Gets query for [[Atleta]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAtleta()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'atleta_id']);
    }

    /**
     * Gets query for [[Creador]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreador()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}