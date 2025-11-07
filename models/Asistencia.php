<?php
// models/atletas/Asistencia.php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "atletas.asistencia".
 *
 * @property int $id
 * @property int $id_atleta
 * @property int $id_escuela
 * @property string $fecha_practica
 * @property string|null $hora_entrada
 * @property string|null $hora_salida
 * @property bool $asistio
 * @property bool|null $tardanza
 * @property string|null $justificacion
 * @property string|null $tipo_justificacion
 * @property string|null $comentarios
 * @property string $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool $eliminado
 * @property string|null $dir_ip
 *
 * @property AtletasRegistro $atleta
 * @property Escuela $escuela
 */
class Asistencia extends ActiveRecord
{
    const TIPO_JUSTIFICACION_ENFERMEDAD = 'enfermedad';
    const TIPO_JUSTIFICACION_PERSONAL = 'personal';
    const TIPO_JUSTIFICACION_FAMILIAR = 'familiar';
    const TIPO_JUSTIFICACION_OTRO = 'otro';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'atletas.asistencia';
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
                'value' => new Expression('NOW()'),
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
            [['id_atleta', 'id_escuela', 'fecha_practica'], 'required'],
            [['id_atleta', 'id_escuela', 'u_creacion', 'u_update'], 'integer'],
            [['fecha_practica', 'hora_entrada', 'hora_salida', 'd_creacion', 'd_update'], 'safe'],
            [['justificacion', 'comentarios'], 'string'],
            [['asistio', 'tardanza', 'eliminado'], 'boolean'],
            [['tipo_justificacion'], 'string', 'max' => 50],
            [['dir_ip'], 'string', 'max' => 45],
            
            // NUEVAS VALIDACIONES PARA BLINDAJE
            ['id_escuela', 'validateEscuelaExistente'],
            ['id_escuela', 'required', 'message' => 'La escuela es requerida'],
            
            // Claves foráneas - CORREGIDAS
            [['id_atleta'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['id_atleta' => 'id']],
            [['id_escuela'], 'exist', 'skipOnError' => true, 'targetClass' => Escuela::class, 'targetAttribute' => ['id_escuela' => 'id']],
            
            // Validación personalizada: no duplicar asistencia el mismo día
            ['fecha_practica', 'validarAsistenciaUnica', 'skipOnError' => true],
            
            // Validación: si no asistió, debe tener justificación
            ['justificacion', 'required', 'when' => function($model) {
                return $model->asistio === false;
            }, 'whenClient' => "function (attribute, value) {
                return $('#asistencia-asistio').val() === '0';
            }", 'message' => 'La justificación es obligatoria cuando el atleta no asiste.'],
            
            // Validación: hora_salida debe ser después de hora_entrada
            ['hora_salida', 'compare', 'compareAttribute' => 'hora_entrada', 'operator' => '>', 
             'when' => function($model) {
                 return !empty($model->hora_entrada) && !empty($model->hora_salida);
             }, 'message' => 'La hora de salida debe ser posterior a la hora de entrada.'],
        ];
    }
    
    /**
     * VALIDACIÓN CRÍTICA - Verifica que la escuela exista y esté activa
     */
    public function validateEscuelaExistente($attribute, $params)
    {
        if (empty($this->id_escuela)) {
            $this->addError($attribute, 'La escuela es requerida.');
            return;
        }
        
        // Validar existencia en BD
        $escuela = Escuela::find()
            ->where(['id' => $this->id_escuela])
            ->andWhere(['eliminado' => false])
            ->one();
            
        if (!$escuela) {
            $this->addError($attribute, 'La escuela seleccionada no existe o está inactiva.');
            Yii::error("Intento de guardar asistencia con escuela inválida: {$this->id_escuela}");
        }
    }

    /**
     * Validación personalizada para evitar duplicados
     */
    public function validarAsistenciaUnica($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $query = self::find()
                ->where(['id_atleta' => $this->id_atleta])
                ->andWhere(['fecha_practica' => $this->fecha_practica])
                ->andWhere(['eliminado' => false]);
            
            // Si es una actualización, excluir el registro actual
            if (!$this->isNewRecord) {
                $query->andWhere(['<>', 'id', $this->id]);
            }
            
            if ($query->exists()) {
                $this->addError($attribute, 'El atleta ya tiene registro de asistencia para esta fecha.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_atleta' => 'Atleta',
            'id_escuela' => 'Escuela',
            'fecha_practica' => 'Fecha Práctica',
            'hora_entrada' => 'Hora Entrada',
            'hora_salida' => 'Hora Salida',
            'asistio' => 'Asistió',
            'tardanza' => 'Tardanza',
            'justificacion' => 'Justificación',
            'tipo_justificacion' => 'Tipo Justificación',
            'comentarios' => 'Comentarios',
            'd_creacion' => 'Fecha Creación',
            'u_creacion' => 'Usuario Creación',
            'd_update' => 'Fecha Actualización',
            'u_update' => 'Usuario Actualización',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'Dirección IP',
        ];
    }

    /**
     * VALIDACIÓN ANTES DE GUARDAR - PROTECCIÓN CRÍTICA
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        // Forzar id_escuela desde sesión si está vacío
        if (empty($this->id_escuela)) {
            $session = Yii::$app->session;
            $this->id_escuela = $session->get('id_escuela');
        }
        
        // Validación final de escuela
        if (empty($this->id_escuela) || !Escuela::find()->where(['id' => $this->id_escuela, 'eliminado' => false])->exists()) {
            Yii::error("BLOQUEO: Intento de guardar asistencia sin escuela válida. User: " . (Yii::$app->user->id ?? 'anonimo'));
            return false;
        }
        
        if ($insert) {
            if (empty($this->dir_ip)) {
                $this->dir_ip = Yii::$app->request->userIP ?? '127.0.0.1';
            }
            if ($this->eliminado === null) {
                $this->eliminado = false;
            }
        }
        
        // Calcular tardanza si se está insertando hora_entrada
        if ($this->hora_entrada && $insert) {
            $this->tardanza = ($this->hora_entrada > '08:00:00');
        }
        
        return true;
    }

    /**
     * Gets query for [[Atleta]].
     */
    public function getAtleta()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'id_atleta']);
    }

    /**
     * Gets query for [[Escuela]].
     */
    public function getEscuela()
    {
        return $this->hasOne(Escuela::class, ['id' => 'id_escuela']);
    }

    public static function registrarAsistencia($idAtleta, $idEscuela, $fecha = null)
    {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }

        // Verificar si ya existe un registro para evitar duplicados
        $existente = self::find()
            ->where([
                'id_atleta' => $idAtleta,
                'id_escuela' => $idEscuela,
                'fecha_practica' => $fecha
            ])
            ->one();

        if ($existente) {
            return [
                'success' => false,
                'message' => 'Ya existe un registro de asistencia para este atleta en la fecha seleccionada'
            ];
        }

        $asistencia = new Asistencia();
        $asistencia->id_atleta = $idAtleta;
        $asistencia->id_escuela = $idEscuela;
        $asistencia->fecha_practica = $fecha;
        $asistencia->asistio = true; // Por defecto según tu esquema
        $asistencia->tardanza = false; // Por defecto según tu esquema
        $asistencia->eliminado = false; // Por defecto según tu esquema
        
        // d_creacion se establece automáticamente por la BD (CURRENT_TIMESTAMP)
        // No necesitas asignarlo manualmente

        if ($asistencia->save()) {
            return [
                'success' => true,
                'id' => $asistencia->id,
                'message' => 'Asistencia registrada correctamente'
            ];
        } else {
            return [
                'success' => false,
                'errors' => $asistencia->getErrors(),
                'message' => 'Error al registrar la asistencia: ' . implode(', ', $asistencia->getFirstErrors())
            ];
        }
    }
}