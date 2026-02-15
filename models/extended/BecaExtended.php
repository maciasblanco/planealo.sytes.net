<?php
namespace app\models\extended;

use app\models\Beca;
use app\models\BecaHistorial;
use Yii;

/**
 * Modelo extendido para la tabla 'atletas.becas'.
 * Incluye todos los campos de la tabla, incluyendo los nuevos.
 *
 * @property int $id_beca
 * @property int $id_atleta
 * @property int $id_tipo_beca
 * @property int $id_familia
 * @property string $fecha_asignacion
 * @property string $fecha_vencimiento
 * @property int $periodo_validez_meses
 * @property int $aprobado_por
 * @property string $estado
 * @property string $observaciones
 * @property string $d_creacion
 * @property int $u_creacion
 * @property string $d_update
 * @property int $u_update
 * @property bool $eliminado
 * @property int $renovada_de
 * @property bool $autorizacion_excepcion
 */
class BecaExtended extends Beca
{
    const ESTADO_ACTIVA = 'activa';
    const ESTADO_VENCIDA = 'vencida';
    const ESTADO_RENOVADA = 'renovada';
    const ESTADO_REACTIVADA = 'reactivada';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'atletas.becas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        // Usamos las reglas del padre y añadimos las nuevas
        $rules = parent::rules();
        $rules[] = [['estado', 'periodo_validez_meses', 'renovada_de', 'autorizacion_excepcion', 'id_familia'], 'safe'];
        $rules[] = ['estado', 'in', 'range' => [self::ESTADO_ACTIVA, self::ESTADO_VENCIDA, self::ESTADO_RENOVADA, self::ESTADO_REACTIVADA]];
        $rules[] = ['periodo_validez_meses', 'integer', 'min' => 1];
        $rules[] = ['renovada_de', 'integer'];
        $rules[] = ['renovada_de', 'exist', 'skipOnError' => true, 'targetClass' => self::class, 'targetAttribute' => ['renovada_de' => 'id_beca']];
        $rules[] = ['autorizacion_excepcion', 'boolean'];
        $rules[] = ['id_familia', 'integer'];
        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels['fecha_asignacion'] = 'Fecha Asignación';
        $labels['fecha_vencimiento'] = 'Fecha Vencimiento';
        $labels['periodo_validez_meses'] = 'Periodo de validez (meses)';
        $labels['aprobado_por'] = 'Aprobado por';
        $labels['estado'] = 'Estado';
        $labels['d_creacion'] = 'Fecha creación';
        $labels['u_creacion'] = 'Usuario creación';
        $labels['d_update'] = 'Fecha actualización';
        $labels['u_update'] = 'Usuario actualización';
        $labels['eliminado'] = 'Eliminado';
        $labels['renovada_de'] = 'Renovada de Beca ID';
        $labels['autorizacion_excepcion'] = 'Autorización de excepción';
        $labels['id_familia'] = 'Familia';
        return $labels;
    }

    /**
     * Relación con el historial.
     */
    public function getHistorial()
    {
        return $this->hasMany(BecaHistorial::class, ['id_beca' => 'id_beca']);
    }

    /**
     * Obtiene el atleta.
     */
    public function getAtleta()
    {
        return $this->hasOne(\app\models\AtletasRegistro::class, ['id_atleta' => 'id_atleta']);
    }

    /**
     * Obtiene la familia directamente desde la beca (por si acaso).
     */
    public function getFamilia()
    {
        return $this->hasOne(\app\models\Familia::class, ['id_familia' => 'id_familia']);
    }

    /**
     * Scope para becas activas.
     */
    public static function findActivas()
    {
        return self::find()->where(['estado' => self::ESTADO_ACTIVA, 'eliminado' => false]);
    }

    /**
     * Verifica si la beca está vencida.
     */
    public function isVencida()
    {
        if ($this->fecha_vencimiento) {
            return strtotime($this->fecha_vencimiento) < time();
        }
        return false;
    }
}