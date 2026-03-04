<?php

namespace app\modules\reportes\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use app\models\RegistroRepresentantes;
use app\models\AtletasRegistro;
use app\models\AportesSemanales;
use app\models\Asistencia;
use app\models\TasaDolar;
use app\models\Beca;
use app\models\TipoBeca;
use app\modules\reportes\models\ReporteAtletasSearch;
use app\modules\reportes\models\ReporteAsistenciasSearch;

class ReportesController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['representante', 'atleta', 'admin', 'superusuario', 'profesor'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Listado de atletas según el rol.
     */
    public function actionReportesRepresentantes()
    {
        $searchModel = new ReporteAtletasSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $user_id = Yii::$app->user->id;
        $esPersonalAutorizado = (Yii::$app->user->id == 1 
                                 || Yii::$app->user->can('admin') 
                                 || Yii::$app->user->can('superusuario') 
                                 || Yii::$app->user->can('profesor'));
        $esRepresentante = Yii::$app->user->can('representante');
        $esAtleta = Yii::$app->user->can('atleta');
        
        $atletas = [];
        $representante = null;

        if ($esPersonalAutorizado) {
            $id_escuela = Yii::$app->session->get('id_escuela');
            if (!$id_escuela) {
                Yii::$app->session->setFlash('error', 'Debe seleccionar una escuela.');
                return $this->redirect(['/ged/default/select-escuela']);
            }
            $atletas = AtletasRegistro::find()
                ->where(['id_escuela' => $id_escuela, 'eliminado' => false])
                ->all();
        } elseif ($esRepresentante) {
            $representante = RegistroRepresentantes::find()
                ->where(['user_id' => $user_id])
                ->one();

            if ($representante) {
                $atletas = AtletasRegistro::find()
                    ->where(['id_representante' => $representante->id])
                    ->andWhere(['eliminado' => false])
                    ->all();
            }
        } elseif ($esAtleta) {
            $atleta = AtletasRegistro::find()
                ->where(['user_id' => $user_id])
                ->andWhere(['eliminado' => false])
                ->one();
                
            if ($atleta) {
                $atletas = [$atleta];
                $representante = $atleta->representante;
            }
        }

        $atletasIds = array_map(function($atleta) {
            return $atleta->id;
        }, $atletas);
        $dataProvider->query->andWhere(['id' => $atletasIds]);

        $datosAtletas = [];
        foreach ($atletas as $atleta) {
            $datos = $this->obtenerDatosConsolidados($atleta);
            $datos['becaActiva'] = $this->tieneBecaActiva($atleta->id);
            $datosAtletas[] = $datos;
        }

        $tasaCambio = $this->obtenerTasaCambioActual();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_grid_atletas', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'datosAtletas' => $datosAtletas,
            ]);
        }

        return $this->render('reportes-representantes', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'representante' => $representante,
            'datosAtletas' => $datosAtletas,
            'esRepresentante' => $esRepresentante,
            'esAtleta' => $esAtleta,
            'esPersonalAutorizado' => $esPersonalAutorizado,
            'tasaCambio' => $tasaCambio,
        ]);
    }

    /**
     * Estadísticas detalladas de un atleta.
     */
    public function actionEstadisticasAtleta($id = null)
    {
        $user_id = Yii::$app->user->id;
        $esPersonalAutorizado = (Yii::$app->user->id == 1 
                                 || Yii::$app->user->can('admin') 
                                 || Yii::$app->user->can('superusuario') 
                                 || Yii::$app->user->can('profesor'));
        
        if (Yii::$app->user->can('atleta') && !$esPersonalAutorizado) {
            $atleta = AtletasRegistro::find()
                ->where(['user_id' => $user_id])
                ->one();
            $id = $atleta ? $atleta->id : null;
        }

        if (!$id) {
            throw new \yii\web\NotFoundHttpException('Atleta no especificado.');
        }

        $atleta = AtletasRegistro::find()
            ->where(['id' => $id])
            ->andWhere(['eliminado' => false])
            ->with([
                'escuela', 'categoria', 'nacionalidad', 'sexoModel',
                'asistencias', 'aportes', 'representante'
            ])
            ->one();

        if (!$atleta) {
            throw new \yii\web\NotFoundHttpException('Atleta no encontrado.');
        }

        if (Yii::$app->user->can('representante') && !$esPersonalAutorizado) {
            $representante = RegistroRepresentantes::find()
                ->where(['user_id' => $user_id])
                ->one();
            if (!$representante || $atleta->id_representante != $representante->id) {
                throw new \yii\web\ForbiddenHttpException('No tiene permisos para ver este atleta.');
            }
        }

        $estadisticas = $this->obtenerEstadisticasDetalladas($atleta);
        $deudasPendientes = $this->obtenerDetalleDeuda($atleta->id);
        $tasaCambio = $this->obtenerTasaCambioActual();

        return $this->render('reporte-atletas', [
            'atleta' => $atleta,
            'estadisticas' => $estadisticas,
            'deudasPendientes' => $deudasPendientes,
            'tasaCambio' => $tasaCambio,
        ]);
    }

    /**
     * Recibo de pagos realizados por el atleta.
     */
    public function actionReciboPago($id)
    {
        $atleta = AtletasRegistro::findOne($id);
        if (!$atleta) {
            throw new \yii\web\NotFoundHttpException('Atleta no encontrado.');
        }

        if (!$this->puedeVerAtleta($atleta)) {
            throw new \yii\web\ForbiddenHttpException('No tiene permisos para ver este atleta.');
        }

        $aportesPagados = AportesSemanales::find()
            ->where(['atleta_id' => $id, 'estado' => AportesSemanales::ESTADO_PAGADO])
            ->andWhere(['>=', 'fecha_quincena', AportesSemanales::FECHA_INICIO_DEUDAS])
            ->orderBy(['fecha_quincena' => SORT_DESC])
            ->all();

        $totalPagado = array_sum(array_column($aportesPagados, 'monto'));

        $representante = $atleta->representante;
        $escuela = $atleta->escuela;
        $tasaCambio = $this->obtenerTasaCambioActual();
        $datosPago = $this->obtenerDatosPagoEscuela($escuela);

        return $this->render('recibo-pago', [
            'atleta' => $atleta,
            'representante' => $representante,
            'escuela' => $escuela,
            'aportes' => $aportesPagados,
            'totalPagado' => $totalPagado,
            'tasaCambio' => $tasaCambio,
            'datosPago' => $datosPago,
        ]);
    }

    /**
     * Recibo de cobro (deudas pendientes).
     */
    public function actionReciboCobro($id)
    {
        $atleta = AtletasRegistro::findOne($id);
        if (!$atleta) {
            throw new \yii\web\NotFoundHttpException('Atleta no encontrado.');
        }

        if (!$this->puedeVerAtleta($atleta)) {
            throw new \yii\web\ForbiddenHttpException('No tiene permisos para ver este atleta.');
        }

        $deudasPendientes = AportesSemanales::find()
            ->where(['atleta_id' => $id, 'estado' => AportesSemanales::ESTADO_PENDIENTE])
            ->andWhere(['>=', 'fecha_quincena', AportesSemanales::FECHA_INICIO_DEUDAS])
            ->orderBy(['fecha_quincena' => SORT_ASC])
            ->all();

        $totalDeuda = array_sum(array_column($deudasPendientes, 'monto'));

        $becaActiva = Beca::find()
            ->where(['id_atleta' => $id, 'estado' => 'ACTIVA'])
            ->one();
        $porcentajeBeca = 0;
        if ($becaActiva && $becaActiva->tipoBeca) {
            $porcentajeBeca = $becaActiva->tipoBeca->porcentaje_descuento;
        }

        $representante = $atleta->representante;
        $escuela = $atleta->escuela;
        $tasaCambio = $this->obtenerTasaCambioActual();
        $datosPago = $this->obtenerDatosPagoEscuela($escuela);

        return $this->render('recibo-cobro', [
            'atleta' => $atleta,
            'representante' => $representante,
            'escuela' => $escuela,
            'deudas' => $deudasPendientes,
            'totalDeuda' => $totalDeuda,
            'becaActiva' => $becaActiva,
            'porcentajeBeca' => $porcentajeBeca,
            'tasaCambio' => $tasaCambio,
            'datosPago' => $datosPago,
        ]);
    }

    /**
     * Reporte de asistencias con filtros.
     */
    public function actionAsistencias()
    {
        $searchModel = new ReporteAsistenciasSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('asistencias', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Reporte de deudas pendientes (consolidado).
     */
    public function actionDeudasPendientes()
    {
        $user_id = Yii::$app->user->id;
        $esPersonalAutorizado = (Yii::$app->user->id == 1 
                                 || Yii::$app->user->can('admin') 
                                 || Yii::$app->user->can('superusuario') 
                                 || Yii::$app->user->can('profesor'));
        
        $deudas = [];
        
        if ($esPersonalAutorizado) {
            $id_escuela = Yii::$app->session->get('id_escuela');
            if ($id_escuela) {
                $atletas = AtletasRegistro::find()
                    ->where(['id_escuela' => $id_escuela, 'eliminado' => false])
                    ->all();
                foreach ($atletas as $atleta) {
                    $deudaAtleta = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id, 'estado' => AportesSemanales::ESTADO_PENDIENTE])
                        ->sum('monto') ?? 0;
                    if ($deudaAtleta > 0) {
                        $deudas[] = [
                            'atleta' => $atleta,
                            'monto' => $deudaAtleta,
                            'detalle' => $this->obtenerDetalleDeuda($atleta->id)
                        ];
                    }
                }
            }
        } elseif (Yii::$app->user->can('representante')) {
            $representante = RegistroRepresentantes::find()
                ->where(['user_id' => $user_id])
                ->one();
            if ($representante) {
                $atletas = AtletasRegistro::find()
                    ->where(['id_representante' => $representante->id, 'eliminado' => false])
                    ->all();
                foreach ($atletas as $atleta) {
                    $deudaAtleta = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id, 'estado' => AportesSemanales::ESTADO_PENDIENTE])
                        ->sum('monto') ?? 0;
                    if ($deudaAtleta > 0) {
                        $deudas[] = [
                            'atleta' => $atleta,
                            'monto' => $deudaAtleta,
                            'detalle' => $this->obtenerDetalleDeuda($atleta->id)
                        ];
                    }
                }
            }
        }

        return $this->render('deudas-pendientes', [
            'deudas' => $deudas,
            'totalDeuda' => array_sum(array_column($deudas, 'monto')),
        ]);
    }

    /**
     * Exportar reporte a PDF (placeholder).
     */
    public function actionExportarPdf($reporte)
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        return $this->generarPdf($reporte);
    }

    /**
     * Exportar reporte a Excel (placeholder).
     */
    public function actionExportarExcel($reporte)
    {
        return $this->generarExcel($reporte);
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Verifica si el usuario puede ver los datos de un atleta.
     */
    private function puedeVerAtleta($atleta)
    {
        $user = Yii::$app->user;
        $esPersonalAutorizado = ($user->id == 1 
                                 || $user->can('admin') 
                                 || $user->can('superusuario') 
                                 || $user->can('profesor'));
        
        if ($esPersonalAutorizado) {
            return true;
        }
        if ($user->can('representante')) {
            $representante = RegistroRepresentantes::find()->where(['user_id' => $user->id])->one();
            return $representante && $atleta->id_representante == $representante->id;
        }
        if ($user->can('atleta')) {
            return $atleta->user_id == $user->id;
        }
        return false;
    }

    /**
     * Determina si un atleta tiene una beca activa.
     */
    private function tieneBecaActiva($atleta_id)
    {
        return Beca::find()
            ->where(['id_atleta' => $atleta_id, 'estado' => 'ACTIVA'])
            ->exists();
    }

    /**
     * Obtiene los datos de pago de la escuela.
     */
    private function obtenerDatosPagoEscuela($escuela)
    {
        $nombreEscuela = $escuela ? $escuela->nombre : 'la escuela';
        // Datos de pago proporcionados por el usuario
        $texto = "Pago Móvil: 0102 11408051 04262137308 a nombre de {$nombreEscuela}.\n";
        $texto .= "También puede realizar el pago en efectivo en la dirección de la escuela.";
        return $texto;
    }

    private function obtenerDatosConsolidados($atleta)
    {
        $deudaPendiente = AportesSemanales::find()
            ->where(['atleta_id' => $atleta->id])
            ->andWhere(['estado' => AportesSemanales::ESTADO_PENDIENTE])
            ->sum('monto') ?? 0;

        $fechaInicio = date('Y-m-01');
        $fechaFin = date('Y-m-t');

        $asistencias = Asistencia::find()
            ->where(['id_atleta' => $atleta->id])
            ->andWhere(['>=', 'fecha_practica', $fechaInicio])
            ->andWhere(['<=', 'fecha_practica', $fechaFin])
            ->andWhere(['eliminado' => false])
            ->all();

        $totalAsistencias = count($asistencias);
        $asistenciasCount = 0;
        $inasistenciasCount = 0;

        foreach ($asistencias as $asistencia) {
            if ($asistencia->asistio) {
                $asistenciasCount++;
            } else {
                $inasistenciasCount++;
            }
        }

        $porcentajeAsistencia = $totalAsistencias > 0 ? 
            round(($asistenciasCount / $totalAsistencias) * 100, 2) : 0;

        return [
            'atleta' => $atleta,
            'deudaPendiente' => $deudaPendiente,
            'totalAsistencias' => $totalAsistencias,
            'asistenciasCount' => $asistenciasCount,
            'inasistenciasCount' => $inasistenciasCount,
            'porcentajeAsistencia' => $porcentajeAsistencia,
            'ultimaAsistencia' => $this->obtenerUltimaAsistencia($atleta->id),
            'proximoAporte' => $this->obtenerProximoAporte($atleta->id),
        ];
    }

    private function obtenerUltimaAsistencia($atletaId)
    {
        return Asistencia::find()
            ->where(['id_atleta' => $atletaId])
            ->andWhere(['asistio' => true])
            ->andWhere(['eliminado' => false])
            ->orderBy(['fecha_practica' => SORT_DESC])
            ->one();
    }

    private function obtenerProximoAporte($atletaId)
    {
        return AportesSemanales::find()
            ->where(['atleta_id' => $atletaId])
            ->andWhere(['estado' => AportesSemanales::ESTADO_PENDIENTE])
            ->orderBy(['fecha_quincena' => SORT_ASC])
            ->one();
    }

    private function obtenerEstadisticasDetalladas($atleta)
    {
        $estadisticasMensuales = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = date('Y-m', strtotime("-$i months"));
            $estadisticasMensuales[$mes] = $this->obtenerEstadisticasMensuales($atleta->id, $mes);
        }

        $totalAportes = AportesSemanales::find()
            ->where(['atleta_id' => $atleta->id])
            ->sum('monto') ?? 0;

        $totalAsistencias = Asistencia::find()
            ->where(['id_atleta' => $atleta->id])
            ->andWhere(['asistio' => true])
            ->count();

        return [
            'mensual' => $estadisticasMensuales,
            'totales' => [
                'aportes' => $totalAportes,
                'asistencias' => $totalAsistencias,
            ]
        ];
    }

    private function obtenerEstadisticasMensuales($atletaId, $mes)
    {
        $fechaInicio = $mes . '-01';
        $fechaFin = date('Y-m-t', strtotime($fechaInicio));

        $asistenciasMes = Asistencia::find()
            ->where(['id_atleta' => $atletaId])
            ->andWhere(['>=', 'fecha_practica', $fechaInicio])
            ->andWhere(['<=', 'fecha_practica', $fechaFin])
            ->andWhere(['eliminado' => false])
            ->all();

        $totalAsistencias = count($asistenciasMes);
        $asistenciasCount = 0;
        
        foreach ($asistenciasMes as $asistencia) {
            if ($asistencia->asistio) {
                $asistenciasCount++;
            }
        }

        $aportesMes = AportesSemanales::find()
            ->where(['atleta_id' => $atletaId])
            ->andWhere(['>=', 'fecha_quincena', $fechaInicio])
            ->andWhere(['<=', 'fecha_quincena', $fechaFin])
            ->all();

        $totalAportes = 0;
        $aportesPagados = 0;
        
        foreach ($aportesMes as $aporte) {
            $totalAportes += $aporte->monto;
            if ($aporte->estado === AportesSemanales::ESTADO_PAGADO) {
                $aportesPagados += $aporte->monto;
            }
        }

        return [
            'asistencias' => [
                'total' => $totalAsistencias,
                'asistidas' => $asistenciasCount,
                'porcentaje' => $totalAsistencias > 0 ? round(($asistenciasCount / $totalAsistencias) * 100, 2) : 0,
            ],
            'aportes' => [
                'total' => $totalAportes,
                'pagados' => $aportesPagados,
                'pendientes' => $totalAportes - $aportesPagados,
            ]
        ];
    }

    private function obtenerDetalleDeuda($atletaId)
    {
        return AportesSemanales::find()
            ->where(['atleta_id' => $atletaId, 'estado' => AportesSemanales::ESTADO_PENDIENTE])
            ->orderBy(['fecha_quincena' => SORT_ASC])
            ->all();
    }

    private function obtenerTasaCambioActual()
    {
        $tasa = TasaDolar::find()
            ->where(['eliminado' => false])
            ->orderBy(['fecha_tasa' => SORT_DESC])
            ->one();
        return $tasa ? (float) $tasa->tasa_dia : 36.50;
    }

    private function generarPdf($reporte)
    {
        return "PDF para: " . $reporte;
    }

    private function generarExcel($reporte)
    {
        return "Excel para: " . $reporte;
    }
}