<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla 'catalogos.tipos_beca'.
 *
 * @property int $id_tipo_beca
 * @property string $codigo
 * @property string $nombre
 * @property string|null $descripcion
 * @property float $porcentaje_descuento
 * @property int|null $max_atletas_por_familia
 * @property bool|null $requiere_aprobacion
 * @property bool|null $activa
 * @property string|null $d_creacion
 * @property int|null $u_creacion
 * @property float $porcentaje_predeterminado
 * @property int $periodo_validez_meses
 *
 * @property Beca[] $becas
 */
class TipoBeca extends ActiveRecord
{
    public static function tableName()
    {
        return 'catalogos.tipos_beca';
    }

    public function rules()
    {
        return [
            [['codigo', 'nombre', 'porcentaje_descuento', 'porcentaje_predeterminado', 'periodo_validez_meses'], 'required'],
            [['porcentaje_descuento', 'porcentaje_predeterminado'], 'number', 'min' => 0, 'max' => 100],
            [['periodo_validez_meses'], 'integer', 'min' => 1, 'max' => 60],
            [['max_atletas_por_familia', 'u_creacion'], 'integer'],
            [['requiere_aprobacion', 'activa'], 'boolean'],
            [['descripcion'], 'string'],
            [['codigo'], 'string', 'max' => 20],
            [['nombre'], 'string', 'max' => 50],
            [['d_creacion'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id_tipo_beca' => 'ID Tipo Beca',
            'codigo' => 'Código',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'porcentaje_descuento' => '% Descuento',
            'max_atletas_por_familia' => 'Máx. atletas por familia',
            'requiere_aprobacion' => 'Requiere aprobación',
            'activa' => 'Activa',
            'd_creacion' => 'Fecha de creación',
            'u_creacion' => 'Usuario de creación',
            'porcentaje_predeterminado' => '% Predeterminado',
            'periodo_validez_meses' => 'Período de validez (meses)',
        ];
    }

    public function getBecas()
    {
        return $this->hasMany(Beca::class, ['id_tipo_beca' => 'id_tipo_beca']);
    }
}