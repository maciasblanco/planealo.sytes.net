<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * This is the model class for table "atletas.registro".
 *
 * PROYECTO SISTEMA DE APORTES Y BECAS - CLUB VOLEIBOL SAN AGUSTÍN
 * --------------------------------------------------------------
 * ✅ INTEGRACIÓN COMPLETA:
 * - Se agregó campo id_familia (FK → atletas.familias)
 * - Se agregó relación getFamilia()
 * - Se agregó método getBecaActiva() para consultar beca vigente
 * - Se agregó getNombreCompleto() para uso en vistas
 * 
 * TODO EL CÓDIGO ORIGINAL SE CONSERVA ÍNTEGRAMENTE.
 * --------------------------------------------------------------
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
 *
 * // NUEVO CAMPO PARA SISTEMA DE BECAS
 * @property int|null $id_familia
 *
 * // Propiedades virtuales (no persistentes)
 * @property string $p_nombre_representante
 * @property string $s_nombre_representante
 * @property string $p_apellido_representante
 * @property string $s_apellido_representante
 * @property int $id_nac_representante
 * @property string $identificacion_representante
 * @property string $cell_representante
 * @property string $nombreEscuelaClub
 * @property string $categoria
 * @property int $edad
 *
 * // RELACIONES
 * @property AportesSemanales[] $aportes
 * @property Escuela $escuela
 * @property CategoriaAtletas $categoriaRel
 * @property User $user
 * @property RegistroRepresentantes $representante
 * @property Asistencia[] $asistencias
 * @property Alergias $alergias
 * @property Enfermedades $enfermedades
 * @property Discapacidad $discapacidad
 * @property Nacionalidad $nacionalidad
 * @property Sexo $sexoModel
 * @property Familia $familia          // NUEVA RELACIÓN
 * @property Beca[] $becas            // NUEVA RELACIÓN
 */
class AtletasRegistro extends ActiveRecord
{
    // -------------------------------------------------------------------------
    // PROPIEDADES VIRTUALES (ORIGINALES)
    // -------------------------------------------------------------------------
    public $p_nombre_representante;
    public $s_nombre_representante;
    public $p_apellido_representante;
    public $s_apellido_representante;
    public $id_nac_representante;
    public $identificacion_representante;
    public $cell_representante;
    public $nombreEscuelaClub;
    public $categoria;
    public $edad;

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
            // -----------------------------------------------------------------
            // REGLAS ORIGINALES (conservadas íntegramente)
            // -----------------------------------------------------------------
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

            // Escuela
            ['id_escuela', 'required', 'message' => 'La escuela es requerida'],
            ['id_escuela', 'integer'],

            // -----------------------------------------------------------------
            // NUEVA REGLA PARA id_familia
            // -----------------------------------------------------------------
            [['id_familia'], 'integer'],
            [['id_familia'], 'exist', 'skipOnError' => true, 'targetClass' => Familia::class, 'targetAttribute' => ['id_familia' => 'id_familia']],
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
            // NUEVO
            'id_familia' => 'Familia',
        ];
    }

    // -------------------------------------------------------------------------
    // BEFORESAVE / AFTERSAVE (ORIGINALES - SIN MODIFICACIONES)
    // -------------------------------------------------------------------------
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
            $this->d_update = $currentTime;
            $this->u_update = $currentUserId;
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert && empty($this->user_id)) {
            try {
                $user = $this->crearUsuarioAtleta();
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

    // -------------------------------------------------------------------------
    // RELACIONES ORIGINALES (TODAS CONSERVADAS)
    // -------------------------------------------------------------------------
    public function getAportes()
    {
        return $this->hasMany(AportesSemanales::class, ['atleta_id' => 'id']);
    }

    public function getEscuela()
    {
        return $this->hasOne(Escuela::class, ['id' => 'id_escuela']);
    }

    public function getCategoria()
    {
        return $this->hasOne(CategoriaAtletas::class, ['id' => 'id_categoria']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getRepresentante()
    {
        return $this->hasOne(RegistroRepresentantes::class, ['id' => 'id_representante']);
    }

    public function getAsistencias()
    {
        return $this->hasMany(Asistencia::class, ['id_atleta' => 'id']);
    }

    public function getAlergias()
    {
        return $this->hasOne(Alergias::class, ['id' => 'id_alergias']);
    }

    public function getEnfermedades()
    {
        return $this->hasOne(Enfermedades::class, ['id' => 'id_enfermedades']);
    }

    public function getDiscapacidad()
    {
        return $this->hasOne(Discapacidad::class, ['id' => 'id_discapacidad']);
    }

    public function getNacionalidad()
    {
        return $this->hasOne(Nacionalidad::class, ['id' => 'id_nac']);
    }

    public function getSexoModel()
    {
        return $this->hasOne(Sexo::class, ['id' => 'sexo']);
    }

    // -------------------------------------------------------------------------
    // NUEVAS RELACIONES PARA EL SISTEMA DE BECAS
    // -------------------------------------------------------------------------

    /**
     * Relación: atleta pertenece a una familia.
     * @return \yii\db\ActiveQuery
     */
    public function getFamilia()
    {
        return $this->hasOne(Familia::class, ['id_familia' => 'id_familia']);
    }

    /**
     * Relación: atleta puede tener múltiples becas.
     * @return \yii\db\ActiveQuery
     */
    public function getBecas()
    {
        return $this->hasMany(Beca::class, ['id_atleta' => 'id']);
    }

    /**
     * Obtiene la beca activa del atleta en la fecha actual.
     * @return Beca|null
     */
    public function getBecaActiva()
    {
        return $this->getBecas()
            ->andWhere(['<=', 'fecha_inicio', date('Y-m-d')])
            ->andWhere(['or', ['fecha_fin' => null], ['>=', 'fecha_fin', date('Y-m-d')]])
            ->one();
    }

    // -------------------------------------------------------------------------
    // MÉTODOS ORIGINALES (CONSERVADOS ÍNTEGRAMENTE)
    // -------------------------------------------------------------------------
    public function getCategoriaCalculada()
    {
        if (!$this->fn) {
            return 'SIN CATEGORÍA';
        }

        $fechaNacimiento = new \DateTime($this->fn);
        $hoy = new \DateTime();
        $edad = $hoy->diff($fechaNacimiento)->y;

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

    public function getCategoriaNombre()
    {
        if ($this->categoria) {
            return $this->categoria->nombre . ' (' . $this->categoria->nombre_venezuela . ')';
        }
        return $this->getCategoriaCalculada();
    }

    public function getEdad()
    {
        if (!$this->fn) {
            return 0;
        }
        $fechaNacimiento = new \DateTime($this->fn);
        $hoy = new \DateTime();
        return $hoy->diff($fechaNacimiento)->y;
    }

    public function crearUsuarioAtleta()
    {
        if (empty($this->identificacion)) {
            Yii::error('No se puede crear usuario: identificación vacía', 'atleta');
            return null;
        }

        try {
            $user = User::crearUsuarioAutomatico(
                $this->identificacion,
                null,
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

    // -------------------------------------------------------------------------
    // NUEVO MÉTODO UTILITARIO
    // -------------------------------------------------------------------------

    /**
     * Devuelve el nombre completo del atleta.
     * @return string
     */
    public function getNombreCompleto()
    {
        $parts = [
            $this->p_nombre,
            $this->s_nombre,
            $this->p_apellido,
            $this->s_apellido,
        ];
        return implode(' ', array_filter($parts));
    }

    // =========================================================================
    // SOFT DELETE BEHAVIOR (NUEVO)
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'softDelete' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => [
                    'eliminado' => true,
                    // Opcional: actualizar marcas de tiempo
                    'd_update' => function () {
                        return date('Y-m-d H:i:s');
                    },
                    'u_update' => function () {
                        return Yii::$app->user->id;
                    },
                ],
                'restoreAttributeValues' => [
                    'eliminado' => false,
                    'd_update' => function () {
                        return date('Y-m-d H:i:s');
                    },
                    'u_update' => function () {
                        return Yii::$app->user->id;
                    },
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     * @return \yii\db\ActiveQuery con soporte para soft delete
     */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query;
    }

    /**
     * Método de conveniencia para restaurar un atleta.
     */
    public function restore()
    {
        $this->trigger(SoftDeleteBehavior::EVENT_BEFORE_RESTORE);
        $this->eliminado = false;
        $this->d_update = date('Y-m-d H:i:s');
        $this->u_update = Yii::$app->user->id;
        $result = $this->save(false);
        $this->trigger(SoftDeleteBehavior::EVENT_AFTER_RESTORE);
        return $result;
    }

    /**
     * Método de conveniencia para soft delete.
     */
    public function softDelete()
    {
        $this->trigger(SoftDeleteBehavior::EVENT_BEFORE_SOFT_DELETE);
        $this->eliminado = true;
        $this->d_update = date('Y-m-d H:i:s');
        $this->u_update = Yii::$app->user->id;
        $result = $this->save(false);
        $this->trigger(SoftDeleteBehavior::EVENT_AFTER_SOFT_DELETE);
        return $result;
    }
}