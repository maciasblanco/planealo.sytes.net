<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "atletas.escuela".
 *
 * @property int $id
 * @property int $id_estado
 * @property int $id_municipio
 * @property int $id_parroquia
 * @property string $direccion_administrativa
 * @property string $direccion_practicas
 * @property float|null $lat
 * @property float|null $lng
 * @property string $nombre
 * @property string|null $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool|null $eliminado
 * @property string|null $dir_ip
 * @property string|null $dir_img
 * @property string|null $logo
 * @property string|null $mision
 * @property string|null $vision
 * @property string|null $objetivos
 * @property string|null $historia
 * @property int|null $puntuacion
 * @property string|null $telefono
 * @property string|null $email
 * @property bool|null $tipo_entidad
 * @property string|null $redes_sociales
 * @property string|null $horarios
 * @property string $estado_registro
 * @property string|null $comentarios_aprobacion
 * @property int|null $aprobado_por
 * @property string|null $fecha_aprobacion
 * @property UploadedFile|null $logoFile
 */
class Escuela extends ActiveRecord
{
    public $logoFile;
    
    // Estados del registro
    const ESTADO_PRE_REGISTRO = 'pre_registro';
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'atletas.escuela';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Campos requeridos para pre-registro
            [['id_estado', 'id_municipio', 'id_parroquia', 'direccion_practicas', 'nombre', 'telefono', 'email'], 'required'],
            
            // Validaciones de tipo
            [['id_estado', 'id_municipio', 'id_parroquia', 'u_creacion', 'u_update', 'puntuacion', 'aprobado_por'], 'integer'],
            [['lat', 'lng'], 'number'],
            [['d_creacion', 'd_update', 'fecha_aprobacion'], 'safe'],
            [['eliminado', 'tipo_entidad'], 'boolean'],
            [['mision', 'vision', 'objetivos', 'historia', 'horarios', 'redes_sociales', 'comentarios_aprobacion'], 'string'],
            
            // Validaciones de string
            [['direccion_administrativa', 'direccion_practicas'], 'string', 'max' => 255],
            [['nombre'], 'string', 'max' => 200],
            [['dir_ip'], 'string', 'max' => 45],
            [['dir_img', 'logo'], 'string', 'max' => 500],
            [['telefono'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['estado_registro'], 'string', 'max' => 20],
            
            // Validaciones de formato
            [['email'], 'email'],
            [['telefono'], 'match', 'pattern' => '/^[0-9\-\+\(\)\s]+$/'],
            
            // Validaciones de archivo
            [['logoFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif', 'maxSize' => 1024 * 1024 * 2],
            
            // Valores por defecto
            [['puntuacion'], 'default', 'value' => 0],
            [['eliminado'], 'default', 'value' => false],
            [['tipo_entidad'], 'default', 'value' => true],
            [['estado_registro'], 'default', 'value' => self::ESTADO_PRE_REGISTRO],
            [['direccion_administrativa'], 'default', 'value' => function($model) {
                return $model->direccion_practicas;
            }],
            
            // Validaciones personalizadas
            [['nombre'], 'filter', 'filter' => 'trim'],
            [['nombre'], 'unique', 'message' => 'Ya existe una escuela con este nombre.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_estado' => 'Estado',
            'id_municipio' => 'Municipio',
            'id_parroquia' => 'Parroquia',
            'direccion_administrativa' => 'Dirección Administrativa',
            'direccion_practicas' => 'Dirección de Prácticas (Ubicación de la Cancha)',
            'lat' => 'Latitud',
            'lng' => 'Longitud',
            'nombre' => 'Nombre de la Escuela',
            'd_creacion' => 'Fecha de Creación',
            'u_creacion' => 'Usuario de Creación',
            'd_update' => 'Fecha de Actualización',
            'u_update' => 'Usuario de Actualización',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'Dirección IP',
            'dir_img' => 'Directorio de Imágenes',
            'logo' => 'Logo',
            'logoFile' => 'Subir Logo',
            'mision' => 'Misión',
            'vision' => 'Visión',
            'objetivos' => 'Objetivos',
            'historia' => 'Historia',
            'puntuacion' => 'Puntuación',
            'telefono' => 'Teléfono',
            'email' => 'Correo Electrónico',
            'tipo_entidad' => 'Tipo de Entidad',
            'redes_sociales' => 'Redes Sociales',
            'horarios' => 'Horarios de Entrenamiento',
            'estado_registro' => 'Estado del Registro',
            'comentarios_aprobacion' => 'Comentarios de Aprobación',
            'aprobado_por' => 'Aprobado Por',
            'fecha_aprobacion' => 'Fecha de Aprobación',
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
            ],
        ];
    }

    /**
     * Upload del logo - Versión Mejorada
     */
    public function uploadLogo()
    {
        if ($this->logoFile) {
            $basePath = Yii::getAlias('@webroot/img/logos/escuelas/');
            
            // Crear directorio si no existe
            if (!file_exists($basePath)) {
                mkdir($basePath, 0755, true);
            }
            
            // Generar nombre único
            $fileName = 'logo_' . $this->id . '_' . time() . '.' . $this->logoFile->extension;
            $filePath = $basePath . $fileName;
            
            if ($this->logoFile->saveAs($filePath)) {
                // Eliminar logo anterior si existe
                if ($this->logo && file_exists($basePath . $this->logo)) {
                    @unlink($basePath . $this->logo);
                }
                
                $this->logo = $fileName;
                $this->dir_img = '/img/logos/escuelas/';
                $this->logoFile = null;
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener la URL del logo
     */
    public function getLogoUrl()
    {
        if ($this->logo) {
            return Yii::getAlias('@web') . $this->dir_img . $this->logo;
        }
        return null;
    }

    /**
     * Relación con Estado
     */
    public function getEstado()
    {
        return $this->hasOne(Estado::class, ['id' => 'id_estado']);
    }

    /**
     * Relación con Municipio
     */
    public function getMunicipio()
    {
        return $this->hasOne(Municipio::class, ['id' => 'id_municipio']);
    }

    /**
     * Relación con Parroquia
     */
    public function getParroquia()
    {
        return $this->hasOne(Parroquia::class, ['id' => 'id_parroquia']);
    }

    /**
     * Obtener opciones de estado de registro
     */
    public static function getEstadoRegistroOptions()
    {
        return [
            self::ESTADO_PRE_REGISTRO => 'Pre-Registro',
            self::ESTADO_PENDIENTE => 'Pendiente de Aprobación',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_RECHAZADO => 'Rechazado',
        ];
    }

    /**
     * Obtener etiqueta del estado de registro
     */
    public function getEstadoRegistroLabel()
    {
        $options = self::getEstadoRegistroOptions();
        return isset($options[$this->estado_registro]) ? $options[$this->estado_registro] : $this->estado_registro;
    }

    /**
     * Verificar si está en pre-registro
     */
    public function isPreRegistro()
    {
        return $this->estado_registro === self::ESTADO_PRE_REGISTRO;
    }

    /**
     * Verificar si está pendiente
     */
    public function isPendiente()
    {
        return $this->estado_registro === self::ESTADO_PENDIENTE;
    }

    /**
     * Verificar si está aprobado
     */
    public function isAprobado()
    {
        return $this->estado_registro === self::ESTADO_APROBADO;
    }

    /**
     * Cambiar a estado pendiente
     */
    public function marcarComoPendiente()
    {
        $this->estado_registro = self::ESTADO_PENDIENTE;
        return $this->save(false);
    }

    /**
     * Aprobar escuela
     */
    public function aprobar($comentarios = null, $aprobado_por = null)
    {
        $this->estado_registro = self::ESTADO_APROBADO;
        $this->comentarios_aprobacion = $comentarios;
        $this->aprobado_por = $aprobado_por ?: Yii::$app->user->id;
        $this->fecha_aprobacion = new Expression('NOW()');
        return $this->save(false);
    }

    /**
     * Rechazar escuela
     */
    public function rechazar($comentarios = null)
    {
        $this->estado_registro = self::ESTADO_RECHAZADO;
        $this->comentarios_aprobacion = $comentarios;
        return $this->save(false);
    }

    /**
     * Obtener dirección completa
     */
    public function getDireccionCompleta()
    {
        $partes = [];
        if ($this->direccion_practicas) {
            $partes[] = $this->direccion_practicas;
        }
        if ($this->parroquia) {
            $partes[] = $this->parroquia->parroquia;
        }
        if ($this->municipio) {
            $partes[] = $this->municipio->municipio;
        }
        if ($this->estado) {
            $partes[] = $this->estado->estado;
        }
        
        return implode(', ', $partes);
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
            
            // Si es nueva y no tiene estado, establecer como pre-registro
            if ($insert && empty($this->estado_registro)) {
                $this->estado_registro = self::ESTADO_PRE_REGISTRO;
            }
            
            return true;
        }
        return false;
    }

    /**
     * After save - subir logo después de guardar
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if ($this->logoFile) {
            $this->uploadLogo();
            $this->save(false); // Guardar el nombre del archivo
        }
    }

    /**
     * Obtener escuelas activas
     */
    public static function findActive()
    {
        return self::find()->where(['eliminado' => false, 'estado_registro' => self::ESTADO_APROBADO]);
    }

    /**
     * Obtener escuelas pendientes de aprobación
     */
    public static function findPendientes()
    {
        return self::find()->where(['eliminado' => false, 'estado_registro' => self::ESTADO_PENDIENTE]);
    }

    /**
     * Obtener estadísticas básicas de la escuela
     */
    public function getEstadisticasBasicas()
    {
        return [
            'total_atletas' => AtletasRegistro::find()->where(['id_escuela' => $this->id, 'eliminado' => false])->count(),
            'total_representantes' => RegistroRepresentantes::find()->where(['id_escuela' => $this->id, 'eliminado' => false])->count(),
        ];
    }
    /**
     * Gets query for [[Atletas]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getAtletas()
    {
        return $this->hasMany(AtletasRegistro::class, ['id_escuela' => 'id']);
    }

    /**
     * Gets query for [[Representantes]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getRepresentantes()
    {
        return $this->hasMany(RegistroRepresentantes::class, ['id_escuela' => 'id']);
    }

    /**
     * Gets query for [[Asistencias]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getAsistencias()
    {
        return $this->hasMany(Asistencia::class, ['id_escuela' => 'id']);
    }

    /**
     * Gets query for [[Aportes]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getAportes()
    {
        return $this->hasMany(AportesSemanales::class, ['escuela_id' => 'id']);
    }
}