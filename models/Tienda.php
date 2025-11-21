<?php
// modules/tienda/models/Tienda.php

namespace app\modules\tienda\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * Modelo para la tabla `comercio.tienda`
 * 
 * @property int $id
 * @property int|null $id_encargado
 * @property int|null $id_vendedor
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $logo
 * @property string|null $banner
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $direccion
 * @property int|null $id_estado
 * @property int|null $id_municipio
 * @property float|null $lat
 * @property float|null $lng
 * @property float $rating
 * @property bool $activo
 * @property string $d_creacion
 * @property bool $eliminado
 * @property int|null $user_id
 * @property string|null $tipo_propietario
 * @property string|null $slug
 * @property string|null $horario_atencion
 * @property string|null $politicas_entrega
 * @property string|null $redes_sociales
 * 
 * @property Producto[] $productos
 * @property Vendedor $vendedor
 */
class Tienda extends ActiveRecord
{
    const TIPO_VENDEDOR = 'vendedor';
    const TIPO_DEPORTIVO = 'deportivo';
    const TIPO_MIXTO = 'mixto';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comercio.tienda';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'd_creacion',
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
            [['nombre'], 'required'],
            [['descripcion', 'direccion', 'horario_atencion', 'politicas_entrega'], 'string'],
            [['id_encargado', 'id_vendedor', 'id_estado', 'id_municipio', 'user_id'], 'integer'],
            [['lat', 'lng', 'rating'], 'number'],
            [['activo', 'eliminado'], 'boolean'],
            [['d_creacion'], 'safe'],
            [['redes_sociales'], 'safe'], // Para JSON
            [['nombre', 'logo', 'banner'], 'string', 'max' => 200],
            [['telefono'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['tipo_propietario'], 'string', 'max' => 20],
            [['slug'], 'string', 'max' => 100],
            [['slug'], 'unique'],
            [['email'], 'email'],
            [['rating'], 'default', 'value' => 0],
            [['activo'], 'default', 'value' => true],
            [['eliminado'], 'default', 'value' => false],
            [['tipo_propietario'], 'default', 'value' => self::TIPO_VENDEDOR],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_encargado' => 'Encargado',
            'id_vendedor' => 'Vendedor',
            'nombre' => 'Nombre de la Tienda',
            'descripcion' => 'Descripción',
            'logo' => 'Logo',
            'banner' => 'Banner',
            'telefono' => 'Teléfono',
            'email' => 'Email',
            'direccion' => 'Dirección',
            'id_estado' => 'Estado',
            'id_municipio' => 'Municipio',
            'lat' => 'Latitud',
            'lng' => 'Longitud',
            'rating' => 'Calificación',
            'activo' => 'Activa',
            'd_creacion' => 'Fecha de Creación',
            'eliminado' => 'Eliminada',
            'user_id' => 'Usuario',
            'tipo_propietario' => 'Tipo de Propietario',
            'slug' => 'Slug',
            'horario_atencion' => 'Horario de Atención',
            'politicas_entrega' => 'Políticas de Entrega',
            'redes_sociales' => 'Redes Sociales',
        ];
    }

    /**
     * Relación con los productos de la tienda
     */
    public function getProductos()
    {
        return $this->hasMany(Producto::class, ['id_tienda' => 'id'])
                    ->andWhere(['activo' => true])
                    ->andWhere(['eliminado' => false]);
    }

    /**
     * Relación con productos activos (para el marketplace)
     */
    public function getProductosActivos()
    {
        return $this->hasMany(Producto::class, ['id_tienda' => 'id'])
                    ->andWhere(['activo' => true])
                    ->andWhere(['eliminado' => false]);
    }

    /**
     * Relación con el vendedor
     */
    public function getVendedor()
    {
        return $this->hasOne(Vendedor::class, ['id' => 'id_vendedor']);
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
     * Genera el slug automáticamente antes de validar
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if (empty($this->slug) && !empty($this->nombre)) {
                $this->slug = $this->generarSlug($this->nombre);
            }
            return true;
        }
        return false;
    }

    /**
     * Genera un slug único a partir del nombre
     */
    private function generarSlug($nombre)
    {
        $slug = \yii\helpers\Inflector::slug($nombre);
        $baseSlug = $slug;
        $counter = 1;

        while (self::find()->where(['slug' => $slug])->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Obtiene la URL de la tienda
     */
    public function getUrl()
    {
        return \yii\helpers\Url::to(['/tienda/tienda/view', 'slug' => $this->slug]);
    }

    /**
     * Obtiene el número de productos activos
     */
    public function getCountProductosActivos()
    {
        return $this->getProductosActivos()->count();
    }

    /**
     * Verifica si la tienda está activa
     */
    public function isActiva()
    {
        return $this->activo && !$this->eliminado;
    }

    /**
     * Obtiene tiendas activas para el marketplace
     */
    public static function getTiendasActivas()
    {
        return self::find()
            ->where(['activo' => true])
            ->andWhere(['eliminado' => false])
            ->orderBy(['nombre' => SORT_ASC]);
    }
}