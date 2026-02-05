<?php
// modules/tienda/models/CategoriaProducto.php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo para la tabla `comercio.categoria_producto`
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $imagen
 * @property int|null $id_padre
 * @property bool $activo
 * @property int $orden
 * 
 * @property CategoriaProducto $padre
 * @property CategoriaProducto[] $hijos
 * @property Producto[] $productos
 */
class CategoriaProducto extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comercio.categoria_producto';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombre'], 'required'],
            [['descripcion'], 'string'],
            [['id_padre', 'orden'], 'integer'],
            [['activo'], 'boolean'],
            [['nombre', 'imagen'], 'string', 'max' => 100],
            [['orden'], 'default', 'value' => 0],
            [['activo'], 'default', 'value' => true],
            [['id_padre'], 'exist', 'skipOnError' => true, 'targetClass' => CategoriaProducto::class, 'targetAttribute' => ['id_padre' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'imagen' => 'Imagen',
            'id_padre' => 'Categoría Padre',
            'activo' => 'Activa',
            'orden' => 'Orden',
        ];
    }

    /**
     * Relación con la categoría padre
     */
    public function getPadre()
    {
        return $this->hasOne(CategoriaProducto::class, ['id' => 'id_padre']);
    }

    /**
     * Relación con categorías hijas
     */
    public function getHijos()
    {
        return $this->hasMany(CategoriaProducto::class, ['id_padre' => 'id'])
                    ->andWhere(['activo' => true])
                    ->orderBy(['orden' => SORT_ASC, 'nombre' => SORT_ASC]);
    }

    /**
     * Relación con productos
     */
    public function getProductos()
    {
        return $this->hasMany(Producto::class, ['id_categoria' => 'id'])
                    ->andWhere(['activo' => true])
                    ->andWhere(['eliminado' => false]);
    }

    /**
     * Verifica si es una categoría raíz (sin padre)
     */
    public function isRaiz()
    {
        return empty($this->id_padre);
    }

    /**
     * Obtiene categorías raíz
     */
    public static function getCategoriasRaiz()
    {
        return self::find()
            ->where(['id_padre' => null])
            ->andWhere(['activo' => true])
            ->orderBy(['orden' => SORT_ASC, 'nombre' => SORT_ASC])
            ->all();
    }

    /**
     * Obtiene el árbol completo de categorías
     */
    public static function getArbolCategorias()
    {
        $categorias = self::find()
            ->where(['activo' => true])
            ->orderBy(['orden' => SORT_ASC, 'nombre' => SORT_ASC])
            ->all();

        return self::construirArbol($categorias);
    }

    /**
     * Construye el árbol de categorías
     */
    private static function construirArbol($categorias, $padreId = null)
    {
        $arbol = [];

        foreach ($categorias as $categoria) {
            if ($categoria->id_padre == $padreId) {
                $hijos = self::construirArbol($categorias, $categoria->id);
                $categoria->populateRelation('hijos', $hijos);
                $arbol[] = $categoria;
            }
        }

        return $arbol;
    }

    /**
     * Obtiene el número de productos en la categoría (incluyendo subcategorías)
     */
    public function getCountProductosTotal()
    {
        $count = $this->getProductos()->count();
        
        foreach ($this->hijos as $hijo) {
            $count += $hijo->getCountProductosTotal();
        }

        return $count;
    }
}