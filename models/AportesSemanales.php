<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * Modelo para la tabla 'contabilidad.aportes_semanales'.
 * 
 * @property int $id
 * @property int $atleta_id
 * @property int $escuela_id
 * @property string $fecha_quincena
 * @property int $numero_quincena
 * @property float|null $monto
 * @property string|null $fecha_pago
 * @property string|null $estado
 * @property string|null $metodo_pago
 * @property string|null $comentarios
 * @property string|null $created_at
 * @property int|null $u_create
 * @property int|null $u_update
 * @property bool|null $pago_parcial
 * @property string|null $update_at
 * @property float|null $tasa_dolar_quincena
 * @property float|null $monto_bs_original
 * @property float $tipo_cambio
 * @property int|null $id_familia
 * @property int|null $id_beca
 * @property float|null $monto_base
 * @property float|null $monto_ajuste
 * @property int|null $total_atletas_familia
 * @property string|null $formula_aplicada
 * @property string|null $tipo_aporte
 *
 * @property AtletasRegistro $atleta
 * @property Escuela $escuela
 * @property Familia $familia
 * @property Beca $beca
 * @property User $creador
 * @property User $actualizador
 */
class AportesSemanales extends ActiveRecord
{
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADO = 'pagado';
    const ESTADO_CANCELADO = 'cancelado';
    const FECHA_INICIO_DEUDAS = '2026-01-15';
    const MONTO_QUINCENAL_USD = 5.00;
    const TASA_CAMBIO_FIJA = 36.50;

    const TIPO_APORTE_NORMAL = 'normal';
    const TIPO_APORTE_ADELANTADO = 'adelantado';
    const TIPO_APORTE_FLEXIBLE = 'flexible';

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
            [['fecha_quincena', 'numero_quincena'], 'required'],
            [['atleta_id', 'escuela_id', 'id_familia', 'id_beca', 'total_atletas_familia', 'u_create', 'u_update'], 'integer'],
            [['fecha_quincena', 'fecha_pago', 'created_at', 'update_at'], 'safe'],
            [['monto', 'monto_bs_original', 'tasa_dolar_quincena', 'tipo_cambio', 'monto_base', 'monto_ajuste'], 'number'],
            [['comentarios', 'formula_aplicada'], 'string'],
            [['pago_parcial'], 'boolean'],
            [['estado'], 'string', 'max' => 20],
            [['estado'], 'default', 'value' => self::ESTADO_PENDIENTE],
            [['metodo_pago'], 'string', 'max' => 50],
            [['tipo_aporte'], 'string', 'max' => 20],
            [['tipo_aporte'], 'default', 'value' => self::TIPO_APORTE_NORMAL],
            [['tipo_cambio'], 'default', 'value' => self::TASA_CAMBIO_FIJA],
            [['monto_base'], 'default', 'value' => self::MONTO_QUINCENAL_USD],
            [['total_atletas_familia'], 'default', 'value' => 1],
            [['monto_ajuste'], 'default', 'value' => 0.00],

            [['atleta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['atleta_id' => 'id']],
            [['escuela_id'], 'exist', 'skipOnError' => true, 'targetClass' => Escuela::class, 'targetAttribute' => ['escuela_id' => 'id']],
            [['id_familia'], 'exist', 'skipOnError' => true, 'targetClass' => Familia::class, 'targetAttribute' => ['id_familia' => 'id_familia']],
            [['id_beca'], 'exist', 'skipOnError' => true, 'targetClass' => Beca::class, 'targetAttribute' => ['id_beca' => 'id_beca']],

            [['fecha_quincena'], 'validateFechaQuincena'],
        ];
    }

    /**
     * Valida que la fecha de quincena no sea anterior a la fecha de inicio de deudas.
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
            'fecha_pago' => 'Fecha Pago',
            'estado' => 'Estado',
            'metodo_pago' => 'Método Pago',
            'comentarios' => 'Comentarios',
            'created_at' => 'Creado',
            'u_create' => 'Creado por',
            'u_update' => 'Actualizado por',
            'pago_parcial' => 'Pago Parcial',
            'update_at' => 'Actualizado',
            'tasa_dolar_quincena' => 'Tasa Dólar Quincena',
            'monto_bs_original' => 'Monto Original (Bs)',
            'tipo_cambio' => 'Tipo Cambio',
            'id_familia' => 'Familia',
            'id_beca' => 'Beca Aplicada',
            'monto_base' => 'Monto Base ($)',
            'monto_ajuste' => 'Ajuste ($)',
            'total_atletas_familia' => 'Total Atletas en Familia',
            'formula_aplicada' => 'Fórmula Aplicada',
            'tipo_aporte' => 'Tipo de Aporte',
        ];
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

    public function getBeca()
    {
        return $this->hasOne(Beca::class, ['id_beca' => 'id_beca']);
    }

    public function getCreador()
    {
        return $this->hasOne(User::class, ['id' => 'u_create']);
    }

    public function getActualizador()
    {
        return $this->hasOne(User::class, ['id' => 'u_update']);
    }

    // =========================================================================
    // MÉTODOS PARA ATLETAS (ORIGINALES, ADAPTADOS A LA NUEVA ESTRUCTURA)
    // =========================================================================

    /**
     * Genera las quincenas faltantes para un atleta desde la fecha de inicio.
     * @param int $atleta_id
     * @return int Número de quincenas generadas
     */
    public static function generarQuincenasParaAtleta($atleta_id)
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return 0;
        }

        $fechaInicio = new \DateTime(self::FECHA_INICIO_DEUDAS);
        $hoy = new \DateTime();
        $fechasQuincenales = self::calcularFechasQuincenalesPeriodo($fechaInicio, $hoy);
        $generados = 0;

        foreach ($fechasQuincenales as $fechaQuincena) {
            $existe = self::find()
                ->where(['atleta_id' => $atleta_id, 'fecha_quincena' => $fechaQuincena])
                ->exists();

            if (!$existe) {
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->numero_quincena = self::calcularNumeroQuincenaExacta($fechaQuincena);
                $aporte->monto = self::MONTO_QUINCENAL_USD;
                $aporte->estado = self::ESTADO_PENDIENTE;
                $aporte->pago_parcial = false;
                $aporte->tipo_cambio = self::TASA_CAMBIO_FIJA;
                $aporte->tipo_aporte = self::TIPO_APORTE_NORMAL;

                if ($aporte->save()) {
                    $generados++;
                }
            }
        }

        return $generados;
    }

    /**
     * Calcula la deuda total de un atleta (solo aportes pendientes).
     * @param int $atleta_id
     * @return float
     */
    public static function calcularDeudaAtleta($atleta_id)
    {
        return self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PENDIENTE])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS])
            ->sum('monto') ?? 0.0;
    }

    /**
     * Obtiene los atletas con mayores aportes pagados.
     * @param int|null $escuela_id
     * @param int $limit
     * @return array
     */
    public static function getTopAtletas($escuela_id = null, $limit = 10)
    {
        $query = self::find()
            ->select(['atleta_id', 'SUM(monto) as total_pagado', 'COUNT(*) as total_aportes'])
            ->where(['estado' => self::ESTADO_PAGADO])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS])
            ->groupBy('atleta_id')
            ->orderBy(['total_pagado' => SORT_DESC])
            ->limit($limit);

        if ($escuela_id) {
            $query->andWhere(['escuela_id' => $escuela_id]);
        }

        $top = $query->asArray()->all();
        foreach ($top as &$item) {
            $item['atleta'] = AtletasRegistro::findOne($item['atleta_id']);
        }
        return $top;
    }

    /**
     * Procesa un aporte flexible (pago que puede cubrir varias quincenas, incluyendo deudas).
     * @param int $atleta_id
     * @param float $montoTotal
     * @param string|null $fechaPago
     * @param string $metodoPago
     * @param string $comentarios
     * @return array Resultado con estadísticas
     */
    public static function procesarAporteFlexible($atleta_id, $montoTotal, $fechaPago = null, $metodoPago = 'efectivo', $comentarios = 'Aporte flexible')
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return ['success' => false, 'message' => 'Atleta no encontrado'];
        }

        $fechaPago = $fechaPago ?: date('Y-m-d');
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Liquidar deudas pendientes en orden cronológico
            $deudasPendientes = self::find()
                ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PENDIENTE])
                ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS])
                ->orderBy(['fecha_quincena' => SORT_ASC])
                ->all();

            $montoRestante = $montoTotal;
            $deudasLiquidadas = 0;
            $quincenasNuevas = 0;

            foreach ($deudasPendientes as $deuda) {
                if ($montoRestante >= $deuda->monto) {
                    $deuda->estado = self::ESTADO_PAGADO;
                    $deuda->fecha_pago = $fechaPago;
                    $deuda->metodo_pago = $metodoPago;
                    $deuda->comentarios = $comentarios . ' (Liquidación de deuda)';
                    $deuda->tipo_aporte = self::TIPO_APORTE_FLEXIBLE;
                    if ($deuda->save()) {
                        $montoRestante -= $deuda->monto;
                        $deudasLiquidadas++;
                    }
                } else {
                    break; // No alcanza para más deudas
                }
            }

            // Si sobra dinero, crear nuevas quincenas adelantadas
            if ($montoRestante > 0) {
                $quincenasCompletas = floor($montoRestante / self::MONTO_QUINCENAL_USD);
                $montoSobrante = $montoRestante - ($quincenasCompletas * self::MONTO_QUINCENAL_USD);

                // Obtener la última fecha de quincena del atleta
                $ultimoAporte = self::find()
                    ->where(['atleta_id' => $atleta_id])
                    ->orderBy(['fecha_quincena' => SORT_DESC])
                    ->one();

                if ($ultimoAporte) {
                    $fechaActual = new \DateTime($ultimoAporte->fecha_quincena);
                    $fechaActual->modify('+15 days');
                } else {
                    $fechaActual = new \DateTime(self::calcularProximaQuincena(new \DateTime()));
                }

                for ($i = 0; $i < $quincenasCompletas; $i++) {
                    $fechaQuincena = $fechaActual->format('Y-m-d');
                    $existe = self::find()->where(['atleta_id' => $atleta_id, 'fecha_quincena' => $fechaQuincena])->exists();
                    if (!$existe) {
                        $aporte = new self();
                        $aporte->atleta_id = $atleta_id;
                        $aporte->escuela_id = $atleta->id_escuela;
                        $aporte->fecha_quincena = $fechaQuincena;
                        $aporte->numero_quincena = self::calcularNumeroQuincenaExacta($fechaQuincena);
                        $aporte->monto = self::MONTO_QUINCENAL_USD;
                        $aporte->estado = self::ESTADO_PAGADO;
                        $aporte->fecha_pago = $fechaPago;
                        $aporte->metodo_pago = $metodoPago;
                        $aporte->comentarios = $comentarios . ' - Quincena adelantada (flexible)';
                        $aporte->pago_parcial = false;
                        $aporte->tipo_cambio = self::TASA_CAMBIO_FIJA;
                        $aporte->tipo_aporte = self::TIPO_APORTE_FLEXIBLE;
                        if ($aporte->save()) {
                            $quincenasNuevas++;
                        }
                    }
                    $fechaActual->modify('+15 days');
                }

                if ($montoSobrante > 0) {
                    // Registrar un pago parcial para la siguiente quincena
                    $fechaQuincena = $fechaActual->format('Y-m-d');
                    $existe = self::find()->where(['atleta_id' => $atleta_id, 'fecha_quincena' => $fechaQuincena])->exists();
                    if (!$existe) {
                        $aporte = new self();
                        $aporte->atleta_id = $atleta_id;
                        $aporte->escuela_id = $atleta->id_escuela;
                        $aporte->fecha_quincena = $fechaQuincena;
                        $aporte->numero_quincena = self::calcularNumeroQuincenaExacta($fechaQuincena);
                        $aporte->monto = $montoSobrante;
                        $aporte->estado = self::ESTADO_PAGADO;
                        $aporte->fecha_pago = $fechaPago;
                        $aporte->metodo_pago = $metodoPago;
                        $aporte->comentarios = $comentarios . ' - Pago parcial (flexible)';
                        $aporte->pago_parcial = true;
                        $aporte->tipo_cambio = self::TASA_CAMBIO_FIJA;
                        $aporte->tipo_aporte = self::TIPO_APORTE_FLEXIBLE;
                        if ($aporte->save()) {
                            $quincenasNuevas++;
                        }
                    }
                }
            }

            $transaction->commit();

            return [
                'success' => true,
                'deudasLiquidadas' => $deudasLiquidadas,
                'quincenasNuevas' => $quincenasNuevas,
                'message' => "Procesado: $deudasLiquidadas deudas liquidadas, $quincenasNuevas nuevas quincenas."
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Procesa pago múltiple de quincenas seleccionadas.
     * @param int $atleta_id
     * @param array $quincenasSeleccionadas (fechas Y-m-d)
     * @param string $fechaPago
     * @param string $metodoPago
     * @param string $comentarios
     * @return int Número de quincenas pagadas
     */
    public static function procesarPagoMultiple($atleta_id, $quincenasSeleccionadas, $fechaPago, $metodoPago = 'efectivo', $comentarios = 'Pago múltiple')
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return 0;
        }

        $pagadas = 0;
        foreach ($quincenasSeleccionadas as $fechaQuincena) {
            $aporte = self::find()
                ->where(['atleta_id' => $atleta_id, 'fecha_quincena' => $fechaQuincena])
                ->one();

            if (!$aporte) {
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->numero_quincena = self::calcularNumeroQuincenaExacta($fechaQuincena);
                $aporte->monto = self::MONTO_QUINCENAL_USD;
                $aporte->pago_parcial = false;
                $aporte->tipo_cambio = self::TASA_CAMBIO_FIJA;
                $aporte->tipo_aporte = self::TIPO_APORTE_NORMAL;
            }

            $aporte->estado = self::ESTADO_PAGADO;
            $aporte->fecha_pago = $fechaPago;
            $aporte->metodo_pago = $metodoPago;
            $aporte->comentarios = $comentarios;

            if ($aporte->save()) {
                $pagadas++;
            }
        }
        return $pagadas;
    }

    /**
     * Procesa pago adelantado de un número de quincenas futuras.
     * @param int $atleta_id
     * @param int $quincenasAdelanto
     * @param string $fechaPago
     * @param string $metodoPago
     * @param string $comentarios
     * @return int Número de quincenas pagadas
     */
    public static function procesarPagoAdelantado($atleta_id, $quincenasAdelanto, $fechaPago, $metodoPago = 'efectivo', $comentarios = 'Pago adelantado')
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return 0;
        }

        $fechaActual = new \DateTime();
        $fechaActual = new \DateTime(self::calcularProximaQuincena($fechaActual));
        $pagadas = 0;

        for ($i = 0; $i < $quincenasAdelanto; $i++) {
            $fechaQuincena = $fechaActual->format('Y-m-d');
            $existe = self::find()
                ->where(['atleta_id' => $atleta_id, 'fecha_quincena' => $fechaQuincena])
                ->exists();

            if (!$existe) {
                $aporte = new self();
                $aporte->atleta_id = $atleta_id;
                $aporte->escuela_id = $atleta->id_escuela;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->numero_quincena = self::calcularNumeroQuincenaExacta($fechaQuincena);
                $aporte->monto = self::MONTO_QUINCENAL_USD;
                $aporte->estado = self::ESTADO_PAGADO;
                $aporte->fecha_pago = $fechaPago;
                $aporte->metodo_pago = $metodoPago;
                $aporte->comentarios = $comentarios . " - Quincena $fechaQuincena";
                $aporte->pago_parcial = false;
                $aporte->tipo_cambio = self::TASA_CAMBIO_FIJA;
                $aporte->tipo_aporte = self::TIPO_APORTE_ADELANTADO;

                if ($aporte->save()) {
                    $pagadas++;
                }
            }
            $fechaActual->modify('+15 days');
        }
        return $pagadas;
    }

    /**
     * Liquida todas las deudas pendientes de un atleta.
     * @param int $atleta_id
     * @param string $fecha_pago
     * @param string $metodo_pago
     * @param string $comentarios
     * @return int Número de deudas liquidadas
     */
    public static function liquidarDeudasPendientes($atleta_id, $fecha_pago, $metodo_pago, $comentarios = '')
    {
        $deudas = self::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => self::ESTADO_PENDIENTE])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS])
            ->all();

        $liquidadas = 0;
        foreach ($deudas as $deuda) {
            $deuda->estado = self::ESTADO_PAGADO;
            $deuda->fecha_pago = $fecha_pago;
            $deuda->metodo_pago = $metodo_pago;
            $deuda->comentarios = $comentarios . ' (Liquidación total)';
            if ($deuda->save()) {
                $liquidadas++;
            }
        }
        return $liquidadas;
    }

    /**
     * Calcula el número de quincena (1-24) para una fecha dada.
     * @param string $fecha
     * @return int
     */
    public static function calcularNumeroQuincena($fecha)
    {
        return self::calcularNumeroQuincenaExacta($fecha);
    }

    // =========================================================================
    // MÉTODOS PARA FAMILIAS
    // =========================================================================

    /**
     * Calcula los valores del aporte familiar usando los campos reales de la tabla.
     * @return bool
     */
    public function calcularAportesFamiliares()
    {
        $familia = $this->familia;
        if (!$familia) {
            return false;
        }

        $this->monto_base = $familia->getAporteBase();
        $totalAtletas = count($familia->atletas);
        $this->total_atletas_familia = $totalAtletas;

        $descuentoMultiple = 0;
        if ($totalAtletas > 1) {
            $descuentoMultiple = 0.25 * ($totalAtletas - 1);
            if ($descuentoMultiple > 0.75) {
                $descuentoMultiple = 0.75;
            }
        }

        $maxDescuentoBeca = 0;
        $becaAplicada = null;
        foreach ($familia->atletas as $atleta) {
            $beca = $atleta->getBecaActiva();
            if ($beca) {
                // Obtener el porcentaje de descuento desde el tipo de beca
                $tipoBeca = $beca->tipoBeca;
                if ($tipoBeca) {
                    $porcentaje = $tipoBeca->porcentaje_descuento / 100;
                    if ($porcentaje > $maxDescuentoBeca) {
                        $maxDescuentoBeca = $porcentaje;
                        $becaAplicada = $beca;
                    }
                }
            }
        }
        $this->id_beca = $becaAplicada ? $becaAplicada->id_beca : null;

        $montoFinal = $this->monto_base;
        $montoFinal *= (1 - $descuentoMultiple);
        $montoFinal *= (1 - $maxDescuentoBeca);
        $this->monto_ajuste = round($montoFinal, 2);

        $this->formula_aplicada = sprintf(
            'Base: %s, Atletas: %d (desc %.2f%%), Beca: %s (desc %.2f%%)',
            $this->monto_base,
            $totalAtletas,
            $descuentoMultiple * 100,
            $becaAplicada ? ($becaAplicada->tipoBeca->nombre ?? 'Desconocida') : 'Ninguna',
            $maxDescuentoBeca * 100
        );

        $this->monto = $this->monto_ajuste;
        return true;
    }

    /**
     * Genera los aportes quincenales para una familia.
     * @param int $id_familia
     * @param string|null $fechaInicio
     * @return int
     */
    public static function generarQuincenasParaFamilia($id_familia, $fechaInicio = null)
    {
        $familia = Familia::findOne($id_familia);
        if (!$familia) {
            return 0;
        }

        $fechaInicioBase = new \DateTime(self::FECHA_INICIO_DEUDAS);
        if ($fechaInicio) {
            $fechaInicio = new \DateTime($fechaInicio);
        } else {
            $fechaInicio = $familia->fecha_registro ? new \DateTime($familia->fecha_registro) : clone $fechaInicioBase;
        }
        if ($fechaInicio < $fechaInicioBase) {
            $fechaInicio = clone $fechaInicioBase;
        }

        $hoy = new \DateTime();
        $fechasQuincenales = self::calcularFechasQuincenalesPeriodo($fechaInicio, $hoy);
        $generados = 0;

        foreach ($fechasQuincenales as $fechaQuincena) {
            $existe = self::find()
                ->where(['id_familia' => $id_familia, 'fecha_quincena' => $fechaQuincena])
                ->exists();

            if (!$existe) {
                $aporte = new self();
                $aporte->id_familia = $id_familia;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->numero_quincena = self::calcularNumeroQuincenaExacta($fechaQuincena);
                $aporte->estado = self::ESTADO_PENDIENTE;
                $aporte->pago_parcial = false;
                $aporte->tipo_aporte = self::TIPO_APORTE_NORMAL;

                if ($aporte->calcularAportesFamiliares() && $aporte->save()) {
                    $generados++;
                }
            }
        }

        return $generados;
    }

    /**
     * Genera quincenas para todas las familias.
     * @return int
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
     * Resumen de aportes por familia.
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
    // MÉTODOS AUXILIARES COMUNES
    // =========================================================================

    /**
     * Calcula las fechas de quincena (días 15 y último día de cada mes) dentro de un período.
     * @param \DateTime $fechaInicio
     * @param \DateTime $fechaFin
     * @return array
     */
    public static function calcularFechasQuincenalesPeriodo($fechaInicio, $fechaFin)
    {
        $fechas = [];
        $inicio = clone $fechaInicio;
        $fin = clone $fechaFin;
        $inicio->modify('first day of this month');

        while ($inicio <= $fin) {
            $dia15 = clone $inicio;
            $dia15->setDate($inicio->format('Y'), $inicio->format('m'), 15);
            if ($dia15 >= $fechaInicio && $dia15 <= $fechaFin) {
                $fechas[] = $dia15->format('Y-m-d');
            }

            $ultimo = clone $inicio;
            $ultimo->modify('last day of this month');
            if ($ultimo >= $fechaInicio && $ultimo <= $fechaFin && $ultimo->format('d') != 15) {
                $fechas[] = $ultimo->format('Y-m-d');
            }

            $inicio->modify('+1 month');
        }

        return $fechas;
    }

    /**
     * Calcula el número de quincena exacto (1-24).
     * @param string $fecha
     * @return int
     */
    public static function calcularNumeroQuincenaExacta($fecha)
    {
        $ts = strtotime($fecha);
        $mes = (int)date('n', $ts);
        $dia = (int)date('j', $ts);
        return ($mes - 1) * 2 + ($dia <= 15 ? 1 : 2);
    }

    /**
     * Calcula la próxima fecha de quincena a partir de una fecha.
     * @param \DateTime|string $fecha
     * @return string Y-m-d
     */
    public static function calcularProximaQuincena($fecha)
    {
        if (!$fecha instanceof \DateTime) {
            $fecha = new \DateTime($fecha);
        }
        $dia = (int)$fecha->format('d');
        $mes = (int)$fecha->format('m');
        $anio = (int)$fecha->format('Y');

        if ($dia < 15) {
            return $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-15';
        } else {
            $fecha->modify('first day of next month');
            return $fecha->format('Y-m-d');
        }
    }

    /**
     * Obtiene la tasa de cambio del dólar para una fecha.
     * @param string|null $fecha
     * @return float
     */
    public static function obtenerTasaDolar($fecha = null)
    {
        $fecha = $fecha ?: date('Y-m-d');
        $tasa = TasaDolar::find()
            ->where(['fecha_tasa' => $fecha, 'eliminado' => false])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        return $tasa ? $tasa->tasa_dia : self::TASA_CAMBIO_FIJA;
    }

    /**
     * Convierte bolívares a dólares.
     * @param float $monto_bs
     * @param float $tasa_dolar
     * @return float
     */
    public static function convertirBsADolares($monto_bs, $tasa_dolar)
    {
        return round($monto_bs / $tasa_dolar, 2);
    }

    /**
     * Convierte dólares a bolívares.
     * @param float $monto_usd
     * @param float $tasa_dolar
     * @return float
     */
    public static function convertirDolaresABs($monto_usd, $tasa_dolar)
    {
        return round($monto_usd * $tasa_dolar, 2);
    }

    // =========================================================================
    // MÉTODOS DE CAMBIO DE ESTADO
    // =========================================================================

    public function marcarPagado($fechaPago = null, $metodoPago = null)
    {
        $this->estado = self::ESTADO_PAGADO;
        $this->fecha_pago = $fechaPago ?: date('Y-m-d');
        if ($metodoPago) {
            $this->metodo_pago = $metodoPago;
        }
        return $this->save(false, ['estado', 'fecha_pago', 'metodo_pago', 'update_at', 'u_update']);
    }

    public function marcarPendiente()
    {
        $this->estado = self::ESTADO_PENDIENTE;
        $this->fecha_pago = null;
        return $this->save(false, ['estado', 'fecha_pago', 'update_at', 'u_update']);
    }

    public function marcarCancelado()
    {
        $this->estado = self::ESTADO_CANCELADO;
        return $this->save(false, ['estado', 'update_at', 'u_update']);
    }

    public function isPagado()
    {
        return $this->estado === self::ESTADO_PAGADO;
    }

    public function isPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function isCancelado()
    {
        return $this->estado === self::ESTADO_CANCELADO;
    }

    // =========================================================================
    // UTILIDADES DE FORMATEO
    // =========================================================================

    public function getEstadoLabel()
    {
        $estados = [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_CANCELADO => 'Cancelado',
        ];
        return $estados[$this->estado] ?? $this->estado;
    }

    // =========================================================================
    // NUEVOS MÉTODOS OPTIMIZADOS (2026-03-02) - CORREGIDOS
    // =========================================================================

    /**
     * Genera todas las quincenas faltantes para una escuela en una sola operación masiva,
     * procesando los atletas en lotes para evitar timeouts.
     * @param int $escuela_id
     * @return int Número de registros insertados
     */
    public static function generarQuincenasMasivo($escuela_id)
    {
        $fechaInicio = self::FECHA_INICIO_DEUDAS; // '2026-01-15'
        $hoy = date('Y-m-d');
        $monto = self::MONTO_QUINCENAL_USD;

        // Generar lista de fechas de quincena con su número correspondiente
        $fechasConNumero = [];
        $current = new \DateTime($fechaInicio);
        $end = new \DateTime($hoy);
        while ($current <= $end) {
            $fecha = $current->format('Y-m-d');
            $numero = self::calcularNumeroQuincenaExacta($fecha);
            $fechasConNumero[] = ['fecha' => $fecha, 'numero' => $numero];
            $current->modify('+15 days');
        }

        if (empty($fechasConNumero)) {
            return 0;
        }

        // Obtener IDs de atletas de la escuela
        $atletas = AtletasRegistro::find()
            ->select(['id'])
            ->where(['id_escuela' => $escuela_id, 'eliminado' => false])
            ->asArray()
            ->all();
        $atletasIds = array_column($atletas, 'id');

        $totalInsertados = 0;
        $batchSize = 100; // Tamaño de lote

        // Procesar atletas en lotes
        foreach (array_chunk($atletasIds, $batchSize) as $chunk) {
            // Construir cláusula VALUES para cada atleta y cada fecha
            $values = [];
            foreach ($chunk as $atletaId) {
                foreach ($fechasConNumero as $item) {
                    $values[] = "({$atletaId}, '{$item['fecha']}', {$item['numero']})";
                }
            }
            if (empty($values)) {
                continue;
            }

            $valuesSql = implode(',', $values);
            $sql = "
                INSERT INTO contabilidad.aportes_semanales 
                    (atleta_id, escuela_id, fecha_quincena, numero_quincena, monto, estado, tipo_aporte, tipo_cambio, pago_parcial, created_at, u_create)
                SELECT 
                    v.atleta_id,
                    :escuela_id,
                    v.fecha::date,   -- Conversión explícita a date
                    v.numero,
                    :monto,
                    :estado,
                    :tipo_aporte,
                    :tipo_cambio,
                    false,
                    NOW(),
                    :user_id
                FROM (VALUES $valuesSql) AS v(atleta_id, fecha, numero)
                WHERE NOT EXISTS (
                    SELECT 1 FROM contabilidad.aportes_semanales ap
                    WHERE ap.atleta_id = v.atleta_id AND ap.fecha_quincena = v.fecha::date
                )
            ";

            $params = [
                ':escuela_id' => $escuela_id,
                ':monto' => $monto,
                ':estado' => self::ESTADO_PENDIENTE,
                ':tipo_aporte' => self::TIPO_APORTE_NORMAL,
                ':tipo_cambio' => self::TASA_CAMBIO_FIJA,
                ':user_id' => Yii::$app->user->id,
            ];

            $totalInsertados += Yii::$app->db->createCommand($sql, $params)->execute();
        }

        return $totalInsertados;
    }

    /**
     * Obtiene un resumen completo de aportes para una lista de IDs de atletas.
     * @param array $atletaIds
     * @return array [atleta_id => [total_pagado, total_pendiente, total_adelantado, quincenas_pagadas, quincenas_pendientes, total_quincenas]]
     */
    public static function getResumenAtletas($atletaIds)
    {
        if (empty($atletaIds)) {
            return [];
        }

        $hoy = date('Y-m-d');
        $resumen = self::find()
            ->select([
                'atleta_id',
                'SUM(CASE WHEN estado = :pagado AND fecha_quincena <= :hoy THEN monto ELSE 0 END) as total_pagado',
                'SUM(CASE WHEN estado = :pendiente THEN monto ELSE 0 END) as total_pendiente',
                'SUM(CASE WHEN estado = :pagado AND fecha_quincena > :hoy THEN monto ELSE 0 END) as total_adelantado',
                'COUNT(CASE WHEN estado = :pagado AND fecha_quincena <= :hoy THEN 1 END) as quincenas_pagadas',
                'COUNT(CASE WHEN estado = :pendiente THEN 1 END) as quincenas_pendientes',
                'COUNT(CASE WHEN estado = :pagado AND fecha_quincena > :hoy THEN 1 END) as quincenas_adelantadas',
                'COUNT(*) as total_quincenas',
            ])
            ->addParams([
                ':pagado' => self::ESTADO_PAGADO,
                ':pendiente' => self::ESTADO_PENDIENTE,
                ':hoy' => $hoy,
            ])
            ->where(['atleta_id' => $atletaIds])
            ->andWhere(['>=', 'fecha_quincena', self::FECHA_INICIO_DEUDAS])
            ->groupBy('atleta_id')
            ->asArray()
            ->all();

        // Re-indexar por atleta_id
        $result = [];
        foreach ($resumen as $row) {
            $result[$row['atleta_id']] = $row;
        }
        return $result;
    }

    // =========================================================================
    // NUEVO MÉTODO OPTIMIZADO PARA UN SOLO ATLETA (2026-03-04)
    // =========================================================================

    /**
     * Genera las quincenas faltantes para un atleta específico usando una sola consulta SQL.
     * @param int $atleta_id
     * @return int Número de registros insertados
     */
    public static function generarQuincenasParaAtletaMasivo($atleta_id)
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return 0;
        }

        $fechaInicio = self::FECHA_INICIO_DEUDAS;
        $hoy = date('Y-m-d');
        $monto = self::MONTO_QUINCENAL_USD;

        // Generar lista de fechas de quincena con su número correspondiente
        $fechasConNumero = [];
        $current = new \DateTime($fechaInicio);
        $end = new \DateTime($hoy);
        while ($current <= $end) {
            $fecha = $current->format('Y-m-d');
            $numero = self::calcularNumeroQuincenaExacta($fecha);
            $fechasConNumero[] = ['fecha' => $fecha, 'numero' => $numero];
            $current->modify('+15 days');
        }

        if (empty($fechasConNumero)) {
            return 0;
        }

        // Construir cláusula VALUES
        $values = [];
        foreach ($fechasConNumero as $item) {
            $values[] = "('{$item['fecha']}', {$item['numero']})";
        }
        $valuesSql = implode(',', $values);

        $sql = "
            INSERT INTO contabilidad.aportes_semanales 
                (atleta_id, escuela_id, fecha_quincena, numero_quincena, monto, estado, tipo_aporte, tipo_cambio, pago_parcial, created_at, u_create)
            SELECT 
                :atleta_id,
                :escuela_id,
                q.fecha::date,   -- Conversión explícita a date
                q.numero,
                :monto,
                :estado,
                :tipo_aporte,
                :tipo_cambio,
                false,
                NOW(),
                :user_id
            FROM (VALUES $valuesSql) AS q(fecha, numero)
            WHERE NOT EXISTS (
                SELECT 1 FROM contabilidad.aportes_semanales ap
                WHERE ap.atleta_id = :atleta_id AND ap.fecha_quincena = q.fecha::date
            )
        ";

        $params = [
            ':atleta_id' => $atleta_id,
            ':escuela_id' => $atleta->id_escuela,
            ':monto' => $monto,
            ':estado' => self::ESTADO_PENDIENTE,
            ':tipo_aporte' => self::TIPO_APORTE_NORMAL,
            ':tipo_cambio' => self::TASA_CAMBIO_FIJA,
            ':user_id' => Yii::$app->user->id,
        ];

        $count = Yii::$app->db->createCommand($sql, $params)->execute();
        return $count;
    }
}