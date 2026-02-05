<?php
// modules/tienda/models/Orden.php

namespace app\modules\tienda\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Modelo para la tabla `comercio.orden`
 * 
 * @property int $id
 * @property string $numero_orden
 * @property int|null $id_cliente
 * @property int|null $id_tienda
 * @property float $total
 * @property string $estado
 * @property string|null $metodo_pago
 * @property string|null $direccion_entrega
 * @property string|null $notas
 * @property string $d_creacion
 * 
 * @property Tienda $tienda
 * @property OrdenItem[] $ordenItems
 */
class Orden extends ActiveRecord
{
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_CONFIRMADO = 'confirmado';
    const ESTADO_ENVIADO = 'enviado';
    const ESTADO_ENTREGADO = 'entregado';
    const ESTADO_CANCELADO = 'cancelado';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comercio.orden';
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
            [['numero_orden', 'total'], 'required'],
            [['id_cliente', 'id_tienda'], 'integer'],
            [['total'], 'number', 'min' => 0],
            [['direccion_entrega', 'notas'], 'string'],
            [['d_creacion'], 'safe'],
            [['numero_orden'], 'string', 'max' => 50],
            [['estado'], 'string', 'max' => 20],
            [['metodo_pago'], 'string', 'max' => 50],
            [['estado'], 'default', 'value' => self::ESTADO_PENDIENTE],
            [['id_tienda'], 'exist', 'skipOnError' => true, 'targetClass' => Tienda::class, 'targetAttribute' => ['id_tienda' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'numero_orden' => 'Número de Orden',
            'id_cliente' => 'Cliente',
            'id_tienda' => 'Tienda',
            'total' => 'Total',
            'estado' => 'Estado',
            'metodo_pago' => 'Método de Pago',
            'direccion_entrega' => 'Dirección de Entrega',
            'notas' => 'Notas',
            'd_creacion' => 'Fecha de Creación',
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
     * Relación con los items de la orden
     */
    public function getOrdenItems()
    {
        return $this->hasMany(OrdenItem::class, ['id_orden' => 'id']);
    }

    /**
     * Genera número de orden único
     */
    public function generarNumeroOrden()
    {
        $prefix = 'ORD';
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $random;
    }

    /**
     * Calcula el total de la orden basado en los items
     */
    public function calcularTotal()
    {
        $total = 0;
        foreach ($this->ordenItems as $item) {
            $total += $item->subtotal;
        }
        $this->total = $total;
        return $total;
    }

    /**
     * Verifica si la orden puede ser cancelada
     */
    public function puedeCancelar()
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_CONFIRMADO]);
    }

    /**
     * Obtiene las opciones de estado
     */
    public static function getEstadosOptions()
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_CONFIRMADO => 'Confirmado',
            self::ESTADO_ENVIADO => 'Enviado',
            self::ESTADO_ENTREGADO => 'Entregado',
            self::ESTADO_CANCELADO => 'Cancelado',
        ];
    }

    /**
     * Obtiene el label del estado
     */
    public function getEstadoLabel()
    {
        $estados = self::getEstadosOptions();
        return $estados[$this->estado] ?? $this->estado;
    }

    /**
     * Obtiene las clases CSS para el estado
     */
    public function getEstadoCssClass()
    {
        $classes = [
            self::ESTADO_PENDIENTE => 'warning',
            self::ESTADO_CONFIRMADO => 'info',
            self::ESTADO_ENVIADO => 'primary',
            self::ESTADO_ENTREGADO => 'success',
            self::ESTADO_CANCELADO => 'danger',
        ];
        
        return $classes[$this->estado] ?? 'secondary';
    }
}