<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla 'atletas.tipos_beca'.
 *
 * @property int $id_tipo_beca
 * @property string $nombre
 * @property string $descripcion
 * @property float $porcentaje_predeterminado
 *
 * @property Beca[] $becas
 */
class TipoBeca extends ActiveRecord
{
    public static function tableName()
    {
        return 'atletas.tipos_beca';
    }

    public function rules()
    {
        return [
            [['nombre', 'porcentaje_predeterminado'], 'required'],
            [['porcentaje_predeterminado'], 'number', 'min' => 0, 'max' => 100],
            [['nombre'], 'string', 'max' => 100],
            [['descripcion'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id_tipo_beca' => 'ID Tipo Beca',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'porcentaje_predeterminado' => '% Predeterminado',
        ];
    }

    public function getBecas()
    {
        return $this->hasMany(Beca::class, ['id_tipo_beca' => 'id_tipo_beca']);
    }
}