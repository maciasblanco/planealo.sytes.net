<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo para la tabla 'atletas.familias'.
 *
 * @property int $id_familia
 * @property string $nombre_representante
 * @property string $email
 * @property string $telefono
 * @property string $direccion
 * @property float $aporte_base_personalizado (opcional, null = usa configuración general)
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Atleta[] $atletas
 * @property AporteSemanal[] $aportesSemanales
 */
class Familia extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'atletas.familias';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre_representante', 'email'], 'required'],
            [['aporte_base_personalizado'], 'number', 'min' => 0],
            [['created_at', 'updated_at'], 'safe'],
            [['nombre_representante', 'email', 'telefono', 'direccion'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['email'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_familia' => 'ID Familia',
            'nombre_representante' => 'Representante',
            'email' => 'Correo Electrónico',
            'telefono' => 'Teléfono',
            'direccion' => 'Dirección',
            'aporte_base_personalizado' => 'Aporte Base Personalizado',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
        ];
    }

    /**
     * Relación: una familia tiene muchos atletas.
     */
    public function getAtletas()
    {
        return $this->hasMany(Atleta::class, ['id_familia' => 'id_familia']);
    }

    /**
     * Relación: una familia tiene muchos aportes semanales.
     */
    public function getAportesSemanales()
    {
        return $this->hasMany(AporteSemanal::class, ['id_familia' => 'id_familia']);
    }

    /**
     * Obtiene el aporte base que aplica a esta familia.
     * Si tiene aporte_base_personalizado, se usa ese; si no, toma el valor de la configuración activa.
     *
     * @return float
     */
    public function getAporteBase()
    {
        if ($this->aporte_base_personalizado !== null && $this->aporte_base_personalizado > 0) {
            return (float) $this->aporte_base_personalizado;
        }

        $config = ConfiguracionAporte::find()->activa()->one();
        return $config ? (float) $config->aporte_base : 20.00; // fallback
    }

    /**
     * Calcula el descuento por múltiples atletas en la familia.
     * Por cada atleta adicional al primero, se aplica un 25% de descuento acumulativo.
     * Ejemplo: 1 atleta = 0%, 2 atletas = 25%, 3 atletas = 50%, etc.
     *
     * @return float (porcentaje entre 0 y 1)
     */
    public function getDescuentoMultipleAtletas()
    {
        $cantidadAtletas = $this->getAtletas()->count();
        if ($cantidadAtletas <= 1) {
            return 0.0;
        }
        // Descuento = (cantidad - 1) * 0.25, con tope 0.75 (75%) por sentido común
        return min(($cantidadAtletas - 1) * 0.25, 0.75);
    }

    /**
     * Calcula el aporte final de la familia para una semana determinada.
     * Aporte final = aporte_base * (1 - descuento_multiples_atletas) * (1 - descuento_becas)
     * El descuento por becas es el máximo descuento entre todos los atletas de la familia
     * (cada beca aplica sobre el aporte de la familia, no se suman, se toma el mayor).
     *
     * @return float
     */
    public function calcularAporteSemanal()
    {
        $aporteBase = $this->getAporteBase();

        // Descuento por múltiples atletas
        $descuentoMultiple = $this->getDescuentoMultipleAtletas();

        // Descuento por becas: se toma el mayor porcentaje de beca entre los atletas activos
        $maxDescuentoBeca = 0.0;
        foreach ($this->atletas as $atleta) {
            $becaActiva = $atleta->getBecaActiva();
            if ($becaActiva) {
                $porcentaje = $becaActiva->porcentaje / 100; // convertir a decimal
                if ($porcentaje > $maxDescuentoBeca) {
                    $maxDescuentoBeca = $porcentaje;
                }
            }
        }

        // Aporte final
        $aporte = $aporteBase;
        $aporte *= (1 - $descuentoMultiple);
        $aporte *= (1 - $maxDescuentoBeca);

        return round($aporte, 2);
    }
}