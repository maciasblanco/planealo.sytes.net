<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "atletas.encargado_escuela".
 *
 * @property int $id
 * @property int $id_escuela
 * @property string $p_nombre
 * @property string|null $s_nombre
 * @property string $p_apellido
 * @property string|null $s_apellido
 * @property int|null $id_nac
 * @property string $identificacion
 * @property string $fn
 * @property int $sexo
 * @property string $cell
 * @property string|null $telf
 * @property string|null $email
 * @property string|null $cargo
 * @property string|null $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool|null $eliminado
 * @property string|null $dir_ip
 * @property int|null $user_id
 * 
 * @property Escuela $escuela
 * @property Nacionalidad $nacionalidad
 * @property Sexo $sexoModel
 */
class EncargadoEscuela extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'atletas.encargado_escuela';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_escuela', 'p_nombre', 'p_apellido', 'identificacion', 'fn', 'sexo', 'cell'], 'required'],
            
            [['id_escuela', 'id_nac', 'sexo', 'u_creacion', 'u_update', 'user_id'], 'integer'],
            [['p_nombre', 's_nombre', 'p_apellido', 's_apellido', 'cell', 'telf'], 'string'],
            [['fn', 'd_creacion', 'd_update'], 'safe'],
            [['eliminado'], 'boolean'],
            
            [['identificacion'], 'string', 'max' => 20],
            [['email', 'cargo'], 'string', 'max' => 100],
            [['dir_ip'], 'string', 'max' => 45],
            
            // Validación única para cédula (solo encargados activos)
            [
                'identificacion', 
                'unique', 
                'targetAttribute' => ['identificacion', 'eliminado'],
                'message' => 'Esta cédula ya está registrada como encargado en otra escuela.',
                'when' => function($model) {
                    return $model->eliminado == false;
                }
            ],
            
            // Validaciones de formato
            [['email'], 'email'],
            [['identificacion'], 'match', 'pattern' => '/^[VEve][0-9]+$/', 'message' => 'La cédula debe comenzar con V o E seguido de números'],
            
            // Valores por defecto
            [['eliminado'], 'default', 'value' => false],
            
            // Relaciones
            [['id_escuela'], 'exist', 'skipOnError' => true, 'targetClass' => Escuela::class, 'targetAttribute' => ['id_escuela' => 'id']],
            [['id_nac'], 'exist', 'skipOnError' => true, 'targetClass' => Nacionalidad::class, 'targetAttribute' => ['id_nac' => 'id']],
            [['sexo'], 'exist', 'skipOnError' => true, 'targetClass' => Sexo::class, 'targetAttribute' => ['sexo' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_escuela' => 'Escuela',
            'p_nombre' => 'Primer Nombre',
            's_nombre' => 'Segundo Nombre',
            'p_apellido' => 'Primer Apellido',
            's_apellido' => 'Segundo Apellido',
            'id_nac' => 'Nacionalidad',
            'identificacion' => 'Cédula de Identidad',
            'fn' => 'Fecha de Nacimiento',
            'sexo' => 'Sexo',
            'cell' => 'Teléfono Celular',
            'telf' => 'Teléfono Local',
            'email' => 'Correo Electrónico',
            'cargo' => 'Cargo',
            'd_creacion' => 'Fecha de Creación',
            'u_creacion' => 'Usuario de Creación',
            'd_update' => 'Fecha de Actualización',
            'u_update' => 'Usuario de Actualización',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'Dirección IP',
            'user_id' => 'Usuario',
        ];
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
                'value' => function () {
                    return Yii::$app->user->isGuest ? null : Yii::$app->user->id;
                },
            ],
        ];
    }

    /**
     * Before save
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Guardar la IP del usuario
            if (empty($this->dir_ip)) {
                $this->dir_ip = Yii::$app->request->userIP;
            }
            
            return true;
        }
        return false;
    }

    /**
     * Gets query for [[Escuela]].
     */
    public function getEscuela()
    {
        return $this->hasOne(Escuela::class, ['id' => 'id_escuela']);
    }

    /**
     * Gets query for [[Nacionalidad]].
     */
    public function getNacionalidad()
    {
        return $this->hasOne(Nacionalidad::class, ['id' => 'id_nac']);
    }

    /**
     * Gets query for [[SexoModel]].
     */
    public function getSexoModel()
    {
        return $this->hasOne(Sexo::class, ['id' => 'sexo']);
    }

    /**
     * Obtener nombre completo del encargado
     */
    public function getNombreCompleto()
    {
        $nombre = $this->p_nombre;
        if ($this->s_nombre) {
            $nombre .= ' ' . $this->s_nombre;
        }
        $nombre .= ' ' . $this->p_apellido;
        if ($this->s_apellido) {
            $nombre .= ' ' . $this->s_apellido;
        }
        return $nombre;
    }

    /**
     * Verificar si el encargado está activo en otra escuela
     */
    public static function estaActivoEnOtraEscuela($identificacion, $excluirId = null)
    {
        $query = self::find()
            ->where(['identificacion' => $identificacion, 'eliminado' => false]);
            
        if ($excluirId) {
            $query->andWhere(['!=', 'id', $excluirId]);
        }
        
        return $query->exists();
    }

    /**
     * Obtener escuelas donde esta activo un encargado
     */
    public static function obtenerEscuelasActivas($identificacion)
    {
        return self::find()
            ->joinWith('escuela')
            ->where([
                'identificacion' => $identificacion,
                'encargado_escuela.eliminado' => false,
                'escuela.eliminado' => false
            ])
            ->all();
    }
}