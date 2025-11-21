<?php
// modules/tienda/models/OrdenItem.php

namespace app\modules\tienda\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo para la tabla `comercio.orden_item`
 * 
 * @property int $id
 * @property int|null $id_orden
 * @property int|null $id_producto
 * @property int $cantidad
 * @property float $precio
 * @property float $subtotal
 * 
 * @property Orden $orden
 * @property Producto $producto
 */
class OrdenItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comercio.orden_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_orden', 'id_producto', 'cantidad'], 'integer'],
            [['cantidad', 'precio'], 'required'],
            [['precio', 'subtotal'], 'number', 'min' => 0],
            [['cantidad'], 'integer', 'min' => 1],
            [['id_orden'], 'exist', 'skipOnError' => true, 'targetClass' => Orden::class, 'targetAttribute' => ['id_orden' => 'id']],
            [['id_producto'], 'exist', 'skipOnError' => true, 'targetClass' => Producto::class, 'targetAttribute' => ['id_producto' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_orden' => 'Orden',
            'id_producto' => 'Producto',
            'cantidad' => 'Cantidad',
            'precio' => 'Precio Unitario',
            'subtotal' => 'Subtotal',
        ];
    }

    /**
     * Relación con la orden
     */
    public function getOrden()
    {
        return $this->hasOne(Orden::class, ['id' => 'id_orden']);
    }

    /**
     * Relación con el producto
     */
    public function getProducto()
    {
        return $this->hasOne(Producto::class, ['id' => 'id_producto']);
    }

    /**
     * Calcula el subtotal
     */
    public function calcularSubtotal()
    {
        $this->subtotal = $this->cantidad * $this->precio;
        return $this->subtotal;
    }

    /**
     * Before save - calcular subtotal
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->calcularSubtotal();
            return true;
        }
        return false;
    }

    /**
     * Verifica si el producto está disponible
     */
    public function validarDisponibilidad()
    {
        if ($this->producto && $this->producto->stock < $this->cantidad) {
            $this->addError('cantidad', 'No hay suficiente stock disponible.');
            return false;
        }
        return true;
    }
}