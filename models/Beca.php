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
 * @property int|null $propuesto_por
 * @property string|null $fecha_propuesta
 * @property string|null $motivo_rechazo
 * @property bool $renovable
 * @property string $estado_aprobacion
 * @property string|null $estado_ciclo
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
    const ESTADO_APROB_PENDIENTE = 'PENDIENTE';
    const ESTADO_APROB_ACTIVA    = 'ACTIVA';
    const ESTADO_APROB_RECHAZADA = 'RECHAZADA';

    const ESTADO_CICLO_VENCIDA  = 'VENCIDA';
    const ESTADO_CICLO_REVOCADA = 'REVOCADA';

    public static function tableName()
    {
        return 'atletas.becas';
    }

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

    public function rules()
    {
        return [
            [['id_atleta', 'id_tipo_beca', 'fecha_asignacion'], 'required'],
            [['id_atleta', 'id_tipo_beca', 'id_familia', 'aprobado_por', 'periodo_validez_meses', 'renovada_de', 'propuesto_por'], 'integer'],
            [['fecha_asignacion', 'fecha_vencimiento', 'd_creacion', 'd_update', 'fecha_propuesta'], 'safe'],
            [['observaciones', 'motivo_rechazo'], 'string'],
            [['eliminado', 'autorizacion_excepcion', 'renovable'], 'boolean'],
            [['estado', 'estado_aprobacion', 'estado_ciclo'], 'string', 'max' => 20],
            [['estado'], 'default', 'value' => self::ESTADO_APROB_ACTIVA],
            [['estado_aprobacion'], 'default', 'value' => self::ESTADO_APROB_PENDIENTE],
            [['renovable'], 'default', 'value' => true],
            [['eliminado'], 'default', 'value' => false],
            [['estado_aprobacion'], 'in', 'range' => [self::ESTADO_APROB_PENDIENTE, self::ESTADO_APROB_ACTIVA, self::ESTADO_APROB_RECHAZADA]],
            [['estado_ciclo'], 'in', 'range' => [self::ESTADO_CICLO_VENCIDA, self::ESTADO_CICLO_REVOCADA, null]],
            
            ['id_atleta', 'validarUnicaActiva', 'on' => ['default', 'create']],
        ];
    }

    public function validarUnicaActiva($attribute, $params)
    {
        if ($this->estado_aprobacion !== self::ESTADO_APROB_ACTIVA) {
            return;
        }
        $query = self::find()
            ->where(['id_atleta' => $this->id_atleta])
            ->andWhere(['estado_aprobacion' => self::ESTADO_APROB_ACTIVA])
            ->andWhere(['eliminado' => false])
            ->andWhere(['IS', 'estado_ciclo', null]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id_beca', $this->id_beca]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'El atleta ya tiene una beca activa. Debe revocarla antes de asignar una nueva.');
        }
    }

    public function attributeLabels()
    {
        return [
            'id_beca' => 'ID Beca',
            'id_atleta' => 'Atleta',
            'id_tipo_beca' => 'Tipo de Beca',
            'id_familia' => 'Familia',
            'fecha_asignacion' => 'Fecha Asignación',
            'fecha_vencimiento' => 'Fecha Vencimiento',
            'periodo_validez_meses' => 'Periodo (meses)',
            'aprobado_por' => 'Aprobado Por',
            'estado' => 'Estado (legado)',
            'observaciones' => 'Observaciones',
            'd_creacion' => 'Fecha Creación',
            'u_creacion' => 'Usuario Creación',
            'd_update' => 'Fecha Actualización',
            'u_update' => 'Usuario Actualización',
            'eliminado' => 'Eliminado',
            'renovada_de' => 'Renovada de Beca',
            'autorizacion_excepcion' => 'Autorización Excepción',
            'propuesto_por' => 'Propuesto por',
            'fecha_propuesta' => 'Fecha Propuesta',
            'motivo_rechazo' => 'Motivo Rechazo',
            'renovable' => 'Renovable',
            'estado_aprobacion' => 'Estado Aprobación',
            'estado_ciclo' => 'Estado Ciclo',
        ];
    }

    public static function find()
    {
        return new BecaQuery(get_called_class());
    }

    // Relaciones
    public function getAtleta() { return $this->hasOne(AtletasRegistro::class, ['id' => 'id_atleta']); }
    public function getTipoBeca() { return $this->hasOne(TipoBeca::class, ['id_tipo_beca' => 'id_tipo_beca']); }
    public function getFamilia() { return $this->hasOne(Familia::class, ['id_familia' => 'id_familia']); }
    public function getBecaRenovadaDe() { return $this->hasOne(self::class, ['id_beca' => 'renovada_de']); }
    public function getBecasRenovadas() { return $this->hasMany(self::class, ['renovada_de' => 'id_beca']); }
    public function getHistorial() { return $this->hasMany(BecaHistorial::class, ['id_beca' => 'id_beca']); }
    public function getPropuestoPor() { return $this->hasOne(User::class, ['id' => 'propuesto_por']); }
    public function getAprobadoPor() { return $this->hasOne(User::class, ['id' => 'aprobado_por']); }

    // Scopes
    public static function findActivas()
    {
        return self::find()
            ->where(['estado_aprobacion' => self::ESTADO_APROB_ACTIVA, 'eliminado' => false])
            ->andWhere(['IS', 'estado_ciclo', null]);
    }
    public static function findPendientes()
    {
        return self::find()
            ->where(['estado_aprobacion' => self::ESTADO_APROB_PENDIENTE, 'eliminado' => false]);
    }

    // Métodos de negocio
    public function aprobar($aprobadoPor)
    {
        $this->estado_aprobacion = self::ESTADO_APROB_ACTIVA;
        $this->aprobado_por = $aprobadoPor;
        $this->fecha_asignacion = date('Y-m-d');
        $this->fecha_vencimiento = $this->calcularProximoJulio();
        return $this->save();
    }

    public function rechazar($motivo)
    {
        $this->estado_aprobacion = self::ESTADO_APROB_RECHAZADA;
        $this->motivo_rechazo = $motivo;
        return $this->save();
    }

    public function revocar()
    {
        $this->estado_ciclo = self::ESTADO_CICLO_REVOCADA;
        return $this->save();
    }

    public function renovar()
    {
        if (!$this->renovable) {
            return false;
        }
        $nuevaFecha = date('Y-m-d', strtotime($this->fecha_vencimiento . ' +1 year'));
        $this->fecha_vencimiento = $nuevaFecha;
        $historial = new BecaHistorial();
        $historial->id_beca = $this->id_beca;
        $historial->fecha_original_inicio = $this->fecha_asignacion;
        $historial->fecha_original_fin = $this->fecha_vencimiento;
        $historial->fecha_reactivacion = date('Y-m-d');
        $historial->motivo = 'Renovación automática';
        $historial->usuario_creacion = Yii::$app->user->id ?? 1;
        $historial->save();
        return $this->save();
    }

    private function calcularProximoJulio($fecha = null)
    {
        $fecha = $fecha ?: date('Y-m-d');
        $timestamp = strtotime($fecha);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        if ($month >= 7) {
            $year++;
        }
        return $year . '-07-01';
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            if (empty($this->fecha_propuesta)) {
                $this->fecha_propuesta = date('Y-m-d H:i:s');
            }
            $tipoBeca = TipoBeca::findOne($this->id_tipo_beca);
            if ($tipoBeca && $tipoBeca->nombre == 'Entrenador') {
                $this->renovable = false;
                $this->estado_aprobacion = self::ESTADO_APROB_ACTIVA;
                $this->aprobado_por = $this->propuesto_por;
                $this->fecha_asignacion = date('Y-m-d');
                $this->fecha_vencimiento = date('Y-m-d', strtotime('+1 year'));
            }
        }
        return true;
    }
}