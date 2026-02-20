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
                        'roles' => ['representante', 'atleta', 'admin','superusuario'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Listado de atletas para el representante (vista: reportes-representantes.php)
     * Ahora el nombre de la acción coincide con el de la vista.
     */
    public function actionReportesRepresentantes()
    {
        $searchModel = new ReporteAtletasSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $user_id = Yii::$app->user->id;
        
        $esRepresentante = Yii::$app->user->can('representante');
        $esAtleta = Yii::$app->user->can('atleta');
        
        $atletas = [];
        $representante = null;

        if ($esRepresentante) {
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

        // Filtrar el dataProvider para que solo incluya los atletas permitidos
        $atletasIds = array_map(function($atleta) {
            return $atleta->id;
        }, $atletas);
        $dataProvider->query->andWhere(['id' => $atletasIds]);

        $datosAtletas = [];
        foreach ($atletas as $atleta) {
            $datosAtletas[] = $this->obtenerDatosConsolidados($atleta);
        }

        // Obtener tasa de cambio actual
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
            'tasaCambio' => $tasaCambio,
        ]);
    }

    /**
     * Detalle de estadísticas de un atleta (vista: reporte-atletas.php)
     */
    public function actionEstadisticasAtleta($id = null)
    {
        $user_id = Yii::$app->user->id;
        
        // Si es atleta, solo puede ver sus propias estadísticas
        if (Yii::$app->user->can('atleta') && !Yii::$app->user->can('admin')) {
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

        // Verificar permisos para representantes
        if (Yii::$app->user->can('representante') && !Yii::$app->user->can('admin')) {
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
     * Reporte de asistencias con filtros (vista: asistencias.php)
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
     * Reporte de deudas pendientes (vista: deudas-pendientes.php)
     */
    public function actionDeudasPendientes()
    {
        $user_id = Yii::$app->user->id;
        $user = Yii::$app->user->identity;
        
        $deudas = [];
        
        if (in_array('representante', $user->roles ?? [])) {
            $representante = RegistroRepresentantes::find()
                ->where(['user_id' => $user_id])
                ->one();

            if ($representante) {
                $atletas = AtletasRegistro::find()
                    ->where(['id_representante' => $representante->id])
                    ->andWhere(['eliminado' => false])
                    ->all();

                foreach ($atletas as $atleta) {
                    $deudaAtleta = AportesSemanales::find()
                        ->where(['atleta_id' => $atleta->id])
                        ->andWhere(['pagado' => false])
                        ->andWhere(['eliminado' => false])
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
     * Exportar reporte a PDF
     */
    public function actionExportarPdf($reporte)
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/pdf');

        // Lógica para generar PDF según el tipo de reporte
        return $this->generarPdf($reporte);
    }

    /**
     * Exportar reporte a Excel
     */
    public function actionExportarExcel($reporte)
    {
        // Lógica para generar Excel
        return $this->generarExcel($reporte);
    }

    // =========================================================================
    // Métodos privados (se mantienen igual)
    // =========================================================================

    private function obtenerDatosConsolidados($atleta)
    {
        $deudaPendiente = AportesSemanales::find()
            ->where(['atleta_id' => $atleta->id])
            ->andWhere(['pagado' => false])
            ->andWhere(['eliminado' => false])
            ->sum('monto') ?? 0;

        $fechaInicio = date('Y-m-01');
        $fechaFin = date('Y-m-t');

        $asistencias = Asistencia::find()
            ->where(['id_atleta' => $atleta->id])
            ->andWhere(['>=', 'fecha', $fechaInicio])
            ->andWhere(['<=', 'fecha', $fechaFin])
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
            ->orderBy(['fecha' => SORT_DESC])
            ->one();
    }

    private function obtenerProximoAporte($atletaId)
    {
        return AportesSemanales::find()
            ->where(['atleta_id' => $atletaId])
            ->andWhere(['pagado' => false])
            ->andWhere(['eliminado' => false])
            ->orderBy(['fecha' => SORT_ASC])
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
            ->andWhere(['eliminado' => false])
            ->sum('monto') ?? 0;

        $totalAsistencias = Asistencia::find()
            ->where(['id_atleta' => $atleta->id])
            ->andWhere(['asistio' => true])
            ->andWhere(['eliminado' => false])
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
            ->andWhere(['>=', 'fecha', $fechaInicio])
            ->andWhere(['<=', 'fecha', $fechaFin])
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
            ->andWhere(['>=', 'fecha', $fechaInicio])
            ->andWhere(['<=', 'fecha', $fechaFin])
            ->andWhere(['eliminado' => false])
            ->all();

        $totalAportes = 0;
        $aportesPagados = 0;
        
        foreach ($aportesMes as $aporte) {
            $totalAportes += $aporte->monto;
            if ($aporte->pagado) {
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
            ->where(['atleta_id' => $atletaId])
            ->andWhere(['pagado' => false])
            ->andWhere(['eliminado' => false])
            ->orderBy(['fecha' => SORT_ASC])
            ->all();
    }

    private function obtenerTasaCambioActual()
    {
        $tasa = TasaDolar::find()
            ->where(['eliminado' => false])
            ->orderBy(['fecha_tasa' => SORT_DESC])
            ->one();

        if ($tasa) {
            return (float) $tasa->tasa_dia;
        }

        return 36.50;
    }

    private function generarPdf($reporte)
    {
        $content = "Reporte: " . $reporte;
        return $content;
    }

    private function generarExcel($reporte)
    {
        return "Excel para: " . $reporte;
    }
}