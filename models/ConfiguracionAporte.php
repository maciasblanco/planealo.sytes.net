<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla 'contabilidad.configuracion_aportes'.
 *
 * @property int $id_configuracion
 * @property float $aporte_base
 * @property string $fecha_inicio
 * @property string|null $fecha_fin
 * @property bool $activa
 *
 * @property-read bool $esActiva
 */
class ConfiguracionAporte extends ActiveRecord
{
    public static function tableName()
    {
        return 'contabilidad.configuracion_aportes';
    }

    public function rules()
    {
        return [
            [['aporte_base', 'fecha_inicio'], 'required'],
            [['aporte_base'], 'number', 'min' => 0],
            [['fecha_inicio', 'fecha_fin'], 'date', 'format' => 'php:Y-m-d'],
            [['activa'], 'boolean'],
            [['activa'], 'default', 'value' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id_configuracion' => 'ID Configuración',
            'aporte_base' => 'Aporte Base ($)',
            'fecha_inicio' => 'Fecha Inicio',
            'fecha_fin' => 'Fecha Fin',
            'activa' => 'Activa',
        ];
    }

    /**
     * Scope para obtener solo la configuración activa.
     */
    public static function find()
    {
        return parent::find()->andWhere(['activa' => true]);
    }

    /**
     * @return bool
     */
    public function getEsActiva()
    {
        $hoy = date('Y-m-d');
        return $this->activa
            && $this->fecha_inicio <= $hoy
            && ($this->fecha_fin === null || $this->fecha_fin >= $hoy);
    }
}