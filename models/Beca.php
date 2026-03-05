<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * Modelo para la tabla 'atletas.becas'.
 *
 * @property int $id_beca
 * @property int $id_atleta
 * @property int $id_tipo_beca
 * @property int|null $id_familia
 * @property string $fecha_asignacion
 * @property string|null $fecha_vencimiento
 * @property int $periodo_validez_meses
 * @property int|null $aprobado_por
 * @property string $estado
 * @property string|null $observaciones
 * @property string $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool $eliminado
 * @property int|null $renovada_de
 * @property bool $autorizacion_excepcion
 *
 * @property AtletasRegistro $atleta
 * @property TipoBeca $tipoBeca
 * @property Familia $familia
 * @property Beca $becaRenovadaDe
 * @property Beca[] $becasRenovadas
 * @property BecaHistorial[] $historial
 */
class Beca extends ActiveRecord
{
    const ESTADO_ACTIVA = 'ACTIVA';
    const ESTADO_VENCIDA = 'VENCIDA';
    const ESTADO_REVOCADA = 'REVOCADA';

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
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'd_creacion',
                'updatedAtAttribute' => 'd_update',
                'value' => date('Y-m-d H:i:s'),
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'u_creacion',
                'updatedByAttribute' => 'u_update',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_atleta', 'id_tipo_beca', 'fecha_asignacion'], 'required'],
            [['id_atleta', 'id_tipo_beca', 'id_familia', 'aprobado_por', 'periodo_validez_meses', 'renovada_de'], 'integer'],
            [['fecha_asignacion', 'fecha_vencimiento', 'd_creacion', 'd_update'], 'safe'],
            [['observaciones'], 'string'],
            [['eliminado', 'autorizacion_excepcion'], 'boolean'],
            [['estado'], 'string', 'max' => 20],
            [['estado'], 'default', 'value' => self::ESTADO_ACTIVA],
            [['estado'], 'in', 'range' => [self::ESTADO_ACTIVA, self::ESTADO_VENCIDA, self::ESTADO_REVOCADA]],
            [['periodo_validez_meses'], 'default', 'value' => 6],
            [['autorizacion_excepcion'], 'default', 'value' => false],
            [['eliminado'], 'default', 'value' => false],
            [['renovada_de'], 'exist', 'skipOnError' => true, 'targetClass' => self::class, 'targetAttribute' => ['renovada_de' => 'id_beca']],
            [['id_atleta'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['id_atleta' => 'id']],
            [['id_tipo_beca'], 'exist', 'skipOnError' => true, 'targetClass' => TipoBeca::class, 'targetAttribute' => ['id_tipo_beca' => 'id_tipo_beca']],
            [['id_familia'], 'exist', 'skipOnError' => true, 'targetClass' => Familia::class, 'targetAttribute' => ['id_familia' => 'id_familia']],
            
            // MOD: validación personalizada para evitar múltiples becas activas por atleta
            ['id_atleta', 'validarUnicaActiva', 'on' => ['default', 'create']],
        ];
    }

    /**
     * MOD: Valida que el atleta no tenga otra beca activa (misma o distinto tipo)
     * @param string $attribute
     * @param array $params
     */
    public function validarUnicaActiva($attribute, $params)
    {
        if ($this->estado !== self::ESTADO_ACTIVA) {
            return; // solo interesa si la beca se va a activar
        }

        $query = self::find()
            ->where(['id_atleta' => $this->id_atleta])
            ->andWhere(['estado' => self::ESTADO_ACTIVA])
            ->andWhere(['eliminado' => false]);

        // Si estamos actualizando, excluir el registro actual
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id_beca', $this->id_beca]);
        }

        $activaExistente = $query->exists();

        if ($activaExistente) {
            $this->addError($attribute, 'El atleta ya tiene una beca activa. Debe revocarla antes de asignar una nueva.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_beca' => 'ID Beca',
            'id_atleta' => 'Atleta',
            'id_tipo_beca' => 'Tipo de Beca',
            'id_familia' => 'Familia',
            'fecha_asignacion' => 'Fecha Asignación',
            'fecha_vencimiento' => 'Fecha Vencimiento',
            'periodo_validez_meses' => 'Periodo de Validez (meses)',
            'aprobado_por' => 'Aprobado Por',
            'estado' => 'Estado',
            'observaciones' => 'Observaciones',
            'd_creacion' => 'Fecha Creación',
            'u_creacion' => 'Usuario Creación',
            'd_update' => 'Fecha Actualización',
            'u_update' => 'Usuario Actualización',
            'eliminado' => 'Eliminado',
            'renovada_de' => 'Renovada de Beca',
            'autorizacion_excepcion' => 'Autorización de Excepción',
        ];
    }

    // 🔧 CORRECCIÓN: Sobrescribir find() para usar BecaQuery
    public static function find()
    {
        return new BecaQuery(get_called_class());
    }

    /**
     * Relación con el atleta.
     */
    public function getAtleta()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'id_atleta']);
    }

    /**
     * Relación con el tipo de beca.
     */
    public function getTipoBeca()
    {
        return $this->hasOne(TipoBeca::class, ['id_tipo_beca' => 'id_tipo_beca']);
    }

    /**
     * Relación con la familia.
     */
    public function getFamilia()
    {
        return $this->hasOne(Familia::class, ['id_familia' => 'id_familia']);
    }

    /**
     * Relación con la beca original de la cual se renovó.
     */
    public function getBecaRenovadaDe()
    {
        return $this->hasOne(self::class, ['id_beca' => 'renovada_de']);
    }

    /**
     * Relación con las becas que se renovaron a partir de esta.
     */
    public function getBecasRenovadas()
    {
        return $this->hasMany(self::class, ['renovada_de' => 'id_beca']);
    }

    /**
     * Relación con el historial de cambios.
     */
    public function getHistorial()
    {
        return $this->hasMany(BecaHistorial::class, ['id_beca' => 'id_beca']);
    }

    /**
     * Scope para becas activas.
     */
    public static function findActivas()
    {
        return self::find()->where(['estado' => self::ESTADO_ACTIVA, 'eliminado' => false]);
    }

    /**
     * Verifica si la beca está vencida según fecha_vencimiento.
     */
    public function isVencida()
    {
        if ($this->fecha_vencimiento) {
            return strtotime($this->fecha_vencimiento) < time();
        }
        return false;
    }
}