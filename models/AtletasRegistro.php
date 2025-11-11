<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "atletas.registro".
 *
 * @property int $id
 * @property int|null $id_club
 * @property int|null $id_escuela
 * @property int|null $id_representante
 * @property int|null $id_alergias
 * @property int|null $id_enfermedades
 * @property int|null $id_discapacidad
 * @property string|null $p_nombre
 * @property string|null $s_nombre
 * @property string|null $p_apellido
 * @property string|null $s_apellido
 * @property int|null $id_nac
 * @property string|null $identificacion
 * @property string|null $fn
 * @property int|null $sexo
 * @property float|null $estatura
 * @property float|null $peso
 * @property string|null $talla_franela
 * @property string|null $talla_short
 * @property string|null $cell
 * @property string|null $telf
 * @property bool|null $asma
 * @property string|null $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool|null $eliminado
 * @property string|null $dir_ip
 * @property string|null $nombreEscuelaClub
 * @property string|null $categoria
 * @property int|null $id_categoria
 * @property int|null $user_id
 * @property string|null $telf_emergencia1
 * @property string|null $telf_emergencia2
 */
class AtletasRegistro extends \yii\db\ActiveRecord
{
    public  $p_nombre_representante;
    public  $s_nombre_representante;
    public  $p_apellido_representante;
    public  $s_apellido_representante;
    public  $id_nac_representante;
    public  $identificacion_representante;
    public  $cell_representante;
    public  $nombreEscuelaClub;
    public  $categoria;
    public  $edad;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'atletas.registro';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_escuela', 'id_representante', 'id_alergias', 'id_enfermedades', 'id_discapacidad', 'id_nac', 'sexo', 'u_creacion', 'u_update', 'id_categoria', 'user_id'], 'default', 'value' => null],
            [['id_escuela', 'id_representante', 'id_alergias', 'id_enfermedades', 'id_discapacidad', 'id_nac', 'sexo', 'u_creacion', 'u_update', 'id_categoria', 'user_id'], 'integer'],
            [['identificacion_representante', 'id_nac_representante'], 'integer'],
            [['p_nombre', 's_nombre', 'p_apellido', 's_apellido', 'identificacion', 'talla_franela', 'talla_short', 'cell', 'telf', 'dir_ip', 'telf_emergencia1', 'telf_emergencia2'], 'string'],
            [['nombreEscuelaClub', 'cell_representante', 's_apellido_representante', 'p_apellido_representante', 'p_nombre_representante', 's_nombre_representante'], 'string'],
            [['fn', 'd_creacion', 'd_update'], 'safe'],
            [['estatura', 'peso'], 'number'],
            [['asma', 'eliminado'], 'boolean'],
            
            // Campos requeridos del atleta
            [['p_nombre', 'p_apellido', 'id_nac', 'identificacion', 'fn', 'sexo', 'estatura', 'talla_franela', 'talla_short', 'cell', 'telf_emergencia1'], 'required'],
            
            // Campos requeridos del representante
            [['p_nombre_representante', 'p_apellido_representante', 'id_nac_representante', 'identificacion_representante', 'cell_representante'], 'required'],
            
            [['edad', 'categoria'], 'safe'],
            
            // ✅ VALIDACIÓN SIMPLIFICADA PARA ESCUELA
            ['id_escuela', 'required', 'message' => 'La escuela es requerida'],
            ['id_escuela', 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_escuela' => 'Id Escuela',
            'id_representante' => 'Id Representante',
            'id_alergias' => 'Id Alergias',
            'id_enfermedades' => 'Id Enfermedades',
            'id_discapacidad' => 'Id Discapacidad',
            'p_nombre' => 'Primer Nombre',
            's_nombre' => 'Segundo Nombre',
            'p_apellido' => 'Primer Apellido',
            's_apellido' => 'Segundo Apellido',
            'id_nac' => 'Nacionalidad',
            'identificacion' => 'Cédula',
            'fn' => 'Fecha de Nacimiento',
            'sexo' => 'Sexo',
            'estatura' => 'Estatura (mts)',
            'peso' => 'Peso (kgs)',
            'talla_franela' => 'Talla Franela',
            'talla_short' => 'Talla Short',
            'cell' => 'Teléfono Celular',
            'telf' => 'Teléfono Fijo',
            'telf_emergencia1' => 'Teléfono Emergencia 1',
            'telf_emergencia2' => 'Teléfono Emergencia 2',
            'asma' => 'Asma',
            'd_creacion' => 'Fecha Creación',
            'u_creacion' => 'Usuario Creación',
            'd_update' => 'Fecha Actualización',
            'u_update' => 'Usuario Actualización',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'Dirección Ip',
            'id_categoria' => 'Categoría',
            'user_id' => 'User ID',
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
        
        // Asegurar que id_escuela tenga valor
        if (empty($this->id_escuela)) {
            $session = Yii::$app->session;
            $this->id_escuela = $session->get('id_escuela');
        }
        
        // Establecer fechas automáticamente
        $currentTime = date('Y-m-d H:i:s');
        $currentUserId = Yii::$app->user->id;
        
        if ($insert) {
            // Nuevo registro
            if (empty($this->d_creacion)) {
                $this->d_creacion = $currentTime;
            }
            if (empty($this->u_creacion)) {
                $this->u_creacion = $currentUserId;
            }
            if (empty($this->dir_ip)) {
                $this->dir_ip = Yii::$app->request->userIP;
            }
            $this->eliminado = false;
        } else {
            // Actualización
            $this->d_update = $currentTime;
            $this->u_update = $currentUserId;
        }
        
        return true;
    }

    /**
     * Gets query for [[AportesSemanales]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAportes()
    {
        return $this->hasMany(AportesSemanales::class, ['atleta_id' => 'id']);
    }

    /**
     * Gets query for [[Escuela]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEscuela()
    {
        return $this->hasOne(Escuela::class, ['id' => 'id_escuela']);
    }

    /**
     * Gets query for [[Categoria]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategoria()
    {
        return $this->hasOne(CategoriaAtletas::class, ['id' => 'id_categoria']);
    }

    /**
     * Calcula la categoría del atleta basándose en su fecha de nacimiento
     * @return string Categoría en formato "Internacional (Venezolana)"
     */
    public function getCategoriaCalculada()
    {
        if (!$this->fn) {
            return 'SIN CATEGORÍA';
        }
        
        $fechaNacimiento = new \DateTime($this->fn);
        $hoy = new \DateTime();
        $edad = $hoy->diff($fechaNacimiento)->y;
        
        // Buscar la categoría correspondiente en la base de datos
        $categoria = CategoriaAtletas::find()
            ->where(['<=', 'edad_minima', $edad])
            ->andWhere(['>=', 'edad_maxima', $edad])
            ->andWhere(['activo' => true])
            ->one();
        
        if ($categoria) {
            return $categoria->nombre . ' (' . $categoria->nombre_venezuela . ')';
        }
        
        return 'SIN CATEGORÍA';
    }

    /**
     * Obtiene la categoría desde la relación o la calcula si no existe
     * @return string
     */
    public function getCategoriaNombre()
    {
        if ($this->categoria) {
            return $this->categoria->nombre . ' (' . $this->categoria->nombre_venezuela . ')';
        }
        
        return $this->getCategoriaCalculada();
    }

    /**
     * Obtiene solo la edad del atleta
     * @return int
     */
    public function getEdad()
    {
        if (!$this->fn) {
            return 0;
        }
        
        $fechaNacimiento = new \DateTime($this->fn);
        $hoy = new \DateTime();
        return $hoy->diff($fechaNacimiento)->y;
    }

    /**
     * Gets query for [[User]].
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Representante]].
     * @return \yii\db\ActiveQuery
     */
    public function getRepresentante()
    {
        return $this->hasOne(RegistroRepresentantes::class, ['id' => 'id_representante']);
    }

    /**
     * Crea usuario automáticamente para el atleta con manejo de transacciones
     */
    public function crearUsuarioAtleta()
    {
        if (empty($this->identificacion)) {
            Yii::error('No se puede crear usuario: identificación vacía', 'atleta');
            return null;
        }

        try {
            $user = User::crearUsuarioAutomatico(
                $this->identificacion,
                null, // email opcional
                $this->p_nombre . ' ' . $this->p_apellido,
                'atleta'
            );

            if ($user) {
                $this->user_id = $user->id;
                Yii::info("Usuario asignado al atleta: {$user->id}", 'atleta');
            }

            return $user;
        } catch (\Exception $e) {
            Yii::error('Excepción en crearUsuarioAtleta: ' . $e->getMessage(), 'atleta');
            return null;
        }
    }

    /**
     * After Save event - Crear usuario automáticamente con mejor manejo de errores
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if ($insert && empty($this->user_id)) {
            try {
                // Crear usuario automáticamente para nuevo atleta
                $user = $this->crearUsuarioAtleta();
                
                // Actualizar el user_id si se creó exitosamente
                if ($user && $user->id) {
                    $this->updateAttributes(['user_id' => $user->id]);
                    Yii::info("Usuario creado y asignado exitosamente para atleta ID: {$this->id}", 'atleta');
                } else {
                    Yii::warning('No se pudo crear usuario para atleta', 'atleta');
                }
            } catch (\Exception $e) {
                Yii::error('Excepción en afterSave del atleta: ' . $e->getMessage(), 'atleta');
            }
        }
    }
    /**
     * Gets query for [[Asistencias]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getAsistencias()
    {
        return $this->hasMany(Asistencia::class, ['id_atleta' => 'id']);
    }

    /**
     * Gets query for [[Alergias]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getAlergias()
    {
        return $this->hasOne(Alergias::class, ['id' => 'id_alergias']);
    }

    /**
     * Gets query for [[Enfermedades]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getEnfermedades()
    {
        return $this->hasOne(Enfermedades::class, ['id' => 'id_enfermedades']);
    }

    /**
     * Gets query for [[Discapacidad]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getDiscapacidad()
    {
        return $this->hasOne(Discapacidad::class, ['id' => 'id_discapacidad']);
    }

    /**
     * Gets query for [[Nacionalidad]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getNacionalidad()
    {
        return $this->hasOne(Nacionalidad::class, ['id' => 'id_nac']);
    }

    /**
     * Gets query for [[SexoModel]].
     * RELACIÓN FALTANTE - AGREGADA
     */
    public function getSexoModel()
    {
        return $this->hasOne(Sexo::class, ['id' => 'sexo']);
    }
}