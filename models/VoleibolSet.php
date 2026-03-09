<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.voleibol_set".
 *
 * @property int $id
 * @property int $sesion_id
 * @property int $numero
 * @property int $puntos_a
 * @property int $puntos_b
 * @property string|null $ganador
 * @property string $estado
 * @property string $created_at
 * @property string $updated_at
 *
 * @property VoleibolSesion $sesion
 * @property VoleibolEvento[] $voleibolEventos
 */
class VoleibolSet extends ActiveRecord
{
    public static function tableName()
    {
        return 'estadisticas.voleibol_set';
    }

    public function rules()
    {
        return [
            [['sesion_id', 'numero'], 'required'],
            [['sesion_id', 'numero', 'puntos_a', 'puntos_b'], 'default', 'value' => null],
            [['sesion_id', 'numero', 'puntos_a', 'puntos_b'], 'integer'],
            [['ganador', 'estado'], 'string'],
            [['ganador'], 'in', 'range' => ['A', 'B']],
            [['estado'], 'in', 'range' => ['A', 'F']],
            [['sesion_id'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSesion::class, 'targetAttribute' => ['sesion_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sesion_id' => 'Sesión',
            'numero' => 'Número',
            'puntos_a' => 'Puntos A',
            'puntos_b' => 'Puntos B',
            'ganador' => 'Ganador',
            'estado' => 'Estado',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
        ];
    }

    public function getSesion()
    {
        return $this->hasOne(VoleibolSesion::class, ['id' => 'sesion_id']);
    }

    public function getVoleibolEventos()
    {
        return $this->hasMany(VoleibolEvento::class, ['set_id' => 'id']);
    }
}