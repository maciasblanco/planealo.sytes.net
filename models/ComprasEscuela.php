<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $escuela_id
 * @property string $descripcion
 * @property string $tipo
 * @property float $monto
 * @property string $fecha_compra
 * @property string|null $comprobante
 * @property string|null $comentarios
 * @property string $created_at
 */
class ComprasEscuela extends ActiveRecord
{
    const TIPO_MATERIALES = 'materiales';
    const TIPO_EQUIPOS = 'equipos';
    const TIPO_UNIFORMES = 'uniformes';
    const TIPO_OTROS = 'otros';

    public static function tableName()
    {
        return 'contabilidad.compras_escuela';
    }

    public function rules()
    {
        return [
            [['escuela_id', 'descripcion', 'tipo', 'monto', 'fecha_compra'], 'required'],
            [['escuela_id'], 'integer'],
            [['monto'], 'number'],
            [['fecha_compra', 'created_at'], 'safe'],
            [['descripcion', 'comprobante', 'comentarios'], 'string'],
            [['tipo'], 'in', 'range' => [self::TIPO_MATERIALES, self::TIPO_EQUIPOS, self::TIPO_UNIFORMES, self::TIPO_OTROS]],
            
            // NUEVAS VALIDACIONES PARA BLINDAJE
            ['escuela_id', 'validateEscuelaExistente'],
            ['escuela_id', 'required', 'message' => 'La escuela es requerida'],
        ];
    }
    
    /**
     * VALIDACIÓN CRÍTICA - Verifica que la escuela exista y esté activa
     */
    public function validateEscuelaExistente($attribute, $params)
    {
        if (empty($this->escuela_id)) {
            $this->addError($attribute, 'La escuela es requerida.');
            return;
        }
        
        // Validar existencia en BD
        $escuela = Escuela::find()
            ->where(['id' => $this->escuela_id])
            ->andWhere(['eliminado' => false])
            ->one();
            
        if (!$escuela) {
            $this->addError($attribute, 'La escuela seleccionada no existe o está inactiva.');
            Yii::error("Intento de guardar compra con escuela inválida: {$this->escuela_id}");
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'escuela_id' => 'Escuela',
            'descripcion' => 'Descripción',
            'tipo' => 'Tipo de Compra',
            'monto' => 'Monto',
            'fecha_compra' => 'Fecha de Compra',
            'comprobante' => 'Número de Comprobante',
            'comentarios' => 'Comentarios',
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
        
        // Forzar escuela_id desde sesión si está vacío
        if (empty($this->escuela_id)) {
            $session = Yii::$app->session;
            $this->escuela_id = $session->get('id_escuela');
        }
        
        // Validación final de escuela
        if (empty($this->escuela_id) || !Escuela::find()->where(['id' => $this->escuela_id, 'eliminado' => false])->exists()) {
            Yii::error("BLOQUEO: Intento de guardar compra sin escuela válida. User: " . (Yii::$app->user->id ?? 'anonimo'));
            return false;
        }
        
        return true;
    }

    public function getEscuela()
    {
        return $this->hasOne(Escuela::class, ['id' => 'escuela_id']);
    }

    /**
     * Obtiene total de compras por escuela
     */
    public static function getTotalCompras($escuela_id)
    {
        return self::find()
            ->where(['escuela_id' => $escuela_id])
            ->sum('monto') ?? 0;
    }

    /**
     * Obtiene compras por tipo
     */
    public static function getComprasPorTipo($escuela_id)
    {
        return self::find()
            ->select(['tipo', 'SUM(monto) as total'])
            ->where(['escuela_id' => $escuela_id])
            ->groupBy(['tipo'])
            ->asArray()
            ->all();
    }
}