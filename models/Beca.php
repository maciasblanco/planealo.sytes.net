<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla 'atletas.becas'.
 *
 * @property int $id_beca
 * @property int $id_atleta
 * @property int $id_tipo_beca
 * @property float $porcentaje
 * @property string $fecha_inicio
 * @property string|null $fecha_fin
 * @property string $observaciones
 *
 * @property Atleta $atleta
 * @property TipoBeca $tipoBeca
 */
class Beca extends ActiveRecord
{
    public static function tableName()
    {
        return 'atletas.becas';
    }

    public function rules()
    {
        return [
            [['id_atleta', 'id_tipo_beca', 'porcentaje', 'fecha_inicio'], 'required'],
            [['id_atleta', 'id_tipo_beca'], 'integer'],
            [['porcentaje'], 'number', 'min' => 0, 'max' => 100],
            [['fecha_inicio', 'fecha_fin'], 'date', 'format' => 'php:Y-m-d'],
            [['observaciones'], 'string'],
            [['fecha_fin'], 'compare', 'compareAttribute' => 'fecha_inicio', 'operator' => '>=', 'enableClientValidation' => false],
            [['id_atleta'], 'exist', 'skipOnError' => true, 'targetClass' => Atleta::class, 'targetAttribute' => ['id_atleta' => 'id_atleta']],
            [['id_tipo_beca'], 'exist', 'skipOnError' => true, 'targetClass' => TipoBeca::class, 'targetAttribute' => ['id_tipo_beca' => 'id_tipo_beca']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id_beca' => 'ID Beca',
            'id_atleta' => 'Atleta',
            'id_tipo_beca' => 'Tipo de Beca',
            'porcentaje' => 'Porcentaje',
            'fecha_inicio' => 'Fecha Inicio',
            'fecha_fin' => 'Fecha Fin',
            'observaciones' => 'Observaciones',
        ];
    }

    public function getAtleta()
    {
        return $this->hasOne(Atleta::class, ['id_atleta' => 'id_atleta']);
    }

    public function getTipoBeca()
    {
        return $this->hasOne(TipoBeca::class, ['id_tipo_beca' => 'id_tipo_beca']);
    }
}