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

/**
 * AportesController implements the CRUD actions for AportesSemanales model.
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
                    ],
                ],
                'access' => [
                    'class' => AccessControl::className(),
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'], // Solo usuarios autenticados
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all AportesSemanales models.
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
            Yii::$app->session->setFlash('warning', 'No se encontraron atletas registrados en esta escuela o no tiene permisos para verlos.');
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

        // Calcular estadísticas para cada atleta
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

                // GENERAR SEMANAS AUTOMÁTICAMENTE desde la fecha de creación
                $semanasGeneradas = 0;
                try {
                    $semanasGeneradas = AportesSemanales::generarSemanasParaAtleta($atleta->id);
                    Yii::info("Semanas generadas para atleta {$atleta->id}: {$semanasGeneradas}");
                } catch (\Exception $e) {
                    $errorMsg = "Error generando semanas para atleta {$atleta->id}: " . $e->getMessage();
                    Yii::error($errorMsg);
                    $erroresProcesamiento[] = $errorMsg;
                }
                
                // Calcular montos PAGADOS
                $montoPagado = 0;
                try {
                    $montoPagado = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id, 'estado' => 'pagado'])
                        ->sum('monto');
                    $montoPagado = $montoPagado ? floatval($montoPagado) : 0;
                    Yii::info("Monto pagado atleta {$atleta->id}: {$montoPagado}");
                } catch (\Exception $e) {
                    $errorMsg = "Error calculando monto pagado para atleta {$atleta->id}: " . $e->getMessage();
                    Yii::warning($errorMsg);
                    $erroresProcesamiento[] = $errorMsg;
                }

                // Calcular montos PENDIENTES (deuda)
                $montoDeuda = 0;
                try {
                    $montoDeuda = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id, 'estado' => 'pendiente'])
                        ->sum('monto');
                    $montoDeuda = $montoDeuda ? floatval($montoDeuda) : 0;
                    Yii::info("Monto deuda atleta {$atleta->id}: {$montoDeuda}");
                } catch (\Exception $e) {
                    $errorMsg = "Error calculando monto deuda para atleta {$atleta->id}: " . $e->getMessage();
                    Yii::warning($errorMsg);
                    $erroresProcesamiento[] = $errorMsg;
                }

                // Calcular adelantados (pagos con fecha_viernes futura)
                $montoAdelantado = 0;
                $semanasAdelantadas = 0;
                try {
                    $hoy = date('Y-m-d');
                    $montoAdelantado = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id, 'estado' => 'pagado'])
                        ->andWhere(['>', 'fecha_viernes', $hoy])
                        ->sum('monto');
                    $montoAdelantado = $montoAdelantado ? floatval($montoAdelantado) : 0;
                    $semanasAdelantadas = $montoAdelantado / AportesSemanales::MONTO_SEMANAL;
                    Yii::info("Monto adelantado atleta {$atleta->id}: {$montoAdelantado}");
                } catch (\Exception $e) {
                    $errorMsg = "Error calculando adelantados para atleta {$atleta->id}: " . $e->getMessage();
                    Yii::warning($errorMsg);
                    $erroresProcesamiento[] = $errorMsg;
                }

                // Información detallada del atleta
                $totalSemanas = AportesSemanales::find()->where(['atleta_id' => $atleta->id])->count();
                $semanasPagadas = AportesSemanales::find()->where(['atleta_id' => $atleta->id, 'estado' => 'pagado'])->count();
                $semanasPendientes = AportesSemanales::find()->where(['atleta_id' => $atleta->id, 'estado' => 'pendiente'])->count();
                
                Yii::info("RESUMEN ATLETA {$atleta->id}: Total semanas: {$totalSemanas}, Pagadas: {$semanasPagadas}, Pendientes: {$semanasPendientes}");

                $atletasConEstadisticas[] = [
                    'atleta' => $atleta,
                    'montoPagado' => $montoPagado,
                    'montoDeuda' => $montoDeuda,
                    'montoAdelantado' => $montoAdelantado,
                    'semanasAdelantadas' => $semanasAdelantadas,
                    'semanasGeneradas' => $semanasGeneradas,
                    'totalSemanas' => $totalSemanas,
                    'semanasPagadas' => $semanasPagadas,
                    'semanasPendientes' => $semanasPendientes,
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
                    'semanasAdelantadas' => 0,
                    'semanasGeneradas' => 0,
                    'totalSemanas' => 0,
                    'semanasPagadas' => 0,
                    'semanasPendientes' => 0,
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

        // Estadísticas generales
        $pendientes = 0;
        try {
            $atletasPermitidosIds = array_map(function($a) { return $a->id; }, $atletas);
            $pendientes = AportesSemanales::find()
                ->where(['estado' => 'pendiente', 'escuela_id' => $id_escuela])
                ->andWhere(['in', 'atleta_id', $atletasPermitidosIds])
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
        $semanasDeuda = 0;
        $montoDeuda = 0;
        $semanasPendientes = [];
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
                
                // Generar semanas automáticamente
                AportesSemanales::generarSemanasParaAtleta($atleta_id);
                
                // Obtener información de deudas
                $historialDeudas = AportesSemanales::obtenerHistorialDeudas($atleta_id);
                $semanasDeuda = AportesSemanales::calcularDeudaAtleta($atleta_id);
                $montoDeuda = AportesSemanales::calcularMontoDeuda($atleta_id);
                $semanasPendientes = array_filter($historialDeudas, function($semana) {
                    return $semana['estado'] == 'pendiente';
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
                    if ($model->load(Yii::$app->request->post())) {
                        // VERIFICAR PERMISOS PARA EL ATLETA SELECCIONADO
                        if (!$this->tienePermisoVerAtletaId($model->atleta_id)) {
                            throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                        }
                        
                        if (empty($model->escuela_id)) {
                            $model->escuela_id = $id_escuela;
                        }
                        
                        // CORRECCIÓN: Usar monto fijo en dólares, no calcular monto_bs
                        $model->monto = AportesSemanales::MONTO_SEMANAL;
                        
                        // Calcular número de semana si no viene del formulario
                        if (empty($model->numero_semana) && !empty($model->fecha_viernes)) {
                            $model->numero_semana = AportesSemanales::calcularNumeroSemana($model->fecha_viernes);
                        }
                        
                        // ✅ NUEVA LÓGICA: PAGO INTELIGENTE CON LIQUIDACIÓN DE DEUDAS
                        $transaction = Yii::$app->db->beginTransaction();
                        try {
                            // 1. Primero verificar si hay deudas pendientes
                            $deudasPendientes = AportesSemanales::find()
                                ->where([
                                    'atleta_id' => $model->atleta_id,
                                    'estado' => 'pendiente'
                                ])
                                ->orderBy(['fecha_viernes' => SORT_ASC]) // Pagar las más antiguas primero
                                ->all();
                            
                            $deudasLiquidadas = 0;
                            $nuevoRegistroCreado = false;
                            
                            // 2. Si hay deudas, liquidarlas primero
                            if (!empty($deudasPendientes)) {
                                foreach ($deudasPendientes as $deuda) {
                                    // Liquidar la deuda pendiente
                                    $deuda->estado = 'pagado';
                                    $deuda->fecha_pago = $model->fecha_pago;
                                    $deuda->metodo_pago = $model->metodo_pago;
                                    $deuda->comentarios = $model->comentarios . " (Liquidación de deuda pendiente)";
                                    
                                    if ($deuda->save()) {
                                        $deudasLiquidadas++;
                                        Yii::info("Deuda liquidada: Atleta {$model->atleta_id}, Semana {$deuda->fecha_viernes}");
                                    } else {
                                        throw new \Exception("Error al liquidar deuda: " . implode(', ', $deuda->getErrors()));
                                    }
                                }
                                
                                Yii::$app->session->setFlash('success', 
                                    "Se liquidaron {$deudasLiquidadas} deudas pendientes. " . 
                                    ($deudasLiquidadas == 1 ? 'La deuda ha sido saldada.' : 'Las deudas han sido saldadas.')
                                );
                            } 
                            // 3. Si NO hay deudas, crear nuevo registro
                            else {
                                if ($model->save()) {
                                    $nuevoRegistroCreado = true;
                                    Yii::$app->session->setFlash('success', 'Aporte individual registrado exitosamente.');
                                } else {
                                    throw new \Exception('Error al guardar el aporte: ' . implode(', ', $model->getErrorSummary(true)));
                                }
                            }
                            
                            $transaction->commit();
                            
                            // Redirigir después del éxito
                            return $this->redirect(['gestion-atleta', 'atleta_id' => $model->atleta_id]);
                            
                        } catch (\Exception $e) {
                            $transaction->rollBack();
                            Yii::$app->session->setFlash('error', $e->getMessage());
                            Yii::error('Error en pago inteligente: ' . $e->getMessage());
                        }
                    }
                    break;
                    
                case 'flexible':
                    // Aporte flexible - CÓDIGO CORREGIDO
                    $monto_flexible = Yii::$app->request->post('monto_flexible');
                    $fecha_pago_flexible = Yii::$app->request->post('fecha_pago_flexible');
                    $metodo_pago_flexible = Yii::$app->request->post('metodo_pago_flexible');
                    $comentarios_flexible = Yii::$app->request->post('comentarios_flexible');
                    $atleta_id_flexible = Yii::$app->request->post('atleta_id_flexible', $atleta_id);

                    if (!$atleta_id_flexible) {
                        Yii::$app->session->setFlash('error', 'Debe seleccionar un atleta.');
                        break;
                    }

                    // VERIFICAR PERMISOS PARA EL ATLETA
                    if (!$this->tienePermisoVerAtletaId($atleta_id_flexible)) {
                        throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                    }

                    $atleta = AtletasRegistro::findOne($atleta_id_flexible);
                    if (!$atleta) {
                        Yii::$app->session->setFlash('error', 'Atleta no encontrado.');
                        break;
                    }

                    // ✅ NUEVA LÓGICA PARA PAGO FLEXIBLE: LIQUIDAR DEUDAS PRIMERO
                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        // 1. Calcular deudas pendientes
                        $deudasPendientes = AportesSemanales::find()
                            ->where([
                                'atleta_id' => $atleta_id_flexible,
                                'estado' => 'pendiente'
                            ])
                            ->orderBy(['fecha_viernes' => SORT_ASC])
                            ->all();
                        
                        $montoDisponible = $monto_flexible;
                        $deudasLiquidadas = 0;
                        $semanasNuevas = 0;
                        
                        // 2. Liquidar deudas pendientes con el monto flexible
                        foreach ($deudasPendientes as $deuda) {
                            if ($montoDisponible >= AportesSemanales::MONTO_SEMANAL) {
                                $deuda->estado = 'pagado';
                                $deuda->fecha_pago = $fecha_pago_flexible;
                                $deuda->metodo_pago = $metodo_pago_flexible;
                                $deuda->comentarios = $comentarios_flexible . " (Liquidación flexible de deuda)";
                                
                                if ($deuda->save()) {
                                    $montoDisponible -= AportesSemanales::MONTO_SEMANAL;
                                    $deudasLiquidadas++;
                                } else {
                                    throw new \Exception("Error al liquidar deuda flexible: " . implode(', ', $deuda->getErrors()));
                                }
                            } else {
                                break; // No hay suficiente monto para más deudas
                            }
                        }
                        
                        // 3. Con el monto restante, crear nuevos aportes (adelantados)
                        if ($montoDisponible > 0) {
                            // Calcular semanas equivalentes del monto restante
                            $semanas_completas = floor($montoDisponible / AportesSemanales::MONTO_SEMANAL);
                            $monto_restante = $montoDisponible - ($semanas_completas * AportesSemanales::MONTO_SEMANAL);

                            $semanas_procesadas = 0;
                            
                            // Obtener la última fecha de viernes registrada
                            $ultimo_aporte = AportesSemanales::find()
                                ->where(['atleta_id' => $atleta_id_flexible])
                                ->orderBy(['fecha_viernes' => SORT_DESC])
                                ->one();
                            
                            if ($ultimo_aporte) {
                                $fecha_actual = new \DateTime($ultimo_aporte->fecha_viernes);
                                $fecha_actual->modify('+1 week');
                            } else {
                                $fecha_actual = new \DateTime();
                                if ($fecha_actual->format('N') != 5) {
                                    $fecha_actual->modify('next friday');
                                }
                            }

                            // Procesar semanas completas con el monto restante
                            for ($i = 0; $i < $semanas_completas; $i++) {
                                $fecha_viernes = $fecha_actual->format('Y-m-d');
                                
                                $aporte_existente = AportesSemanales::find()
                                    ->where(['atleta_id' => $atleta_id_flexible, 'fecha_viernes' => $fecha_viernes])
                                    ->one();

                                if (!$aporte_existente) {
                                    $aporte = new AportesSemanales();
                                    $aporte->atleta_id = $atleta_id_flexible;
                                    $aporte->escuela_id = $atleta->id_escuela;
                                    $aporte->fecha_viernes = $fecha_viernes;
                                    $aporte->numero_semana = (int)$fecha_actual->format('W');
                                    $aporte->monto = AportesSemanales::MONTO_SEMANAL;
                                    $aporte->estado = 'pagado';
                                    $aporte->fecha_pago = $fecha_pago_flexible;
                                    $aporte->metodo_pago = $metodo_pago_flexible;
                                    $aporte->comentarios = $comentarios_flexible . " - Aporte flexible semana completa (después de liquidar deudas)";
                                    $aporte->tipo_aporte = 'flexible';

                                    if ($aporte->save()) {
                                        $semanas_procesadas++;
                                        $semanasNuevas++;
                                    }
                                }

                                $fecha_actual->modify('+1 week');
                            }

                            // Procesar monto restante como aporte parcial
                            if ($monto_restante > 0) {
                                $fecha_viernes = $fecha_actual->format('Y-m-d');
                                
                                $aporte_existente = AportesSemanales::find()
                                    ->where(['atleta_id' => $atleta_id_flexible, 'fecha_viernes' => $fecha_viernes])
                                    ->one();
                                
                                if (!$aporte_existente) {
                                    $aporte_parcial = new AportesSemanales();
                                    $aporte_parcial->atleta_id = $atleta_id_flexible;
                                    $aporte_parcial->escuela_id = $atleta->id_escuela;
                                    $aporte_parcial->fecha_viernes = $fecha_viernes;
                                    $aporte_parcial->numero_semana = (int)$fecha_actual->format('W');
                                    $aporte_parcial->monto = $monto_restante;
                                    $aporte_parcial->estado = 'pagado';
                                    $aporte_parcial->fecha_pago = $fecha_pago_flexible;
                                    $aporte_parcial->metodo_pago = $metodo_pago_flexible;
                                    $aporte_parcial->comentarios = $comentarios_flexible . " - Aporte flexible parcial (después de liquidar deudas)";
                                    $aporte_parcial->tipo_aporte = 'flexible';
                                    $aporte_parcial->es_parcial = true;

                                    if ($aporte_parcial->save()) {
                                        $semanasNuevas++;
                                    }
                                }
                            }
                        }
                        
                        $transaction->commit();
                        
                        // Mensaje informativo consolidado
                        $mensaje = "Pago flexible procesado: ";
                        if ($deudasLiquidadas > 0) {
                            $mensaje .= "{$deudasLiquidadas} deudas liquidadas";
                        }
                        if ($semanasNuevas > 0) {
                            $mensaje .= ($deudasLiquidadas > 0 ? " + " : "") . "{$semanasNuevas} semanas nuevas";
                        }
                        if ($deudasLiquidadas == 0 && $semanasNuevas == 0) {
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
                    // Pago múltiple - CÓDIGO MEJORADO
                    $semanasSeleccionadas = Yii::$app->request->post('semanas', []);
                    $fechaPago = Yii::$app->request->post('fecha_pago', date('Y-m-d'));
                    $metodoPago = Yii::$app->request->post('metodo_pago', 'efectivo');
                    $comentarios = Yii::$app->request->post('comentarios', '');
                    $atleta_id_multiple = Yii::$app->request->post('atleta_id_multiple', $atleta_id);

                    if (!$atleta_id_multiple) {
                        Yii::$app->session->setFlash('error', 'Debe seleccionar un atleta.');
                        break;
                    }

                    // VERIFICAR PERMISOS PARA EL ATLETA
                    if (!$this->tienePermisoVerAtletaId($atleta_id_multiple)) {
                        throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                    }

                    $atleta = AtletasRegistro::findOne($atleta_id_multiple);
                    if (!$atleta) {
                        Yii::$app->session->setFlash('error', 'Atleta no encontrado.');
                        break;
                    }

                    if (empty($semanasSeleccionadas)) {
                        Yii::$app->session->setFlash('warning', 'No se seleccionaron semanas para pagar.');
                        break;
                    }

                    $semanasPagadas = 0;

                    foreach ($semanasSeleccionadas as $fechaViernes) {
                        $aporte = AportesSemanales::find()
                            ->where([
                                'atleta_id' => $atleta_id_multiple,
                                'fecha_viernes' => $fechaViernes
                            ])
                            ->one();

                        if (!$aporte) {
                            $aporte = new AportesSemanales();
                            $aporte->atleta_id = $atleta_id_multiple;
                            $aporte->escuela_id = $atleta->id_escuela;
                            $aporte->fecha_viernes = $fechaViernes;
                            
                            $fechaObj = new \DateTime($fechaViernes);
                            $aporte->numero_semana = (int)$fechaObj->format('W');
                            $aporte->monto = AportesSemanales::MONTO_SEMANAL;
                        }

                        $aporte->estado = 'pagado';
                        $aporte->fecha_pago = $fechaPago;
                        $aporte->metodo_pago = $metodoPago;
                        $aporte->comentarios = $comentarios;

                        if ($aporte->save()) {
                            $semanasPagadas++;
                        } else {
                            Yii::error("Error al guardar aporte múltiple: " . implode(', ', $aporte->getErrors()));
                        }
                    }

                    if ($semanasPagadas > 0) {
                        Yii::$app->session->setFlash('success', "Se registró el pago de {$semanasPagadas} semanas mediante pago múltiple.");
                    } else {
                        Yii::$app->session->setFlash('warning', 'No se pudo registrar ningún pago.');
                    }
                    return $this->redirect(['gestion-atleta', 'atleta_id' => $atleta_id_multiple]);
                    break;
                    
                case 'adelantado':
                    // Pago adelantado - CÓDIGO MEJORADO
                    $semanasAdelanto = Yii::$app->request->post('semanas_adelanto', 1);
                    $fechaPago = Yii::$app->request->post('fecha_pago_adelanto', date('Y-m-d'));
                    $metodoPago = Yii::$app->request->post('metodo_pago_adelanto', 'efectivo');
                    $comentarios = Yii::$app->request->post('comentarios_adelanto', 'Pago por adelantado');
                    $atleta_id_adelanto = Yii::$app->request->post('atleta_id_adelanto', $atleta_id);

                    if (!$atleta_id_adelanto) {
                        Yii::$app->session->setFlash('error', 'Debe seleccionar un atleta.');
                        break;
                    }

                    // VERIFICAR PERMISOS PARA EL ATLETA
                    if (!$this->tienePermisoVerAtletaId($atleta_id_adelanto)) {
                        throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                    }

                    $atleta = AtletasRegistro::findOne($atleta_id_adelanto);
                    if (!$atleta) {
                        Yii::$app->session->setFlash('error', 'Atleta no encontrado.');
                        break;
                    }

                    $fechaActual = new \DateTime();
                    // Ajustar al próximo viernes si no es viernes
                    if ($fechaActual->format('N') != 5) {
                        $fechaActual->modify('next friday');
                    }

                    $semanasPagadas = 0;

                    for ($i = 0; $i < $semanasAdelanto; $i++) {
                        $fechaViernes = $fechaActual->format('Y-m-d');

                        // Verificar si ya existe un aporte para esta fecha
                        $existeAporte = AportesSemanales::find()
                            ->where([
                                'atleta_id' => $atleta_id_adelanto,
                                'fecha_viernes' => $fechaViernes
                            ])
                            ->exists();

                        if (!$existeAporte) {
                            $aporte = new AportesSemanales();
                            $aporte->atleta_id = $atleta_id_adelanto;
                            $aporte->escuela_id = $atleta->id_escuela;
                            $aporte->fecha_viernes = $fechaViernes;
                            $aporte->numero_semana = (int)$fechaActual->format('W');
                            $aporte->monto = AportesSemanales::MONTO_SEMANAL;
                            $aporte->estado = 'pagado';
                            $aporte->fecha_pago = $fechaPago;
                            $aporte->metodo_pago = $metodoPago;
                            $aporte->comentarios = $comentarios . " - Semana {$fechaViernes} (Adelantado)";
                            $aporte->tipo_aporte = 'adelantado';

                            if ($aporte->save()) {
                                $semanasPagadas++;
                            } else {
                                Yii::error("Error al guardar aporte adelantado: " . implode(', ', $aporte->getErrors()));
                            }
                        }

                        $fechaActual->modify('+1 week');
                    }

                    if ($semanasPagadas > 0) {
                        Yii::$app->session->setFlash('success', "Se registró el pago por adelantado de {$semanasPagadas} semanas.");
                    } else {
                        Yii::$app->session->setFlash('warning', 'No se pudo registrar ningún pago adelantado. Puede que las semanas ya estén pagadas.');
                    }
                    return $this->redirect(['gestion-atleta', 'atleta_id' => $atleta_id_adelanto]);
                    break;
            }
        }

        // Establecer valores por defecto para el formulario individual
        if (!$model->isNewRecord) {
            $model->loadDefaultValues();
            if ($atleta) {
                $model->atleta_id = $atleta_id;
                $model->escuela_id = $atleta->id_escuela;
            }
            $model->monto = AportesSemanales::MONTO_SEMANAL;
            $model->estado = 'pendiente';
            
            // Establecer fecha del último viernes
            $hoy = new \DateTime();
            $diaSemana = $hoy->format('w');
            $diasHastaViernes = ($diaSemana <= 5) ? (5 - $diaSemana) : (5 - $diaSemana + 7);
            $viernes = clone $hoy;
            $viernes->modify("+{$diasHastaViernes} days");
            $model->fecha_viernes = $viernes->format('Y-m-d');
            
            $model->numero_semana = (int)$viernes->format('W');
        }

        return $this->render('gestion-atleta', [
            'model' => $model,
            'atletas' => $atletas,
            'atleta' => $atleta,
            'historialDeudas' => $historialDeudas,
            'semanasDeuda' => $semanasDeuda,
            'montoDeuda' => $montoDeuda,
            'semanasPendientes' => $semanasPendientes,
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
                
                // CORRECCIÓN: Usar monto fijo en dólares
                $model->monto = AportesSemanales::MONTO_SEMANAL;
                
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Aporte semanal registrado exitosamente.');
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    Yii::$app->session->setFlash('error', 'Error al guardar el aporte: ' . json_encode($model->getErrors()));
                }
            }
        } else {
            $model->loadDefaultValues();
            // Establecer valores por defecto
            $model->escuela_id = $id_escuela;
            $model->monto = AportesSemanales::MONTO_SEMANAL;
            $model->estado = 'pendiente';
            
            // Establecer fecha del último viernes
            $hoy = new \DateTime();
            $diaSemana = $hoy->format('w');
            $diasHastaViernes = ($diaSemana <= 5) ? (5 - $diaSemana) : (5 - $diaSemana + 7);
            $viernes = clone $hoy;
            $viernes->modify("+{$diasHastaViernes} days");
            $model->fecha_viernes = $viernes->format('Y-m-d');
            
            // Calcular número de semana
            $model->numero_semana = (int)$viernes->format('W');
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
            Yii::$app->session->setFlash('success', 'Aporte semanal actualizado exitosamente.');
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
        Yii::$app->session->setFlash('success', 'Aporte semanal eliminado exitosamente.');

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

    // =========================================================================
    // MÉTODOS RBAC - CONTROL DE ACCESO
    // =========================================================================

    /**
     * Obtiene los atletas permitidos según los permisos RBAC del usuario
     * @param int $id_escuela
     * @return AtletasRegistro[]
     */
    protected function getAtletasPermitidos($id_escuela)
    {
        $user = Yii::$app->user;
        
        // Admin ve todos los atletas de la escuela
        if ($user->can('admin')) {
            return AtletasRegistro::find()
                ->where(['id_escuela' => $id_escuela])
                ->andWhere(['eliminado' => false])
                ->orderBy(['p_nombre' => SORT_ASC, 'p_apellido' => SORT_ASC])
                ->all();
        }
        
        // Atleta ve solo su propio perfil
        if ($user->can('viewOwnAportes')) {
            return AtletasRegistro::find()
                ->where(['id_escuela' => $id_escuela])
                ->andWhere(['eliminado' => false])
                ->andWhere(['user_id' => $user->id])
                ->orderBy(['p_nombre' => SORT_ASC, 'p_apellido' => SORT_ASC])
                ->all();
        }
        
        // Representante ve los atletas que representa
        if ($user->can('viewRepresentedAportes')) {
            $representante = RegistroRepresentantes::find()
                ->where(['user_id' => $user->id])
                ->one();
                
            if ($representante) {
                return AtletasRegistro::find()
                    ->where(['id_escuela' => $id_escuela])
                    ->andWhere(['eliminado' => false])
                    ->andWhere(['id_representante' => $representante->id])
                    ->orderBy(['p_nombre' => SORT_ASC, 'p_apellido' => SORT_ASC])
                    ->all();
            }
        }
        
        // Por defecto, no ve ningún atleta
        return [];
    }

    /**
     * Verifica si el usuario tiene permiso para ver un atleta específico
     * @param AtletasRegistro $atleta
     * @return bool
     */
    protected function tienePermisoVerAtleta($atleta)
    {
        $user = Yii::$app->user;
        
        // Admin puede ver todos los atletas
        if ($user->can('admin')) {
            return true;
        }
        
        // Atleta puede verse a sí mismo
        if ($user->can('viewOwnAportes')) {
            return $atleta->user_id == $user->id;
        }
        
        // Representante puede ver sus atletas representados
        if ($user->can('viewRepresentedAportes')) {
            $representante = RegistroRepresentantes::find()
                ->where(['user_id' => $user->id])
                ->one();
                
            return $representante && $atleta->id_representante == $representante->id;
        }
        
        return false;
    }

    /**
     * Verifica si el usuario tiene permiso para ver un atleta por ID
     * @param int $atleta_id
     * @return bool
     */
    protected function tienePermisoVerAtletaId($atleta_id)
    {
        $atleta = AtletasRegistro::findOne($atleta_id);
        return $atleta && $this->tienePermisoVerAtleta($atleta);
    }

    /**
     * Verifica si el usuario tiene permiso para ver un aporte específico
     * @param AportesSemanales $aporte
     * @return bool
     */
    protected function tienePermisoVerAporte($aporte)
    {
        $atleta = AtletasRegistro::findOne($aporte->atleta_id);
        return $atleta && $this->tienePermisoVerAtleta($atleta);
    }

    /**
     * Obtiene top atletas permitidos según RBAC
     * @param int $id_escuela
     * @param array $atletasPermitidos
     * @return array
     */
    protected function getTopAtletasPermitidos($id_escuela, $atletasPermitidos)
    {
        if (empty($atletasPermitidos)) {
            return [];
        }
        
        $atletasIds = array_map(function($a) { return $a->id; }, $atletasPermitidos);
        
        return AportesSemanales::find()
            ->select(['atleta_id', 'COUNT(*) as total_aportes', 'SUM(monto) as total_pagado'])
            ->where(['estado' => 'pagado', 'escuela_id' => $id_escuela])
            ->andWhere(['in', 'atleta_id', $atletasIds])
            ->groupBy(['atleta_id'])
            ->orderBy(['total_pagado' => SORT_DESC])
            ->limit(5)
            ->all();
    }

    // =========================================================================
    // MÉTODOS EXISTENTES (sin cambios significativos)
    // =========================================================================

    /**
     * Pago múltiple de semanas para un atleta
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
            // Generar semanas automáticamente
            AportesSemanales::generarSemanasParaAtleta($atleta->id);
            
            $deuda = AportesSemanales::calcularDeudaAtleta($atleta->id);
            if ($deuda > 0) {
                $atletasConDeuda[] = $atleta;
            }
        }

        if ($this->request->isPost) {
            $atleta_id = $this->request->post('atleta_id');
            $semanas = $this->request->post('semanas', []);
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

            $semanasPagadas = 0;

            foreach ($semanas as $fecha_viernes) {
                // Buscar si ya existe un aporte para esta fecha
                $aporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_viernes' => $fecha_viernes
                    ])
                    ->one();

                if (!$aporte) {
                    // Crear nuevo aporte
                    $aporte = new AportesSemanales();
                    $aporte->atleta_id = $atleta_id;
                    $aporte->escuela_id = $atleta->id_escuela;
                    $aporte->fecha_viernes = $fecha_viernes;
                    
                    $fechaObj = new \DateTime($fecha_viernes);
                    $aporte->numero_semana = (int)$fechaObj->format('W');
                    $aporte->monto = AportesSemanales::MONTO_SEMANAL;
                }

                $aporte->estado = 'pagado';
                $aporte->fecha_pago = $fecha_pago;
                $aporte->metodo_pago = $metodo_pago;
                $aporte->comentarios = $comentarios;

                if ($aporte->save()) {
                    $semanasPagadas++;
                }
            }

            if ($semanasPagadas > 0) {
                Yii::$app->session->setFlash('success', 
                    "Se registró el pago de {$semanasPagadas} semanas para {$atleta->p_nombre} {$atleta->p_apellido}."
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
            $semanas_adelanto = $this->request->post('semanas_adelanto', 1);
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
            if ($fechaActual->format('N') != 5) {
                $fechaActual->modify('next friday');
            }

            $semanasPagadas = 0;

            for ($i = 0; $i < $semanas_adelanto; $i++) {
                $fechaViernes = $fechaActual->format('Y-m-d');

                // Verificar si ya existe un aporte para esta fecha
                $existeAporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_viernes' => $fechaViernes
                    ])
                    ->exists();

                if (!$existeAporte) {
                    $aporte = new AportesSemanales();
                    $aporte->atleta_id = $atleta_id;
                    $aporte->escuela_id = $atleta->id_escuela;
                    $aporte->fecha_viernes = $fechaViernes;
                    $aporte->numero_semana = (int)$fechaActual->format('W');
                    $aporte->monto = AportesSemanales::MONTO_SEMANAL;
                    $aporte->estado = 'pagado';
                    $aporte->fecha_pago = $fecha_pago;
                    $aporte->metodo_pago = $metodo_pago;
                    $aporte->comentarios = $comentarios . " - Semana {$fechaViernes}";

                    if ($aporte->save()) {
                        $semanasPagadas++;
                    }
                }

                $fechaActual->modify('+1 week');
            }

            if ($semanasPagadas > 0) {
                Yii::$app->session->setFlash('success', 
                    "Se registró el pago por adelantado de {$semanasPagadas} semanas para {$atleta->p_nombre} {$atleta->p_apellido}."
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
     * Registro masivo MEJORADO de aportes
     * @return string|\yii\web\Response
     */
    public function actionRegistroMasivo()
    {
        $model = new AportesSemanales();
        
        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');

        // OBTENER LOS ATLETAS PERMITIDOS SEGÚN RBAC
        $atletas = $this->getAtletasPermitidos($id_escuela);

        // Calcular fecha del viernes y número de semana
        $hoy = new \DateTime();
        $diaSemana = $hoy->format('w');
        $diasHastaViernes = ($diaSemana <= 5) ? (5 - $diaSemana) : (5 - $diaSemana + 7);
        $viernes = clone $hoy;
        $viernes->modify("+{$diasHastaViernes} days");
        $fechaViernes = $viernes->format('Y-m-d');
        $numeroSemana = (int)$viernes->format('W');

        if ($this->request->isPost) {
            $atletasSeleccionados = $this->request->post('atletas', []);
            $fechaViernes = $this->request->post('AportesSemanales')['fecha_viernes'] ?? $fechaViernes;
            $monto = $this->request->post('AportesSemanales')['monto'] ?? AportesSemanales::MONTO_SEMANAL;
            
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
                        'fecha_viernes' => $fechaViernes,
                        'escuela_id' => $id_escuela
                    ])
                    ->exists();
                
                if (!$existeAporte) {
                    $nuevoAporte = new AportesSemanales();
                    $nuevoAporte->atleta_id = $atletaId;
                    $nuevoAporte->escuela_id = $id_escuela;
                    $nuevoAporte->fecha_viernes = $fechaViernes;
                    $nuevoAporte->numero_semana = $numeroSemana;
                    $nuevoAporte->monto = $monto;
                    $nuevoAporte->estado = 'pagado'; // En registro masivo se marca como pagado automáticamente
                    $nuevoAporte->fecha_pago = date('Y-m-d');
                    $nuevoAporte->metodo_pago = 'efectivo';
                    $nuevoAporte->comentarios = 'Registro masivo semanal';
                    
                    if ($nuevoAporte->save()) {
                        $registrosCreados++;
                    }
                }
            }
            
            if ($registrosCreados > 0) {
                Yii::$app->session->setFlash('success', "Se crearon {$registrosCreados} nuevos aportes semanales.");
            } else {
                Yii::$app->session->setFlash('info', "No se crearon nuevos aportes. Puede que ya existan registros para la fecha seleccionada.");
            }
            
            return $this->redirect(['index']);
        }

        return $this->render('registro-masivo', [
            'model' => $model,
            'atletas' => $atletas,
            'fechaViernes' => $fechaViernes,
            'numeroSemana' => $numeroSemana,
        ]);
    }

    /**
     * Gestión de compras de la escuela
     * @return string|\yii\web\Response
     */
    public function actionCompras()
    {
        // Solo admin puede gestionar compras
        if (!Yii::$app->user->can('admin')) {
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
        if (!Yii::$app->user->can('admin')) {
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
            ->select(['atleta.*', 'COUNT(aportes.id) as semanas_deuda', 'SUM(aportes.monto) as monto_deuda'])
            ->from('atletas.registro atleta')
            ->leftJoin('contabilidad.aportes_semanales aportes', 'aportes.atleta_id = atleta.id AND aportes.estado = \'pendiente\'')
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
        if (!Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException('No tiene permisos para ver reportes de morosos.');
        }

        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');
        
        // Primero generar semanas para todos los atletas
        $atletasEscuela = AtletasRegistro::find()
            ->where(['id_escuela' => $id_escuela, 'eliminado' => false])
            ->all();
            
        foreach ($atletasEscuela as $atleta) {
            AportesSemanales::generarSemanasParaAtleta($atleta->id);
        }
        
        // Consulta para obtener atletas morosos de la escuela actual
        $sql = "
            SELECT 
                ar.id,
                ar.p_nombre || ' ' || ar.p_apellido as nombre_completo,
                e.nombre as escuela_nombre,
                COUNT(asem.id) as semanas_deuda,
                COALESCE(SUM(asem.monto), 0) as total_deuda
            FROM atletas.registro ar
            LEFT JOIN contabilidad.aportes_semanales asem ON asem.atleta_id = ar.id AND asem.estado = 'pendiente'
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
            $semanas = Yii::$app->request->post('semanas', []);
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

            $semanasPagadas = 0;

            foreach ($semanas as $fecha_viernes) {
                $aporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_viernes' => $fecha_viernes
                    ])
                    ->one();

                if (!$aporte) {
                    $aporte = new AportesSemanales();
                    $aporte->atleta_id = $atleta_id;
                    $aporte->escuela_id = $atleta->id_escuela;
                    $aporte->fecha_viernes = $fecha_viernes;
                    
                    $fechaObj = new \DateTime($fecha_viernes);
                    $aporte->numero_semana = (int)$fechaObj->format('W');
                    $aporte->monto = AportesSemanales::MONTO_SEMANAL;
                }

                $aporte->estado = 'pagado';
                $aporte->fecha_pago = $fecha_pago;
                $aporte->metodo_pago = $metodo_pago;
                $aporte->comentarios = $comentarios;

                if ($aporte->save()) {
                    $semanasPagadas++;
                }
            }

            return [
                'success' => true,
                'message' => "Se registró el pago de {$semanasPagadas} semanas.",
                'semanasPagadas' => $semanasPagadas
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
            $semanas_adelanto = Yii::$app->request->post('semanas_adelanto', 1);
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
            if ($fechaActual->format('N') != 5) {
                $fechaActual->modify('next friday');
            }

            $semanasPagadas = 0;

            for ($i = 0; $i < $semanas_adelanto; $i++) {
                $fechaViernes = $fechaActual->format('Y-m-d');

                $existeAporte = AportesSemanales::find()
                    ->where([
                        'atleta_id' => $atleta_id,
                        'fecha_viernes' => $fechaViernes
                    ])
                    ->exists();

                if (!$existeAporte) {
                    $aporte = new AportesSemanales();
                    $aporte->atleta_id = $atleta_id;
                    $aporte->escuela_id = $atleta->id_escuela;
                    $aporte->fecha_viernes = $fechaViernes;
                    $aporte->numero_semana = (int)$fechaActual->format('W');
                    $aporte->monto = AportesSemanales::MONTO_SEMANAL;
                    $aporte->estado = 'pagado';
                    $aporte->fecha_pago = $fecha_pago;
                    $aporte->metodo_pago = $metodo_pago;
                    $aporte->comentarios = $comentarios . " - Semana {$fechaViernes}";

                    if ($aporte->save()) {
                        $semanasPagadas++;
                    }
                }

                $fechaActual->modify('+1 week');
            }

            return [
                'success' => true,
                'message' => "Se registró el pago por adelantado de {$semanasPagadas} semanas.",
                'semanasPagadas' => $semanasPagadas
            ];
        }

        return ['success' => false, 'message' => 'Solicitud inválida.'];
    }

    /**
     * Obtener semanas pendientes para un atleta (AJAX)
     */
    public function actionObtenerSemanasPendientes($atleta_id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // VERIFICAR PERMISOS
        if (!$this->tienePermisoVerAtletaId($atleta_id)) {
            return ['success' => false, 'message' => 'No tiene permisos para ver las semanas de este atleta.'];
        }

        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return ['success' => false, 'message' => 'Atleta no encontrado.'];
        }

        // Generar semanas automáticamente
        AportesSemanales::generarSemanasParaAtleta($atleta_id);

        $historial = AportesSemanales::obtenerHistorialDeudas($atleta_id);
        $semanasPendientes = array_filter($historial, function($semana) {
            return $semana['estado'] == 'pendiente';
        });

        return [
            'success' => true,
            'semanasPendientes' => array_values($semanasPendientes),
            'totalSemanas' => count($semanasPendientes),
            'montoTotal' => count($semanasPendientes) * AportesSemanales::MONTO_SEMANAL
        ];
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