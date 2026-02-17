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

// =========================================================================
// NUEVOS MODELOS PARA EL SISTEMA DE FAMILIAS Y BECAS
// =========================================================================
use app\models\Familia;
use app\models\Beca;
use app\models\TipoBeca;
use app\models\ConfiguracionAporte;

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
                        // NUEVAS REGLAS PARA FAMILIAS Y BECAS
                        // Solo administradores y superusuario (ID 1)
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
                                'asignar-beca',
                                'revocar-beca',
                                'configuracion-aporte',
                            ],
                            'matchCallback' => function ($rule, $action) {
                                $user = Yii::$app->user;
                                // Superusuario (ID 1) o usuario con rol admin
                                return $user->id == 1 || $user->can('admin');
                            },
                        ],
                    ],
                ],
            ]
        );
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
        $this->layout = 'escuelas'; 
        
        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');
        
        if (!$id_escuela) {
            Yii::$app->session->setFlash('error', 'No se ha seleccionado una escuela. Por favor, seleccione una escuela primero.');
            return $this->redirect(['/site/index']);
        }

        Yii::info("Buscando atletas para escuela ID: " . $id_escuela);

        // OBTENER ATLETAS SEGÚN PERMISOS RBAC
        $atletas = $this->getAtletasPermitidos($id_escuela);

        Yii::info("Atletas encontrados: " . count($atletas));

        if (empty($atletas)) {
            // Determinar el motivo por el cual no hay atletas
            $user = Yii::$app->user;
            $mensaje = '';
            
            if ($user->id == 1 || $user->can('admin')) {
                $mensaje = 'No se encontraron atletas registrados en esta escuela.';
            } elseif ($user->can('viewOwnAportes')) {
                $mensaje = 'No tienes un perfil de atleta asignado a tu usuario en esta escuela.';
            } elseif ($user->can('viewRepresentedAportes')) {
                $representante = RegistroRepresentantes::find()->where(['user_id' => $user->id])->one();
                if ($representante) {
                    $mensaje = 'No tienes atletas asignados como representante en esta escuela.';
                } else {
                    $mensaje = 'No estás registrado como representante.';
                }
            } else {
                $mensaje = 'No tienes permisos para ver atletas.';
            }
            
            Yii::$app->session->setFlash('warning', $mensaje);
            return $this->render('index', [
                'atletasConEstadisticas' => [],
                'totalRecaudado' => 0,
                'pendientes' => 0,
                'deudaTotal' => 0,
                'atletasConDeuda' => 0,
                'topAtletas' => [],
                'totalAtletas' => 0,
                'erroresProcesamiento' => []
            ]);
        }

        // Calcular estadísticas para cada atleta (SOLO DESDE 15/01/2026)
        $atletasConEstadisticas = [];
        $totalRecaudado = 0;
        $deudaTotal = 0;
        $atletasConDeuda = 0;
        $erroresProcesamiento = [];

        foreach ($atletas as $atleta) {
            try {
                Yii::info("=== PROCESANDO ATLETA: " . $atleta->id . " - " . $atleta->p_nombre . " " . $atleta->p_apellido . " ===");
                
                // Verificar permisos específicos para este atleta
                if (!$this->tienePermisoVerAtleta($atleta)) {
                    Yii::info("Usuario no tiene permisos para ver atleta: " . $atleta->id);
                    continue;
                }

                // Fecha de creación del atleta para debug
                $fechaCreacion = $atleta->fecha_creacion ?? 'No especificada';
                Yii::info("Fecha creación atleta: " . $fechaCreacion);

                // GENERAR QUINCENAS AUTOMÁTICAMENTE desde la fecha de inicio (15/01/2026)
                $quincenasGeneradas = 0;
                try {
                    $quincenasGeneradas = AportesSemanales::generarQuincenasParaAtleta($atleta->id);
                    Yii::info("Quincenas generadas para atleta {$atleta->id}: {$quincenasGeneradas}");
                } catch (\Exception $e) {
                    $errorMsg = "Error generando quincenas para atleta {$atleta->id}: " . $e->getMessage();
                    Yii::error($errorMsg);
                    $erroresProcesamiento[] = $errorMsg;
                }
                
                // Calcular montos PAGADOS (SOLO DESDE 15/01/2026)
                $montoPagado = 0;
                try {
                    $montoPagado = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id, 'estado' => 'pagado'])
                        ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                        ->sum('monto');
                    $montoPagado = $montoPagado ? floatval($montoPagado) : 0;
                    Yii::info("Monto pagado atleta {$atleta->id}: {$montoPagado}");
                } catch (\Exception $e) {
                    $errorMsg = "Error calculando monto pagado para atleta {$atleta->id}: " . $e->getMessage();
                    Yii::warning($errorMsg);
                    $erroresProcesamiento[] = $errorMsg;
                }

                // Calcular montos PENDIENTES (deuda) (SOLO DESDE 15/01/2026)
                $montoDeuda = 0;
                try {
                    $montoDeuda = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id, 'estado' => 'pendiente'])
                        ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                        ->sum('monto');
                    $montoDeuda = $montoDeuda ? floatval($montoDeuda) : 0;
                    Yii::info("Monto deuda atleta {$atleta->id}: {$montoDeuda}");
                } catch (\Exception $e) {
                    $errorMsg = "Error calculando monto deuda para atleta {$atleta->id}: " . $e->getMessage();
                    Yii::warning($errorMsg);
                    $erroresProcesamiento[] = $errorMsg;
                }

                // Calcular adelantados (pagos con fecha_quincena futura) (SOLO DESDE 15/01/2026)
                $montoAdelantado = 0;
                $quincenasAdelantadas = 0;
                try {
                    $hoy = date('Y-m-d');
                    $montoAdelantado = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id, 'estado' => 'pagado'])
                        ->andWhere(['>', 'fecha_quincena', $hoy])
                        ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                        ->sum('monto');
                    $montoAdelantado = $montoAdelantado ? floatval($montoAdelantado) : 0;
                    $quincenasAdelantadas = $montoAdelantado / AportesSemanales::MONTO_QUINCENAL_USD;
                    Yii::info("Monto adelantado atleta {$atleta->id}: {$montoAdelantado}");
                } catch (\Exception $e) {
                    $errorMsg = "Error calculando adelantados para atleta {$atleta->id}: " . $e->getMessage();
                    Yii::warning($errorMsg);
                    $erroresProcesamiento[] = $errorMsg;
                }

                // Información detallada del atleta (SOLO DESDE 15/01/2026)
                $totalQuincenas = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta->id])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                    ->count();
                $quincenasPagadas = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta->id, 'estado' => 'pagado'])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                    ->count();
                $quincenasPendientes = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta->id, 'estado' => 'pendiente'])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                    ->count();
                
                Yii::info("RESUMEN ATLETA {$atleta->id}: Total quincenas: {$totalQuincenas}, Pagadas: {$quincenasPagadas}, Pendientes: {$quincenasPendientes}");

                $atletasConEstadisticas[] = [
                    'atleta' => $atleta,
                    'montoPagado' => $montoPagado,
                    'montoDeuda' => $montoDeuda,
                    'montoAdelantado' => $montoAdelantado,
                    'quincenasAdelantadas' => $quincenasAdelantadas,
                    'quincenasGeneradas' => $quincenasGeneradas,
                    'totalQuincenas' => $totalQuincenas,
                    'quincenasPagadas' => $quincenasPagadas,
                    'quincenasPendientes' => $quincenasPendientes,
                    'error' => false
                ];

                // Acumular para estadísticas generales
                $totalRecaudado += $montoPagado;
                $deudaTotal += $montoDeuda;
                if ($montoDeuda > 0) {
                    $atletasConDeuda++;
                }

            } catch (\Exception $e) {
                $errorMsg = "Error crítico procesando atleta {$atleta->id}: " . $e->getMessage();
                Yii::error($errorMsg);
                $erroresProcesamiento[] = $errorMsg;
                
                // Incluir el atleta incluso si hay error, con valores por defecto
                $atletasConEstadisticas[] = [
                    'atleta' => $atleta,
                    'montoPagado' => 0,
                    'montoDeuda' => 0,
                    'montoAdelantado' => 0,
                    'quincenasAdelantadas' => 0,
                    'quincenasGeneradas' => 0,
                    'totalQuincenas' => 0,
                    'quincenasPagadas' => 0,
                    'quincenasPendientes' => 0,
                    'error' => true
                ];
            }
        }

        // DEBUG: Información final detallada
        Yii::info("=== RESUMEN FINAL PROCESAMIENTO ===");
        Yii::info("Total atletas encontrados: " . count($atletas));
        Yii::info("Total atletas procesados: " . count($atletasConEstadisticas));
        Yii::info("Total recaudado: " . $totalRecaudado);
        Yii::info("Deuda total: " . $deudaTotal);
        Yii::info("Atletas con deuda: " . $atletasConDeuda);
        Yii::info("Errores durante procesamiento: " . count($erroresProcesamiento));

        // Estadísticas generales (SOLO DESDE 15/01/2026)
        $pendientes = 0;
        try {
            $atletasPermitidosIds = array_map(function($a) { return $a->id; }, $atletas);
            $pendientes = AportesSemanales::find()
                ->where(['estado' => 'pendiente', 'escuela_id' => $id_escuela])
                ->andWhere(['in', 'atleta_id', $atletasPermitidosIds])
                ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                ->count();
            Yii::info("Total aportes pendientes en escuela: " . $pendientes);
        } catch (\Exception $e) {
            Yii::warning("Error contando pendientes: " . $e->getMessage());
            $pendientes = 0;
        }

        $topAtletas = [];
        try {
            $topAtletas = $this->getTopAtletasPermitidos($id_escuela, $atletas);
            Yii::info("Top atletas encontrados: " . count($topAtletas));
        } catch (\Exception $e) {
            Yii::warning("Error obteniendo top atletas: " . $e->getMessage());
            $topAtletas = [];
        }

        return $this->render('index', [
            'atletasConEstadisticas' => $atletasConEstadisticas,
            'totalRecaudado' => $totalRecaudado,
            'pendientes' => $pendientes,
            'deudaTotal' => $deudaTotal,
            'atletasConDeuda' => $atletasConDeuda,
            'topAtletas' => $topAtletas,
            'totalAtletas' => count($atletas),
            'erroresProcesamiento' => $erroresProcesamiento
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
        $this->layout = 'escuelas'; 
        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
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

        // Si se seleccionó un atleta
        if ($atleta_id) {
            $atleta = AtletasRegistro::findOne($atleta_id);
            if ($atleta) {
                // VERIFICAR PERMISOS RBAC PARA ESTE ATLETA
                if (!$this->tienePermisoVerAtleta($atleta)) {
                    throw new ForbiddenHttpException('No tiene permisos para gestionar los aportes de este atleta.');
                }
                
                // Verificar que el atleta pertenece a la escuela
                if ($atleta->id_escuela != $id_escuela) {
                    Yii::$app->session->setFlash('error', 'El atleta no pertenece a su escuela.');
                    return $this->redirect(['gestion-atleta']);
                }
                
                // Generar quincenas automáticamente (SOLO DESDE 15/01/2026)
                AportesSemanales::generarQuincenasParaAtleta($atleta_id);
                
                // Obtener información de deudas (SOLO DESDE 15/01/2026)
                $historialDeudas = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta_id])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                    ->orderBy(['fecha_quincena' => SORT_ASC])
                    ->asArray()
                    ->all();
                    
                $quincenasDeuda = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta_id, 'estado' => 'pendiente'])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                    ->count();
                    
                $montoDeuda = AportesSemanales::find()
                    ->where(['atleta_id' => $atleta_id, 'estado' => 'pendiente'])
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                    ->sum('monto');
                $montoDeuda = $montoDeuda ? floatval($montoDeuda) : 0;
                    
                $quincenasPendientes = array_filter($historialDeudas, function($quincena) {
                    return $quincena['estado'] == 'pendiente';
                });
            }
        }

        // OBTENER ATLETAS PERMITIDOS SEGÚN RBAC
        $atletas = $this->getAtletasPermitidos($id_escuela);

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
                            if (!empty($deudasPendientes)) {
                                foreach ($deudasPendientes as $deuda) {
                                    $deuda->estado = 'pagado';
                                    $deuda->fecha_pago = $model->fecha_pago;
                                    $deuda->metodo_pago = $model->metodo_pago;
                                    $deuda->comentarios = $model->comentarios . " (Liquidación de deuda pendiente)";
                                    if ($deuda->save()) {
                                        $deudasLiquidadas++;
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
                                    Yii::$app->session->setFlash('success', 'Aporte individual registrado exitosamente.');
                                } else {
                                    throw new \Exception('Error al guardar el aporte: ' . implode(', ', $model->getErrorSummary(true)));
                                }
                            }
                            $transaction->commit();
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
                        
                        foreach ($deudasPendientes as $deuda) {
                            if ($montoDisponible >= AportesSemanales::MONTO_QUINCENAL_USD) {
                                $deuda->estado = 'pagado';
                                $deuda->fecha_pago = $fecha_pago_flexible;
                                $deuda->metodo_pago = $metodo_pago_flexible;
                                $deuda->comentarios = $comentarios_flexible . " (Liquidación flexible de deuda)";
                                if ($deuda->save()) {
                                    $montoDisponible -= AportesSemanales::MONTO_QUINCENAL_USD;
                                    $deudasLiquidadas++;
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
                                $fecha_actual->modify('+15 days');
                            } else {
                                $fecha_actual = new \DateTime();
                                $fecha_actual = new \DateTime($this->calcularProximaQuincena($fecha_actual));
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
                                    $aporte->numero_quincena = $this->calcularNumeroQuincena($fecha_quincena);
                                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                                    $aporte->estado = 'pagado';
                                    $aporte->fecha_pago = $fecha_pago_flexible;
                                    $aporte->metodo_pago = $metodo_pago_flexible;
                                    $aporte->comentarios = $comentarios_flexible . " - Aporte flexible quincena completa (después de liquidar deudas)";
                                    $aporte->tipo_aporte = AportesSemanales::TIPO_APORTE_FLEXIBLE;

                                    if ($aporte->save()) {
                                        $quincenasNuevas++;
                                    }
                                }

                                $fecha_actual->modify('+15 days');
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
                                    $aporte_parcial->numero_quincena = $this->calcularNumeroQuincena($fecha_quincena);
                                    $aporte_parcial->monto = $monto_restante;
                                    $aporte_parcial->estado = 'pagado';
                                    $aporte_parcial->fecha_pago = $fecha_pago_flexible;
                                    $aporte_parcial->metodo_pago = $metodo_pago_flexible;
                                    $aporte_parcial->comentarios = $comentarios_flexible . " - Aporte flexible parcial (después de liquidar deudas)";
                                    $aporte_parcial->tipo_aporte = AportesSemanales::TIPO_APORTE_FLEXIBLE;
                                    $aporte_parcial->pago_parcial = true;

                                    if ($aporte_parcial->save()) {
                                        $quincenasNuevas++;
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
                            $aporte->numero_quincena = $this->calcularNumeroQuincena($fechaQuincena);
                            $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                        }

                        $aporte->estado = 'pagado';
                        $aporte->fecha_pago = $fechaPago;
                        $aporte->metodo_pago = $metodoPago;
                        $aporte->comentarios = $comentarios;

                        if ($aporte->save()) {
                            $quincenasPagadas++;
                        } else {
                            Yii::error("Error al guardar aporte múltiple: " . implode(', ', $aporte->getErrors()));
                        }
                    }

                    if ($quincenasPagadas > 0) {
                        Yii::$app->session->setFlash('success', "Se registró el pago de {$quincenasPagadas} quincenas mediante pago múltiple.");
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
                    $fechaActual = new \DateTime($this->calcularProximaQuincena($fechaActual));

                    $quincenasPagadas = 0;

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
                            $aporte->numero_quincena = $this->calcularNumeroQuincena($fechaQuincena);
                            $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                            $aporte->estado = 'pagado';
                            $aporte->fecha_pago = $fechaPago;
                            $aporte->metodo_pago = $metodoPago;
                            $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena} (Adelantado)";
                            $aporte->tipo_aporte = AportesSemanales::TIPO_APORTE_ADELANTADO;

                            if ($aporte->save()) {
                                $quincenasPagadas++;
                            } else {
                                Yii::error("Error al guardar aporte adelantado: " . implode(', ', $aporte->getErrors()));
                            }
                        }

                        $fechaActual->modify('+15 days');
                    }

                    if ($quincenasPagadas > 0) {
                        Yii::$app->session->setFlash('success', "Se registró el pago por adelantado de {$quincenasPagadas} quincenas.");
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
            $model->fecha_quincena = $this->calcularProximaQuincena($hoy);
            if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                $model->fecha_quincena = '2026-01-15';
            }
            $model->numero_quincena = $this->calcularNumeroQuincena($model->fecha_quincena);
        }

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
                    $model->fecha_quincena = $this->calcularProximaQuincena($hoy);
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
            $model->fecha_quincena = $this->calcularProximaQuincena($hoy);
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
                    $aporte->numero_quincena = $this->calcularNumeroQuincena($fecha_quincena);
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

            if ($quincenasPagadas > 0) {
                Yii::$app->session->setFlash('success', 
                    "Se registró el pago de {$quincenasPagadas} quincenas para {$atleta->p_nombre} {$atleta->p_apellido}."
                );
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
            $fechaActual = new \DateTime($this->calcularProximaQuincena($fechaActual));

            $quincenasPagadas = 0;

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
                    $aporte->numero_quincena = $this->calcularNumeroQuincena($fechaQuincena);
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

                $fechaActual->modify('+15 days');
            }

            if ($quincenasPagadas > 0) {
                Yii::$app->session->setFlash('success', 
                    "Se registró el pago por adelantado de {$quincenasPagadas} quincenas para {$atleta->p_nombre} {$atleta->p_apellido}."
                );
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
        $fechaQuincena = $this->calcularProximaQuincena($hoy);
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

        // Atletas morosos
        $atletasMorosos = AtletasRegistro::find()
            ->select(['atleta.*', 'COUNT(aportes.id) as quincenas_deuda', 'SUM(aportes.monto) as monto_deuda'])
            ->from('atletas.registro atleta')
            ->leftJoin('contabilidad.aportes_semanales aportes', 'aportes.atleta_id = atleta.id AND aportes.estado = \'pendiente\'')
            ->where(['atleta.id_escuela' => $id_escuela, 'atleta.eliminado' => false])
            ->andWhere(['>=', 'aportes.fecha_quincena', '2026-01-15'])
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
            LEFT JOIN contabilidad.aportes_semanales asem ON asem.atleta_id = ar.id AND asem.estado = 'pendiente' AND asem.fecha_quincena >= '2026-01-15'
            LEFT JOIN atletas.escuela e ON e.id = ar.id_escuela
            WHERE ar.id_escuela = :id_escuela 
            AND ar.eliminado = false
            GROUP BY ar.id, ar.p_nombre, ar.p_apellido, e.nombre
            HAVING COUNT(asem.id) > 0
            ORDER BY total_deuda DESC
        ";
        
        $atletasMorosos = Yii::$app->db->createCommand($sql, [':id_escuela' => $id_escuela])->queryAll();

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
                    $aporte->numero_quincena = $this->calcularNumeroQuincena($fecha_quincena);
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
            $fechaActual = new \DateTime($this->calcularProximaQuincena($fechaActual));

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
                    $aporte->numero_quincena = $this->calcularNumeroQuincena($fechaQuincena);
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

                $fechaActual->modify('+15 days');
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
    // 2. NUEVAS ACCIONES – MODO FAMILIA Y BECAS
    // =========================================================================

    /**
     * Listado de familias con resumen de sus aportes quincenales.
     * @return string
     */
    public function actionFamilias()
    {
        $this->layout = 'escuelas';

        // Obtener familias permitidas según permisos
        $familias = $this->getFamiliasPermitidas();

        $familiasConEstadisticas = [];
        $totalRecaudadoFamilias = 0;
        $deudaTotalFamilias = 0;
        $familiasConDeuda = 0;

        foreach ($familias as $familia) {
            // Generar quincenas para la familia automáticamente
            $quincenasGeneradas = AportesSemanales::generarQuincenasParaFamilia($familia->id_familia);

            // Calcular aportes pagados y pendientes
            $aportes = AportesSemanales::find()
                ->where(['id_familia' => $familia->id_familia])
                ->andWhere(['>=', 'fecha_quincena', AportesSemanales::FECHA_INICIO_DEUDAS])
                ->all();

            $montoPagado = 0;
            $montoPendiente = 0;
            $quincenasPagadas = 0;
            $quincenasPendientes = 0;

            foreach ($aportes as $aporte) {
                if ($aporte->estado == AportesSemanales::ESTADO_PAGADO) {
                    $montoPagado += $aporte->monto;
                    $quincenasPagadas++;
                } else {
                    $montoPendiente += $aporte->monto;
                    $quincenasPendientes++;
                }
            }

            $totalRecaudadoFamilias += $montoPagado;
            $deudaTotalFamilias += $montoPendiente;
            if ($montoPendiente > 0) {
                $familiasConDeuda++;
            }

            $familiasConEstadisticas[] = [
                'familia' => $familia,
                'montoPagado' => $montoPagado,
                'montoPendiente' => $montoPendiente,
                'quincenasPagadas' => $quincenasPagadas,
                'quincenasPendientes' => $quincenasPendientes,
                'quincenasGeneradas' => $quincenasGeneradas,
                'totalAtletas' => count($familia->atletas),
            ];
        }

        return $this->render('familias', [
            'familiasConEstadisticas' => $familiasConEstadisticas,
            'totalRecaudadoFamilias' => $totalRecaudadoFamilias,
            'deudaTotalFamilias' => $deudaTotalFamilias,
            'familiasConDeuda' => $familiasConDeuda,
            'totalFamilias' => count($familias),
        ]);
    }

    /**
     * Genera quincenas para todas las familias (desde 15/01/2026 o fecha de creación).
     * @return \yii\web\Response
     */
    public function actionGenerarQuincenasFamilias()
    {
        $generadas = AportesSemanales::generarQuincenasTodasFamilias();
        Yii::$app->session->setFlash('success', "Se generaron {$generadas} nuevas quincenas para las familias.");
        return $this->redirect(['familias']);
    }

    /**
     * Vista de gestión de aportes de una familia específica.
     * @param int $id_familia
     * @return string
     */
    public function actionGestionFamilia($id_familia)
    {
        $this->layout = 'escuelas';

        $familia = Familia::findOne($id_familia);
        if (!$familia) {
            throw new NotFoundHttpException('La familia no existe.');
        }

        // Verificar permisos
        if (!$this->tienePermisoVerFamilia($familia)) {
            throw new ForbiddenHttpException('No tiene permisos para gestionar los aportes de esta familia.');
        }

        // Generar quincenas automáticamente
        AportesSemanales::generarQuincenasParaFamilia($id_familia);

        // Obtener todos los aportes de la familia
        $aportes = AportesSemanales::find()
            ->where(['id_familia' => $id_familia])
            ->andWhere(['>=', 'fecha_quincena', AportesSemanales::FECHA_INICIO_DEUDAS])
            ->orderBy(['fecha_quincena' => SORT_DESC])
            ->all();

        // Estadísticas
        $totalPagado = 0;
        $totalPendiente = 0;
        $quincenasPagadas = 0;
        $quincenasPendientes = 0;

        foreach ($aportes as $aporte) {
            if ($aporte->estado == AportesSemanales::ESTADO_PAGADO) {
                $totalPagado += $aporte->monto;
                $quincenasPagadas++;
            } else {
                $totalPendiente += $aporte->monto;
                $quincenasPendientes++;
            }
        }

        // Calcular aporte base actual
        $aporteBase = $familia->getAporteBase();

        // Descuentos
        $descuentoMultiple = $familia->getDescuentoMultipleAtletas() * 100; // porcentaje
        $becasActivas = [];
        foreach ($familia->atletas as $atleta) {
            $beca = $atleta->getBecaActiva();
            if ($beca) {
                $becasActivas[] = [
                    'atleta' => $atleta,
                    'beca' => $beca,
                ];
            }
        }

        return $this->render('gestion-familia', [
            'familia' => $familia,
            'aportes' => $aportes,
            'totalPagado' => $totalPagado,
            'totalPendiente' => $totalPendiente,
            'quincenasPagadas' => $quincenasPagadas,
            'quincenasPendientes' => $quincenasPendientes,
            'aporteBase' => $aporteBase,
            'descuentoMultiple' => $descuentoMultiple,
            'becasActivas' => $becasActivas,
        ]);
    }

    /**
     * Marca un aporte familiar como pagado.
     * @param int $id_aporte
     * @return \yii\web\Response
     */
    public function actionPagarAporteFamilia($id_aporte)
    {
        $aporte = AportesSemanales::findOne($id_aporte);
        if (!$aporte) {
            throw new NotFoundHttpException('Aporte no encontrado.');
        }

        // Verificar que sea un aporte familiar
        if ($aporte->id_familia === null) {
            throw new ForbiddenHttpException('Este aporte no corresponde a una familia.');
        }

        $familia = Familia::findOne($aporte->id_familia);
        if (!$familia || !$this->tienePermisoVerFamilia($familia)) {
            throw new ForbiddenHttpException('No tiene permisos para modificar este aporte.');
        }

        $aporte->marcarPagado();
        Yii::$app->session->setFlash('success', 'Aporte marcado como pagado exitosamente.');
        return $this->redirect(['gestion-familia', 'id_familia' => $aporte->id_familia]);
    }

    /**
     * Reporte ejecutivo de aportes por familias.
     * @return string
     */
    public function actionReporteFamilias()
    {
        if (!Yii::$app->user->can('admin') && Yii::$app->user->id != 1) {
            throw new ForbiddenHttpException('No tiene permisos para ver este reporte.');
        }

        $fechaInicio = Yii::$app->request->get('fecha_inicio', AportesSemanales::FECHA_INICIO_DEUDAS);
        $fechaFin = Yii::$app->request->get('fecha_fin', date('Y-m-d'));

        $resumen = AportesSemanales::resumenPorFamilia($fechaInicio, $fechaFin);

        // Datos adicionales de las familias
        foreach ($resumen as &$item) {
            $familia = Familia::findOne($item['id_familia']);
            $item['nombre_representante'] = $familia ? $familia->nombre_representante : 'N/A';
            $item['total_atletas'] = $familia ? count($familia->atletas) : 0;
        }

        // Totales generales
        $totalAportado = array_sum(array_column($resumen, 'total_aportado'));
        $totalPagado = array_sum(array_column($resumen, 'total_pagado'));
        $totalPendiente = $totalAportado - $totalPagado;

        return $this->render('reporte-familias', [
            'resumen' => $resumen,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'totalAportado' => $totalAportado,
            'totalPagado' => $totalPagado,
            'totalPendiente' => $totalPendiente,
        ]);
    }

    // -------------------------------------------------------------------------
    // GESTIÓN DE BECAS
    // -------------------------------------------------------------------------

    /**
     * Listado de becas activas.
     * @return string
     */
    public function actionBecas()
    {
        $this->layout = 'escuelas';

        $becas = Beca::find()
            ->where(['<=', 'fecha_asignacion', date('Y-m-d')])
            ->andWhere(['or', ['fecha_vencimiento' => null], ['>=', 'fecha_vencimiento', date('Y-m-d')]])
            ->orderBy(['fecha_asignacion' => SORT_DESC])
            ->all();

        $tiposBeca = TipoBeca::find()->all();

        // Estadísticas
        $totalBecas = count($becas);
        $becasMerito = Beca::find()
            ->joinWith('tipoBeca')
            ->where(['tipos_beca.nombre' => 'Mérito'])
            ->andWhere(['<=', 'becas.fecha_asignacion', date('Y-m-d')])
            ->andWhere(['or', ['becas.fecha_vencimiento' => null], ['>=', 'becas.fecha_vencimiento', date('Y-m-d')]])
            ->count();
        $becasEntrenador = Beca::find()
            ->joinWith('tipoBeca')
            ->where(['tipos_beca.nombre' => 'Entrenador'])
            ->andWhere(['<=', 'becas.fecha_asignacion', date('Y-m-d')])
            ->andWhere(['or', ['becas.fecha_vencimiento' => null], ['>=', 'becas.fecha_vencimiento', date('Y-m-d')]])
            ->count();

        return $this->render('becas', [
            'becas' => $becas,
            'tiposBeca' => $tiposBeca,
            'totalBecas' => $totalBecas,
            'becasMerito' => $becasMerito,
            'becasEntrenador' => $becasEntrenador,
        ]);
    }

    /**
     * Asigna una beca a un atleta.
     * @return string|\yii\web\Response
     */
    public function actionAsignarBeca()
    {
        $this->layout = 'escuelas';

        $model = new Beca();
        $model->fecha_asignacion = date('Y-m-d');
        $model->estado = Beca::ESTADO_ACTIVA;

        // Solo se pueden asignar becas a atletas de familias (opcional: todos los atletas)
        $atletas = AtletasRegistro::find()
            ->where(['not', ['id_familia' => null]])
            ->andWhere(['eliminado' => false])
            ->orderBy(['p_nombre' => SORT_ASC])
            ->all();

        $tiposBeca = TipoBeca::find()->all();

        if ($this->request->isPost && $model->load($this->request->post())) {
            // Verificar que no exista una beca activa del mismo tipo para el atleta
            $activa = Beca::find()
                ->where(['id_atleta' => $model->id_atleta, 'id_tipo_beca' => $model->id_tipo_beca])
                ->andWhere(['<=', 'fecha_asignacion', date('Y-m-d')])
                ->andWhere(['or', ['fecha_vencimiento' => null], ['>=', 'fecha_vencimiento', date('Y-m-d')]])
                ->exists();

            if ($activa) {
                Yii::$app->session->setFlash('error', 'El atleta ya tiene una beca activa de este tipo.');
            } else {
                // Calcular fecha de vencimiento según período del tipo de beca
                $tipoBeca = TipoBeca::findOne($model->id_tipo_beca);
                if ($tipoBeca && $tipoBeca->periodo_validez_meses) {
                    $fecha = new \DateTime($model->fecha_asignacion);
                    $fecha->modify('+' . $tipoBeca->periodo_validez_meses . ' months');
                    $model->fecha_vencimiento = $fecha->format('Y-m-d');
                }
                // Asignar familia si no viene
                if (!$model->id_familia) {
                    $atleta = AtletasRegistro::findOne($model->id_atleta);
                    $model->id_familia = $atleta ? $atleta->id_familia : null;
                }
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Beca asignada exitosamente.');
                    return $this->redirect(['becas']);
                } else {
                    Yii::$app->session->setFlash('error', 'Error al asignar la beca: ' . json_encode($model->getErrors()));
                }
            }
        }

        return $this->render('asignar-beca', [
            'model' => $model,
            'atletas' => $atletas,
            'tiposBeca' => $tiposBeca,
        ]);
    }

    /**
     * Revoca (finaliza) una beca activa.
     * @param int $id_beca
     * @return \yii\web\Response
     */
    public function actionRevocarBeca($id_beca)
    {
        $beca = Beca::findOne($id_beca);
        if (!$beca) {
            throw new NotFoundHttpException('Beca no encontrada.');
        }

        $beca->fecha_vencimiento = date('Y-m-d');
        $beca->estado = Beca::ESTADO_REVOCADA;
        if ($beca->save()) {
            Yii::$app->session->setFlash('success', 'Beca revocada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al revocar la beca.');
        }

        return $this->redirect(['becas']);
    }

    // -------------------------------------------------------------------------
    // CONFIGURACIÓN DE APORTE BASE
    // -------------------------------------------------------------------------

    /**
     * Administración de la configuración del aporte base general.
     * @return string|\yii\web\Response
     */
    public function actionConfiguracionAporte()
    {
        $this->layout = 'escuelas';

        // Obtener la configuración activa
        $configuracion = ConfiguracionAporte::find()->activa()->one();
        if (!$configuracion) {
            $configuracion = new ConfiguracionAporte();
            $configuracion->aporte_base = 20.00;
            $configuracion->fecha_inicio = date('Y-m-d');
            $configuracion->activa = true;
        }

        if ($this->request->isPost && $configuracion->load($this->request->post())) {
            // Si se marca como activa, desactivar las demás
            if ($configuracion->activa) {
                ConfiguracionAporte::updateAll(['activa' => false], ['activa' => true]);
            }
            if ($configuracion->save()) {
                Yii::$app->session->setFlash('success', 'Configuración de aporte base guardada exitosamente.');
                return $this->redirect(['configuracion-aporte']);
            } else {
                Yii::$app->session->setFlash('error', 'Error al guardar la configuración.');
            }
        }

        // Historial de configuraciones anteriores
        $historial = ConfiguracionAporte::find()
            ->orderBy(['fecha_inicio' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('configuracion-aporte', [
            'model' => $configuracion,
            'historial' => $historial,
        ]);
    }

    // =========================================================================
    // 3. MÉTODOS AUXILIARES PARA CONTROL DE ACCESO (ATLETAS + FAMILIAS)
    // =========================================================================

    // -------------------------------------------------------------------------
    // MÉTODOS PARA ATLETAS (basados en RBAC y lógica existente en actionIndex)
    // -------------------------------------------------------------------------

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
}