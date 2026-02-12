<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * Modelo para la tabla 'contabilidad.aportes_semanales'.
 * 
 * SOPORTA DOS MODOS DE OPERACIÓN BAJO EL MISMO ESQUEMA QUINCENAL:
 * 
 * 1. MODO ATLETA (ORIGINAL):
 *    - Aportes quincenales por atleta, monto fijo $5.00 USD (con proporcionalidad).
 *    - Manejo dual de moneda (USD/BS) con tasa de cambio.
 *    - Pagos flexibles, adelantados, múltiples, liquidaciones.
 * 
 * 2. MODO FAMILIA (NUEVO CON BECAS):
 *    - Aportes quincenales por familia.
 *    - Monto dinámico = aporte_base * (1 - descuento_multiples_atletas) * (1 - descuento_becas).
 *    - Descuento múltiples atletas: 25% por cada atleta adicional al primero.
 *    - Descuento becas: mayor porcentaje de beca activa entre los atletas de la familia.
 *    - Se registran los componentes del cálculo en campos dedicados.
 * 
 * @property int $id_aporte
 * 
 * // Campos comunes
 * @property string $fecha_quincena
 * @property int $numero_quincena
 * @property float $monto
 * @property string $estado
 * @property string|null $fecha_pago
 * @property string|null $metodo_pago
 * @property string|null $comentarios
 * @property bool|null $pago_parcial
 * @property float|null $tasa_dolar_quincena
 * @property float|null $monto_bs_original
 * @property float|null $tipo_cambio
 * 
 * // Modo Atleta
 * @property int|null $atleta_id
 * @property int|null $escuela_id
 * 
 * // Modo Familia
 * @property int|null $id_familia
 * @property float|null $aporte_base_usado
 * @property float|null $descuento_multiples_atletas
 * @property float|null $descuento_becas
 * 
 * // Auditoría
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 * 
 * // Relaciones
 * @property AtletasRegistro $atleta
 * @property Escuela $escuela
 * @property Familia $familia
 */
class AportesSemanales extends ActiveRecord
{
    // =========================================================================
    // CONSTANTES COMPARTIDAS
    // =========================================================================
    const MONTO_QUINCENAL_USD = 5.00;
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADO = 'pagado';
    const ESTADO_CANCELADO = 'cancelado';
    const FECHA_INICIO_DEUDAS = '2026-01-15';
    const DIAS_FRACCION = 8;
    const TASA_CAMBIO_FIJA = 36.5;

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
                'updatedAtAttribute' => 'updated_at',
                'value' => date('Y-m-d H:i:s'),
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // -----------------------------------------------------------------
            // Reglas comunes
            // -----------------------------------------------------------------
            [['fecha_quincena', 'numero_quincena', 'monto', 'estado'], 'required'],
            [['fecha_quincena', 'fecha_pago', 'created_at', 'updated_at'], 'safe'],
            [['numero_quincena', 'created_by', 'updated_by'], 'integer'],
            [['monto', 'tasa_dolar_quincena', 'monto_bs_original', 'tipo_cambio',
              'aporte_base_usado', 'descuento_multiples_atletas', 'descuento_becas'], 'number'],
            [['comentarios'], 'string'],
            [['pago_parcial'], 'boolean'],
            [['estado'], 'string', 'max' => 255],
            [['estado'], 'default', 'value' => self::ESTADO_PENDIENTE],
            [['metodo_pago'], 'string', 'max' => 255],

            // -----------------------------------------------------------------
            // Reglas para MODO ATLETA
            // -----------------------------------------------------------------
            [['atleta_id', 'escuela_id'], 'required', 'on' => 'atleta'],
            [['atleta_id', 'escuela_id'], 'integer'],
            [['atleta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_id' => 'id']],
            [['escuela_id'], 'exist', 'skipOnError' => true, 'targetClass' => Escuela::class, 'targetAttribute' => ['escuela_id' => 'id']],
            // Validación de fecha quincena para modo atleta (>= 15/01/2026)
            [['fecha_quincena'], 'validateFechaQuincena', 'on' => 'atleta'],

            // -----------------------------------------------------------------
            // Reglas para MODO FAMILIA
            // -----------------------------------------------------------------
            [['id_familia'], 'required', 'on' => 'familia'],
            [['id_familia'], 'integer'],
            [['id_familia'], 'exist', 'skipOnError' => true, 'targetClass' => Familia::class, 'targetAttribute' => ['id_familia' => 'id_familia']],
            // Validación de fecha quincena para modo familia (>= 15/01/2026)
            [['fecha_quincena'], 'validateFechaQuincena', 'on' => 'familia'],
            // No duplicar aporte para misma familia y fecha quincena
            [['id_familia', 'fecha_quincena'], 'unique', 'targetAttribute' => ['id_familia', 'fecha_quincena'], 'on' => 'familia'],

            // -----------------------------------------------------------------
            // Reglas condicionales según modo (se asignan en beforeValidate)
            // -----------------------------------------------------------------
            [['atleta_id', 'escuela_id'], 'default', 'value' => null, 'on' => 'familia'],
            [['id_familia', 'aporte_base_usado', 'descuento_multiples_atletas', 'descuento_becas'], 'default', 'value' => null, 'on' => 'atleta'],
        ];
    }

    /**
     * Validación personalizada para fecha de quincena (ambos modos).
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
            'id_aporte' => 'ID Aporte',
            'fecha_quincena' => 'Fecha Quincena',
            'numero_quincena' => 'Número Quincena',
            'monto' => 'Monto (USD)',
            'estado' => 'Estado',
            'fecha_pago' => 'Fecha Pago',
            'metodo_pago' => 'Método Pago',
            'comentarios' => 'Comentarios',
            'pago_parcial' => 'Pago Parcial',
            'tasa_dolar_quincena' => 'Tasa Dólar Quincena',
            'monto_bs_original' => 'Monto Original (Bs)',
            'tipo_cambio' => 'Tipo Cambio',
            'atleta_id' => 'Atleta',
            'escuela_id' => 'Escuela',
            'id_familia' => 'Familia',
            'aporte_base_usado' => 'Aporte Base ($)',
            'descuento_multiples_atletas' => 'Desc. Múltiples Atletas',
            'descuento_becas' => 'Desc. Becas',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
            'created_by' => 'Creado por',
            'updated_by' => 'Actualizado por',
        ];
    }

    // =========================================================================
    // DETECCIÓN AUTOMÁTICA DE MODO Y SCENARIOS
    // =========================================================================

    /**
     * {@inheritdoc}
     * Asigna el escenario automáticamente según los campos presentes.
     */
    public function beforeValidate()
    {
        if ($this->id_familia !== null) {
            $this->scenario = 'familia';
        } elseif ($this->atleta_id !== null) {
            $this->scenario = 'atleta';
        }

        // Si no se pudo determinar, error
        if (empty($this->scenario)) {
            $this->addError('id_familia', 'Debe especificar un atleta o una familia.');
            return false;
        }

        // Cálculo automático del número de quincena si está vacío
        if (empty($this->numero_quincena) && !empty($this->fecha_quincena)) {
            $this->numero_quincena = self::calcularNumeroQuincenaExacta($this->fecha_quincena);
        }

        // Lógica específica de modo atleta (conversión Bs/USD si aplica)
        if ($this->scenario === 'atleta') {
            if (!empty($this->monto_bs) && empty($this->monto)) {
                if (empty($this->tasa_dia)) {
                    $this->tasa_dia = self::obtenerTasaDolar($this->fecha_quincena);
                }
                $this->monto = self::convertirBsADolares($this->monto_bs, $this->tasa_dia);
                $this->monto_bs_original = $this->monto_bs;
                $this->tasa_dolar_quincena = $this->tasa_dia;
            }
        }

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Validar fecha quincena (>= 15/01/2026) para ambos modos
        if ($this->fecha_quincena && strtotime($this->fecha_quincena) < strtotime(self::FECHA_INICIO_DEUDAS)) {
            $this->addError('fecha_quincena', 'No se pueden crear/modificar aportes con fecha anterior al 15 de enero de 2026.');
            return false;
        }

        // Ejecutar lógica según el modo
        if ($this->scenario === 'familia') {
            return $this->beforeSaveFamilia($insert);
        } else {
            return $this->beforeSaveAtleta($insert);
        }
    }

    /**
     * Lógica beforeSave para MODO FAMILIA.
     */
    private function beforeSaveFamilia($insert)
    {
        // Calcular el monto con descuentos si es nuevo o cambió la familia/fecha
        if ($insert || $this->isAttributeChanged('id_familia') || $this->isAttributeChanged('fecha_quincena')) {
            if (!$this->calcularAportesFamiliares()) {
                return false;
            }
        }

        // Si es pago y no tiene fecha de pago, usar fecha actual
        if ($this->estado == self::ESTADO_PAGADO && empty($this->fecha_pago)) {
            $this->fecha_pago = date('Y-m-d');
        }

        return true;
    }

    /**
     * Lógica beforeSave para MODO ATLETA (original).
     */
    private function beforeSaveAtleta($insert)
    {
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

        // Establecer tipo_cambio si está vacío (para compatibilidad)
        if (empty($this->tipo_cambio)) {
            $this->tipo_cambio = self::TASA_CAMBIO_FIJA;
        }

        return true;
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function getAtleta()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'atleta_id']);
    }

    public function getEscuela()
    {
        return $this->hasOne(Escuela::class, ['id' => 'escuela_id']);
    }

    public function getFamilia()
    {
        return $this->hasOne(Familia::class, ['id_familia' => 'id_familia']);
    }

    public function getCreador()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getActualizador()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    // =========================================================================
    // NUEVA LÓGICA: CÁLCULO DE APORTES FAMILIARES CON DESCUENTOS (QUINCENAL)
    // =========================================================================

    /**
     * Calcula los valores del aporte familiar basándose en la familia vinculada.
     * Asigna: aporte_base_usado, descuento_multiples_atletas, descuento_becas, monto.
     *
     * @return bool True si la familia existe y se pudo calcular.
     */
    public function calcularAportesFamiliares()
    {
        $familia = $this->familia;
        if (!$familia) {
            return false;
        }

        // 1. Aporte base
        $this->aporte_base_usado = $familia->getAporteBase();

        // 2. Descuento por múltiples atletas
        $this->descuento_multiples_atletas = $familia->getDescuentoMultipleAtletas();

        // 3. Descuento por becas (máximo porcentaje entre atletas activos)
        $maxDescuentoBeca = 0.0;
        foreach ($familia->atletas as $atleta) {
            $becaActiva = $atleta->getBecaActiva();
            if ($becaActiva) {
                $porcentaje = $becaActiva->porcentaje / 100;
                if ($porcentaje > $maxDescuentoBeca) {
                    $maxDescuentoBeca = $porcentaje;
                }
            }
        }
        $this->descuento_becas = $maxDescuentoBeca;

        // 4. Monto final (asignado al campo común 'monto')
        $monto = $this->aporte_base_usado;
        $monto *= (1 - $this->descuento_multiples_atletas);
        $monto *= (1 - $this->descuento_becas);
        $this->monto = round($monto, 2);

        return true;
    }

    /**
     * Marca el aporte (cualquier modo) como pagado.
     *
     * @param string|null $fechaPago
     * @param string|null $metodoPago
     * @return bool
     */
    public function marcarPagado($fechaPago = null, $metodoPago = null)
    {
        $this->estado = self::ESTADO_PAGADO;
        $this->fecha_pago = $fechaPago ?: date('Y-m-d');
        if ($metodoPago) {
            $this->metodo_pago = $metodoPago;
        }
        return $this->save(false, ['estado', 'fecha_pago', 'metodo_pago', 'updated_at', 'updated_by']);
    }

    /**
     * Marca el aporte como pendiente.
     *
     * @return bool
     */
    public function marcarPendiente()
    {
        $this->estado = self::ESTADO_PENDIENTE;
        $this->fecha_pago = null;
        return $this->save(false, ['estado', 'fecha_pago', 'updated_at', 'updated_by']);
    }

    /**
     * Marca el aporte como cancelado.
     *
     * @return bool
     */
    public function marcarCancelado()
    {
        $this->estado = self::ESTADO_CANCELADO;
        return $this->save(false, ['estado', 'updated_at', 'updated_by']);
    }

    /**
     * Verifica si el aporte está pagado.
     *
     * @return bool
     */
    public function isPagado()
    {
        return $this->estado === self::ESTADO_PAGADO;
    }

    /**
     * Verifica si el aporte está pendiente.
     */
    public function isPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verifica si el aporte está cancelado.
     */
    public function isCancelado()
    {
        return $this->estado === self::ESTADO_CANCELADO;
    }

    /**
     * Genera los aportes quincenales para una familia desde 15/01/2026.
     * Respeta el calendario quincenal (15 y último día de cada mes).
     * No duplica registros existentes.
     *
     * @param int $id_familia
     * @param string|null $fechaInicio (opcional, por defecto 15/01/2026 o fecha de creación de la familia)
     * @return int Cantidad de aportes generados.
     */
    public static function generarQuincenasParaFamilia($id_familia, $fechaInicio = null)
    {
        $familia = Familia::findOne($id_familia);
        if (!$familia) {
            return 0;
        }

        // Fecha de inicio: 15/01/2026 o fecha de creación de la familia (si es posterior)
        $fechaInicioBase = new \DateTime(self::FECHA_INICIO_DEUDAS);
        if ($fechaInicio) {
            $fechaInicio = new \DateTime($fechaInicio);
        } else {
            // Intentar obtener la fecha de creación de la familia (puede variar según la implementación)
            $fechaInicio = $familia->created_at ? new \DateTime($familia->created_at) : clone $fechaInicioBase;
        }
        if ($fechaInicio < $fechaInicioBase) {
            $fechaInicio = clone $fechaInicioBase;
        }

        $hoy = new \DateTime();
        $fechasQuincenales = self::calcularFechasQuincenalesPeriodo($fechaInicio, $hoy);
        $generados = 0;

        foreach ($fechasQuincenales as $fechaQuincena) {
            $existe = self::find()
                ->where([
                    'id_familia' => $id_familia,
                    'fecha_quincena' => $fechaQuincena,
                ])
                ->exists();

            if (!$existe) {
                $aporte = new self();
                $aporte->scenario = 'familia';
                $aporte->id_familia = $id_familia;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->numero_quincena = self::calcularNumeroQuincenaExacta($fechaQuincena);
                $aporte->estado = self::ESTADO_PENDIENTE;
                $aporte->pago_parcial = false; // No aplica a familias
                
                if ($aporte->save()) {
                    $generados++;
                }
            }
        }

        return $generados;
    }

    /**
     * Genera quincenas para todas las familias.
     *
     * @return int Total de aportes generados.
     */
    public static function generarQuincenasTodasFamilias()
    {
        $familias = Familia::find()->all();
        $total = 0;
        foreach ($familias as $familia) {
            $total += self::generarQuincenasParaFamilia($familia->id_familia);
        }
        return $total;
    }

    /**
     * Obtiene el resumen de aportes por familia (solo modo familia).
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array
     */
    public static function resumenPorFamilia($fechaInicio, $fechaFin)
    {
        return self::find()
            ->select([
                'id_familia',
                'SUM(monto) as total_aportado',
                'SUM(CASE WHEN estado = :pagado THEN monto ELSE 0 END) as total_pagado',
                'COUNT(*) as total_quincenas',
            ])
            ->addParams([':pagado' => self::ESTADO_PAGADO])
            ->where(['not', ['id_familia' => null]])
            ->andWhere(['between', 'fecha_quincena', $fechaInicio, $fechaFin])
            ->groupBy('id_familia')
            ->asArray()
            ->all();
    }

    // =========================================================================
    // MÉTODOS ORIGINALES (TOTALMENTE CONSERVADOS)
    // =========================================================================

    // --- Cálculo de fechas quincenales ---
    public static function calcularFechasQuincenalesPeriodo($fechaInicio, $fechaFin = null) { /* ... */ }
    public static function calcularNumeroQuincenaExacta($fecha) { /* ... */ }
    public static function debePagarFraccion($fechaInscripcion, $fechaQuincena) { /* ... */ }
    public static function calcularMontoProporcional($fechaInscripcion, $fechaQuincena, $tasaDolar = null) { /* ... */ }

    // --- Manejo de moneda ---
    public static function obtenerTasaDolar($fecha = null) { /* ... */ }
    public static function convertirBsADolares($monto_bs, $tasa_dolar) { /* ... */ }
    public static function convertirDolaresABs($monto_usd, $tasa_dolar) { /* ... */ }
    public function getMontoBs() { /* ... */ }
    public function getMontoDual() { /* ... */ }

    // --- Deudas de atletas ---
    public static function calcularDeudaAtleta($atleta_id) { /* ... */ }
    public static function calcularMontoDeudaConMoneda($atleta_id) { /* ... */ }
    public static function calcularMontoDeuda($atleta_id) { /* ... */ }
    public static function obtenerHistorialDeudas($atleta_id) { /* ... */ }
    public static function obtenerDeudasPendientes($atleta_id) { /* ... */ }

    // --- Gestión de quincenas de atletas ---
    public static function generarQuincenasParaAtleta($atleta_id) { /* ... */ }
    public static function calcularQuincenasEquivalentes($montoAportado) { /* ... */ }
    public static function calcularNumeroQuincena($fecha) { /* ... */ }
    private static function calcularProximaQuincena($fecha) { /* ... */ }

    // --- Pagos de atletas ---
    public static function procesarAporteFlexible($atleta_id, $montoTotal, $fechaPago = null, $metodoPago = 'efectivo', $comentarios = 'Aporte flexible') { /* ... */ }
    public static function procesarPagoMultiple($atleta_id, $quincenasSeleccionadas, $fechaPago, $metodoPago = 'efectivo', $comentarios = 'Pago múltiple') { /* ... */ }
    public static function procesarPagoAdelantado($atleta_id, $quincenasAdelanto, $fechaPago, $metodoPago = 'efectivo', $comentarios = 'Pago adelantado') { /* ... */ }
    public static function liquidarDeudasPendientes($atleta_id, $fecha_pago, $metodo_pago, $comentarios = '') { /* ... */ }

    // --- Estadísticas de atletas ---
    public static function getTopAtletas($escuela_id = null, $limit = 10) { /* ... */ }
    public static function getEstadisticasAtleta($atleta_id) { /* ... */ }

    // --- Utilidades de formato ---
    public function getEstadoLabel() { 
        $estados = [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_CANCELADO => 'Cancelado'
        ];
        return $estados[$this->estado] ?? $this->estado;
    }
    public function getFechaQuincenaFormateada() { /* ... */ }
    public function getFechaPagoFormateada() { /* ... */ }
}