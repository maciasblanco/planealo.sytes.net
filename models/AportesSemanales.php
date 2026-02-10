<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "contabilidad.aportes_semanales".
 * MODIFICADO: Sistema quincenal ($4.00 cada 15 días) con manejo dual de moneda
 * Fecha inicio: 15 de enero de 2026
 * Fechas exactas: días 15 y último día de cada mes
 * SOLO DESDE 15/01/2026
 *
 * @property int $id
 * @property int $atleta_id
 * @property int $escuela_id
 * @property string $fecha_quincena
 * @property int $numero_quincena
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
 * @property float|null $tasa_dolar_quincena
 * @property float|null $monto_bs_original
 * @property string|null $tipo_cambio
 */
class AportesSemanales extends ActiveRecord
{
    // =========================================================================
    // CONSTANTES DEL SISTEMA
    // =========================================================================
    const MONTO_QUINCENAL_USD = 5.00; // $5.00 dólares por quincena
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADO = 'pagado';
    const ESTADO_CANCELADO = 'cancelado';
    
    // Fecha de inicio para nuevas deudas (15 de enero de 2026)
    const FECHA_INICIO_DEUDAS = '2026-01-15';
    
    // Fracción proporcional (solo primera quincena después de inscripción)
    const DIAS_FRACCION = 8; // Menos de 8 días = pago proporcional
    
    // Propiedades para manejo de moneda (no se persisten en BD)
    public $monto_bs; // Para formularios (entrada en bolívares)
    public $tasa_dia; // Tasa del día para cálculos
    public $mostrar_bs = true; // Para vistas

    // =========================================================================
    // MÉTODOS DE YII2
    // =========================================================================

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
            [['atleta_id', 'escuela_id', 'fecha_quincena', 'numero_quincena'], 'required'],
            [['atleta_id', 'escuela_id', 'numero_quincena', 'u_create', 'u_update'], 'default', 'value' => null],
            [['atleta_id', 'escuela_id', 'numero_quincena', 'u_create', 'u_update'], 'integer'],
            [['fecha_quincena', 'fecha_pago', 'created_at', 'update_at'], 'safe'],
            [['monto', 'tasa_dolar_quincena', 'monto_bs_original', 'monto_bs', 'tasa_dia'], 'number'],
            [['comentarios'], 'string'],
            [['pago_parcial'], 'boolean'],
            [['estado', 'metodo_pago', 'tipo_cambio'], 'string', 'max' => 255],
            [['estado'], 'default', 'value' => self::ESTADO_PENDIENTE],
            [['monto'], 'default', 'value' => self::MONTO_QUINCENAL_USD],
            [['pago_parcial'], 'default', 'value' => false],
            [['tasa_dolar_quincena'], 'default', 'value' => 0],
            [['monto_bs_original'], 'default', 'value' => 0],
            [['tipo_cambio'], 'default', 'value' => 'oficial'],
            [['atleta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_id' => 'id']],
            [['escuela_id'], 'exist', 'skipOnError' => true, 'targetClass' => Escuela::class, 'targetAttribute' => ['escuela_id' => 'id']],
            
            // Validaciones personalizadas
            [['monto_bs'], 'validateMontoBs'],
            // Validar que la fecha de quincena sea >= 15/01/2026
            [['fecha_quincena'], 'validateFechaQuincena'],
        ];
    }

    /**
     * Validación personalizada para monto en bolívares
     */
    public function validateMontoBs($attribute, $params)
    {
        if ($this->$attribute !== null && $this->$attribute < 0) {
            $this->addError($attribute, 'El monto en bolívares no puede ser negativo.');
        }
    }

    /**
     * Validación personalizada para fecha de quincena (debe ser >= 15/01/2026)
     */
    public function validateFechaQuincena($attribute, $params)
    {
        if ($this->$attribute && strtotime($this->$attribute) < strtotime(self::FECHA_INICIO_DEUDAS)) {
            $this->addError($attribute, 'La fecha de quincena no puede ser anterior al 15 de enero de 2026.');
        }
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
            'fecha_quincena' => 'Fecha Quincena',
            'numero_quincena' => 'Número Quincena',
            'monto' => 'Monto (USD)',
            'monto_bs' => 'Monto (Bs)',
            'estado' => 'Estado',
            'fecha_pago' => 'Fecha Pago',
            'metodo_pago' => 'Método Pago',
            'comentarios' => 'Comentarios',
            'created_at' => 'Creado En',
            'u_create' => 'Usuario Creación',
            'u_update' => 'Usuario Actualización',
            'pago_parcial' => 'Pago Parcial',
            'update_at' => 'Actualizado En',
            'tasa_dolar_quincena' => 'Tasa Dólar Quincena',
            'monto_bs_original' => 'Monto Original (Bs)',
            'tipo_cambio' => 'Tipo Cambio',
        ];
    }

    /**
     * Before validate event
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            // Calcular número de quincena automáticamente si está vacío
            if (empty($this->numero_quincena) && !empty($this->fecha_quincena)) {
                $this->numero_quincena = self::calcularNumeroQuincenaExacta($this->fecha_quincena);
            }
            
            // Si viene monto_bs, calcular monto en dólares
            if ($this->monto_bs !== null && $this->monto_bs > 0 && empty($this->monto)) {
                if (empty($this->tasa_dia)) {
                    $this->tasa_dia = self::obtenerTasaDolar($this->fecha_quincena);
                }
                $this->monto = self::convertirBsADolares($this->monto_bs, $this->tasa_dia);
                $this->monto_bs_original = $this->monto_bs;
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
        
        // Validar que la fecha de quincena sea >= 15/01/2026
        if ($this->fecha_quincena && strtotime($this->fecha_quincena) < strtotime(self::FECHA_INICIO_DEUDAS)) {
            $this->addError('fecha_quincena', 'No se pueden crear/modificar aportes con fecha anterior al 15 de enero de 2026.');
            return false;
        }
        
        // Si es pago y no tiene fecha de pago, usar fecha actual
        if ($this->estado == self::ESTADO_PAGADO && empty($this->fecha_pago)) {
            $this->fecha_pago = date('Y-m-d');
        }
        
        // Si no tiene tasa, obtener la tasa del día de la quincena
        if (empty($this->tasa_dolar_quincena) && !empty($this->fecha_quincena)) {
            $this->tasa_dolar_quincena = self::obtenerTasaDolar($this->fecha_quincena);
        }
        
        // Calcular monto en bolívares si no está establecido
        if (empty($this->monto_bs_original) && $this->monto && $this->tasa_dolar_quincena) {
            $this->monto_bs_original = self::convertirDolaresABs($this->monto, $this->tasa_dolar_quincena);
        }
        
        return true;
    }

    /**
     * After save event
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        // Si el estado cambió a PAGADO, podemos hacer seguimiento
        if (isset($changedAttributes['estado']) && $this->estado == self::ESTADO_PAGADO) {
            Yii::info("Aporte ID {$this->id} marcado como pagado. Atleta: {$this->atleta_id}");
        }
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
    // MÉTODOS DE CÁLCULO DE FECHAS QUINCENALES (MODIFICADOS SOLO DESDE 15/01/2026)
    // =========================================================================

    /**
     * Calcula las fechas quincenales exactas (15 y último día de cada mes)
     * SOLO DESDE 15/01/2026
     */
    public static function calcularFechasQuincenalesPeriodo($fechaInicio, $fechaFin = null)
    {
        if (!$fechaFin) {
            $fechaFin = new \DateTime();
        } elseif (is_string($fechaFin)) {
            $fechaFin = new \DateTime($fechaFin);
        }
        
        if (is_string($fechaInicio)) {
            $fechaInicio = new \DateTime($fechaInicio);
        }
        
        // Si la fecha de inicio es anterior al 15/01/2026, ajustar al 15/01/2026
        $fechaInicioSistema = new \DateTime(self::FECHA_INICIO_DEUDAS);
        if ($fechaInicio < $fechaInicioSistema) {
            $fechaInicio = clone $fechaInicioSistema;
        }
        
        $fechasQuincenales = [];
        $fechaActual = clone $fechaInicio;
        
        // Ajustar al primer día del mes para iterar correctamente
        $fechaActual->modify('first day of this month');
        
        // Si el primer día del mes es anterior al 15/01/2026, comenzar desde enero 2026
        if ($fechaActual < $fechaInicioSistema) {
            $fechaActual = new \DateTime('2026-01-01');
        }
        
        while ($fechaActual <= $fechaFin) {
            $mes = (int)$fechaActual->format('m');
            $ano = (int)$fechaActual->format('Y');
            
            // Primera quincena: día 15 (solo si el mes/año es >= enero 2026)
            if ($ano > 2025 || ($ano == 2025 && $mes >= 12)) {
                $primerQuincena = new \DateTime("$ano-$mes-15");
                if ($primerQuincena >= $fechaInicio && $primerQuincena <= $fechaFin) {
                    $fechasQuincenales[] = $primerQuincena->format('Y-m-d');
                }
                
                // Segunda quincena: último día del mes
                $ultimoDia = $fechaActual->format('t');
                $segundaQuincena = new \DateTime("$ano-$mes-$ultimoDia");
                if ($segundaQuincena >= $fechaInicio && $segundaQuincena <= $fechaFin) {
                    $fechasQuincenales[] = $segundaQuincena->format('Y-m-d');
                }
            }
            
            // Avanzar al siguiente mes
            $fechaActual->modify('first day of next month');
        }
        
        // Ordenar fechas
        sort($fechasQuincenales);
        
        return $fechasQuincenales;
    }

    /**
     * Calcula el número de quincena exacta desde la fecha de inicio (15 de enero de 2026)
     * Retorna 0 para fechas anteriores al 15/01/2026
     */
    public static function calcularNumeroQuincenaExacta($fecha)
    {
        $fechaInicio = new \DateTime(self::FECHA_INICIO_DEUDAS);
        $fechaActual = new \DateTime($fecha);
        
        if ($fechaActual < $fechaInicio) {
            return 0; // Fecha anterior al inicio de deudas
        }
        
        $mesInicio = (int)$fechaInicio->format('m');
        $anoInicio = (int)$fechaInicio->format('Y');
        $mesActual = (int)$fechaActual->format('m');
        $anoActual = (int)$fechaActual->format('Y');
        
        // Calcular meses de diferencia
        $mesesDiferencia = ($anoActual - $anoInicio) * 12 + ($mesActual - $mesInicio);
        
        // Cada mes tiene 2 quincenas
        $quincenasBase = $mesesDiferencia * 2;
        
        // Ajustar según día del mes
        $dia = (int)$fechaActual->format('d');
        if ($dia < 15) {
            // Antes del 15, aún no ha llegado la primera quincena de este mes
            $quincenasBase -= 1;
        }
        
        // Sumar 1 porque la primera quincena es la número 1
        return $quincenasBase + 1;
    }

    /**
     * Calcula si debe pagar fracción proporcional (solo primera quincena después de inscripción)
     * SOLO DESDE 15/01/2026
     */
    public static function debePagarFraccion($fechaInscripcion, $fechaQuincena)
    {
        // Si la fecha de quincena es anterior al 15/01/2026, no aplica fracción
        if (strtotime($fechaQuincena) < strtotime(self::FECHA_INICIO_DEUDAS)) {
            return false;
        }
        
        $inscripcion = new \DateTime($fechaInscripcion);
        $quincena = new \DateTime($fechaQuincena);
        
        // Si la inscripción es anterior al 15/01/2026, ajustar al 15/01/2026
        $fechaInicioSistema = new \DateTime(self::FECHA_INICIO_DEUDAS);
        if ($inscripcion < $fechaInicioSistema) {
            $inscripcion = clone $fechaInicioSistema;
        }
        
        // Verificar que sea la primera quincena después de la inscripción
        $inscripcionAnterior = clone $inscripcion;
        $inscripcionAnterior->modify('-15 days');
        
        // Si la inscripción está entre la quincena anterior y esta quincena
        if ($inscripcion > $inscripcionAnterior && $inscripcion <= $quincena) {
            $diferencia = $inscripcion->diff($quincena);
            $diasDiferencia = $diferencia->days;
            
            // Si se inscribe con menos de 8 días antes de la quincena
            return $diasDiferencia < self::DIAS_FRACCION && $diasDiferencia > 0;
        }
        
        return false;
    }

    /**
     * Calcula monto proporcional basado en días (solo para primera quincena si aplica)
     * SOLO DESDE 15/01/2026
     */
    public static function calcularMontoProporcional($fechaInscripcion, $fechaQuincena, $tasaDolar = null)
    {
        // Si la fecha de quincena es anterior al 15/01/2026, retornar monto completo
        if (strtotime($fechaQuincena) < strtotime(self::FECHA_INICIO_DEUDAS)) {
            return [
                'monto_usd' => self::MONTO_QUINCENAL_USD,
                'monto_bs' => $tasaDolar ? self::convertirDolaresABs(self::MONTO_QUINCENAL_USD, $tasaDolar) : 0,
                'es_fraccion' => false
            ];
        }
        
        if (!self::debePagarFraccion($fechaInscripcion, $fechaQuincena)) {
            return [
                'monto_usd' => self::MONTO_QUINCENAL_USD,
                'monto_bs' => $tasaDolar ? self::convertirDolaresABs(self::MONTO_QUINCENAL_USD, $tasaDolar) : 0,
                'es_fraccion' => false
            ];
        }
        
        $inscripcion = new \DateTime($fechaInscripcion);
        $quincena = new \DateTime($fechaQuincena);
        
        // Si la inscripción es anterior al 15/01/2026, ajustar al 15/01/2026
        $fechaInicioSistema = new \DateTime(self::FECHA_INICIO_DEUDAS);
        if ($inscripcion < $fechaInicioSistema) {
            $inscripcion = clone $fechaInicioSistema;
        }
        
        $diferencia = $inscripcion->diff($quincena);
        $diasDiferencia = $diferencia->days;
        
        // Calcular proporción (ej: si faltan 5 días, paga 5/15 del monto)
        $proporcion = $diasDiferencia / 15;
        $montoUsd = round(self::MONTO_QUINCENAL_USD * $proporcion, 2);
        
        $montoBs = $tasaDolar ? self::convertirDolaresABs($montoUsd, $tasaDolar) : 0;
        
        return [
            'monto_usd' => $montoUsd,
            'monto_bs' => $montoBs,
            'es_fraccion' => true,
            'dias_diferencia' => $diasDiferencia
        ];
    }

    // =========================================================================
    // MÉTODOS DE MANEJO DE MONEDA
    // =========================================================================

    /**
     * Obtiene la tasa del dólar para una fecha específica
     */
    public static function obtenerTasaDolar($fecha = null)
    {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }
        
        $tasa = TasaDolar::find()
            ->where(['fecha_tasa' => $fecha])
            ->andWhere(['eliminado' => false])
            ->orderBy(['d_creacion' => SORT_DESC])
            ->one();
        
        if ($tasa) {
            return (float)$tasa->tasa_dia;
        }
        
        // Si no hay tasa para esa fecha, buscar la más reciente anterior
        $tasaAnterior = TasaDolar::find()
            ->where(['<=', 'fecha_tasa', $fecha])
            ->andWhere(['eliminado' => false])
            ->orderBy(['fecha_tasa' => SORT_DESC])
            ->one();
        
        return $tasaAnterior ? (float)$tasaAnterior->tasa_dia : 1.0;
    }

    /**
     * Convierte bolívares a dólares usando tasa específica
     */
    public static function convertirBsADolares($monto_bs, $tasa_dolar)
    {
        if ($tasa_dolar <= 0) {
            throw new \InvalidArgumentException('La tasa de dólar debe ser mayor a 0.');
        }
        return round($monto_bs / $tasa_dolar, 2); // 2 decimales para dólares
    }

    /**
     * Convierte dólares a bolívares usando tasa específica
     */
    public static function convertirDolaresABs($monto_usd, $tasa_dolar)
    {
        return round($monto_usd * $tasa_dolar, 1); // 1 decimal para bolívares
    }

    /**
     * Obtiene el monto en bolívares usando la tasa guardada
     */
    public function getMontoBs()
    {
        if ($this->tasa_dolar_quincena && $this->monto) {
            return self::convertirDolaresABs($this->monto, $this->tasa_dolar_quincena);
        }
        return 0;
    }

    /**
     * Obtiene el monto formateado en ambas monedas
     */
    public function getMontoDual()
    {
        $montoBs = $this->getMontoBs();
        return [
            'usd' => Yii::$app->formatter->asCurrency($this->monto, 'USD'),
            'bs' => Yii::$app->formatter->asCurrency($montoBs, 'VES'),
            'monto_usd' => $this->monto,
            'monto_bs' => $montoBs,
            'tasa' => $this->tasa_dolar_quincena
        ];
    }

    // =========================================================================
    // MÉTODOS DE CÁLCULO DE DEUDAS (MODIFICADOS SOLO DESDE 15/01/2026)
    // =========================================================================

    /**
     * Calcula la deuda en quincenas de un atleta (SOLO DESDE 15/01/2026)
     */
    public static function calcularDeudaAtleta($atleta_id)
    {
        return self::find()
            ->where([
                'atleta_id' => $atleta_id,
                'estado' => self::ESTADO_PENDIENTE
            ])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->count();
    }

    /**
     * Calcula el monto total de deuda de un atleta en ambas monedas (SOLO DESDE 15/01/2026)
     */
    public static function calcularMontoDeudaConMoneda($atleta_id)
    {
        $deudas = self::find()
            ->where([
                'atleta_id' => $atleta_id,
                'estado' => self::ESTADO_PENDIENTE
            ])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->all();
        
        $totalUsd = 0;
        $totalBs = 0;
        $tasaActual = self::obtenerTasaDolar();
        
        foreach ($deudas as $deuda) {
            $totalUsd += $deuda->monto;
            
            // Usar tasa original de cada quincena para calcular bolívares
            if ($deuda->tasa_dolar_quincena) {
                $totalBs += self::convertirDolaresABs($deuda->monto, $deuda->tasa_dolar_quincena);
            } else {
                // Si no tiene tasa, usar tasa actual
                $totalBs += self::convertirDolaresABs($deuda->monto, $tasaActual);
            }
        }
        
        return [
            'total_usd' => round($totalUsd, 2),
            'total_bs' => round($totalBs, 1),
            'tasa_actual' => $tasaActual,
            'quincenas' => count($deudas)
        ];
    }

    /**
     * Calcula el monto total de deuda (método compatible) (SOLO DESDE 15/01/2026)
     */
    public static function calcularMontoDeuda($atleta_id)
    {
        $deuda = self::find()
            ->where([
                'atleta_id' => $atleta_id,
                'estado' => self::ESTADO_PENDIENTE
            ])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->sum('monto');
        
        return $deuda ? floatval($deuda) : 0;
    }

    /**
     * Obtiene el historial completo de deudas/pagos de un atleta (SOLO DESDE 15/01/2026)
     */
    public static function obtenerHistorialDeudas($atleta_id)
    {
        return self::find()
            ->where(['atleta_id' => $atleta_id])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->orderBy(['fecha_quincena' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * Obtiene las deudas pendientes de un atleta ordenadas por antigüedad (SOLO DESDE 15/01/2026)
     */
    public static function obtenerDeudasPendientes($atleta_id)
    {
        return self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => 'pendiente'])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->orderBy(['fecha_quincena' => SORT_ASC])
            ->all();
    }

    // =========================================================================
    // MÉTODOS DE GESTIÓN DE APORTES (MODIFICADOS SOLO DESDE 15/01/2026)
    // =========================================================================

    /**
     * Genera quincenas automáticamente para un atleta desde 15/01/2026
     * con manejo de moneda dual y fracción proporcional
     */
    public static function generarQuincenasParaAtleta($atleta_id)
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return 0;
        }

        // Fecha de inicio: 15 de enero de 2026
        $fechaInicio = new \DateTime(self::FECHA_INICIO_DEUDAS);
        
        // Usar fecha de creación del atleta si es posterior al 15/01/2026
        $fechaCreacionAtleta = $atleta->fecha_creacion ?? $atleta->d_creacion ?? null;
        if ($fechaCreacionAtleta) {
            $fechaCreacion = new \DateTime($fechaCreacionAtleta);
            // Solo usar la fecha de creación si es posterior al 15/01/2026
            if ($fechaCreacion > $fechaInicio) {
                $fechaInicio = $fechaCreacion;
            }
        }

        $hoy = new \DateTime();
        $quincenasGeneradas = 0;
        
        // Obtener todas las fechas quincenales entre inicio y hoy
        $fechasQuincenales = self::calcularFechasQuincenalesPeriodo($fechaInicio, $hoy);
        
        foreach ($fechasQuincenales as $fechaQuincena) {
            // Verificar si ya existe un aporte para esta fecha
            $existeAporte = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_quincena' => $fechaQuincena
                ])
                ->exists();

            if (!$existeAporte) {
                // Obtener tasa del dólar para la fecha de la quincena
                $tasaDolar = self::obtenerTasaDolar($fechaQuincena);
                
                // Calcular monto (completo o fracción)
                $calculoMonto = self::calcularMontoProporcional(
                    $fechaCreacionAtleta ?? $fechaInicio->format('Y-m-d'),
                    $fechaQuincena,
                    $tasaDolar
                );
                
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->numero_quincena = self::calcularNumeroQuincenaExacta($fechaQuincena);
                $aporte->monto = $calculoMonto['monto_usd'];
                $aporte->tasa_dolar_quincena = $tasaDolar;
                $aporte->monto_bs_original = $calculoMonto['monto_bs'];
                $aporte->estado = self::ESTADO_PENDIENTE;
                $aporte->pago_parcial = $calculoMonto['es_fraccion'];
                
                if ($calculoMonto['es_fraccion']) {
                    $aporte->comentarios = "Pago proporcional por inscripción tardía ({$calculoMonto['dias_diferencia']} días)";
                }

                if ($aporte->save()) {
                    $quincenasGeneradas++;
                }
            }
        }

        return $quincenasGeneradas;
    }

    /**
     * Calcula quincenas equivalentes basado en monto aportado (SOLO DESDE 15/01/2026)
     */
    public static function calcularQuincenasEquivalentes($montoAportado)
    {
        return $montoAportado / self::MONTO_QUINCENAL_USD;
    }

    /**
     * Calcula el número de quincena (método compatible) (SOLO DESDE 15/01/2026)
     */
    public static function calcularNumeroQuincena($fecha)
    {
        if (empty($fecha)) {
            return self::calcularNumeroQuincenaExacta(date('Y-m-d'));
        }
        
        try {
            return self::calcularNumeroQuincenaExacta($fecha);
        } catch (\Exception $e) {
            return self::calcularNumeroQuincenaExacta(date('Y-m-d'));
        }
    }

    /**
     * Calcula la próxima quincena desde una fecha dada (asegurando >= 15/01/2026)
     */
    private static function calcularProximaQuincena($fecha)
    {
        $fecha = clone $fecha;
        
        // Si la fecha es anterior al 15/01/2026, usar 15/01/2026
        $fechaInicio = new \DateTime(self::FECHA_INICIO_DEUDAS);
        if ($fecha < $fechaInicio) {
            return $fechaInicio->format('Y-m-d');
        }
        
        // Si estamos después del día 15, ir al último día del mes
        // Si estamos antes del día 15, ir al día 15
        $dia = (int)$fecha->format('d');
        
        if ($dia < 15) {
            return $fecha->modify('first day of this month')->modify('+14 days')->format('Y-m-d');
        } else {
            return $fecha->modify('last day of this month')->format('Y-m-d');
        }
    }

    // =========================================================================
    // MÉTODOS DE PAGOS (MODIFICADOS SOLO DESDE 15/01/2026)
    // =========================================================================

    /**
     * Procesa aportes flexibles con cualquier monto (SOLO DESDE 15/01/2026)
     */
    public static function procesarAporteFlexible($atleta_id, $montoTotal, $fechaPago = null, 
                                                $metodoPago = 'efectivo', $comentarios = 'Aporte flexible')
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            throw new \Exception('Atleta no encontrado');
        }

        $montoQuincenal = self::MONTO_QUINCENAL_USD;
        $quincenasCompletas = floor($montoTotal / $montoQuincenal);
        $montoRestante = $montoTotal - ($quincenasCompletas * $montoQuincenal);
        
        $quincenasProcesadas = 0;
        $fechaActual = new \DateTime();
        
        // Si la fecha actual es anterior al 15/01/2026, empezar desde el 15/01/2026
        $fechaInicio = new \DateTime(self::FECHA_INICIO_DEUDAS);
        if ($fechaActual < $fechaInicio) {
            $fechaActual = clone $fechaInicio;
        }
        
        // Procesar quincenas completas
        for ($i = 0; $i < $quincenasCompletas; $i++) {
            $fechaQuincena = self::calcularProximaQuincena($fechaActual);
            
            // Verificar que la fecha sea >= 15/01/2026
            if (strtotime($fechaQuincena) < strtotime(self::FECHA_INICIO_DEUDAS)) {
                $fechaQuincena = self::FECHA_INICIO_DEUDAS;
            }
            
            // Verificar si ya existe un aporte para esta fecha
            $existeAporte = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_quincena' => $fechaQuincena
                ])
                ->exists();

            if (!$existeAporte) {
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->numero_quincena = self::calcularNumeroQuincena($fechaQuincena);
                $aporte->monto = $montoQuincenal;
                $aporte->estado = self::ESTADO_PAGADO;
                $aporte->fecha_pago = $fechaPago ?: date('Y-m-d');
                $aporte->metodo_pago = $metodoPago;
                $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena}";
                $aporte->pago_parcial = false;
                
                if ($aporte->save()) {
                    $quincenasProcesadas++;
                }
            }
            
            $fechaActual = new \DateTime($fechaQuincena);
            $fechaActual->modify('+15 days');
        }
        
        // Procesar monto restante como aporte parcial (SOLO SI ES >= 15/01/2026)
        if ($montoRestante > 0) {
            $fechaQuincena = self::calcularProximaQuincena($fechaActual);
            
            // Verificar que la fecha sea >= 15/01/2026
            if (strtotime($fechaQuincena) >= strtotime(self::FECHA_INICIO_DEUDAS)) {
                // Verificar si ya existe un aporte parcial para esta fecha
                $existeAporteParcial = self::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_quincena' => $fechaQuincena,
                        'pago_parcial' => true
                    ])
                    ->exists();

                if (!$existeAporteParcial) {
                    $aporteParcial = new self();
                    $aporteParcial->atleta_id = $atleta_id;
                    $aporteParcial->escuela_id = $atleta->id_escuela;
                    $aporteParcial->fecha_quincena = $fechaQuincena;
                    $aporteParcial->numero_quincena = self::calcularNumeroQuincena($fechaQuincena);
                    $aporteParcial->monto = $montoRestante;
                    $aporteParcial->estado = self::ESTADO_PAGADO;
                    $aporteParcial->fecha_pago = $fechaPago ?: date('Y-m-d');
                    $aporteParcial->metodo_pago = $metodoPago;
                    $aporteParcial->comentarios = $comentarios . " - Aporte parcial";
                    $aporteParcial->pago_parcial = true;
                    
                    if ($aporteParcial->save()) {
                        $quincenasProcesadas++;
                    }
                }
            }
        }
        
        return $quincenasProcesadas;
    }

    /**
     * Procesa pago múltiple de quincenas pendientes (SOLO DESDE 15/01/2026)
     */
    public static function procesarPagoMultiple($atleta_id, $quincenasSeleccionadas, $fechaPago, 
                                               $metodoPago = 'efectivo', $comentarios = 'Pago múltiple')
    {
        $quincenasPagadas = 0;

        foreach ($quincenasSeleccionadas as $fechaQuincena) {
            // Verificar que la fecha sea >= 15/01/2026
            if (strtotime($fechaQuincena) < strtotime(self::FECHA_INICIO_DEUDAS)) {
                continue; // Saltar quincenas anteriores
            }
            
            $aporte = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_quincena' => $fechaQuincena
                ])
                ->one();

            if ($aporte) {
                $aporte->estado = self::ESTADO_PAGADO;
                $aporte->fecha_pago = $fechaPago;
                $aporte->metodo_pago = $metodoPago;
                $aporte->comentarios = $comentarios;

                if ($aporte->save()) {
                    $quincenasPagadas++;
                }
            }
        }

        return $quincenasPagadas;
    }

    /**
     * Procesa pago adelantado de quincenas futuras (SOLO DESDE 15/01/2026)
     */
    public static function procesarPagoAdelantado($atleta_id, $quincenasAdelanto, $fechaPago,
                                                 $metodoPago = 'efectivo', $comentarios = 'Pago adelantado')
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return 0;
        }

        $fechaActual = new \DateTime();
        $fechaActual = new \DateTime(self::calcularProximaQuincena($fechaActual));
        
        // Si la fecha calculada es anterior al 15/01/2026, usar 15/01/2026
        if ($fechaActual < new \DateTime(self::FECHA_INICIO_DEUDAS)) {
            $fechaActual = new \DateTime(self::FECHA_INICIO_DEUDAS);
        }

        $quincenasPagadas = 0;

        for ($i = 0; $i < $quincenasAdelanto; $i++) {
            $fechaQuincena = $fechaActual->format('Y-m-d');

            $existeAporte = self::find()
                ->where([
                    'atleta_id' => $atleta_id,
                    'fecha_quincena' => $fechaQuincena
                ])
                ->exists();

            if (!$existeAporte) {
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->numero_quincena = self::calcularNumeroQuincena($fechaQuincena);
                $aporte->monto = self::MONTO_QUINCENAL_USD;
                $aporte->estado = self::ESTADO_PAGADO;
                $aporte->fecha_pago = $fechaPago;
                $aporte->metodo_pago = $metodoPago;
                $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena}";
                $aporte->pago_parcial = false;

                if ($aporte->save()) {
                    $quincenasPagadas++;
                }
            }

            $fechaActual->modify('+15 days');
        }

        return $quincenasPagadas;
    }

    /**
     * Liquidar deudas pendientes para un atleta (SOLO DESDE 15/01/2026)
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
    // MÉTODOS DE ESTADÍSTICAS Y REPORTES (MODIFICADOS SOLO DESDE 15/01/2026)
    // =========================================================================

    /**
     * Obtiene los top atletas por aportes (SOLO DESDE 15/01/2026)
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
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->groupBy(['atleta_id'])
            ->orderBy(['total_monto' => SORT_DESC])
            ->limit($limit);

        if ($escuela_id) {
            $query->andWhere(['escuela_id' => $escuela_id]);
        }

        return $query->asArray()->all();
    }

    /**
     * Obtiene estadísticas completas de un atleta (SOLO DESDE 15/01/2026)
     */
    public static function getEstadisticasAtleta($atleta_id)
    {
        $totalAportes = self::find()
            ->where(['atleta_id' => $atleta_id])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->count();
            
        $aportesPagados = self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PAGADO])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->count();
            
        $aportesPendientes = self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PENDIENTE])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->count();
        
        $montoTotalPagado = self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PAGADO])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->sum('monto') ?? 0;
        
        $montoTotalPendiente = self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PENDIENTE])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS]) // FILTRO CRÍTICO
            ->sum('monto') ?? 0;

        return [
            'total_aportes' => $totalAportes,
            'aportes_pagados' => $aportesPagados,
            'aportes_pendientes' => $aportesPendientes,
            'monto_total_pagado' => floatval($montoTotalPagado),
            'monto_total_pendiente' => floatval($montoTotalPendiente),
            'quincenas_equivalentes' => self::calcularQuincenasEquivalentes($montoTotalPagado),
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
     * Verifica si el aporte está pagado
     */
    public function isPagado()
    {
        return $this->estado === self::ESTADO_PAGADO;
    }

    /**
     * Verifica si el aporte está pendiente
     */
    public function isPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verifica si el aporte está cancelado
     */
    public function isCancelado()
    {
        return $this->estado === self::ESTADO_CANCELADO;
    }

    /**
     * Obtiene la fecha formateada de quincena
     */
    public function getFechaQuincenaFormateada()
    {
        return Yii::$app->formatter->asDate($this->fecha_quincena, 'php:d/m/Y');
    }

    /**
     * Obtiene la fecha formateada de pago
     */
    public function getFechaPagoFormateada()
    {
        return $this->fecha_pago ? Yii::$app->formatter->asDate($this->fecha_pago, 'php:d/m/Y') : '';
    }
}