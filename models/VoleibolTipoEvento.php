<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.voleibol_tipo_evento".
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string $equipo_afectado
 * @property int $puntos
 * @property string $created_at
 * @property string $updated_at
 *
 * @property VoleibolEvento[] $voleibolEventos
 */
class VoleibolTipoEvento extends ActiveRecord
{
    public static function tableName()
    {
        return 'estadisticas.voleibol_tipo_evento';
    }

    public function rules()
    {
        return [
            [['codigo', 'nombre'], 'required'],
            [['puntos'], 'default', 'value' => null],
            [['puntos'], 'integer'],
            [['codigo', 'nombre', 'equipo_afectado'], 'string', 'max' => 255],
            [['equipo_afectado'], 'in', 'range' => ['P', 'C', 'N']],
            [['codigo'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'codigo' => 'Código',
            'nombre' => 'Nombre',
            'equipo_afectado' => 'Equipo Afectado',
            'puntos' => 'Puntos',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
        ];
    }

    public function getVoleibolEventos()
    {
        return $this->hasMany(VoleibolEvento::class, ['tipo_evento_id' => 'id']);
    }
}