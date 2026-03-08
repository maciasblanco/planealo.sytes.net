<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo para la tabla 'atletas.familias'.
 *
 * @property int $id_familia
 * @property int|null $id_representante
 * @property string $codigo_familia
 * @property string|null $direccion
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $situacion_economica
 * @property string|null $fecha_registro
 * @property bool|null $activa
 * @property string|null $observaciones
 * @property string|null $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool|null $eliminado
 * @property string|null $dir_ip
 *
 * // Campos adicionales (pueden existir o no en la BD, se manejan como atributos virtuales)
 * @property float|null $aporte_base_personalizado
 *
 * // Propiedad virtual para nombre del representante (obtenida vía getter)
 * @property string $nombre_representante
 *
 * // Relaciones
 * @property RegistroRepresentantes $representante
 * @property AtletasRegistro[] $atletas
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
            // Columnas reales de la tabla
            [['id_representante', 'u_creacion', 'u_update'], 'integer'],
            [['direccion', 'observaciones'], 'string'],
            [['fecha_registro', 'd_creacion', 'd_update'], 'safe'],
            [['activa', 'eliminado'], 'boolean'],
            [['codigo_familia'], 'string', 'max' => 20],
            [['telefono'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['situacion_economica'], 'string', 'max' => 50],
            [['dir_ip'], 'string', 'max' => 45],
            [['email'], 'email'],
            [['codigo_familia'], 'unique'],
            [['codigo_familia'], 'required'],

            // Campo adicional (virtual) – se permite asignación masiva pero no se guarda en BD
            [['aporte_base_personalizado'], 'number', 'min' => 0],

            // nombre_representante es un getter, no se guarda ni valida
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_familia' => 'ID Familia',
            'id_representante' => 'Representante',
            'codigo_familia' => 'Código de Familia',
            'direccion' => 'Dirección',
            'telefono' => 'Teléfono',
            'email' => 'Correo Electrónico',
            'situacion_economica' => 'Situación Económica',
            'fecha_registro' => 'Fecha de Registro',
            'activa' => 'Activa',
            'observaciones' => 'Observaciones',
            'd_creacion' => 'Creado',
            'u_creacion' => 'Usuario Creación',
            'd_update' => 'Actualizado',
            'u_update' => 'Usuario Actualización',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'Dirección IP',
            'aporte_base_personalizado' => 'Aporte Base Personalizado',
            'nombre_representante' => 'Representante',
        ];
    }

    // -------------------------------------------------------------------------
    // RELACIONES
    // -------------------------------------------------------------------------

    /**
     * Relación con el representante de la familia.
     * @return \yii\db\ActiveQuery
     */
    public function getRepresentante()
    {
        return $this->hasOne(RegistroRepresentantes::class, ['id' => 'id_representante']);
    }

    /**
     * Relación: una familia tiene muchos atletas.
     * @return \yii\db\ActiveQuery
     */
    public function getAtletas()
    {
        return $this->hasMany(AtletasRegistro::class, ['id_familia' => 'id_familia']);
    }

    /**
     * Relación: una familia tiene muchos aportes semanales.
     * @return \yii\db\ActiveQuery
     */
    public function getAportesSemanales()
    {
        return $this->hasMany(AporteSemanal::class, ['id_familia' => 'id_familia']);
    }

    // -------------------------------------------------------------------------
    // GETTERS VIRTUALES
    // -------------------------------------------------------------------------

    /**
     * Obtiene el nombre completo del representante (para usar en vistas).
     * Nombre exacto del getter: getNombre_representante() para que la propiedad sea "nombre_representante".
     * @return string
     */
    public function getNombre_representante()
    {
        $representante = $this->representante;
        if (!$representante) {
            return '';
        }
        $parts = [
            $representante->p_nombre,
            $representante->s_nombre,
            $representante->p_apellido,
            $representante->s_apellido,
        ];
        return implode(' ', array_filter($parts));
    }

    // -------------------------------------------------------------------------
    // MÉTODOS DE NEGOCIO (conservados íntegramente)
    // -------------------------------------------------------------------------

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