<?php
// modules/tienda/models/Producto.php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\UploadedFile;

/**
 * Modelo para la tabla `comercio.producto`
 * 
 * @property int $id
 * @property int $id_tienda
 * @property int|null $id_categoria
 * @property string $nombre
 * @property string|null $descripcion
 * @property float $precio
 * @property float|null $precio_oferta
 * @property int $stock
 * @property string|null $sku
 * @property string|null $imagens
 * @property string|null $caracteristicas
 * @property bool $activo
 * @property bool $destacado
 * @property string $d_creacion
 * @property int|null $u_creacion
 * @property bool $eliminado
 * 
 * @property Tienda $tienda
 * @property CategoriaProducto $categoria
 */
class Producto extends ActiveRecord
{
    public $imagenArchivo; // Para upload de imágenes
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comercio.producto';
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
            [['id_tienda', 'nombre', 'precio'], 'required'],
            [['id_tienda', 'id_categoria', 'stock', 'u_creacion'], 'integer'],
            [['descripcion', 'caracteristicas'], 'string'],
            [['precio', 'precio_oferta'], 'number', 'min' => 0],
            [['activo', 'destacado', 'eliminado'], 'boolean'],
            [['d_creacion'], 'safe'],
            [['nombre'], 'string', 'max' => 200],
            [['sku'], 'string', 'max' => 100],
            [['imagens'], 'string', 'max' => 500],
            [['stock'], 'default', 'value' => 0],
            [['activo'], 'default', 'value' => true],
            [['destacado'], 'default', 'value' => false],
            [['eliminado'], 'default', 'value' => false],
            [['imagenArchivo'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif', 'maxFiles' => 5],
            [['id_tienda'], 'exist', 'skipOnError' => true, 'targetClass' => Tienda::class, 'targetAttribute' => ['id_tienda' => 'id']],
            [['id_categoria'], 'exist', 'skipOnError' => true, 'targetClass' => CategoriaProducto::class, 'targetAttribute' => ['id_categoria' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_tienda' => 'Tienda',
            'id_categoria' => 'Categoría',
            'nombre' => 'Nombre del Producto',
            'descripcion' => 'Descripción',
            'precio' => 'Precio',
            'precio_oferta' => 'Precio en Oferta',
            'stock' => 'Stock Disponible',
            'sku' => 'SKU',
            'imagens' => 'Imágenes',
            'caracteristicas' => 'Características',
            'activo' => 'Activo',
            'destacado' => 'Destacado',
            'd_creacion' => 'Fecha de Creación',
            'u_creacion' => 'Usuario Creación',
            'eliminado' => 'Eliminado',
            'imagenArchivo' => 'Imágenes del Producto',
        ];
    }

    /**
     * Relación con la tienda
     */
    public function getTienda()
    {
        return $this->hasOne(Tienda::class, ['id' => 'id_tienda']);
    }

    /**
     * Relación con la categoría
     */
    public function getCategoria()
    {
        return $this->hasOne(CategoriaProducto::class, ['id' => 'id_categoria']);
    }

    /**
     * Obtiene el precio de venta (usa oferta si existe)
     */
    public function getPrecioVenta()
    {
        return $this->precio_oferta ?: $this->precio;
    }

    /**
     * Verifica si tiene descuento
     */
    public function getTieneDescuento()
    {
        return $this->precio_oferta && $this->precio_oferta < $this->precio;
    }

    /**
     * Calcula el porcentaje de descuento
     */
    public function getPorcentajeDescuento()
    {
        if (!$this->tieneDescuento) {
            return 0;
        }
        
        return round((($this->precio - $this->precio_oferta) / $this->precio) * 100);
    }

    /**
     * Obtiene las imágenes como array
     */
    public function getImagenesArray()
    {
        if (empty($this->imagens)) {
            return [];
        }
        
        return json_decode($this->imagens, true) ?: [];
    }

    /**
     * Obtiene la imagen principal
     */
    public function getImagenPrincipal()
    {
        $imagenes = $this->imagenesArray;
        return !empty($imagenes) ? $imagenes[0] : null;
    }

    /**
     * Verifica si el producto está disponible
     */
    public function isDisponible()
    {
        return $this->activo && !$this->eliminado && $this->stock > 0;
    }

    /**
     * Obtiene productos activos para el marketplace
     */
    public static function getProductosActivos()
    {
        return self::find()
            ->where(['activo' => true])
            ->andWhere(['eliminado' => false])
            ->andWhere(['>', 'stock', 0])
            ->with(['tienda' => function($query) {
                $query->andWhere(['activo' => true, 'eliminado' => false]);
            }])
            ->orderBy(['destacado' => SORT_DESC, 'd_creacion' => SORT_DESC]);
    }

    /**
     * Obtiene productos destacados
     */
    public static function getProductosDestacados($limit = 8)
    {
        return self::getProductosActivos()
            ->andWhere(['destacado' => true])
            ->limit($limit)
            ->all();
    }

    /**
     * Procesa el upload de imágenes
     */
    public function uploadImagenes()
    {
        $imagenes = UploadedFile::getInstances($this, 'imagenArchivo');
        $imagenPaths = [];

        if ($imagenes) {
            $uploadPath = Yii::getAlias('@webroot/uploads/productos/');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            foreach ($imagenes as $imagen) {
                $fileName = uniqid() . '_' . $imagen->baseName . '.' . $imagen->extension;
                $filePath = $uploadPath . $fileName;
                
                if ($imagen->saveAs($filePath)) {
                    $imagenPaths[] = '/uploads/productos/' . $fileName;
                }
            }
        }

        if (!empty($imagenPaths)) {
            $this->imagens = json_encode($imagenPaths);
        }

        return true;
    }
}