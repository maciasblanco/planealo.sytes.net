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
 * AportesController implementa el CRUD para el modelo AportesSemanales.
 * ACTUALIZADO: Sistema quincenal ($5.00 cada 15 días) con manejo dual de moneda (Bs/USD)
 * SOLO DESDE 15/01/2026
 * ✅ CORREGIDO: Superusuario (ID 1) ahora tiene acceso completo
 * ✅ ACTUALIZADO: Monto quincenal $5.00 (antes $4.00)
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
            
            // ✅ CORRECCIÓN: Superusuario (ID 1) siempre tiene acceso
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
                    ->andWhere(['>=', 'fecha_quincena', '2026-01-2026']) // FILTRO CRÍTICO
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
                    if ($model->load(Yii::$app->request->post())) {
                        // VERIFICAR PERMISOS PARA EL ATLETA SELECCIONADO
                        if (!$this->tienePermisoVerAtletaId($model->atleta_id)) {
                            throw new ForbiddenHttpException('No tiene permisos para gestionar aportes de este atleta.');
                        }
                        
                        if (empty($model->escuela_id)) {
                            $model->escuela_id = $id_escuela;
                        }
                        
                        // CORRECCIÓN: Manejo dual de moneda Bs/USD
                        if (!empty($model->monto_bs)) {
                            // Convertir Bs a USD usando tipo_cambio
                            $tipoCambio = !empty($model->tipo_cambio) ? floatval($model->tipo_cambio) : 36.50;
                            $model->monto = $model->monto_bs / $tipoCambio;
                            $model->monto_bs_original = $model->monto_bs;
                        } else {
                            // Si no viene monto_bs, usar monto en USD
                            $model->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                        }
                        
                        // Calcular número de quincena si no viene del formulario
                        if (empty($model->numero_quincena) && !empty($model->fecha_quincena)) {
                            $model->numero_quincena = AportesSemanales::calcularNumeroQuincena($model->fecha_quincena);
                        }
                        
                        // ✅ NUEVA LÓGICA: PAGO INTELIGENTE CON LIQUIDACIÓN DE DEUDAS
                        $transaction = Yii::$app->db->beginTransaction();
                        try {
                            // 1. Primero verificar si hay deudas pendientes (SOLO DESDE 15/01/2026)
                            $deudasPendientes = AportesSemanales::find()
                                ->where([
                                    'atleta_id' => $model->atleta_id,
                                    'estado' => 'pendiente'
                                ])
                                ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                                ->orderBy(['fecha_quincena' => SORT_ASC]) // Pagar las más antiguas primero
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
                                        Yii::info("Deuda liquidada: Atleta {$model->atleta_id}, Quincena {$deuda->fecha_quincena}");
                                    } else {
                                        throw new \Exception("Error al liquidar deuda: " . implode(', ', $deuda->getErrors()));
                                    }
                                }
                                
                                Yii::$app->session->setFlash('success', 
                                    "Se liquidaron {$deudasLiquidadas} deudas pendientes. " . 
                                    ($deudasLiquidadas == 1 ? 'La deuda ha sido saldada.' : 'Las deudas han sido saldadas.')
                                );
                            } 
                            // 3. Si NO hay deudas, crear nuevo registro (SOLO SI ES POSTERIOR A 15/01/2026)
                            else {
                                // Verificar que la fecha de quincena sea >= 15/01/2026
                                if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                                    throw new \Exception('No se pueden registrar aportes anteriores al 15 de enero de 2026.');
                                }
                                
                                if ($model->save()) {
                                    $nuevoRegistroCreado = true;
                                    Yii::$app->session->setFlash('success', 'Aporte quincenal registrado exitosamente.');
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
                    // Aporte flexible - CÓDIGO ACTUALIZADO para sistema dual
                    $monto_flexible = floatval(Yii::$app->request->post('monto_flexible'));
                    $tipo_cambio_flexible = floatval(Yii::$app->request->post('tipo_cambio_flexible', 36.50));
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

                    // Convertir monto flexible a USD si se ingresó en Bs
                    $moneda_flexible = Yii::$app->request->post('moneda_flexible', 'bs');
                    if ($moneda_flexible === 'bs') {
                        $monto_flexible_usd = $monto_flexible / $tipo_cambio_flexible;
                    } else {
                        $monto_flexible_usd = $monto_flexible;
                    }

                    // ✅ NUEVA LÓGICA PARA PAGO FLEXIBLE: LIQUIDAR DEUDAS PRIMERO (SOLO DESDE 15/01/2026)
                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        // 1. Calcular deudas pendientes (SOLO DESDE 15/01/2026)
                        $deudasPendientes = AportesSemanales::find()
                            ->where([
                                'atleta_id' => $atleta_id_flexible,
                                'estado' => 'pendiente'
                            ])
                            ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                            ->orderBy(['fecha_quincena' => SORT_ASC])
                            ->all();
                        
                        $montoDisponibleUsd = $monto_flexible_usd;
                        $deudasLiquidadas = 0;
                        $quincenasNuevas = 0;
                        
                        // 2. Liquidar deudas pendientes con el monto flexible
                        foreach ($deudasPendientes as $deuda) {
                            if ($montoDisponibleUsd >= AportesSemanales::MONTO_QUINCENAL_USD) {
                                $deuda->estado = 'pagado';
                                $deuda->fecha_pago = $fecha_pago_flexible;
                                $deuda->metodo_pago = $metodo_pago_flexible;
                                $deuda->comentarios = $comentarios_flexible . " (Liquidación flexible de deuda)";
                                
                                if ($deuda->save()) {
                                    $montoDisponibleUsd -= AportesSemanales::MONTO_QUINCENAL_USD;
                                    $deudasLiquidadas++;
                                } else {
                                    throw new \Exception("Error al liquidar deuda flexible: " . implode(', ', $deuda->getErrors()));
                                }
                            } else {
                                break; // No hay suficiente monto para más deudas
                            }
                        }
                        
                        // 3. Con el monto restante, crear nuevos aportes (adelantados) SOLO FUTUROS
                        if ($montoDisponibleUsd > 0) {
                            // Calcular quincenas equivalentes del monto restante
                            $quincenas_completas = floor($montoDisponibleUsd / AportesSemanales::MONTO_QUINCENAL_USD);
                            $monto_restante = $montoDisponibleUsd - ($quincenas_completas * AportesSemanales::MONTO_QUINCENAL_USD);

                            $quincenas_procesadas = 0;
                            
                            // Obtener la última fecha de quincena registrada (SOLO DESDE 15/01/2026)
                            $ultimo_aporte = AportesSemanales::find()
                                ->where(['atleta_id' => $atleta_id_flexible])
                                ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                                ->orderBy(['fecha_quincena' => SORT_DESC])
                                ->one();
                            
                            $fecha_actual = new \DateTime();
                            if ($ultimo_aporte) {
                                $fecha_actual = new \DateTime($ultimo_aporte->fecha_quincena);
                                // Asegurar que sea >= 15/01/2026
                                if ($fecha_actual < new \DateTime('2026-01-15')) {
                                    $fecha_actual = new \DateTime('2026-01-15');
                                }
                            } else {
                                $fecha_actual = new \DateTime('2026-01-15');
                            }
                            
                            // Calcular próxima quincena (asegurar que sea >= 15/01/2026)
                            $fecha_actual = new \DateTime(AportesSemanales::calcularProximaQuincena($fecha_actual));
                            if ($fecha_actual < new \DateTime('2026-01-15')) {
                                $fecha_actual = new \DateTime('2026-01-15');
                            }

                            // Procesar quincenas completas con el monto restante
                            for ($i = 0; $i < $quincenas_completas; $i++) {
                                $fecha_quincena = $fecha_actual->format('Y-m-d');
                                
                                // Verificar que la fecha sea >= 15/01/2026
                                if (strtotime($fecha_quincena) < strtotime('2026-01-15')) {
                                    break;
                                }
                                
                                $aporte_existente = AportesSemanales::find()
                                    ->where(['atleta_id' => $atleta_id_flexible, 'fecha_quincena' => $fecha_quincena])
                                    ->one();

                                if (!$aporte_existente) {
                                    $aporte = new AportesSemanales();
                                    $aporte->atleta_id = $atleta_id_flexible;
                                    $aporte->escuela_id = $atleta->id_escuela;
                                    $aporte->fecha_quincena = $fecha_quincena;
                                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincena($fecha_quincena);
                                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                                    $aporte->estado = 'pagado';
                                    $aporte->fecha_pago = $fecha_pago_flexible;
                                    $aporte->metodo_pago = $metodo_pago_flexible;
                                    $aporte->comentarios = $comentarios_flexible . " - Aporte flexible quincena completa (después de liquidar deudas)";
                                    $aporte->tipo_aporte = 'flexible';
                                    
                                    // Guardar tasa de cambio y monto original en Bs
                                    if ($moneda_flexible === 'bs') {
                                        $aporte->tipo_cambio = $tipo_cambio_flexible;
                                        $aporte->monto_bs_original = $monto_flexible / $quincenas_completas * AportesSemanales::MONTO_QUINCENAL_USD / $monto_flexible_usd;
                                    }

                                    if ($aporte->save()) {
                                        $quincenas_procesadas++;
                                        $quincenasNuevas++;
                                    }
                                }

                                // Avanzar a la siguiente quincena
                                $fecha_actual->modify('+15 days');
                                $fecha_actual = new \DateTime(AportesSemanales::calcularProximaQuincena($fecha_actual));
                            }

                            // Procesar monto restante como aporte parcial (SOLO SI ES >= 15/01/2026)
                            if ($monto_restante > 0) {
                                $fecha_quincena = $fecha_actual->format('Y-m-d');
                                
                                // Verificar que la fecha sea >= 15/01/2026
                                if (strtotime($fecha_quincena) >= strtotime('2026-01-15')) {
                                    $aporte_existente = AportesSemanales::find()
                                        ->where(['atleta_id' => $atleta_id_flexible, 'fecha_quincena' => $fecha_quincena])
                                        ->one();
                                    
                                    if (!$aporte_existente) {
                                        $aporte_parcial = new AportesSemanales();
                                        $aporte_parcial->atleta_id = $atleta_id_flexible;
                                        $aporte_parcial->escuela_id = $atleta->id_escuela;
                                        $aporte_parcial->fecha_quincena = $fecha_quincena;
                                        $aporte_parcial->numero_quincena = AportesSemanales::calcularNumeroQuincena($fecha_quincena);
                                        $aporte_parcial->monto = $monto_restante;
                                        $aporte_parcial->estado = 'pagado';
                                        $aporte_parcial->fecha_pago = $fecha_pago_flexible;
                                        $aporte_parcial->metodo_pago = $metodo_pago_flexible;
                                        $aporte_parcial->comentarios = $comentarios_flexible . " - Aporte flexible parcial (después de liquidar deudas)";
                                        $aporte_parcial->tipo_aporte = 'flexible';
                                        $aporte_parcial->pago_parcial = true;
                                        
                                        // Guardar tasa de cambio y monto original en Bs
                                        if ($moneda_flexible === 'bs') {
                                            $aporte_parcial->tipo_cambio = $tipo_cambio_flexible;
                                            $aporte_parcial->monto_bs_original = $monto_flexible / $quincenas_completas * $monto_restante / $monto_flexible_usd;
                                        }

                                        if ($aporte_parcial->save()) {
                                            $quincenasNuevas++;
                                        }
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
                    // Pago múltiple - CÓDIGO MEJORADO para sistema quincenal (SOLO DESDE 15/01/2026)
                    $quincenasSeleccionadas = Yii::$app->request->post('quincenas', []);
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

                    if (empty($quincenasSeleccionadas)) {
                        Yii::$app->session->setFlash('warning', 'No se seleccionaron quincenas para pagar.');
                        break;
                    }

                    $quincenasPagadas = 0;

                    foreach ($quincenasSeleccionadas as $fechaQuincena) {
                        // Verificar que la fecha sea >= 15/01/2026
                        if (strtotime($fechaQuincena) < strtotime('2026-01-15')) {
                            continue; // Saltar quincenas anteriores
                        }
                        
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
                            $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincena($fechaQuincena);
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
                    // Pago adelantado - CÓDIGO MEJORADO para sistema quincenal (SOLO DESDE 15/01/2026)
                    $quincenasAdelanto = Yii::$app->request->post('quincenas_adelanto', 1);
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
                    // Calcular próxima quincena, asegurando que sea >= 15/01/2026
                    $fechaActual = new \DateTime(AportesSemanales::calcularProximaQuincena($fechaActual));
                    if ($fechaActual < new \DateTime('2026-01-15')) {
                        $fechaActual = new \DateTime('2026-01-15');
                    }

                    $quincenasPagadas = 0;

                    for ($i = 0; $i < $quincenasAdelanto; $i++) {
                        $fechaQuincena = $fechaActual->format('Y-m-d');

                        // Verificar si ya existe un aporte para esta fecha
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
                            $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincena($fechaQuincena);
                            $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                            $aporte->estado = 'pagado';
                            $aporte->fecha_pago = $fechaPago;
                            $aporte->metodo_pago = $metodoPago;
                            $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena} (Adelantado)";
                            $aporte->tipo_aporte = 'adelantado';

                            if ($aporte->save()) {
                                $quincenasPagadas++;
                            } else {
                                Yii::error("Error al guardar aporte adelantado: " . implode(', ', $aporte->getErrors()));
                            }
                        }

                        // Avanzar a la siguiente quincena
                        $fechaActual->modify('+15 days');
                        $fechaActual = new \DateTime(AportesSemanales::calcularProximaQuincena($fechaActual));
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
        if (!$model->isNewRecord) {
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
            // Si la fecha calculada es anterior a 15/01/2026, usar 15/01/2026
            if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                $model->fecha_quincena = '2026-01-15';
            }
            $model->numero_quincena = AportesSemanales::calcularNumeroQuincena($model->fecha_quincena);
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
                
                // Verificar que la fecha sea >= 15/01/2026
                if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                    Yii::$app->session->setFlash('error', 'No se pueden crear aportes con fecha anterior al 15 de enero de 2026.');
                    return $this->render('create', [
                        'model' => $model,
                        'atletas' => $atletas,
                        'escuelas' => $escuelas,
                    ]);
                }
                
                // CORRECCIÓN: Manejo dual de moneda
                if (!empty($model->monto_bs)) {
                    // Convertir Bs a USD usando tipo_cambio
                    $tipoCambio = !empty($model->tipo_cambio) ? floatval($model->tipo_cambio) : 36.50;
                    $model->monto = $model->monto_bs / $tipoCambio;
                    $model->monto_bs_original = $model->monto_bs;
                } else {
                    // Si no viene monto_bs, usar monto en USD
                    $model->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                }
                
                // Calcular número de quincena automáticamente
                if (empty($model->numero_quincena) && !empty($model->fecha_quincena)) {
                    $model->numero_quincena = AportesSemanales::calcularNumeroQuincena($model->fecha_quincena);
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
            
            // Establecer fecha de la próxima quincena (asegurar >= 15/01/2026)
            $hoy = new \DateTime();
            $model->fecha_quincena = AportesSemanales::calcularProximaQuincena($hoy);
            // Si la fecha calculada es anterior a 15/01/2026, usar 15/01/2026
            if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                $model->fecha_quincena = '2026-01-15';
            }
            
            // Calcular número de quincena
            $model->numero_quincena = AportesSemanales::calcularNumeroQuincena($model->fecha_quincena);
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

        if ($this->request->isPost && $model->load($this->request->post())) {
            // Verificar que la fecha sea >= 15/01/2026
            if (strtotime($model->fecha_quincena) < strtotime('2026-01-15')) {
                Yii::$app->session->setFlash('error', 'No se pueden actualizar aportes con fecha anterior al 15 de enero de 2026.');
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
            
            // Manejo dual de moneda
            if (!empty($model->monto_bs)) {
                $tipoCambio = !empty($model->tipo_cambio) ? floatval($model->tipo_cambio) : 36.50;
                $model->monto = $model->monto_bs / $tipoCambio;
                $model->monto_bs_original = $model->monto_bs;
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Aporte quincenal actualizado exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Error al actualizar el aporte quincenal.');
            }
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

    // =========================================================================
    // MÉTODOS RBAC - CONTROL DE ACCESO (CON CORRECCIÓN PARA SUPERUSUARIO)
    // =========================================================================

    /**
     * Obtiene los atletas permitidos según los permisos RBAC del usuario
     * ✅ CORREGIDO: Superusuario (ID 1) ahora tiene acceso completo
     * @param int $id_escuela
     * @return AtletasRegistro[]
     */
    protected function getAtletasPermitidos($id_escuela)
    {
        $user = Yii::$app->user;
        
        // ✅ CORRECCIÓN CRÍTICA: Superusuario (ID 1) siempre ve todos los atletas
        if ($user->id == 1) {
            return AtletasRegistro::find()
                ->where(['id_escuela' => $id_escuela])
                ->andWhere(['eliminado' => false])
                ->orderBy(['p_nombre' => SORT_ASC, 'p_apellido' => SORT_ASC])
                ->all();
        }
        
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
     * ✅ CORREGIDO: Superusuario (ID 1) siempre tiene permiso
     * @param AtletasRegistro $atleta
     * @return bool
     */
    protected function tienePermisoVerAtleta($atleta)
    {
        $user = Yii::$app->user;
        
        // ✅ CORRECCIÓN CRÍTICA: Superusuario (ID 1) siempre tiene permiso
        if ($user->id == 1) {
            return true;
        }
        
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
     * ✅ CORREGIDO: Superusuario (ID 1) siempre tiene permiso
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
     * ✅ CORREGIDO: Superusuario (ID 1) siempre tiene permiso
     * @param AportesSemanales $aporte
     * @return bool
     */
    protected function tienePermisoVerAporte($aporte)
    {
        $user = Yii::$app->user;
        
        // ✅ CORRECCIÓN CRÍTICA: Superusuario (ID 1) siempre tiene permiso
        if ($user->id == 1) {
            return true;
        }
        
        $atleta = AtletasRegistro::findOne($aporte->atleta_id);
        return $atleta && $this->tienePermisoVerAtleta($atleta);
    }

    /**
     * Obtiene top atletas permitidos según RBAC (SOLO DESDE 15/01/2026)
     * ✅ CORREGIDO: Superusuario (ID 1) ve todos los top atletas
     * @param int $id_escuela
     * @param array $atletasPermitidos
     * @return array
     */
    protected function getTopAtletasPermitidos($id_escuela, $atletasPermitidos)
    {
        $user = Yii::$app->user;
        
        // ✅ CORRECCIÓN: Superusuario (ID 1) ve todos los top atletas sin filtrar
        if ($user->id == 1 || $user->can('admin')) {
            return AportesSemanales::find()
                ->select(['atleta_id', 'COUNT(*) as total_aportes', 'SUM(monto) as total_pagado'])
                ->where(['estado' => 'pagado', 'escuela_id' => $id_escuela])
                ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                ->groupBy(['atleta_id'])
                ->orderBy(['total_pagado' => SORT_DESC])
                ->limit(5)
                ->asArray()
                ->all();
        }
        
        if (empty($atletasPermitidos)) {
            return [];
        }
        
        $atletasIds = array_map(function($a) { return $a->id; }, $atletasPermitidos);
        
        return AportesSemanales::find()
            ->select(['atleta_id', 'COUNT(*) as total_aportes', 'SUM(monto) as total_pagado'])
            ->where(['estado' => 'pagado', 'escuela_id' => $id_escuela])
            ->andWhere(['in', 'atleta_id', $atletasIds])
            ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
            ->groupBy(['atleta_id'])
            ->orderBy(['total_pagado' => SORT_DESC])
            ->limit(5)
            ->asArray()
            ->all();
    }

    // =========================================================================
    // MÉTODOS EXISTENTES (actualizados para sistema quincenal)
    // =========================================================================

    /**
     * Pago múltiple de quincenas para un atleta (SOLO DESDE 15/01/2026)
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
            // Generar quincenas automáticamente (SOLO DESDE 15/01/2026)
            AportesSemanales::generarQuincenasParaAtleta($atleta->id);
            
            // Calcular deuda (SOLO DESDE 15/01/2026)
            $deuda = AportesSemanales::find()
                ->where(['atleta_id' => $atleta->id, 'estado' => 'pendiente'])
                ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
                ->count();
                
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
                // Verificar que la fecha sea >= 15/01/2026
                if (strtotime($fecha_quincena) < strtotime('2026-01-15')) {
                    continue; // Saltar quincenas anteriores
                }
                
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
                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincena($fecha_quincena);
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
     * Pago por adelantado (SOLO DESDE 15/01/2026)
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
            // Asegurar que sea >= 15/01/2026
            if ($fechaActual < new \DateTime('2026-01-15')) {
                $fechaActual = new \DateTime('2026-01-15');
            }

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
                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincena($fechaQuincena);
                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                    $aporte->estado = 'pagado';
                    $aporte->fecha_pago = $fecha_pago;
                    $aporte->metodo_pago = $metodo_pago;
                    $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena}";

                    if ($aporte->save()) {
                        $quincenasPagadas++;
                    }
                }

                // Avanzar a la siguiente quincena
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
     * Registro masivo MEJORADO de aportes (SOLO DESDE 15/01/2026)
     * @return string|\yii\web\Response
     */
    public function actionRegistroMasivo()
    {
        $model = new AportesSemanales();
        
        // OBTENER LA ESCUELA ACTUAL DEL USUARIO
        $id_escuela = Yii::$app->session->get('id_escuela');

        // OBTENER LOS ATLETAS PERMITIDOS SEGÚN RBAC
        $atletas = $this->getAtletasPermitidos($id_escuela);

        // Calcular fecha de la próxima quincena (asegurar >= 15/01/2026)
        $hoy = new \DateTime();
        $fechaQuincena = AportesSemanales::calcularProximaQuincena($hoy);
        // Si la fecha calculada es anterior a 15/01/2026, usar 15/01/2026
        if (strtotime($fechaQuincena) < strtotime('2026-01-15')) {
            $fechaQuincena = '2026-01-15';
        }
        $numeroQuincena = AportesSemanales::calcularNumeroQuincena($fechaQuincena);

        if ($this->request->isPost) {
            $atletasSeleccionados = $this->request->post('atletas', []);
            $fechaQuincena = $this->request->post('AportesSemanales')['fecha_quincena'] ?? $fechaQuincena;
            $monto = $this->request->post('AportesSemanales')['monto'] ?? AportesSemanales::MONTO_QUINCENAL_USD;
            
            // Verificar que la fecha sea >= 15/01/2026
            if (strtotime($fechaQuincena) < strtotime('2026-01-15')) {
                Yii::$app->session->setFlash('error', 'No se pueden registrar aportes con fecha anterior al 15 de enero de 2026.');
                return $this->redirect(['registro-masivo']);
            }
            
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
     * Reporte ejecutivo MEJORADO (SOLO DESDE 15/01/2026)
     * @return string
     */
    public function actionReporteEjecutivo()
    {
        // Solo admin puede ver reportes ejecutivos
        if (!Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException('No tiene permisos para ver reportes ejecutivos.');
        }

        $id_escuela = Yii::$app->session->get('id_escuela');
        
        $fechaInicio = Yii::$app->request->get('fecha_inicio', '2026-01-15'); // CAMBIADO: Inicio desde 15/01/2026
        $fechaFin = Yii::$app->request->get('fecha_fin', date('Y-m-d'));

        // Estadísticas financieras (SOLO DESDE 15/01/2026)
        $totalRecaudado = AportesSemanales::find()
            ->where(['estado' => 'pagado', 'escuela_id' => $id_escuela])
            ->andWhere(['between', 'fecha_pago', $fechaInicio, $fechaFin])
            ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
            ->sum('monto') ?? 0;

        $totalCompras = ComprasEscuela::find()
            ->where(['escuela_id' => $id_escuela])
            ->andWhere(['between', 'fecha_compra', $fechaInicio, $fechaFin])
            ->sum('monto') ?? 0;

        $balance = $totalRecaudado - $totalCompras;

        // Atletas morosos (SOLO DESDE 15/01/2026)
        $atletasMorosos = AtletasRegistro::find()
            ->select(['atleta.*', 'COUNT(aportes.id) as quincenas_deuda', 'SUM(aportes.monto) as monto_deuda'])
            ->from('atletas.registro atleta')
            ->leftJoin('contabilidad.aportes_semanales aportes', 'aportes.atleta_id = atleta.id AND aportes.estado = \'pendiente\' AND aportes.fecha_quincena >= \'2026-01-15\'') // FILTRO CRÍTICO
            ->where(['atleta.id_escuela' => $id_escuela, 'atleta.eliminado' => false])
            ->groupBy(['atleta.id'])
            ->having('COUNT(aportes.id) > 0')
            ->asArray()
            ->all();

        // Top atletas (SOLO DESDE 15/01/2026)
        $topAtletas = AportesSemanales::getTopAtletas($id_escuela);

        // Evolución mensual (SOLO DESDE 15/01/2026)
        $evolucionMensual = AportesSemanales::find()
            ->select([
                "TO_CHAR(fecha_pago, 'YYYY-MM') as mes",
                'COUNT(*) as total_aportes',
                'SUM(monto) as recaudado'
            ])
            ->where(['estado' => 'pagado', 'escuela_id' => $id_escuela])
            ->andWhere(['between', 'fecha_pago', $fechaInicio, $fechaFin])
            ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
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
     * Reporte de atletas morosos (SOLO DESDE 15/01/2026)
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
        
        // Primero generar quincenas para todos los atletas (SOLO DESDE 15/01/2026)
        $atletasEscuela = AtletasRegistro::find()
            ->where(['id_escuela' => $id_escuela, 'eliminado' => false])
            ->all();
            
        foreach ($atletasEscuela as $atleta) {
            AportesSemanales::generarQuincenasParaAtleta($atleta->id);
        }
        
        // Consulta para obtener atletas morosos de la escuela actual (SOLO DESDE 15/01/2026)
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
                AND asem.fecha_quincena >= '2026-01-15'  -- FILTRO CRÍTICO: SOLO DESDE 15/01/2026
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
     * Procesar pago múltiple desde AJAX (SOLO DESDE 15/01/2026)
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
                // Verificar que la fecha sea >= 15/01/2026
                if (strtotime($fecha_quincena) < strtotime('2026-01-15')) {
                    continue; // Saltar quincenas anteriores
                }
                
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
                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincena($fecha_quincena);
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
     * Procesar pago adelantado desde AJAX (SOLO DESDE 15/01/2026)
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
            // Asegurar que sea >= 15/01/2026
            if ($fechaActual < new \DateTime('2026-01-15')) {
                $fechaActual = new \DateTime('2026-01-15');
            }

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
                    $aporte->numero_quincena = AportesSemanales::calcularNumeroQuincena($fechaQuincena);
                    $aporte->monto = AportesSemanales::MONTO_QUINCENAL_USD;
                    $aporte->estado = 'pagado';
                    $aporte->fecha_pago = $fecha_pago;
                    $aporte->metodo_pago = $metodo_pago;
                    $aporte->comentarios = $comentarios . " - Quincena {$fechaQuincena}";

                    if ($aporte->save()) {
                        $quincenasPagadas++;
                    }
                }

                // Avanzar a la siguiente quincena
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

    /**
     * Obtener quincenas pendientes para un atleta (AJAX) (SOLO DESDE 15/01/2026)
     */
    public function actionObtenerQuincenasPendientes($atleta_id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // VERIFICAR PERMISOS
        if (!$this->tienePermisoVerAtletaId($atleta_id)) {
            return ['success' => false, 'message' => 'No tiene permisos para ver las quincenas de este atleta.'];
        }

        $atleta = AtletasRegistro::findOne($atleta_id);
        if (!$atleta) {
            return ['success' => false, 'message' => 'Atleta no encontrado.'];
        }

        // Generar quincenas automáticamente (SOLO DESDE 15/01/2026)
        AportesSemanales::generarQuincenasParaAtleta($atleta_id);

        // Obtener historial (SOLO DESDE 15/01/2026)
        $historial = AportesSemanales::find()
            ->where(['atleta_id' => $atleta_id])
            ->andWhere(['>=', 'fecha_quincena', '2026-01-15']) // FILTRO CRÍTICO
            ->orderBy(['fecha_quincena' => SORT_ASC])
            ->asArray()
            ->all();
            
        $quincenasPendientes = array_filter($historial, function($quincena) {
            return $quincena['estado'] == 'pendiente';
        });

        return [
            'success' => true,
            'quincenasPendientes' => array_values($quincenasPendientes),
            'totalQuincenas' => count($quincenasPendientes),
            'montoTotal' => count($quincenasPendientes) * AportesSemanales::MONTO_QUINCENAL_USD
        ];
    }

    /**
     * Acción para limpiar quincenas anteriores al 15/01/2026
     */
    public function actionLimpiarQuincenasAnteriores()
    {
        // Solo admin puede limpiar datos
        if (!Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException('No tiene permisos para limpiar datos.');
        }

        $id_escuela = Yii::$app->session->get('id_escuela');
        
        if (!$id_escuela) {
            Yii::$app->session->setFlash('error', 'No se ha seleccionado una escuela.');
            return $this->redirect(['index']);
        }
        
        $eliminadas = AportesSemanales::deleteAll([
            'and',
            ['escuela_id' => $id_escuela],
            ['<', 'fecha_quincena', '2026-01-15']
        ]);
        
        Yii::$app->session->setFlash('success', "Se eliminaron {$eliminadas} quincenas anteriores al 15/01/2026.");
        return $this->redirect(['index']);
    }

    /**
     * Acción para migrar datos existentes al nuevo sistema quincenal
     * Nota: Esto solo creará quincenas desde 15/01/2026
     */
    public function actionMigrarDatos()
    {
        // Solo admin puede migrar datos
        if (!Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException('No tiene permisos para migrar datos.');
        }

        $id_escuela = Yii::$app->session->get('id_escuela');
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 1. Primero limpiar quincenas anteriores al 15/01/2026
            $eliminadas = AportesSemanales::deleteAll([
                'and',
                ['escuela_id' => $id_escuela],
                ['<', 'fecha_quincena', '2026-01-15']
            ]);
            
            Yii::info("Eliminadas {$eliminadas} quincenas anteriores al 15/01/2026");
            
            // 2. Generar quincenas para todos los atletas de la escuela (SOLO DESDE 15/01/2026)
            $atletasEscuela = AtletasRegistro::find()
                ->where(['id_escuela' => $id_escuela, 'eliminado' => false])
                ->all();
            
            $atletasProcesados = 0;
            $quincenasGeneradas = 0;
            
            foreach ($atletasEscuela as $atleta) {
                $generadas = AportesSemanales::generarQuincenasParaAtleta($atleta->id);
                $quincenasGeneradas += $generadas;
                $atletasProcesados++;
            }
            
            $transaction->commit();
            
            Yii::$app->session->setFlash('success', 
                "Migración completada: {$atletasProcesados} atletas procesados, {$quincenasGeneradas} quincenas generadas desde 15/01/2026. " 
                . "Se eliminaron {$eliminadas} quincenas antiguas."
            );
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', "Error en migración: " . $e->getMessage());
            Yii::error('Error en migración: ' . $e->getMessage());
        }
        
        return $this->redirect(['index']);
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