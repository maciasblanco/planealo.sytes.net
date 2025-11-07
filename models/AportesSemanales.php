<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "contabilidad.aportes_semanales".
 *
 * @property int $id
 * @property int $atleta_id
 * @property int $escuela_id
 * @property string $fecha_viernes
 * @property int $numero_semana
 * @property float $monto
 * @property string|null $estado
 * @property string|null $fecha_pago
 * @property string|null $metodo_pago
 * @property string|null $comentarios
 * @property string|null $created_at
 * @property int|null $u_create
 * @property int|null $u_update
 * @property bool|null $pago_parcial
 * @property string|null $update_at
 */
class AportesSemanales extends ActiveRecord
{
    const MONTO_SEMANAL = 2.00;
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADO = 'pagado';
    const ESTADO_CANCELADO = 'cancelado';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contabilidad.aportes_semanales';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'update_at',
                'value' => date('Y-m-d H:i:s'),
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'u_create',
                'updatedByAttribute' => 'u_update',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['atleta_id', 'escuela_id', 'fecha_viernes', 'numero_semana', 'monto'], 'required'],
            [['atleta_id', 'escuela_id', 'numero_semana', 'u_create', 'u_update'], 'default', 'value' => null],
            [['atleta_id', 'escuela_id', 'numero_semana', 'u_create', 'u_update'], 'integer'],
            [['fecha_viernes', 'fecha_pago', 'created_at', 'update_at'], 'safe'],
            [['monto'], 'number'],
            [['comentarios'], 'string'],
            [['pago_parcial'], 'boolean'],
            [['estado', 'metodo_pago'], 'string', 'max' => 255],
            [['estado'], 'default', 'value' => self::ESTADO_PENDIENTE],
            [['monto'], 'default', 'value' => self::MONTO_SEMANAL],
            [['pago_parcial'], 'default', 'value' => false],
            [['atleta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_id' => 'id']],
            [['escuela_id'], 'exist', 'skipOnError' => true, 'targetClass' => Escuela::class, 'targetAttribute' => ['escuela_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'atleta_id' => 'Atleta',
            'escuela_id' => 'Escuela',
            'fecha_viernes' => 'Fecha Viernes',
            'numero_semana' => 'Número Semana',
            'monto' => 'Monto ($)',
            'estado' => 'Estado',
            'fecha_pago' => 'Fecha Pago',
            'metodo_pago' => 'Método Pago',
            'comentarios' => 'Comentarios',
            'created_at' => 'Creado En',
            'u_create' => 'Usuario Creación',
            'u_update' => 'Usuario Actualización',
            'pago_parcial' => 'Pago Parcial',
            'update_at' => 'Actualizado En',
        ];
    }

    /**
     * Before validate event
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            // Calcular número de semana automáticamente si está vacío
            if (empty($this->numero_semana) && !empty($this->fecha_viernes)) {
                $this->numero_semana = self::calcularNumeroSemana($this->fecha_viernes);
            }
            
            return true;
        }
        return false;
    }

    /**
     * Before save event
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        // Si es pago y no tiene fecha de pago, usar fecha actual
        if ($this->estado == self::ESTADO_PAGADO && empty($this->fecha_pago)) {
            $this->fecha_pago = date('Y-m-d');
        }
        
        return true;
    }

    /**
     * Gets query for [[Atleta]].
     */
    public function getAtleta()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'atleta_id']);
    }

    /**
     * Gets query for [[Escuela]].
     */
    public function getEscuela()
    {
        return $this->hasOne(Escuela::class, ['id' => 'escuela_id']);
    }

    // =========================================================================
    // MÉTODOS DE CÁLCULO Y DEUDAS
    // =========================================================================

    /**
     * Calcula semanas equivalentes basado en monto aportado
     */
    public static function calcularSemanasEquivalentes($montoAportado)
    {
        return $montoAportado / self::MONTO_SEMANAL;
    }

    /**
     * Calcula el número de semana a partir de una fecha
     */
    public static function calcularNumeroSemana($fecha)
    {
        if (empty($fecha)) {
            return (int)date('W');
        }
        
        try {
            $fechaObj = new \DateTime($fecha);
            return (int)$fechaObj->format('W');
        } catch (\Exception $e) {
            return (int)date('W');
        }
    }

    /**
     * Calcula la deuda en semanas de un atleta
     */
    public static function calcularDeudaAtleta($atleta_id)
    {
        return self::find()
            ->where([
                'atleta_id' => $atleta_id,
                'estado' => self::ESTADO_PENDIENTE
            ])
            ->count();
    }

    /**
     * Calcula el monto total de deuda de un atleta
     */
    public static function calcularMontoDeuda($atleta_id)
    {
        $deuda = self::find()
            ->where([
                'atleta_id' => $atleta_id,
                'estado' => self::ESTADO_PENDIENTE
            ])
            ->sum('monto');
        
        return $deuda ? floatval($deuda) : 0;
    }

    /**
     * Obtiene el historial completo de deudas/pagos de un atleta
     */
    public static function obtenerHistorialDeudas($atleta_id)
    {
        return self::find()
            ->where(['atleta_id' => $atleta_id])
            ->orderBy(['fecha_viernes' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * Obtiene las deudas pendientes de un atleta ordenadas por antigüedad
     */
    public static function obtenerDeudasPendientes($atleta_id)
    {
        return self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => 'pendiente'])
            ->orderBy(['fecha_viernes' => SORT_ASC])
            ->all();
    }

    // =========================================================================
    // MÉTODOS DE GESTIÓN DE APORTES
    // =========================================================================

    /**
     * Genera semanas automáticamente para un atleta desde su fecha de creación
     */
    public static function generarSemanasParaAtleta($atleta_id)
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return 0;
        }

        // Usar fecha de creación del atleta o fecha por defecto
        $fechaInicio = $atleta->d_creacion ?? '2024-09-15';
        $fechaInicioObj = new \DateTime($fechaInicio);
        
        // Ajustar al próximo viernes si no es viernes
        if ($fechaInicioObj->format('N') != 5) {
            $fechaInicioObj->modify('next friday');
        }

        $hoy = new \DateTime();
        $semanasGeneradas = 0;

        while ($fechaInicioObj <= $hoy) {
            $fechaViernes = $fechaInicioObj->format('Y-m-d');
            
            // Verificar si ya existe un aporte para esta fecha
            $existeAporte = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_viernes' => $fechaViernes
                ])
                ->exists();

            if (!$existeAporte) {
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_viernes = $fechaViernes;
                $aporte->numero_semana = (int)$fechaInicioObj->format('W');
                $aporte->monto = self::MONTO_SEMANAL;
                $aporte->estado = self::ESTADO_PENDIENTE;
                $aporte->pago_parcial = false;

                if ($aporte->save()) {
                    $semanasGeneradas++;
                }
            }

            $fechaInicioObj->modify('+1 week');
        }

        return $semanasGeneradas;
    }

    /**
     * Procesa aportes flexibles con cualquier monto
     */
    public static function procesarAporteFlexible($atleta_id, $montoTotal, $fechaPago = null, 
                                                $metodoPago = 'efectivo', $comentarios = 'Aporte flexible')
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            throw new \Exception('Atleta no encontrado');
        }

        $montoSemanal = self::MONTO_SEMANAL;
        $semanasCompletas = floor($montoTotal / $montoSemanal);
        $montoRestante = $montoTotal - ($semanasCompletas * $montoSemanal);
        
        $semanasProcesadas = 0;
        $fechaActual = new \DateTime();
        
        // Procesar semanas completas
        for ($i = 0; $i < $semanasCompletas; $i++) {
            $fechaViernes = self::calcularProximoViernes($fechaActual);
            
            // Verificar si ya existe un aporte para esta fecha
            $existeAporte = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_viernes' => $fechaViernes
                ])
                ->exists();

            if (!$existeAporte) {
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_viernes = $fechaViernes;
                $aporte->numero_semana = (int)date('W', strtotime($fechaViernes));
                $aporte->monto = $montoSemanal;
                $aporte->estado = self::ESTADO_PAGADO;
                $aporte->fecha_pago = $fechaPago ?: date('Y-m-d');
                $aporte->metodo_pago = $metodoPago;
                $aporte->comentarios = $comentarios . " - Semana {$fechaViernes}";
                $aporte->pago_parcial = false;
                
                if ($aporte->save()) {
                    $semanasProcesadas++;
                }
            }
            
            $fechaActual = new \DateTime($fechaViernes);
            $fechaActual->modify('+1 week');
        }
        
        // Procesar monto restante como aporte parcial
        if ($montoRestante > 0) {
            $fechaViernes = self::calcularProximoViernes($fechaActual);
            
            // Verificar si ya existe un aporte parcial para esta fecha
            $existeAporteParcial = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_viernes' => $fechaViernes,
                    'pago_parcial' => true
                ])
                ->exists();

            if (!$existeAporteParcial) {
                $aporteParcial = new self();
                $aporteParcial->atleta_id = $atleta_id;
                $aporteParcial->escuela_id = $atleta->id_escuela;
                $aporteParcial->fecha_viernes = $fechaViernes;
                $aporteParcial->numero_semana = (int)date('W', strtotime($fechaViernes));
                $aporteParcial->monto = $montoRestante;
                $aporteParcial->estado = self::ESTADO_PAGADO;
                $aporteParcial->fecha_pago = $fechaPago ?: date('Y-m-d');
                $aporteParcial->metodo_pago = $metodoPago;
                $aporteParcial->comentarios = $comentarios . " - Aporte parcial";
                $aporteParcial->pago_parcial = true;
                
                if ($aporteParcial->save()) {
                    $semanasProcesadas++;
                }
            }
        }
        
        return $semanasProcesadas;
    }

    /**
     * Calcula el próximo viernes desde una fecha dada
     */
    private static function calcularProximoViernes($fecha)
    {
        $fecha = clone $fecha;
        if ($fecha->format('N') != 5) { // Si no es viernes
            $fecha->modify('next friday');
        }
        return $fecha->format('Y-m-d');
    }

    // =========================================================================
    // MÉTODOS DE PAGOS COMPLETOS
    // =========================================================================

    /**
     * Procesa pago múltiple de semanas pendientes
     */
    public static function procesarPagoMultiple($atleta_id, $semanasSeleccionadas, $fechaPago, 
                                               $metodoPago = 'efectivo', $comentarios = 'Pago múltiple')
    {
        $semanasPagadas = 0;

        foreach ($semanasSeleccionadas as $fechaViernes) {
            $aporte = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_viernes' => $fechaViernes
                ])
                ->one();

            if ($aporte) {
                $aporte->estado = self::ESTADO_PAGADO;
                $aporte->fecha_pago = $fechaPago;
                $aporte->metodo_pago = $metodoPago;
                $aporte->comentarios = $comentarios;

                if ($aporte->save()) {
                    $semanasPagadas++;
                }
            }
        }

        return $semanasPagadas;
    }

    /**
     * Procesa pago adelantado de semanas futuras
     */
    public static function procesarPagoAdelantado($atleta_id, $semanasAdelanto, $fechaPago,
                                                 $metodoPago = 'efectivo', $comentarios = 'Pago adelantado')
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return 0;
        }

        $fechaActual = new \DateTime();
        if ($fechaActual->format('N') != 5) {
            $fechaActual->modify('next friday');
        }

        $semanasPagadas = 0;

        for ($i = 0; $i < $semanasAdelanto; $i++) {
            $fechaViernes = $fechaActual->format('Y-m-d');

            $existeAporte = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_viernes' => $fechaViernes
                ])
                ->exists();

            if (!$existeAporte) {
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_viernes = $fechaViernes;
                $aporte->numero_semana = (int)$fechaActual->format('W');
                $aporte->monto = self::MONTO_SEMANAL;
                $aporte->estado = self::ESTADO_PAGADO;
                $aporte->fecha_pago = $fechaPago;
                $aporte->metodo_pago = $metodoPago;
                $aporte->comentarios = $comentarios . " - Semana {$fechaViernes}";
                $aporte->pago_parcial = false;

                if ($aporte->save()) {
                    $semanasPagadas++;
                }
            }

            $fechaActual->modify('+1 week');
        }

        return $semanasPagadas;
    }

    /**
     * Liquidar deudas pendientes para un atleta
     */
    public static function liquidarDeudasPendientes($atleta_id, $fecha_pago, $metodo_pago, $comentarios = '')
    {
        $deudas = self::obtenerDeudasPendientes($atleta_id);
        $liquidadas = 0;
        
        foreach ($deudas as $deuda) {
            $deuda->estado = self::ESTADO_PAGADO;
            $deuda->fecha_pago = $fecha_pago;
            $deuda->metodo_pago = $metodo_pago;
            $deuda->comentarios = $comentarios . " (Liquidación automática de deuda)";
            
            if ($deuda->save()) {
                $liquidadas++;
            }
        }
        
        return $liquidadas;
    }

    // =========================================================================
    // MÉTODOS DE ESTADÍSTICAS Y REPORTES
    // =========================================================================

    /**
     * Obtiene los top atletas por aportes
     */
    public static function getTopAtletas($escuela_id = null, $limit = 10)
    {
        $query = self::find()
            ->select([
                'atleta_id',
                'COUNT(*) as total_aportes',
                'SUM(monto) as total_monto'
            ])
            ->where(['estado' => self::ESTADO_PAGADO])
            ->groupBy(['atleta_id'])
            ->orderBy(['total_monto' => SORT_DESC])
            ->limit($limit);

        if ($escuela_id) {
            $query->andWhere(['escuela_id' => $escuela_id]);
        }

        return $query->asArray()->all();
    }

    /**
     * Obtiene estadísticas completas de un atleta
     */
    public static function getEstadisticasAtleta($atleta_id)
    {
        $totalAportes = self::find()->where(['atleta_id' => $atleta_id])->count();
        $aportesPagados = self::find()->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PAGADO])->count();
        $aportesPendientes = self::find()->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PENDIENTE])->count();
        
        $montoTotalPagado = self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PAGADO])
            ->sum('monto') ?? 0;
        
        $montoTotalPendiente = self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PENDIENTE])
            ->sum('monto') ?? 0;

        return [
            'total_aportes' => $totalAportes,
            'aportes_pagados' => $aportesPagados,
            'aportes_pendientes' => $aportesPendientes,
            'monto_total_pagado' => floatval($montoTotalPagado),
            'monto_total_pendiente' => floatval($montoTotalPendiente),
            'semanas_equivalentes' => self::calcularSemanasEquivalentes($montoTotalPagado),
        ];
    }

    // =========================================================================
    // MÉTODOS DE UTILIDAD Y FORMATO
    // =========================================================================

    /**
     * Obtiene el nombre del estado con formato legible
     */
    public function getEstadoLabel()
    {
        $estados = [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_CANCELADO => 'Cancelado'
        ];
        
        return $estados[$this->estado] ?? $this->estado;
    }

    /**
     * Verifica si el aporte está completo (no es parcial)
     */
    public function esCompleto()
    {
        return !$this->pago_parcial && $this->monto >= self::MONTO_SEMANAL;
    }

    /**
     * Obtiene el saldo pendiente para completar la semana (solo para aportes parciales)
     */
    public function getSaldoPendiente()
    {
        if ($this->pago_parcial) {
            return self::MONTO_SEMANAL - $this->monto;
        }
        return 0;
    }

    /**
     * Obtiene la tasa de cambio del día de un pago específico
     */
    public function getTasaCambioDelDia()
    {
        if (!$this->fecha_pago) {
            return null;
        }
        
        $tasa = TasaDolar::find()
            ->where(['fecha_tasa' => $this->fecha_pago])
            ->one();
            
        return $tasa ? $tasa->tasa_dia : null;
    }

    /**
     * Calcula el monto en bolívares usando la tasa del día del pago
     */
    public function getMontoEnBolivares()
    {
        $tasa = $this->getTasaCambioDelDia();
        return $tasa ? $this->monto * $tasa : null;
    }
}