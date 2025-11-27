<?php
// modules/tienda/models/Vendedor.php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Modelo para la tabla `comercio.vendedor`
 * 
 * @property int $id
 * @property string $nombre_completo
 * @property string $email
 * @property string $password_hash
 * @property string|null $telefono
 * @property string|null $identificacion
 * @property string|null $tipo_identificacion
 * @property string|null $direccion
 * @property int|null $id_estado
 * @property int|null $id_municipio
 * @property bool $activo
 * @property string $fecha_registro
 * @property string|null $ultimo_login
 * @property bool $terminos_aceptados
 * @property int|null $user_id
 * 
 * @property Tienda[] $tiendas
 */
class Vendedor extends ActiveRecord
{
    public $password; // Para el formulario de registro
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comercio.vendedor';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'fecha_registro',
                'updatedAtAttribute' => null,
                'value' => new \yii\db\Expression('CURRENT_TIMESTAMP'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombre_completo', 'email'], 'required'],
            [['nombre_completo', 'direccion'], 'string', 'max' => 200],
            [['email'], 'string', 'max' => 100],
            [['telefono'], 'string', 'max' => 20],
            [['identificacion'], 'string', 'max' => 20],
            [['tipo_identificacion'], 'string', 'max' => 10],
            [['id_estado', 'id_municipio', 'user_id'], 'integer'],
            [['activo', 'terminos_aceptados'], 'boolean'],
            [['fecha_registro', 'ultimo_login'], 'safe'],
            [['email'], 'unique'],
            [['email'], 'email'],
            [['identificacion'], 'unique'],
            [['password'], 'string', 'min' => 6],
            [['activo'], 'default', 'value' => true],
            [['terminos_aceptados'], 'default', 'value' => false],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre_completo' => 'Nombre Completo',
            'email' => 'Email',
            'password' => 'Contraseña',
            'password_hash' => 'Password Hash',
            'telefono' => 'Teléfono',
            'identificacion' => 'Identificación',
            'tipo_identificacion' => 'Tipo Identificación',
            'direccion' => 'Dirección',
            'id_estado' => 'Estado',
            'id_municipio' => 'Municipio',
            'activo' => 'Activo',
            'fecha_registro' => 'Fecha de Registro',
            'ultimo_login' => 'Último Login',
            'terminos_aceptados' => 'Términos Aceptados',
            'user_id' => 'Usuario Asociado',
        ];
    }

    /**
     * Relación con las tiendas del vendedor
     */
    public function getTiendas()
    {
        return $this->hasMany(Tienda::class, ['id_vendedor' => 'id'])
                    ->andWhere(['eliminado' => false]);
    }

    /**
     * Relación con el estado
     */
    public function getEstado()
    {
        return $this->hasOne(\app\models\Estado::class, ['id' => 'id_estado']);
    }

    /**
     * Relación con el municipio
     */
    public function getMunicipio()
    {
        return $this->hasOne(\app\models\Municipio::class, ['id' => 'id_municipio']);
    }

    /**
     * Hash de la contraseña antes de guardar
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->password) {
                $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            }
            return true;
        }
        return false;
    }

    /**
     * Valida la contraseña
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Genera un token de autenticación
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Actualiza el último login
     */
    public function updateUltimoLogin()
    {
        $this->ultimo_login = new \yii\db\Expression('CURRENT_TIMESTAMP');
        $this->save(false, ['ultimo_login']);
    }

    /**
     * Obtiene vendedores activos
     */
    public static function getVendedoresActivos()
    {
        return self::find()
            ->where(['activo' => true])
            ->orderBy(['nombre_completo' => SORT_ASC]);
    }

    /**
     * Busca vendedor por email
     */
    public static function findByEmail($email)
    {
        return self::findOne(['email' => $email, 'activo' => true]);
    }
}