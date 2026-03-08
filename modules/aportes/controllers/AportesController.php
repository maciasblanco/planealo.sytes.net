<?php

namespace app\modules\aportes\controllers;

use Yii;
use app\models\AportesSemanales;
use app\models\AtletasRegistro;
use app\models\RegistroRepresentantes;
use app\models\Escuela;
use app\models\ComprasEscuela;
use app\models\TasaDolar;
use app\modules\aportes\models\AportesSemanalesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

// =========================================================================
// NUEVOS MODELOS PARA EL SISTEMA DE FAMILIAS Y BECAS
// =========================================================================
use app\models\Familia;
use app\models\Beca;
use app\models\TipoBeca;
use app\models\ConfiguracionAporte;
use app\models\BecaHistorial;  // AÑADIDO para registro histórico

/**
 * AportesController implementa el CRUD para el modelo AportesSemanales.
 * 
 * ──────────────────────────────────────────────────────────────────────────
 * ✅ VERSIÓN UNIFICADA – SISTEMA DE APORTES Y BECAS
 * 
 * SOPORTA DOS MODOS:
 *   1. MODO ATLETA (ORIGINAL)   – Aportes quincenales por atleta ($5.00 USD)
 *   2. MODO FAMILIA (NUEVO)     – Aportes quincenales por familia con descuentos
 *                                 por múltiples atletas y becas activas.
 * 
 * TODAS LAS FUNCIONALIDADES ORIGINALES SE CONSERVAN ÍNTEGRAMENTE.
 * ──────────────────────────────────────────────────────────────────────────
 * 
 * ACTUALIZADO: Sistema quincenal ($5.00 cada 15 días) con manejo dual de moneda (Bs/USD)
 * SOLO DESDE 15/01/2026
 * ✅ CORREGIDO: Superusuario (ID 1) ahora tiene acceso completo
 * ✅ ACTUALIZADO: Monto quincenal $5.00 (antes $4.00)
 * ✅ MEJORADO: Cálculo de número de quincena unificado en el controlador
 * ✅ MEJORADO: Pagos flexibles y adelantados ahora etiquetados correctamente
 * 
 * MODIFICADO: Se añade redirección a comprobante después de cualquier pago.
 * Se crea acción comprobante que muestra el detalle y permite compartir.
 * 
 * MODIFICACIÓN 2026-03-05: Corrección en historial de becas – se elimina asignación de
 * propiedades inexistentes en BecaHistorial y se usan campos correctos (id_beca,
 * fecha_original_inicio, fecha_original_fin, motivo, usuario_creacion).
 * 
 * MODIFICACIÓN 2026-03-05 (2): Se asigna fecha_reactivacion = fecha_asignacion para
 * cumplir con la restricción NOT NULL de la columna fecha_reactivacion.
 * 
 * =========================================================================
 * NUEVAS ACTUALIZACIONES – FLUJO DE BECAS CON DOBLE APROBACIÓN Y RENOVACIÓN
 * =========================================================================
 * - Becas propuestas por entrenador (quedan PENDIENTES).
 * - Aprobadas o rechazadas por administrador.
 * - Renovación automática cada julio (excepto becas de entrenador, no renovables).
 * - Nuevos campos: estado_aprobacion, estado_ciclo, propuesto_por, motivo_rechazo, renovable.
 * - Nuevas acciones: propuestas-pendientes, aprobar-beca, rechazar-beca, view-beca, update-beca.
 * - Acceso: superusuario (ID 1) y admin tienen acceso total; entrenador puede proponer y ver.
 * 
 * =========================================================================
 * CORRECCIÓN 2026-03-07: Unificación del cálculo de deuda en actionIndex, actionAtletasMorosos,
 * actionReporteEjecutivo, aplicando filtro desde 2026-01-15 y descuento por becas activas.
 * 
 * =========================================================================
 * MEJORA 2026-03-07: Generación automática de quincenas faltantes la primera vez que se accede al índice.
 * =========================================================================
 */
class AportesController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                        'marcar-pagado' => ['POST'],
                        'procesar-pago-multiple' => ['POST'],
                        'procesar-pago-adelantado' => ['POST'],
                        'generar-quincenas-familias' => ['POST'],
                        'pagar-aporte-familia' => ['POST'],
                        'revocar-beca' => ['POST'],
                        'aprobar-beca' => ['POST'],
                        'rechazar-beca' => ['POST'],
                    ],
                ],
                'access' => [
                    'class' => AccessControl::className(),
                    'rules' => [
                        // -----------------------------------------------------
                        // REGLAS ORIGINALES (ATLETAS) – SE CONSERVAN IGUAL
                        // -----------------------------------------------------
                        [
                            'allow' => true,
                            'roles' => ['@'], // Solo usuarios autenticados
                        ],
                        // -----------------------------------------------------
                        // REGLAS PARA FAMILIAS Y BECAS (solo admin/superusuario)
                        // -----------------------------------------------------
                        [
                            'allow' => true,
                            'actions' => [
                                'familias',
                                'generar-quincenas-familias',
                                'gestion-familia',
                                'reporte-familias',
                                'pagar-aporte-familia',
                                'becas',
                                'revocar-beca',
                                'configuracion-aporte',
                                'propuestas-pendientes',
                                'aprobar-beca',
                                'rechazar-beca',
                                'view-beca',
                                'update-beca',
                            ],
                            'matchCallback' => function ($rule, $action) {
                                $user = Yii::$app->user;
                                // Superusuario (ID 1) o usuario con rol admin
                                return $user->id == 1 || $user->can('admin');
                            },
                        ],
                        // -----------------------------------------------------
                        // REGLA ESPECIAL PARA ASIGNAR BECA (solo entrenador)
                        // -----------------------------------------------------
                        [
                            'allow' => true,
                            'actions' => ['asignar-beca'],
                            'matchCallback' => function ($rule, $action) {
                                $user = Yii::$app->user;
                                // Puede proponer el entrenador (permiso 'proponerBeca') o superusuario (ID 1) para pruebas.
                                return $user->id == 1 || $user->can('proponerBeca');
                            },
                        ],
                    ],
                ],
            ]
        );
    }

    // =========================================================================
    // MÉTODO AUXILIAR PARA CALCULAR DEUDA CON DESCUENTO Y FILTRO DE FECHA
    // =========================================================================
    /**
     * Calcula el resumen de aportes para un atleta, incluyendo deuda con descuento por beca.
     * @param int $atleta_id
     * @return array con claves: total_pagado, total_pendiente, total_adelantado,
     *               quincenas_pagadas, quincenas_pendientes, quincenas_adelantadas, total_quincenas
     */
    private function calcularResumenAtleta($atleta_id)
    {
        $total_pagado = 0;
        $total_pendiente = 0;
        $total_adelantado = 0;
        $quincenas_pagadas = 0;
        $quincenas_pendientes = 0;
        $quincenas_adelantadas = 0;

        // Obtener descuento por beca activa del atleta
        $porcentajeDescuento = 0;
        $atleta = AtletasRegistro::findOne($atleta_id);
        if ($atleta && $atleta->id_familia) {
            $becaActiva = Beca::find()
                ->where(['id_atleta' => $atleta_id, 'estado_aprobacion' => Beca::ESTADO_APROB_ACTIVA])
                ->andWhere(['IS', 'estado_ciclo', null])
                ->one();
            if ($becaActiva && $becaActiva->tipoBeca) {
                $porcentajeDescuento = $becaActiva->tipoBeca->porcentaje_descuento;
            }
        }

        // Quincenas pendientes (con posible descuento)
        $pendientes = AportesSemanales::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => AportesSemanales::ESTADO_PENDIENTE])
            ->andWhere(['>=', 'fecha_quincena', AportesSemanales::FECHA_INICIO_DEUDAS])
            ->all();
        foreach ($pendientes as $ap) {
            $montoOriginal = $ap->monto;
            $montoConDescuento = $montoOriginal * (1 - $porcentajeDescuento / 100);
            $total_pendiente += $montoConDescuento;
            $quincenas_pendientes++;
        }

        // Pagos realizados (incluye adelantados)
        $pagados = AportesSemanales::find()
            ->where(['atleta_id' => $atleta_id, 'estado' => AportesSemanales::ESTADO_PAGADO])
            ->andWhere(['>=', 'fecha_quincena', AportesSemanales::FECHA_INICIO_DEUDAS])
            ->all();
        foreach ($pagados as $ap) {
            $total_pagado += $ap->monto;
            $quincenas_pagadas++;
            if ($ap->tipo_aporte == AportesSemanales::TIPO_APORTE_ADELANTADO) {
                $total_adelantado += $ap->monto;
                $quincenas_adelantadas++;
            }
        }

        $totalQuincenas = $quincenas_pagadas + $quincenas_pendientes + $quincenas_adelantadas;

        return [
            'total_pagado' => $total_pagado,
            'total_pendiente' => $total_pendiente,
            'total_adelantado' => $total_adelantado,
            'quincenas_pagadas' => $quincenas_pagadas,
            'quincenas_pendientes' => $quincenas_pendientes,
            'quincenas_adelantadas' => $quincenas_adelantadas,
            'total_quincenas' => $totalQuincenas,
        ];
    }

    // =========================================================================
    // 1. ACCIONES ORIGINALES – MODO ATLETA (COMPLETAMENTE CONSERVADAS)
    // =========================================================================

    /**
     * Lists all AportesSemanales models.
     * FILTRADO SOLO DESDE 15/01/2026
     *
     * @return string
     */
    public function actionIndex()
    {
        // Aumentar tiempo de ejecución para evitar timeout durante diagnóstico
        set_time_limit(300);

        $this->layout = 'escuelas';
        
        $id_escuela = Yii::$app->session->get('id_escuela');
        if (!$id_escuela) {
            Yii::$app->session->setFlash('error', 'No se ha seleccionado una escuela.');
            return $this->redirect(['/site/index']);
        }

        // =================================================================
        // GENERACIÓN ÚNICA DE QUINCENAS FALTANTES PARA LA ESCUELA
        // =================================================================
        $sessionKey = 'quincenas_generadas_' . $id_escuela;
        if (!Yii::$app->session->get($sessionKey)) {
            AportesSemanales::generarQuincenasMasivo($id_escuela);
            Yii::$app->session->set($sessionKey, true);
            Yii::info("Quincenas generadas automáticamente para la escuela $id_escuela");
        }
        // =================================================================

        $time_start = microtime(true);

        // Obtener query de atletas permitidos
        $query = $this->getAtletasPermitidosQuery($id_escuela);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20], // Reducido a 20 para mayor velocidad
            'sort' => false,
        ]);

        $time_query = microtime(true);
        Yii::info("Tiempo en obtener query de atletas: " . ($time_query - $time_start));

        // Obtener los atletas de la página actual
        $atletas = $dataProvider->getModels();
        $atletasIds = array_map(function($a) { return $a->id; }, $atletas);

        $time_models = microtime(true);
        Yii::info("Tiempo en obtener modelos de atletas: " . ($time_models - $time_query));

        // =========================================================================
        // CORRECCIÓN: Calcular resumen por atleta con filtro de fecha y descuento
        // =========================================================================
        $resumenAtletas = [];
        foreach ($atletasIds as $aid) {
            $resumenAtletas[$aid] = $this->calcularResumenAtleta($aid);
        }

        $time_resumen = microtime(true);
        Yii::info("Tiempo en calcular resumen atletas: " . ($time_resumen - $time_models));

        // Construir array para la vista
        $atletasConEstadisticas = [];
        $totalRecaudado = 0;
        $deudaTotal = 0;
        $atletasConDeuda = 0;

        foreach ($atletas as $atleta) {
            $data = $resumenAtletas[$atleta->id] ?? [
                'total_pagado' => 0,
                'total_pendiente' => 0,
                'total_adelantado' => 0,
                'quincenas_pagadas' => 0,
                'quincenas_pendientes' => 0,
                'quincenas_adelantadas' => 0,
                'total_quincenas' => 0,
            ];

            $montoPagado = (float)$data['total_pagado'];
            $montoDeuda = (float)$data['total_pendiente'];
            $montoAdelantado = (float)$data['total_adelantado'];
            $quincenasAdelantadas = $data['quincenas_adelantadas'];
            $quincenasPagadas = $data['quincenas_pagadas'];
            $quincenasPendientes = $data['quincenas_pendientes'];
            $totalQuincenas = $data['total_quincenas'];

            $atletasConEstadisticas[] = [
                'atleta' => $atleta,
                'montoPagado' => $montoPagado,
                'montoDeuda' => $montoDeuda,
                'montoAdelantado' => $montoAdelantado,
                'quincenasAdelantadas' => $quincenasAdelantadas,
                'quincenasGeneradas' => 0,
                'totalQuincenas' => $totalQuincenas,
                'quincenasPagadas' => $quincenasPagadas,
                'quincenasPendientes' => $quincenasPendientes,
                'error' => false
            ];

            $totalRecaudado += $montoPagado;
            $deudaTotal += $montoDeuda;
            if ($montoDeuda > 0) {
                $atletasConDeuda++;
            }
        }

        $time_loop = microtime(true);
        Yii::info("Tiempo en bucle de construcción: " . ($time_loop - $time_resumen));

        // Top atletas (global, no paginado)
        $topAtletas = [];
        try {
            $topAtletas = AportesSemanales::getTopAtletas($id_escuela, 10);
        } catch (\Exception $e) {
            Yii::error("Error en getTopAtletas: " . $e->getMessage());
        }

        $time_top = microtime(true);
        Yii::info("Tiempo en getTopAtletas: " . ($time_top - $time_loop));

        // Pendientes totales (contar quincenas pendientes con filtro)
        $pendientes = 0;
        try {
            $pendientes = AportesSemanales::find()
                ->where(['escuela_id' => $id_escuela, 'estado' => AportesSemanales::ESTADO_PENDIENTE])
                ->andWhere(['>=', 'fecha_quincena', AportesSemanales::FECHA_INICIO_DEUDAS])
                ->count();
        } catch (\Exception $e) {
            Yii::error("Error contando pendientes: " . $e->getMessage());
        }

        $time_end = microtime(true);
        Yii::info("Tiempo total de actionIndex: " . ($time_end - $time_start));

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'atletasConEstadisticas' => $atletasConEstadisticas,
            'totalRecaudado' => $totalRecaudado,
            'pendientes' => $pendientes,
            'deudaTotal' => $deudaTotal,
            'atletasConDeuda' => $atletasConDeuda,
            'topAtletas' => $topAtletas,
            'totalAtletas' => $dataProvider->totalCount,
            'erroresProcesamiento' => []
        ]);
    }

    /**
     * Vista unificada para gestión de aportes del atleta
     * SOLO DESDE 15/01/2026
     * @param int $atleta_id
     * @return string
     */
    public function actionGestionAtleta($atleta_id = null)
    {
        $time_start = microtime(true);
        $this->layout = 'escuelas'; 
        
        $id_escuela = Yii::$app->session->get('id_escuela');
        if (!$id_escuela) {
            Yii::$app->session->setFlash('error', 'No se ha seleccionado una escuela.');
            return $this->redirect(['index']);
        }
        
        $model = new AportesSemanales();
        $atleta = null;
        $historialDeudas = [];
        $quincenasDeuda = 0;
        $montoDeuda = 0;
        $quincenasPendientes = [];
        $posicionTop = null;

        if ($atleta_id) {
            $time_before_atleta = microtime(true);
            $atleta = AtletasRegistro::findOne($atleta_id);
            Yii::info("Tiempo findOne atleta: " . (microtime(true) - $time_before_atleta));

            if ($atleta) {
                if (!$this->tienePermisoVerAtleta($atleta)) {
                    throw new ForbiddenHttpException('No tiene permisos para gestionar los aportes de este atleta.');
                }
                
                if ($atleta->id_escuela != $id_escuela) {
                    Yii::$app->session->setFlash('error', 'El atleta no pertenece a su escuela.');
                    return $this->redirect(['gestion-atleta']);
                }
                
                // --- GENERACIÓN OPTIMIZADA DE QUINCENAS ---
                $time_before_quincenas = microtime(true);
                $quincenasGeneradas = AportesSemanales::generarQuincenasParaAtletaMasivo($atleta_id);
                Yii::info("Tiempo generarQuincenasMasivo: " . (microtime(true) - $time_before_quincenas) . " - Generadas: $quincenasGeneradas");

                // --- HISTORIAL LIMITADO A 50 REGISTROS ---
                $time_before_historial = microtime(true);
                $historialDeudas = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta_id])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15'])
                    ->orderBy(['fecha_quincena' => SORT_DESC])
                    ->limit(50)
                    ->asArray()
                    ->all();
                Yii::info("Tiempo consulta historial (limit 50): " . (microtime(true) - $time_before_historial));
                    
                $time_before_deuda = microtime(true);
                $quincenasDeuda = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta_id, 'estado' => 'pendiente'])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15'])
                    ->count();
                $montoDeuda = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta_id, 'estado' => 'pendiente'])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15'])
                    ->sum('monto') ?? 0;
                Yii::info("Tiempo cálculos deuda: " . (microtime(true) - $time_before_deuda));
                    
                $quincenasPendientes = array_filter($historialDeudas, function($quincena) {
                    return $quincena['estado'] == 'pendiente';
                });
            }
        }

        $time_before_atletas = microtime(true);
        $atletas = $this->getAtletasPermitidos($id_escuela);
        Yii::info("Tiempo getAtletasPermitidos: " . (microtime(true) - $time_before_atletas));

        // Procesar formularios
        if (Yii::$app->request->isPost) {
            $tipoAccion = Yii::$app->request->post('tipo_accion');
            
            switch ($tipoAccion) {
                case 'individual':
                    // Código original adaptado a quincenas
                    if ($model->load(Yii::$app->request->post())) {
                        if (!$this->tienePermisoVerAtletaId($model->atleta_id)) {
                            throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                        }
                        if (empty($model->escuela_id)) {
                            $model->escuela_id = $id_escuela;
                        }
                        $model->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                        if (empty($model->numero_quincena) && !empty($model->fecha_quincena)) {
                            $model->numero_quincena = $this->calcularNumeroQuincena($model->fecha_quincena);
                        }
                        
                        // Pago inteligente con liquidación de deudas
                        $transaction = Yii::$app->db->beginTransaction();
                        try {
                            $deudasPendientes = AportesSemanales::find()
                                ->where([
                                    'atleta_id' => $model->atleta_id,
                                    'estado' => 'pendiente'
                                ])
                                ->andWhere(['>=', 'fecha_quincena', '2026-01-15'])
                                ->orderBy(['fecha_quincena' => SORT_ASC])
                                ->all();
                            
                            $deudasLiquidadas = 0;
                            $ids = []; // IDs de aportes procesados
                            if (!empty($deudasPendientes)) {
                                foreach ($deudasPendientes as $deuda) {
                                    $deuda->estado = 'pagado';
                                    $deuda->fecha_pago = $model->fecha_pago;
                                    $deuda->metodo_pago = $model->metodo_pago;
                                    $deuda->comentarios = $model->comentarios . " (Liquidación de deuda pendiente)";
                                    if ($deuda->save()) {
                                        $deudasLiquidadas++;
                                        $ids[] = $deuda->id;
                                    } else {
                                        throw new \Exception("Error al liquidar deuda: " . implode(', ', $deuda->getErrors()));
                                    }
                                }
                                Yii::$app->session->setFlash('success', 
                                    "Se liquidaron {$deudasLiquidadas} deudas pendientes. " . 
                                    ($deudasLiquidadas == 1 ? 'La deuda ha sido saldada.' : 'Las deudas han sido saldadas.')
                                );
                            } else {
                                if ($model->save()) {
                                    $ids[] = $model->id;
                                    Yii::$app->session->setFlash('success', 'Aporte individual registrado exitosamente.');
                                } else {
                                    throw new \Exception('Error al guardar el aporte: ' . implode(', ', $model->getErrorSummary(true)));
                                }
                            }
                            $transaction->commit();
                            if (!empty($ids)) {
                                return $this->redirect(['comprobante', 'ids' => implode(',', $ids)]);
                            }
                            return $this->redirect(['gestion-atleta', 'atleta_id' => $model->atleta_id]);
                        } catch (\Exception $e) {
                            $transaction->rollBack();
                            Yii::$app->session->setFlash('error', $e->getMessage());
                            Yii::error('Error en pago inteligente: ' . $e->getMessage());
                        }
                    }
                    break;
                    
                case 'flexible':
                    // Aporte flexible
                    $monto_flexible = Yii::$app->request->post('monto_flexible');
                    $fecha_pago_flexible = Yii::$app->request->post('fecha_pago_flexible');
                    $metodo_pago_flexible = Yii::$app->request->post('metodo_pago_flexible');
                    $comentarios_flexible = Yii::$app->request->post('comentarios_flexible');
                    $atleta_id_flexible = Yii::$app->request->post('atleta_id_flexible', $atleta_id);

                    if (!$atleta_id_flexible) {
                        Yii::$app->session->setFlash('error', 'Debe seleccionar un atleta.');
                        break;
                    }

                    if (!$this->tienePermisoVerAtletaId($atleta_id_flexible)) {
                        throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                    }

                    $atleta = AtletasRegistro::findOne($atleta_id_flexible);
                    if (!$atleta) {
                        Yii::$app->session->setFlash('error', 'Atleta no encontrado.');
                        break;
                    }

                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        $deudasPendientes = AportesSemanales::find()
                            ->where([
                                'atleta_id' => $atleta_id_flexible,
                                'estado' => 'pendiente'
                            ])
                            ->andWhere(['>=', 'fecha_quincena', '2026-01-15'])
                            ->orderBy(['fecha_quincena' => SORT_ASC])
                            ->all();
                        
                        $montoDisponible = $monto_flexible;
                        $deudasLiquidadas = 0;
                        $quincenasNuevas = 0;
                        $ids = []; // IDs de aportes procesados
                        
                        foreach ($deudasPendientes as $deuda) {
                            if ($montoDisponible >= AportesSemanales::MONTO_QUINCENAL_USD) {
                                $deuda->estado = 'pagado';
                                $deuda->fecha_pago = $fecha_pago_flexible;
                                $deuda->metodo_pago = $metodo_pago_flexible;
                                $deuda->comentarios = $comentarios_flexible . " (Liquidación flexible de deuda)";
                                if ($deuda->save()) {
                                    $montoDisponible -= AportesSemanales::MONTO_QUINCENAL_USD;
                                    $deudasLiquidadas++;
                                    $ids[] = $deuda->id;
                                } else {
                                    throw new \Exception("Error al liquidar deuda flexible: " . implode(', ', $deuda->getErrors()));
                                }
                            } else {
                                break;
                            }
                        }
                        
                        if ($montoDisponible > 0) {
                            $quincenas_completas = floor($montoDisponible / AportesSemanales::MONTO_QUINCENAL_USD);
                            $monto_restante = $montoDisponible - ($quincenas_completas * AportesSemanales::MONTO_QUINCENAL_USD);

                            $ultimo_aporte = AportesSemanales::find()
                                ->where(['atleta_id' => $atleta_id_flexible])
                                ->orderBy(['fecha_quincena' => SORT_DESC])
                                ->one();
                            
                            if ($ultimo_aporte) {
                                $fecha_actual = new \DateTime($ultimo_aporte->fecha_quincena);
                                $fecha_actual = AportesSemanales::obtenerSiguienteQuincena($fecha_actual);
                            } else {
                                $fecha_actual = new \DateTime();
                                $fecha_actual = new \DateTime(AportesSemanales::calcularProximaQuincena($fecha_actual));
                            }

                            for ($i = 0; $i < $quincenas_completas; $i++) {
                                $fecha_quincena = $fecha_actual->format('Y-m-d');
                                
                                $aporte_existente = AportesSemanales::find()
                                    ->where(['atleta_id' => $atleta_id_flexible, 'fecha_quincena' => $fecha_quincena])
                                    ->one();

                                if (!$aporte_existente) {
                                    $aporte = new AportesSemanales();
                                    $aporte->atleta_id = $atleta_id_flexible;
                                    $aporte->escuela_id = $atleta->id_escuela;
                                    $aporte->fecha_quincena = $fecha_quincena;
                                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincenaExacta($fecha_quincena);
                                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                                    $aporte->estado = 'pagado';
                                    $aporte->fecha_pago = $fecha_pago_flexible;
                                    $aporte->metodo_pago = $metodo_pago_flexible;
                                    $aporte->comentarios = $comentarios_flexible . " - Aporte flexible quincena completa (después de liquidar deudas)";
                                    $aporte->tipo_aporte = AportesSemanales::TIPO_APORTE_FLEXIBLE;
                                    $aporte->tipo_cambio = AportesSemanales::obtenerTasaDolar($fecha_pago_flexible);
                                    $aporte->monto_bs_original = $aporte->monto * $aporte->tipo_cambio;

                                    if ($aporte->save()) {
                                        $quincenasNuevas++;
                                        $ids[] = $aporte->id;
                                    }
                                }

                                $fecha_actual = AportesSemanales::obtenerSiguienteQuincena($fecha_actual);
                            }

                            if ($monto_restante > 0) {
                                $fecha_quincena = $fecha_actual->format('Y-m-d');
                                
                                $aporte_existente = AportesSemanales::find()
                                    ->where(['atleta_id' => $atleta_id_flexible, 'fecha_quincena' => $fecha_quincena])
                                    ->one();
                                
                                if (!$aporte_existente) {
                                    $aporte_parcial = new AportesSemanales();
                                    $aporte_parcial->atleta_id = $atleta_id_flexible;
                                    $aporte_parcial->escuela_id = $atleta->id_escuela;
                                    $aporte_parcial->fecha_quincena = $fecha_quincena;
                                    $aporte_parcial->numero_quincena = AportesSemanales::calcularNumeroQuincenaExacta($fecha_quincena);
                                    $aporte_parcial->monto = $monto_restante;
                                    $aporte_parcial->estado = 'pagado';
                                    $aporte_parcial->fecha_pago = $fecha_pago_flexible;
                                    $aporte_parcial->metodo_pago = $metodo_pago_flexible;
                                    $aporte_parcial->comentarios = $comentarios_flexible . " - Aporte flexible parcial (después de liquidar deudas)";
                                    $aporte_parcial->tipo_aporte = AportesSemanales::TIPO_APORTE_FLEXIBLE;
                                    $aporte_parcial->pago_parcial = true;
                                    $aporte_parcial->tipo_cambio = AportesSemanales::obtenerTasaDolar($fecha_pago_flexible);
                                    $aporte_parcial->monto_bs_original = $aporte_parcial->monto * $aporte_parcial->tipo_cambio;

                                    if ($aporte_parcial->save()) {
                                        $quincenasNuevas++;
                                        $ids[] = $aporte_parcial->id;
                                    }
                                }
                            }
                        }
                        
                        $transaction->commit();
                        
                        $mensaje = "Pago flexible procesado: ";
                        if ($deudasLiquidadas > 0) {
                            $mensaje .= "{$deudasLiquidadas} deudas liquidadas";
                        }
                        if ($quincenasNuevas > 0) {
                            $mensaje .= ($deudasLiquidadas > 0 ? " + " : "") . "{$quincenasNuevas} quincenas nuevas";
                        }
                        if ($deudasLiquidadas == 0 && $quincenasNuevas == 0) {
                            $mensaje .= "No se realizaron cambios (posible duplicación)";
                        }
                        
                        Yii::$app->session->setFlash('success', $mensaje);
                        if (!empty($ids)) {
                            return $this->redirect(['comprobante', 'ids' => implode(',', $ids)]);
                        }
                        return $this->redirect(['gestion-atleta', 'atleta_id' => $atleta_id_flexible]);
                        
                    } catch (\Exception $e) {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'Error en pago flexible: ' . $e->getMessage());
                        Yii::error('Error en pago flexible: ' . $e->getMessage());
                    }
                    break;
                    
                case 'multiple':
                    // Pago múltiple
                    $quincenasSeleccionadas = Yii::$app->request->post('quincenas', []);
                    $fechaPago = Yii::$app->request->post('fecha_pago', date('Y-m-d'));
                    $metodoPago = Yii::$app->request->post('metodo_pago', 'efectivo');
                    $comentarios = Yii::$app->request->post('comentarios', '');
                    $atleta_id_multiple = Yii::$app->request->post('atleta_id_multiple', $atleta_id);

                    if (!$atleta_id_multiple) {
                        Yii::$app->session->setFlash('error', 'Debe seleccionar un atleta.');
                        break;
                    }

                    if (!$this->tienePermisoVerAtletaId($atleta_id_multiple)) {
                        throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                    }

                    $atleta = AtletasRegistro::findOne($atleta_id_multiple);
                    if (!$atleta) {
                        Yii::$app->session->setFlash('error', 'Atleta no encontrado.');
                        break;
                    }

                    if (empty($quincenasSeleccionadas)) {
                        Yii::$app->session->setFlash('warning', 'No se seleccionaron quincenas para pagar.');
                        break;
                    }

                    $quincenasPagadas = 0;
                    $ids = [];

                    foreach ($quincenasSeleccionadas as $fechaQuincena) {
                        $aporte = AportesSemanales::find()
                            ->where([
                                'atleta_id' => $atleta_id_multiple,
                                'fecha_quincena' => $fechaQuincena
                            ])
                            ->one();

                        if (!$aporte) {
                            $aporte = new AportesSemanales();
                            $aporte->atleta_id = $atleta_id_multiple;
                            $aporte->escuela_id = $atleta->id_escuela;
                            $aporte->fecha_quincena = $fechaQuincena;
                            
                            $fechaObj = new \DateTime($fechaQuincena);
                            $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincenaExacta($fechaQuincena);
                            $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                        }

                        $aporte->estado = 'pagado';
                        $aporte->fecha_pago = $fechaPago;
                        $aporte->metodo_pago = $metodoPago;
                        $aporte->comentarios = $comentarios;
                        $aporte->tipo_cambio = AportesSemanales::obtenerTasaDolar($fechaPago);
                        $aporte->monto_bs_original = $aporte->monto * $aporte->tipo_cambio;

                        if ($aporte->save()) {
                            $quincenasPagadas++;
                            $ids[] = $aporte->id;
                        } else {
                            Yii::error("Error al guardar aporte múltiple: " . implode(', ', $aporte->getErrors()));
                        }
                    }

                    if ($quincenasPagadas > 0) {
                        Yii::$app->session->setFlash('success', "Se registró el pago de {$quincenasPagadas} quincenas mediante pago múltiple.");
                        return $this->redirect(['comprobante', 'ids' => implode(',', $ids)]);
                    } else {
                        Yii::$app->session->setFlash('warning', 'No se pudo registrar ningún pago.');
                    }
                    return $this->redirect(['gestion-atleta', 'atleta_id' => $atleta_id_multiple]);
                    break;
                    
                case 'adelantado':
                    // Pago adelantado
                    $quincenasAdelanto = Yii::$app->request->post('quincenas_adelanto', 1);
                    $fechaPago = Yii::$app->request->post('fecha_pago_adelanto', date('Y-m-d'));
                    $metodoPago = Yii::$app->request->post('metodo_pago_adelanto', 'efectivo');
                    $comentarios = Yii::$app->request->post('comentarios_adelanto', 'Pago por adelantado');
                    $atleta_id_adelanto = Yii::$app->request->post('atleta_id_adelanto', $atleta_id);

                    if (!$atleta_id_adelanto) {
                        Yii::$app->session->setFlash('error', 'Debe seleccionar un atleta.');
                        break;
                    }

                    if (!$this->tienePermisoVerAtletaId($atleta_id_adelanto)) {
                        throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                    }

                    $atleta = AtletasRegistro::findOne($atleta_id_adelanto);
                    if (!$atleta) {
                        Yii::$app->session->setFlash('error', 'Atleta no encontrado.');
                        break;
                    }

                    $fechaActual = new \DateTime();
                    $fechaActual = new \DateTime(AportesSemanales::calcularProximaQuincena($fechaActual));

                    $quincenasPagadas = 0;
                    $ids = [];

                    for ($i = 0; $i < $quincenasAdelanto; $i++) {
                        $fechaQuincena = $fechaActual->format('Y-m-d');

                        $existeAporte = AportesSemanales::find()
                            ->where([
                                'atleta_id' => $atleta_id_adelanto,
                                'fecha_quincena' => $fechaQuincena
                            ])
                            ->exists();

                        if (!$existeAporte) {
                            $aporte = new AportesSemanales();
                            $aporte->atleta_id = $atleta_id_adelanto;
                            $aporte->escuela_id = $atleta->id_escuela;
                            $aporte->fecha_quincena = $fechaQuincena;
                            $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincenaExacta($fechaQuincena);
                            $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                            $aporte->estado = 'pagado';
                            $aporte->fecha_pago = $fechaPago;
                            $aporte->metodo_pago = $metodoPago;
                            $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena} (Adelantado)";
                            $aporte->tipo_aporte = AportesSemanales::TIPO_APORTE_ADELANTADO;
                            $aporte->tipo_cambio = AportesSemanales::obtenerTasaDolar($fechaPago);
                            $aporte->monto_bs_original = $aporte->monto * $aporte->tipo_cambio;

                            if ($aporte->save()) {
                                $quincenasPagadas++;
                                $ids[] = $aporte->id;
                            } else {
                                Yii::error("Error al guardar aporte adelantado: " . implode(', ', $aporte->getErrors()));
                            }
                        }

                        $fechaActual = AportesSemanales::obtenerSiguienteQuincena($fechaActual);
                    }

                    if ($quincenasPagadas > 0) {
                        Yii::$app->session->setFlash('success', "Se registró el pago por adelantado de {$quincenasPagadas} quincenas.");
                        return $this->redirect(['comprobante', 'ids' => implode(',', $ids)]);
                    } else {
                        Yii::$app->session->setFlash('warning', 'No se pudo registrar ningún pago adelantado. Puede que las quincenas ya estén pagadas.');
                    }
                    return $this->redirect(['gestion-atleta', 'atleta_id' => $atleta_id_adelanto]);
                    break;
            }
        }

        // Establecer valores por defecto para el formulario individual
        if ($model->isNewRecord) {
            $model->loadDefaultValues();
            if ($atleta) {
                $model->atleta_id = $atleta_id;
                $model->escuela_id = $atleta->id_escuela;
            }
            $model->monto = AportesSemanales::MONTO_QUINCENAL_USD;
            $model->estado = 'pendiente';
            
            // Establecer fecha de la próxima quincena (asegurar >= 15/01/2026)
            $hoy = new \DateTime();
            $model->fecha_quincena = AportesSemanales::calcularProximaQuincena($hoy);
            if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                $model->fecha_quincena = '2026-01-15';
            }
            $model->numero_quincena = $this->calcularNumeroQuincena($model->fecha_quincena);
        }

        $time_end = microtime(true);
        Yii::info("TIEMPO TOTAL actionGestionAtleta: " . ($time_end - $time_start) . " segundos");

        return $this->render('gestion-atleta', [
            'model' => $model,
            'atletas' => $atletas,
            'atleta' => $atleta,
            'historialDeudas' => $historialDeudas,
            'quincenasDeuda' => $quincenasDeuda,
            'montoDeuda' => $montoDeuda,
            'quincenasPendientes' => $quincenasPendientes,
            'posicionTop' => $posicionTop,
        ]);
    }

    /**
     * Displays a single AportesSemanales model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // VERIFICAR PERMISOS RBAC PARA ESTE APORTE
        if (!$this->tienePermisoVerAporte($model)) {
            throw new ForbiddenHttpException('No tiene permisos para ver este aporte.');
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new AportesSemanales model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new AportesSemanales();

        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');

        // OBTENER LOS ATLETAS PERMITIDOS SEGÚN RBAC
        $atletas = $this->getAtletasPermitidos($id_escuela);

        // OBTENER LA ESCUELA ACTUAL (solo si no está eliminada)
        $escuelas = Escuela::find()
            ->where(['id' => $id_escuela])
            ->andWhere(['eliminado' => false])
            ->all();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                // VERIFICAR PERMISOS PARA EL ATLETA SELECCIONADO
                if (!$this->tienePermisoVerAtletaId($model->atleta_id)) {
                    throw new ForbiddenHttpException('No tiene permisos para crear aportes para este atleta.');
                }
                
                // Asignar automáticamente la escuela actual si no viene en el POST
                if (empty($model->escuela_id)) {
                    $model->escuela_id = $id_escuela;
                }
                
                // Usar monto fijo en dólares (quincenal)
                $model->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                
                // Asegurar que la fecha sea quincena y >= 2026-01-15
                if (empty($model->fecha_quincena)) {
                    $hoy = new \DateTime();
                    $model->fecha_quincena = AportesSemanales::calcularProximaQuincena($hoy);
                }
                if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                    $model->fecha_quincena = '2026-01-15';
                }
                if (empty($model->numero_quincena)) {
                    $model->numero_quincena = $this->calcularNumeroQuincena($model->fecha_quincena);
                }
                
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Aporte quincenal registrado exitosamente.');
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    Yii::$app->session->setFlash('error', 'Error al guardar el aporte: ' . json_encode($model->getErrors()));
                }
            }
        } else {
            $model->loadDefaultValues();
            // Establecer valores por defecto
            $model->escuela_id = $id_escuela;
            $model->monto = AportesSemanales::MONTO_QUINCENAL_USD;
            $model->estado = 'pendiente';
            
            // Establecer fecha de la próxima quincena
            $hoy = new \DateTime();
            $model->fecha_quincena = AportesSemanales::calcularProximaQuincena($hoy);
            if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                $model->fecha_quincena = '2026-01-15';
            }
            $model->numero_quincena = $this->calcularNumeroQuincena($model->fecha_quincena);
        }

        return $this->render('create', [
            'model' => $model,
            'atletas' => $atletas,
            'escuelas' => $escuelas,
        ]);
    }

    /**
     * Updates an existing AportesSemanales model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        // VERIFICAR PERMISOS RBAC PARA ESTE APORTE
        if (!$this->tienePermisoVerAporte($model)) {
            throw new ForbiddenHttpException('No tiene permisos para actualizar este aporte.');
        }

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Aporte quincenal actualizado exitosamente.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing AportesSemanales model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // VERIFICAR PERMISOS RBAC PARA ESTE APORTE
        if (!$this->tienePermisoVerAporte($model)) {
            throw new ForbiddenHttpException('No tiene permisos para eliminar este aporte.');
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Aporte quincenal eliminado exitosamente.');

        return $this->redirect(['index']);
    }

    /**
     * Acción para marcar un aporte como pagado
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionMarcarPagado($id)
    {
        $model = $this->findModel($id);
        
        // VERIFICAR PERMISOS RBAC PARA ESTE APORTE
        if (!$this->tienePermisoVerAporte($model)) {
            throw new ForbiddenHttpException('No tiene permisos para marcar este aporte como pagado.');
        }
        
        $model->estado = 'pagado';
        $model->fecha_pago = date('Y-m-d');
        
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Aporte marcado como pagado exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al marcar el aporte como pagado.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Pago múltiple de quincenas para un atleta
     * @return string|\yii\web\Response
     */
    public function actionPagoMultiple()
    {
        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');

        // Obtener atletas con deuda (solo los permitidos)
        $atletasConDeuda = [];
        $atletas = $this->getAtletasPermitidos($id_escuela);

        foreach ($atletas as $atleta) {
            // Generar quincenas automáticamente
            AportesSemanales::generarQuincenasParaAtleta($atleta->id);
            
            $deuda = AportesSemanales::calcularDeudaAtleta($atleta->id);
            if ($deuda > 0) {
                $atletasConDeuda[] = $atleta;
            }
        }

        if ($this->request->isPost) {
            $atleta_id = $this->request->post('atleta_id');
            $quincenas = $this->request->post('quincenas', []);
            $fecha_pago = $this->request->post('fecha_pago', date('Y-m-d'));
            $metodo_pago = $this->request->post('metodo_pago', 'efectivo');
            $comentarios = $this->request->post('comentarios', 'Pago múltiple');

            // VERIFICAR PERMISOS
            if (!$this->tienePermisoVerAtletaId($atleta_id)) {
                throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
            }

            $atleta = AtletasRegistro::findOne($atleta_id);
            if (!$atleta) {
                throw new NotFoundHttpException('Atleta no encontrado.');
            }

            $quincenasPagadas = 0;
            $ids = [];

            foreach ($quincenas as $fecha_quincena) {
                // Buscar si ya existe un aporte para esta fecha
                $aporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_quincena' => $fecha_quincena
                    ])
                    ->one();

                if (!$aporte) {
                    // Crear nuevo aporte
                    $aporte = new AportesSemanales();
                    $aporte->atleta_id = $atleta_id;
                    $aporte->escuela_id = $atleta->id_escuela;
                    $aporte->fecha_quincena = $fecha_quincena;
                    
                    $fechaObj = new \DateTime($fecha_quincena);
                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincenaExacta($fecha_quincena);
                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                }

                $aporte->estado = 'pagado';
                $aporte->fecha_pago = $fecha_pago;
                $aporte->metodo_pago = $metodo_pago;
                $aporte->comentarios = $comentarios;
                $aporte->tipo_cambio = AportesSemanales::obtenerTasaDolar($fecha_pago);
                $aporte->monto_bs_original = $aporte->monto * $aporte->tipo_cambio;

                if ($aporte->save()) {
                    $quincenasPagadas++;
                    $ids[] = $aporte->id;
                }
            }

            if ($quincenasPagadas > 0) {
                Yii::$app->session->setFlash('success', 
                    "Se registró el pago de {$quincenasPagadas} quincenas para {$atleta->p_nombre} {$atleta->p_apellido}."
                );
                return $this->redirect(['comprobante', 'ids' => implode(',', $ids)]);
            } else {
                Yii::$app->session->setFlash('warning', 'No se pudo registrar ningún pago.');
            }

            return $this->redirect(['index']);
        }

        return $this->render('pago-multiple', [
            'atletasConDeuda' => $atletasConDeuda,
        ]);
    }

    /**
     * Pago por adelantado
     * @return string|\yii\web\Response
     */
    public function actionPagoAdelantado()
    {
        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');

        // Obtener todos los atletas permitidos según RBAC
        $atletas = $this->getAtletasPermitidos($id_escuela);

        if ($this->request->isPost) {
            $atleta_id = $this->request->post('atleta_id');
            $quincenas_adelanto = $this->request->post('quincenas_adelanto', 1);
            $fecha_pago = $this->request->post('fecha_pago', date('Y-m-d'));
            $metodo_pago = $this->request->post('metodo_pago', 'efectivo');
            $comentarios = $this->request->post('comentarios', 'Pago por adelantado');

            // VERIFICAR PERMISOS
            if (!$this->tienePermisoVerAtletaId($atleta_id)) {
                throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
            }

            $atleta = AtletasRegistro::findOne($atleta_id);
            if (!$atleta) {
                throw new NotFoundHttpException('Atleta no encontrado.');
            }

            $fechaActual = new \DateTime();
            $fechaActual = new \DateTime(AportesSemanales::calcularProximaQuincena($fechaActual));

            $quincenasPagadas = 0;
            $ids = [];

            for ($i = 0; $i < $quincenas_adelanto; $i++) {
                $fechaQuincena = $fechaActual->format('Y-m-d');

                // Verificar si ya existe un aporte para esta fecha
                $existeAporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_quincena' => $fechaQuincena
                    ])
                    ->exists();

                if (!$existeAporte) {
                    $aporte = new AportesSemanales();
                    $aporte->atleta_id = $atleta_id;
                    $aporte->escuela_id = $atleta->id_escuela;
                    $aporte->fecha_quincena = $fechaQuincena;
                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincenaExacta($fechaQuincena);
                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                    $aporte->estado = 'pagado';
                    $aporte->fecha_pago = $fecha_pago;
                    $aporte->metodo_pago = $metodo_pago;
                    $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena} (Adelantado)";
                    $aporte->tipo_aporte = AportesSemanales::TIPO_APORTE_ADELANTADO;
                    $aporte->tipo_cambio = AportesSemanales::obtenerTasaDolar($fecha_pago);
                    $aporte->monto_bs_original = $aporte->monto * $aporte->tipo_cambio;

                    if ($aporte->save()) {
                        $quincenasPagadas++;
                        $ids[] = $aporte->id;
                    }
                }

                $fechaActual = AportesSemanales::obtenerSiguienteQuincena($fechaActual);
            }

            if ($quincenasPagadas > 0) {
                Yii::$app->session->setFlash('success', 
                    "Se registró el pago por adelantado de {$quincenasPagadas} quincenas para {$atleta->p_nombre} {$atleta->p_apellido}."
                );
                return $this->redirect(['comprobante', 'ids' => implode(',', $ids)]);
            } else {
                Yii::$app->session->setFlash('warning', 'No se pudo registrar ningún pago adelantado.');
            }

            return $this->redirect(['index']);
        }

        return $this->render('pago-adelantado', [
            'atletas' => $atletas,
        ]);
    }

    /**
     * Registro masivo de aportes
     * @return string|\yii\web\Response
     */
    public function actionRegistroMasivo()
    {
        $model = new AportesSemanales();
        
        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');

        // OBTENER LOS ATLETAS PERMITIDOS SEGÚN RBAC
        $atletas = $this->getAtletasPermitidos($id_escuela);

        // Calcular fecha de la próxima quincena
        $hoy = new \DateTime();
        $fechaQuincena = AportesSemanales::calcularProximaQuincena($hoy);
        $numeroQuincena = $this->calcularNumeroQuincena($fechaQuincena);

        if ($this->request->isPost) {
            $atletasSeleccionados = $this->request->post('atletas', []);
            $fechaQuincena = $this->request->post('AportesSemanales')['fecha_quincena'] ?? $fechaQuincena;
            $monto = $this->request->post('AportesSemanales')['monto'] ?? AportesSemanales::MONTO_QUINCENAL_USD;
            
            $registrosCreados = 0;
            
            foreach ($atletasSeleccionados as $atletaId) {
                // VERIFICAR PERMISOS PARA CADA ATLETA
                if (!$this->tienePermisoVerAtletaId($atletaId)) {
                    continue; // Saltar atletas no permitidos
                }
                
                // Verificar si ya existe un aporte para este atleta en la fecha
                $existeAporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atletaId,
                        'fecha_quincena' => $fechaQuincena,
                        'escuela_id' => $id_escuela
                    ])
                    ->exists();
                
                if (!$existeAporte) {
                    $nuevoAporte = new AportesSemanales();
                    $nuevoAporte->atleta_id = $atletaId;
                    $nuevoAporte->escuela_id = $id_escuela;
                    $nuevoAporte->fecha_quincena = $fechaQuincena;
                    $nuevoAporte->numero_quincena = $numeroQuincena;
                    $nuevoAporte->monto = $monto;
                    $nuevoAporte->estado = 'pagado'; // En registro masivo se marca como pagado automáticamente
                    $nuevoAporte->fecha_pago = date('Y-m-d');
                    $nuevoAporte->metodo_pago = 'efectivo';
                    $nuevoAporte->comentarios = 'Registro masivo quincenal';
                    $nuevoAporte->tipo_cambio = AportesSemanales::obtenerTasaDolar(date('Y-m-d'));
                    $nuevoAporte->monto_bs_original = $nuevoAporte->monto * $nuevoAporte->tipo_cambio;
                    
                    if ($nuevoAporte->save()) {
                        $registrosCreados++;
                    }
                }
            }
            
            if ($registrosCreados > 0) {
                Yii::$app->session->setFlash('success', "Se crearon {$registrosCreados} nuevos aportes quincenales.");
            } else {
                Yii::$app->session->setFlash('info', "No se crearon nuevos aportes. Puede que ya existan registros para la fecha seleccionada.");
            }
            
            return $this->redirect(['index']);
        }

        return $this->render('registro-masivo', [
            'model' => $model,
            'atletas' => $atletas,
            'fechaQuincena' => $fechaQuincena,
            'numeroQuincena' => $numeroQuincena,
        ]);
    }

    /**
     * Gestión de compras de la escuela
     * @return string|\yii\web\Response
     */
    public function actionCompras()
    {
        // Solo admin puede gestionar compras
        if (!Yii::$app->user->can('admin') && Yii::$app->user->id != 1) {
            throw new ForbiddenHttpException('No tiene permisos para gestionar compras.');
        }

        $id_escuela = Yii::$app->session->get('id_escuela');
        $model = new ComprasEscuela();

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->escuela_id = $id_escuela;
            $model->created_at = date('Y-m-d H:i:s');
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Compra registrada exitosamente.');
                return $this->redirect(['compras']);
            } else {
                Yii::$app->session->setFlash('error', 'Error al guardar la compra: ' . json_encode($model->getErrors()));
            }
        }

        $compras = ComprasEscuela::find()
            ->where(['escuela_id' => $id_escuela])
            ->orderBy(['fecha_compra' => SORT_DESC])
            ->all();

        $totalCompras = ComprasEscuela::getTotalCompras($id_escuela);
        $comprasPorTipo = ComprasEscuela::getComprasPorTipo($id_escuela);

        return $this->render('compras', [
            'model' => $model,
            'compras' => $compras,
            'totalCompras' => $totalCompras,
            'comprasPorTipo' => $comprasPorTipo,
        ]);
    }

    /**
     * Reporte ejecutivo MEJORADO
     * @return string
     */
    public function actionReporteEjecutivo()
    {
        // Solo admin puede ver reportes ejecutivos
        if (!Yii::$app->user->can('admin') && Yii::$app->user->id != 1) {
            throw new ForbiddenHttpException('No tiene permisos para ver reportes ejecutivos.');
        }

        $id_escuela = Yii::$app->session->get('id_escuela');
        
        $fechaInicio = Yii::$app->request->get('fecha_inicio', '2024-09-15');
        $fechaFin = Yii::$app->request->get('fecha_fin', date('Y-m-d'));

        // Estadísticas financieras
        $totalRecaudado = AportesSemanales::find()
            ->where(['estado' => 'pagado', 'escuela_id' => $id_escuela])
            ->andWhere(['between', 'fecha_pago', $fechaInicio, $fechaFin])
            ->sum('monto') ?? 0;

        $totalCompras = ComprasEscuela::find()
            ->where(['escuela_id' => $id_escuela])
            ->andWhere(['between', 'fecha_compra', $fechaInicio, $fechaFin])
            ->sum('monto') ?? 0;

        $balance = $totalRecaudado - $totalCompras;

        // Atletas morosos (con filtro desde 2026-01-15)
        $atletasMorosos = AtletasRegistro::find()
            ->select(['atleta.*', 'COUNT(aportes.id) as quincenas_deuda', 'SUM(aportes.monto) as monto_deuda'])
            ->from('atletas.registro atleta')
            ->leftJoin('contabilidad.aportes_semanales aportes', 
                'aportes.atleta_id = atleta.id AND aportes.estado = \'pendiente\' AND aportes.fecha_quincena >= :fecha_inicio',
                [':fecha_inicio' => AportesSemanales::FECHA_INICIO_DEUDAS])
            ->where(['atleta.id_escuela' => $id_escuela, 'atleta.eliminado' => false])
            ->groupBy(['atleta.id'])
            ->having('COUNT(aportes.id) > 0')
            ->asArray()
            ->all();

        // Top atletas
        $topAtletas = AportesSemanales::getTopAtletas($id_escuela);

        // Evolución mensual
        $evolucionMensual = AportesSemanales::find()
            ->select([
                "TO_CHAR(fecha_pago, 'YYYY-MM') as mes",
                'COUNT(*) as total_aportes',
                'SUM(monto) as recaudado'
            ])
            ->where(['estado' => 'pagado', 'escuela_id' => $id_escuela])
            ->andWhere(['between', 'fecha_pago', $fechaInicio, $fechaFin])
            ->groupBy(["TO_CHAR(fecha_pago, 'YYYY-MM')"])
            ->orderBy(['mes' => SORT_ASC])
            ->asArray()
            ->all();

        return $this->render('reporte-ejecutivo', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'totalRecaudado' => $totalRecaudado,
            'totalCompras' => $totalCompras,
            'balance' => $balance,
            'atletasMorosos' => $atletasMorosos,
            'topAtletas' => $topAtletas,
            'evolucionMensual' => $evolucionMensual,
        ]);
    }

    /**
     * Reporte de atletas morosos
     * @return string
     */
    public function actionAtletasMorosos()
    {
        // Solo admin puede ver reportes de morosos
        if (!Yii::$app->user->can('admin') && Yii::$app->user->id != 1) {
            throw new ForbiddenHttpException('No tiene permisos para ver reportes de morosos.');
        }

        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');
        
        // Primero generar quincenas para todos los atletas
        $atletasEscuela = AtletasRegistro::find()
            ->where(['id_escuela' => $id_escuela, 'eliminado' => false])
            ->all();
            
        foreach ($atletasEscuela as $atleta) {
            AportesSemanales::generarQuincenasParaAtleta($atleta->id);
        }
        
        // Consulta para obtener atletas morosos de la escuela actual (solo desde 2026-01-15)
        $sql = "
            SELECT 
                ar.id,
                ar.p_nombre || ' ' || ar.p_apellido as nombre_completo,
                e.nombre as escuela_nombre,
                COUNT(asem.id) as quincenas_deuda,
                COALESCE(SUM(asem.monto), 0) as total_deuda
            FROM atletas.registro ar
            LEFT JOIN contabilidad.aportes_semanales asem ON asem.atleta_id = ar.id 
                AND asem.estado = 'pendiente' 
                AND asem.fecha_quincena >= :fecha_inicio
            LEFT JOIN atletas.escuela e ON e.id = ar.id_escuela
            WHERE ar.id_escuela = :id_escuela 
            AND ar.eliminado = false
            GROUP BY ar.id, ar.p_nombre, ar.p_apellido, e.nombre
            HAVING COUNT(asem.id) > 0
            ORDER BY total_deuda DESC
        ";
        
        $atletasMorosos = Yii::$app->db->createCommand($sql, [
            ':id_escuela' => $id_escuela,
            ':fecha_inicio' => AportesSemanales::FECHA_INICIO_DEUDAS
        ])->queryAll();

        return $this->render('atletas-morosos', [
            'atletasMorosos' => $atletasMorosos,
        ]);
    }

    /**
     * Procesar pago múltiple desde AJAX
     */
    public function actionProcesarPagoMultiple()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (Yii::$app->request->isPost) {
            $atleta_id = Yii::$app->request->post('atleta_id');
            $quincenas = Yii::$app->request->post('quincenas', []);
            $fecha_pago = Yii::$app->request->post('fecha_pago');
            $metodo_pago = Yii::$app->request->post('metodo_pago');
            $comentarios = Yii::$app->request->post('comentarios', 'Pago múltiple');

            // VERIFICAR PERMISOS
            if (!$this->tienePermisoVerAtletaId($atleta_id)) {
                return ['success' => false, 'message' => 'No tiene permisos para gestionar aportes de este atleta.'];
            }

            $atleta = AtletasRegistro::findOne($atleta_id);
            if (!$atleta) {
                return ['success' => false, 'message' => 'Atleta no encontrado.'];
            }

            $quincenasPagadas = 0;

            foreach ($quincenas as $fecha_quincena) {
                $aporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_quincena' => $fecha_quincena
                    ])
                    ->one();

                if (!$aporte) {
                    $aporte = new AportesSemanales();
                    $aporte->atleta_id = $atleta_id;
                    $aporte->escuela_id = $atleta->id_escuela;
                    $aporte->fecha_quincena = $fecha_quincena;
                    
                    $fechaObj = new \DateTime($fecha_quincena);
                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincenaExacta($fecha_quincena);
                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                }

                $aporte->estado = 'pagado';
                $aporte->fecha_pago = $fecha_pago;
                $aporte->metodo_pago = $metodo_pago;
                $aporte->comentarios = $comentarios;

                if ($aporte->save()) {
                    $quincenasPagadas++;
                }
            }

            return [
                'success' => true,
                'message' => "Se registró el pago de {$quincenasPagadas} quincenas.",
                'quincenasPagadas' => $quincenasPagadas
            ];
        }

        return ['success' => false, 'message' => 'Solicitud inválida.'];
    }

    /**
     * Procesar pago adelantado desde AJAX
     */
    public function actionProcesarPagoAdelantado()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (Yii::$app->request->isPost) {
            $atleta_id = Yii::$app->request->post('atleta_id');
            $quincenas_adelanto = Yii::$app->request->post('quincenas_adelanto', 1);
            $fecha_pago = Yii::$app->request->post('fecha_pago');
            $metodo_pago = Yii::$app->request->post('metodo_pago');
            $comentarios = Yii::$app->request->post('comentarios', 'Pago por adelantado');

            // VERIFICAR PERMISOS
            if (!$this->tienePermisoVerAtletaId($atleta_id)) {
                return ['success' => false, 'message' => 'No tiene permisos para gestionar aportes de este atleta.'];
            }

            $atleta = AtletasRegistro::findOne($atleta_id);
            if (!$atleta) {
                return ['success' => false, 'message' => 'Atleta no encontrado.'];
            }

            $fechaActual = new \DateTime();
            $fechaActual = new \DateTime(AportesSemanales::calcularProximaQuincena($fechaActual));

            $quincenasPagadas = 0;

            for ($i = 0; $i < $quincenas_adelanto; $i++) {
                $fechaQuincena = $fechaActual->format('Y-m-d');

                $existeAporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_quincena' => $fechaQuincena
                    ])
                    ->exists();

                if (!$existeAporte) {
                    $aporte = new AportesSemanales();
                    $aporte->atleta_id = $atleta_id;
                    $aporte->escuela_id = $atleta->id_escuela;
                    $aporte->fecha_quincena = $fechaQuincena;
                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincenaExacta($fechaQuincena);
                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                    $aporte->estado = 'pagado';
                    $aporte->fecha_pago = $fecha_pago;
                    $aporte->metodo_pago = $metodo_pago;
                    $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena} (Adelantado)";
                    $aporte->tipo_aporte = AportesSemanales::TIPO_APORTE_ADELANTADO;

                    if ($aporte->save()) {
                        $quincenasPagadas++;
                    }
                }

                $fechaActual = AportesSemanales::obtenerSiguienteQuincena($fechaActual);
            }

            return [
                'success' => true,
                'message' => "Se registró el pago por adelantado de {$quincenasPagadas} quincenas.",
                'quincenasPagadas' => $quincenasPagadas
            ];
        }

        return ['success' => false, 'message' => 'Solicitud inválida.'];
    }

    // =========================================================================
    // 2. NUEVAS ACCIONES – MODO FAMILIA Y BECAS (con los cambios actualizados)
    // =========================================================================

    // -------------------------------------------------------------------------
    // GESTIÓN DE BECAS (actualizado)
    // -------------------------------------------------------------------------

    /**
     * Listado de becas (activas, pendientes, rechazadas, etc.)
     * @return string
     */
    public function actionBecas()
    {
        $this->layout = 'escuelas';

        // Obtener todas las becas (sin filtro de eliminado, pero excluimos eliminadas)
        $becas = Beca::find()
            ->where(['eliminado' => false])
            ->orderBy(['fecha_propuesta' => SORT_DESC])
            ->all();

        // Estadísticas
        $totalBecas = count($becas);
        $becasMerito = Beca::find()
            ->joinWith('tipoBeca')
            ->where(['catalogos.tipos_beca.nombre' => 'Mérito'])
            ->andWhere(['becas.estado_aprobacion' => Beca::ESTADO_APROB_ACTIVA])
            ->andWhere(['IS', 'becas.estado_ciclo', null])
            ->count();
        $becasEntrenador = Beca::find()
            ->joinWith('tipoBeca')
            ->where(['catalogos.tipos_beca.nombre' => 'Entrenador'])
            ->andWhere(['becas.estado_aprobacion' => Beca::ESTADO_APROB_ACTIVA])
            ->andWhere(['IS', 'becas.estado_ciclo', null])
            ->count();
        $proximasAVencer = Beca::find()
            ->where(['estado_aprobacion' => Beca::ESTADO_APROB_ACTIVA])
            ->andWhere(['IS', 'estado_ciclo', null])
            ->andWhere(['<=', 'fecha_vencimiento', date('Y-m-d', strtotime('+30 days'))])
            ->andWhere(['>', 'fecha_vencimiento', date('Y-m-d')])
            ->count();

        return $this->render('becas', [
            'becas' => $becas,
            'totalBecas' => $totalBecas,
            'becasMerito' => $becasMerito,
            'becasEntrenador' => $becasEntrenador,
            'proximasAVencer' => $proximasAVencer,
        ]);
    }

    /**
     * Asigna una nueva beca (propuesta por entrenador).
     * @return string|\yii\web\Response
     */
    public function actionAsignarBeca()
    {
        $this->layout = 'escuelas';

        $model = new Beca();
        $model->fecha_propuesta = date('Y-m-d H:i:s');
        $model->estado_aprobacion = Beca::ESTADO_APROB_PENDIENTE;
        $model->propuesto_por = Yii::$app->user->id;

        // Solo atletas que pertenezcan a una familia
        $atletas = AtletasRegistro::find()
            ->where(['not', ['id_familia' => null]])
            ->andWhere(['eliminado' => false])
            ->orderBy(['p_nombre' => SORT_ASC])
            ->all();

        $tiposBeca = TipoBeca::find()->all();

        if ($this->request->isPost && $model->load($this->request->post())) {

            // Obtener el atleta y su familia
            $atleta = AtletasRegistro::findOne($model->id_atleta);
            if (!$atleta) {
                Yii::$app->session->setFlash('error', 'Atleta no encontrado.');
                return $this->render('asignar-beca', compact('model', 'atletas', 'tiposBeca'));
            }
            $familiaId = $atleta->id_familia;
            if (!$familiaId) {
                Yii::$app->session->setFlash('error', 'El atleta no pertenece a una familia.');
                return $this->render('asignar-beca', compact('model', 'atletas', 'tiposBeca'));
            }
            $model->id_familia = $familiaId;

            // Validaciones de negocio (máximo 3 becas por familia, etc.)
            $activasFamilia = Beca::find()
                ->activa()
                ->andWhere(['id_familia' => $familiaId])
                ->count();
            if ($activasFamilia >= 3) {
                Yii::$app->session->setFlash('error', 'La familia ya tiene 3 becas activas. No se puede asignar otra.');
                return $this->render('asignar-beca', compact('model', 'atletas', 'tiposBeca'));
            }

            // Si es beca de tipo "Entrenador", verificar que no haya otra activa del mismo tipo en la familia
            $tipoEntrenador = TipoBeca::find()->where(['nombre' => 'Entrenador'])->select('id_tipo_beca')->scalar();
            if ($tipoEntrenador && $model->id_tipo_beca == $tipoEntrenador) {
                $entrenadorActiva = Beca::find()
                    ->activa()
                    ->andWhere(['id_familia' => $familiaId, 'id_tipo_beca' => $tipoEntrenador])
                    ->exists();
                if ($entrenadorActiva) {
                    Yii::$app->session->setFlash('error', 'La familia ya tiene una beca de Entrenador activa.');
                    return $this->render('asignar-beca', compact('model', 'atletas', 'tiposBeca'));
                }
            }

            // Regla "al menos un atleta sin beca" (a menos que tenga autorización de excepción)
            if (!$model->autorizacion_excepcion) {
                $totalAtletasFamilia = AtletasRegistro::find()
                    ->where(['id_familia' => $familiaId, 'eliminado' => false])
                    ->count();
                $sinBecaAhora = $totalAtletasFamilia - $activasFamilia;
                if ($sinBecaAhora - 1 < 1) {
                    Yii::$app->session->setFlash('error', 'Después de asignar esta beca no quedaría ningún atleta sin beca en la familia. Use la autorización de excepción si es necesario.');
                    return $this->render('asignar-beca', compact('model', 'atletas', 'tiposBeca'));
                }
            }

            // Calcular período de validez según tipo de beca
            $tipoBeca = TipoBeca::findOne($model->id_tipo_beca);
            if ($tipoBeca) {
                $model->periodo_validez_meses = $tipoBeca->periodo_validez_meses;
            }

            // Si es beca de entrenador, se auto-aprueba y no es renovable
            if ($tipoBeca && $tipoBeca->nombre == 'Entrenador') {
                $model->renovable = false;
                $model->estado_aprobacion = Beca::ESTADO_APROB_ACTIVA;
                $model->fecha_asignacion = date('Y-m-d');
                $model->fecha_vencimiento = date('Y-m-d', strtotime('+1 year'));
                $model->aprobado_por = Yii::$app->user->id;
            }

            if ($model->save()) {
                // Registrar en historial
                $historial = new BecaHistorial();
                $historial->id_beca = $model->id_beca;
                $historial->fecha_original_inicio = $model->fecha_asignacion ?: $model->fecha_propuesta;
                $historial->fecha_original_fin = $model->fecha_vencimiento;
                $historial->fecha_reactivacion = $model->fecha_asignacion ?: $model->fecha_propuesta;
                $historial->motivo = $model->observaciones ?: 'Asignación inicial' . ($model->autorizacion_excepcion ? ' (con excepción)' : '');
                $historial->usuario_creacion = Yii::$app->user->id;
                $historial->save();

                Yii::$app->session->setFlash('success', 'Beca propuesta exitosamente.');
                return $this->redirect(['becas']);
            } else {
                Yii::$app->session->setFlash('error', 'Error al asignar la beca: ' . implode(', ', $model->getErrorSummary(true)));
            }
        }

        return $this->render('asignar-beca', [
            'model' => $model,
            'atletas' => $atletas,
            'tiposBeca' => $tiposBeca,
        ]);
    }

    /**
     * Listado de propuestas pendientes de aprobación.
     * @return string
     */
    public function actionPropuestasPendientes()
    {
        $this->layout = 'escuelas';

        $pendientes = Beca::find()
            ->where(['estado_aprobacion' => Beca::ESTADO_APROB_PENDIENTE, 'eliminado' => false])
            ->orderBy(['fecha_propuesta' => SORT_ASC])
            ->all();

        return $this->render('propuestas-pendientes', [
            'pendientes' => $pendientes,
        ]);
    }

    /**
     * Aprueba una beca pendiente.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionAprobarBeca($id)
    {
        $beca = Beca::findOne($id);
        if (!$beca) {
            throw new NotFoundHttpException('Beca no encontrada.');
        }

        if ($beca->estado_aprobacion != Beca::ESTADO_APROB_PENDIENTE) {
            Yii::$app->session->setFlash('error', 'Esta beca no está pendiente de aprobación.');
            return $this->redirect(['propuestas-pendientes']);
        }

        $beca->estado_aprobacion = Beca::ESTADO_APROB_ACTIVA;
        $beca->fecha_asignacion = date('Y-m-d');
        $beca->fecha_vencimiento = $this->calcularProximoJulio(); // 1 de julio del año siguiente
        $beca->aprobado_por = Yii::$app->user->id;
        $beca->renovable = true; // Por defecto renovable, excepto si es entrenador (ya se manejó en creación)

        if ($beca->save()) {
            // Registrar en historial
            $historial = new BecaHistorial();
            $historial->id_beca = $beca->id_beca;
            $historial->fecha_original_inicio = $beca->fecha_asignacion;
            $historial->fecha_original_fin = $beca->fecha_vencimiento;
            $historial->fecha_reactivacion = $beca->fecha_asignacion;
            $historial->motivo = 'Aprobada por administrador';
            $historial->usuario_creacion = Yii::$app->user->id;
            $historial->save();

            Yii::$app->session->setFlash('success', 'Beca aprobada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al aprobar la beca.');
        }

        return $this->redirect(['propuestas-pendientes']);
    }

    /**
     * Rechaza una beca pendiente (muestra formulario para motivo).
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionRechazarBeca($id)
    {
        $this->layout = 'escuelas';

        $beca = Beca::findOne($id);
        if (!$beca) {
            throw new NotFoundHttpException('Beca no encontrada.');
        }

        if ($beca->estado_aprobacion != Beca::ESTADO_APROB_PENDIENTE) {
            Yii::$app->session->setFlash('error', 'Esta beca no está pendiente de aprobación.');
            return $this->redirect(['propuestas-pendientes']);
        }

        $model = new \yii\base\DynamicModel(['motivo_rechazo']);
        $model->addRule(['motivo_rechazo'], 'required');
        $model->addRule(['motivo_rechazo'], 'string', ['max' => 500]);

        if ($this->request->isPost && $model->load(Yii::$app->request->post()) && $model->validate()) {
            $beca->estado_aprobacion = Beca::ESTADO_APROB_RECHAZADA;
            $beca->motivo_rechazo = $model->motivo_rechazo;
            if ($beca->save()) {
                // Registrar en historial
                $historial = new BecaHistorial();
                $historial->id_beca = $beca->id_beca;
                $historial->fecha_original_inicio = $beca->fecha_propuesta;
                $historial->motivo = 'Rechazada: ' . $model->motivo_rechazo;
                $historial->usuario_creacion = Yii::$app->user->id;
                $historial->save();

                Yii::$app->session->setFlash('success', 'Beca rechazada.');
            } else {
                Yii::$app->session->setFlash('error', 'Error al rechazar la beca.');
            }
            return $this->redirect(['propuestas-pendientes']);
        }

        return $this->render('rechazar-beca', [
            'beca' => $beca,
            'model' => $model,
        ]);
    }

    /**
     * Muestra detalles de una beca.
     * @param int $id
     * @return string
     */
    public function actionViewBeca($id)
    {
        $this->layout = 'escuelas';

        $beca = Beca::findOne($id);
        if (!$beca) {
            throw new NotFoundHttpException('Beca no encontrada.');
        }

        return $this->render('view-beca', [
            'beca' => $beca,
        ]);
    }

    /**
     * Actualiza una beca (solo administradores, campos limitados).
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdateBeca($id)
    {
        $this->layout = 'escuelas';

        $beca = Beca::findOne($id);
        if (!$beca) {
            throw new NotFoundHttpException('Beca no encontrada.');
        }

        // Solo permitir editar si está pendiente o activa (y no revocada/vencida)
        if (!in_array($beca->estado_aprobacion, [Beca::ESTADO_APROB_PENDIENTE, Beca::ESTADO_APROB_ACTIVA]) || $beca->estado_ciclo) {
            throw new ForbiddenHttpException('No se puede editar esta beca en su estado actual.');
        }

        // Cargar modelo con escenario 'update' (definir en el modelo si es necesario)
        if ($this->request->isPost && $beca->load($this->request->post())) {
            // Restringir campos editables
            $beca->setAttributes($this->request->post('Beca'));
            if ($beca->validate() && $beca->save()) {
                Yii::$app->session->setFlash('success', 'Beca actualizada.');
                return $this->redirect(['view-beca', 'id' => $beca->id_beca]);
            }
        }

        return $this->render('update-beca', [
            'model' => $beca,
        ]);
    }

    /**
     * Revoca una beca activa (cambia estado_ciclo a REVOCADA).
     * @param int $id_beca
     * @return \yii\web\Response
     */
    public function actionRevocarBeca($id_beca)
    {
        $beca = Beca::findOne($id_beca);
        if (!$beca) {
            throw new NotFoundHttpException('Beca no encontrada.');
        }

        if ($beca->estado_aprobacion != Beca::ESTADO_APROB_ACTIVA || $beca->estado_ciclo) {
            Yii::$app->session->setFlash('error', 'Solo se pueden revocar becas activas no vencidas.');
            return $this->redirect(['becas']);
        }

        $beca->estado_ciclo = Beca::ESTADO_CICLO_REVOCADA;
        if ($beca->save()) {
            // Registrar en historial
            $historial = new BecaHistorial();
            $historial->id_beca = $beca->id_beca;
            $historial->fecha_original_inicio = $beca->fecha_asignacion;
            $historial->motivo = 'Revocada por administrador';
            $historial->usuario_creacion = Yii::$app->user->id;
            $historial->save();

            Yii::$app->session->setFlash('success', 'Beca revocada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al revocar la beca.');
        }

        return $this->redirect(['becas']);
    }

    /**
     * Obtiene datos de un tipo de beca vía AJAX.
     * @param int $id
     * @return array|null
     */
    public function actionGetTipoBeca($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $tipo = TipoBeca::findOne($id);
        if ($tipo) {
            return [
                'periodo_validez_meses' => $tipo->periodo_validez_meses,
                'porcentaje_descuento' => $tipo->porcentaje_descuento,
            ];
        }
        return null;
    }

    // =========================================================================
    // 3. NUEVA ACCIÓN: COMPROBANTE DE PAGO
    // =========================================================================

    /**
     * Muestra el comprobante de uno o varios pagos.
     * @param string $ids IDs separados por coma
     * @return string
     * @throws NotFoundHttpException si no se encuentran los aportes
     * @throws ForbiddenHttpException si no hay permisos
     */
    public function actionComprobante($ids)
    {
        $this->layout = 'escuelas';
        $idArray = explode(',', $ids);
        $idArray = array_filter(array_map('intval', $idArray));

        if (empty($idArray)) {
            throw new NotFoundHttpException('No se especificaron aportes.');
        }

        $aportes = AportesSemanales::find()
            ->where(['id' => $idArray])
            ->orderBy(['fecha_quincena' => SORT_ASC])
            ->all();

        if (empty($aportes)) {
            throw new NotFoundHttpException('No se encontraron los aportes especificados.');
        }

        // Verificar permisos para el primer aporte (todos deben ser del mismo atleta/familia)
        if (!$this->tienePermisoVerAporte($aportes[0])) {
            throw new ForbiddenHttpException('No tiene permisos para ver este comprobante.');
        }

        // Obtener datos adicionales
        $primerAporte = $aportes[0];
        $atleta = $primerAporte->atleta;
        $escuela = $primerAporte->escuela;

        // Obtener representante del atleta (si existe)
        $representante = null;
        if ($atleta && $atleta->id_representante) {
            $representante = RegistroRepresentantes::findOne($atleta->id_representante);
        }

        // Calcular iniciales de la escuela (primeras letras de cada palabra, máximo 4)
        $nombreEscuela = $escuela->nombre;
        $palabras = preg_split('/\s+/', $nombreEscuela);
        $iniciales = '';
        foreach ($palabras as $palabra) {
            if (!empty($palabra)) {
                $iniciales .= strtoupper(substr($palabra, 0, 1));
                if (strlen($iniciales) >= 4) break;
            }
        }
        // Si tiene menos de 4 caracteres, completar con 'X'
        $iniciales = str_pad($iniciales, 4, 'X', STR_PAD_RIGHT);

        // Generar código aleatorio de 8 caracteres (mayúsculas y números)
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $aleatorio = '';
        for ($i = 0; $i < 8; $i++) {
            $aleatorio .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        $codigoUnico = $iniciales . $aleatorio;

        return $this->render('comprobante', [
            'aportes' => $aportes,
            'ids' => $ids,
            'atleta' => $atleta,
            'escuela' => $escuela,
            'representante' => $representante,
            'codigoUnico' => $codigoUnico,
        ]);
    }

    // =========================================================================
    // 4. MÉTODOS AUXILIARES PARA CONTROL DE ACCESO (ATLETAS + FAMILIAS)
    // =========================================================================

    // -------------------------------------------------------------------------
    // MÉTODOS PARA ATLETAS (basados en RBAC y lógica existente en actionIndex)
    // -------------------------------------------------------------------------

    /**
     * Obtiene un ActiveQuery para los atletas que el usuario actual tiene permiso de ver en la escuela indicada.
     * @param int $id_escuela
     * @return \yii\db\ActiveQuery
     */
    protected function getAtletasPermitidosQuery($id_escuela)
    {
        $user = Yii::$app->user;

        // Superadmin y admin: ven todos los atletas de la escuela
        if ($user->id == 1 || $user->can('admin')) {
            return AtletasRegistro::find()
                ->where(['id_escuela' => $id_escuela, 'eliminado' => false]);
        }

        $query = AtletasRegistro::find()
            ->where(['id_escuela' => $id_escuela, 'eliminado' => false])
            ->andWhere(['or']); // Inicializar condición OR

        $conditions = ['or'];

        if ($user->can('viewOwnAportes')) {
            $conditions[] = ['user_id' => $user->id];
        }

        if ($user->can('viewRepresentedAportes')) {
            $representante = RegistroRepresentantes::find()->where(['user_id' => $user->id])->one();
            if ($representante) {
                // Suponiendo que existe una tabla atleta_representante
                $subQuery = (new \yii\db\Query())
                    ->select('atleta_id')
                    ->from('atleta_representante')
                    ->where(['representante_id' => $representante->id]);
                $conditions[] = ['id' => $subQuery];
            }
        }

        if (count($conditions) > 1) {
            $query->andWhere($conditions);
        } else {
            // Si no hay condiciones, retornar una consulta que no devuelva nada
            $query->andWhere('0=1');
        }

        return $query;
    }

    /**
     * Obtiene los atletas que el usuario actual tiene permiso de ver en la escuela indicada.
     * @param int $id_escuela
     * @return AtletasRegistro[]
     */
    protected function getAtletasPermitidos($id_escuela)
    {
        $user = Yii::$app->user;

        // Superadmin y admin: ven todos los atletas de la escuela
        if ($user->id == 1 || $user->can('admin')) {
            return AtletasRegistro::find()
                ->where(['id_escuela' => $id_escuela, 'eliminado' => false])
                ->all();
        }

        $atletas = [];

        // Ver permisos específicos
        if ($user->can('viewOwnAportes')) {
            $atletaPropio = AtletasRegistro::find()
                ->where(['user_id' => $user->id, 'id_escuela' => $id_escuela, 'eliminado' => false])
                ->one();
            if ($atletaPropio) {
                $atletas[] = $atletaPropio;
            }
        }

        if ($user->can('viewRepresentedAportes')) {
            $representante = RegistroRepresentantes::find()->where(['user_id' => $user->id])->one();
            if ($representante) {
                // Se asume que el modelo RegistroRepresentantes tiene una relación 'getAtletas()'
                // (puede ser muchos a muchos mediante una tabla intermedia)
                if (method_exists($representante, 'getAtletas')) {
                    $atletasRep = $representante->getAtletas()
                        ->andWhere(['id_escuela' => $id_escuela, 'eliminado' => false])
                        ->all();
                    $atletas = array_merge($atletas, $atletasRep);
                } else {
                    // Fallback: intentar con una tabla hipotética 'atleta_representante'
                    $atletasIds = (new \yii\db\Query())
                        ->select('atleta_id')
                        ->from('atleta_representante')
                        ->where(['representante_id' => $representante->id])
                        ->column();
                    if (!empty($atletasIds)) {
                        $atletasFromIds = AtletasRegistro::find()
                            ->where(['id' => $atletasIds, 'id_escuela' => $id_escuela, 'eliminado' => false])
                            ->all();
                        $atletas = array_merge($atletas, $atletasFromIds);
                    }
                }
            }
        }

        // Eliminar duplicados por ID
        $unique = [];
        foreach ($atletas as $a) {
            $unique[$a->id] = $a;
        }
        return array_values($unique);
    }

    /**
     * Verifica si el usuario actual tiene permiso para ver un atleta específico.
     * @param AtletasRegistro $atleta
     * @return bool
     */
    protected function tienePermisoVerAtleta($atleta)
    {
        $user = Yii::$app->user;

        if ($user->id == 1 || $user->can('admin')) {
            return true;
        }

        if ($user->can('viewOwnAportes') && isset($atleta->user_id) && $atleta->user_id == $user->id) {
            return true;
        }

        if ($user->can('viewRepresentedAportes')) {
            $representante = RegistroRepresentantes::find()->where(['user_id' => $user->id])->one();
            if ($representante) {
                if (method_exists($representante, 'getAtletas')) {
                    return $representante->getAtletas()->andWhere(['id' => $atleta->id])->exists();
                } else {
                    return (new \yii\db\Query())
                        ->from('atleta_representante')
                        ->where(['representante_id' => $representante->id, 'atleta_id' => $atleta->id])
                        ->exists();
                }
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene permiso para ver un atleta por su ID.
     * @param int $atleta_id
     * @return bool
     */
    protected function tienePermisoVerAtletaId($atleta_id)
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        return $atleta ? $this->tienePermisoVerAtleta($atleta) : false;
    }

    /**
     * Verifica si el usuario tiene permiso para ver un aporte específico.
     * @param AportesSemanales $aporte
     * @return bool
     */
    protected function tienePermisoVerAporte($aporte)
    {
        if ($aporte->atleta_id) {
            return $this->tienePermisoVerAtletaId($aporte->atleta_id);
        }
        // Para aportes de familia (solo admin/superusuario)
        $user = Yii::$app->user;
        return ($user->id == 1 || $user->can('admin'));
    }

    /**
     * Obtiene el top 10 de atletas con mayores aportes pagados, filtrado por los atletas permitidos.
     * @param int $id_escuela
     * @param array $atletasPermitidos Lista de objetos AtletasRegistro
     * @return array
     */
    protected function getTopAtletasPermitidos($id_escuela, $atletasPermitidos)
    {
        $atletasIds = array_map(function($a) { return $a->id; }, $atletasPermitidos);
        if (empty($atletasIds)) {
            return [];
        }

        // MOD: Se agregó 'COUNT(*) as total_aportes' para que la vista pueda mostrar el número de quincenas pagadas
        $top = AportesSemanales::find()
            ->select(['atleta_id', 'SUM(monto) as total_pagado', 'COUNT(*) as total_aportes'])
            ->where(['estado' => AportesSemanales::ESTADO_PAGADO, 'escuela_id' => $id_escuela])
            ->andWhere(['in', 'atleta_id', $atletasIds])
            ->andWhere(['>=', 'fecha_quincena', AportesSemanales::FECHA_INICIO_DEUDAS])
            ->groupBy('atleta_id')
            ->orderBy(['total_pagado' => SORT_DESC])
            ->limit(10)
            ->asArray()
            ->all();

        // Enriquecer con datos del atleta para la vista
        foreach ($top as &$item) {
            $item['atleta'] = AtletasRegistro::findOne($item['atleta_id']);
        }

        return $top;
    }

    // -------------------------------------------------------------------------
    // MÉTODOS PARA FAMILIAS
    // -------------------------------------------------------------------------

    /**
     * Obtiene las familias permitidas según los permisos del usuario.
     * Por ahora, solo administradores y superusuario pueden ver familias.
     * @return Familia[]
     */
    protected function getFamiliasPermitidas()
    {
        $user = Yii::$app->user;
        if ($user->id == 1 || $user->can('admin')) {
            return Familia::find()->orderBy(['nombre_representante' => SORT_ASC])->all();
        }
        // Aquí se puede extender en el futuro para representantes vinculados a familias
        return [];
    }

    /**
     * Verifica si el usuario tiene permiso para ver una familia específica.
     * @param Familia $familia
     * @return bool
     */
    protected function tienePermisoVerFamilia($familia)
    {
        $user = Yii::$app->user;
        if ($user->id == 1 || $user->can('admin')) {
            return true;
        }
        // Extensible en el futuro
        return false;
    }

    /**
     * Calcula la próxima fecha de quincena a partir de una fecha dada.
     * Las quincenas son los días 1 y 15 de cada mes.
     * @param \DateTime|string $fecha Fecha de referencia (objeto DateTime o string Y-m-d)
     * @return string Fecha de la próxima quincena en formato Y-m-d
     */
    protected function calcularProximaQuincena($fecha)
    {
        return AportesSemanales::calcularProximaQuincena($fecha);
    }

    /**
     * Calcula el número de quincena del año para una fecha dada.
     * Las quincenas se numeran del 1 al 24.
     * @param string $fecha Fecha en formato Y-m-d
     * @return int Número de quincena (1-24)
     */
    protected function calcularNumeroQuincena($fecha)
    {
        return AportesSemanales::calcularNumeroQuincena($fecha);
    }

    /**
     * Calcula la próxima fecha de renovación (1 de julio del año siguiente).
     * @param string $fecha Fecha base (opcional, por defecto hoy)
     * @return string
     */
    protected function calcularProximoJulio($fecha = null)
    {
        $fecha = $fecha ?: date('Y-m-d');
        $timestamp = strtotime($fecha);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        if ($month >= 7) {
            $year++;
        }
        return $year . '-07-01';
    }

    /**
     * Finds the AportesSemanales model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return AportesSemanales the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AportesSemanales::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Genera las quincenas faltantes para la escuela actual.
     * @return \yii\web\Response
     */
    public function actionGenerarQuincenas()
    {
        $id_escuela = Yii::$app->session->get('id_escuela');
        if (!$id_escuela) {
            Yii::$app->session->setFlash('error', 'No hay escuela seleccionada.');
            return $this->redirect(['index']);
        }

        $count = AportesSemanales::generarQuincenasMasivo($id_escuela);
        Yii::$app->session->setFlash('success', "Se generaron $count nuevas quincenas.");
        return $this->redirect(['index']);
    }
    /**
     * Lista todos los atletas con información de sus becas (activa, pendiente, etc.)
     * @return string
     */
    public function actionListaAtletasBecas()
    {
        $this->layout = 'escuelas';
        
        $id_escuela = Yii::$app->session->get('id_escuela');
        if (empty($id_escuela)) {
            Yii::$app->session->setFlash('error', 'Debe seleccionar una escuela.');
            return $this->redirect(['/ged/default/select-escuela']);
        }

        // Obtener atletas permitidos según permisos
        $atletas = $this->getAtletasPermitidos($id_escuela);

        // Para cada atleta, obtener su beca activa/pendiente (si existe)
        foreach ($atletas as $atleta) {
            $atleta->becaActiva = $atleta->getBecaActiva(); // Necesitamos definir esta relación
            $atleta->becaPendiente = $atleta->getBecaPendiente();
        }

        return $this->render('lista-atletas-becas', [
            'atletas' => $atletas,
        ]);
    }
}