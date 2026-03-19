<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.voleibol_sustitucion".
 *
 * @property int $id
 * @property int $sesion_id
 * @property int $set_id
 * @property int $atleta_sale_id
 * @property int $atleta_entra_id
 * @property string $equipo
 * @property string|null $created_at
 * @property int|null $created_by
 *
 * @property VoleibolSesion $sesion
 * @property VoleibolSet $set
 * @property AtletasRegistro $atletaSale
 * @property AtletasRegistro $atletaEntra
 * @property User $creador
 */
class VoleibolSustitucion extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'estadisticas.voleibol_sustitucion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sesion_id', 'set_id', 'atleta_sale_id', 'atleta_entra_id', 'equipo'], 'required'],
            [['sesion_id', 'set_id', 'atleta_sale_id', 'atleta_entra_id', 'created_by'], 'integer'],
            [['equipo'], 'string', 'max' => 1],
            [['equipo'], 'in', 'range' => ['A', 'B']],
            [['created_at'], 'safe'],
            [['sesion_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSesion::class, 'targetAttribute' => ['sesion_id' => 'id']],
            [['set_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSet::class, 'targetAttribute' => ['set_id' => 'id']],
            [['atleta_sale_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_sale_id' => 'id']],
            [['atleta_entra_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_entra_id' => 'id']],
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
            'atleta_sale_id' => 'Atleta que sale',
            'atleta_entra_id' => 'Atleta que entra',
            'equipo' => 'Equipo',
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
     * Gets query for [[AtletaSale]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAtletaSale()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'atleta_sale_id']);
    }

    /**
     * Gets query for [[AtletaEntra]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAtletaEntra()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'atleta_entra_id']);
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