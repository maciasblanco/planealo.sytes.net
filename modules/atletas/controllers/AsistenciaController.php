<?php
// modules/atletas/controllers/AsistenciaController.php

namespace app\modules\atletas\controllers;

use Yii;
use app\models\Asistencia;
use app\models\AtletasRegistro;
use app\modules\atletas\models\AsistenciaSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * AsistenciaController implements the CRUD actions for Asistencia model.
 */
class AsistenciaController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'registrar-rapido' => ['POST'],
                    'marcar-salida' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Asistencia models.
     */
    public function actionIndex()
    {
        $searchModel = new AsistenciaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Estadísticas rápidas
        $estadisticas = $this->getEstadisticasRapidas();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * Displays a single Asistencia model.
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Asistencia model.
     */
    public function actionCreate()
    {
        $model = new Asistencia();
        $model->fecha_practica = date('Y-m-d');
        $model->hora_entrada = date('H:i:s');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Asistencia registrada exitosamente.');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Registro rápido de asistencia (para uso en tablets/móviles)
     */
    public function actionRegistroRapido()
    {
        $model = new Asistencia();
        $model->fecha_practica = date('Y-m-d');
        $model->hora_entrada = date('H:i:s');
        $model->asistio = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 
                '✅ Asistencia registrada: ' . $model->atleta->p_nombre . ' ' . $model->atleta->p_apellido);
            return $this->redirect(['registro-rapido']);
        }

        // Obtener atletas activos para el select - CORREGIDO
        $atletas = AtletasRegistro::find()
            ->where(['eliminado' => false])
            ->orderBy(['p_nombre' => SORT_ASC, 'p_apellido' => SORT_ASC])
            ->all();

        return $this->render('registro-rapido', [
            'model' => $model,
            'atletas' => $atletas,
        ]);
    }

    /**
     * API para registro rápido via AJAX
     */
    public function actionRegistrarRapido()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $idAtleta = Yii::$app->request->post('id_atleta');
        $idEscuela = Yii::$app->request->post('id_escuela');

        $resultado = Asistencia::registrarAsistencia($idAtleta, $idEscuela);

        if ($resultado['success']) {
            return ['success' => true, 'message' => 'Asistencia registrada exitosamente'];
        } else {
            return ['success' => false, 'message' => $resultado['message']];
        }
    }

    /**
     * Marcar salida de atleta
     */
    public function actionMarcarSalida($id)
    {
        $model = $this->findModel($id);
        
        if ($model->registrarSalida()) {
            Yii::$app->session->setFlash('success', 'Salida registrada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al registrar salida.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Updates an existing Asistencia model.
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Asistencia actualizada exitosamente.');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Asistencia model.
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->eliminado = true;
        $model->save();

        Yii::$app->session->setFlash('success', 'Asistencia eliminada exitosamente.');
        return $this->redirect(['index']);
    }

    /**
     * Reporte de asistencias
     */
    public function actionReporte()
    {
        $searchModel = new AsistenciaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Estadísticas detalladas
        $estadisticas = Asistencia::getEstadisticas(
            Yii::$app->request->get('id_escuela'),
            Yii::$app->request->get('mes'),
            Yii::$app->request->get('ano')
        );

        if (Yii::$app->request->get('export')) {
            return $this->exportarReporte($dataProvider);
        }

        return $this->render('reporte', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * Dashboard de asistencias
     */
    public function actionDashboard()
    {
        $mes = date('m');
        $ano = date('Y');

        // Obtener estadísticas del mes actual
        $estadisticas = Asistencia::getEstadisticas(null, $mes, $ano);

        // Asistencias por día del mes actual
        $asistenciasPorDia = Asistencia::find()
            ->select(['fecha_practica', 'COUNT(*) as total'])
            ->where(['asistio' => true, 'eliminado' => false])
            ->andWhere("EXTRACT(MONTH FROM fecha_practica) = $mes")
            ->andWhere("EXTRACT(YEAR FROM fecha_practica) = $ano")
            ->groupBy('fecha_practica')
            ->asArray()
            ->all();

        return $this->render('dashboard', [
            'estadisticas' => $estadisticas,
            'asistenciasPorDia' => $asistenciasPorDia,
            'mes' => $mes,
            'ano' => $ano,
        ]);
    }

    /**
     * Obtener estadísticas rápidas para el índice
     */
    private function getEstadisticasRapidas()
    {
        $hoy = date('Y-m-d');
        $mes = date('m');
        $ano = date('Y');

        return [
            'asistencias_hoy' => Asistencia::find()
                ->where(['fecha_practica' => $hoy, 'asistio' => true, 'eliminado' => false])
                ->count(),
            'total_atletas' => AtletasRegistro::find()
                ->where(['eliminado' => false])
                ->count(),
            'porcentaje_asistencia_mes' => $this->calcularPorcentajeAsistenciaMes($mes, $ano),
        ];
    }

    /**
     * Calcular porcentaje de asistencia del mes
     */
    private function calcularPorcentajeAsistenciaMes($mes, $ano)
    {
        $totalDias = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
        $totalAtletas = AtletasRegistro::find()->where(['eliminado' => false])->count();
        $maxAsistencias = $totalDias * $totalAtletas;

        if ($maxAsistencias == 0) return 0;

        $totalAsistencias = Asistencia::find()
            ->where(['asistio' => true, 'eliminado' => false])
            ->andWhere("EXTRACT(MONTH FROM fecha_practica) = $mes")
            ->andWhere("EXTRACT(YEAR FROM fecha_practica) = $ano")
            ->count();

        return round(($totalAsistencias / $maxAsistencias) * 100, 1);
    }

    /**
     * Exportar reporte a Excel
     */
    private function exportarReporte($dataProvider)
    {
        // Implementar exportación a Excel aquí
        // Puedes usar PHPExcel o PhpSpreadsheet
        Yii::$app->session->setFlash('info', 'Función de exportación en desarrollo.');
        return $this->redirect(['reporte']);
    }

    /**
     * Finds the Asistencia model based on its primary key value.
     */
    protected function findModel($id)
    {
        if (($model = Asistencia::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La asistencia solicitada no existe.');
    }
    /**
     * Registro múltiple de asistencia por escuela
     */
    public function actionRegistroMultiple()
    {
        $model = new Asistencia();
        $model->fecha_practica = date('Y-m-d');
        $model->hora_entrada = date('H:i:s');
        $model->asistio = true;

        $atletas = [];
        $idEscuelaSeleccionada = Yii::$app->request->get('id_escuela');

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $idAtletas = $post['id_atletas'] ?? [];
            $idEscuela = $post['id_escuela'] ?? null;
            $comentarios = $post['Asistencia']['comentarios'] ?? '';
            
            $registrosExitosos = 0;
            $errores = [];

            foreach ($idAtletas as $idAtleta) {
                $fechaPractica = $post['Asistencia']['fecha_practica'] ?? date('Y-m-d');
                $resultado = Asistencia::registrarAsistencia($idAtleta, $idEscuela, $fechaPractica);
                
                if ($resultado['success']) {
                    $registrosExitosos++;
                    
                    // Si hay comentarios, actualizar el registro
                    if (!empty($comentarios)) {
                        $asistencia = Asistencia::findOne($resultado['id']);
                        if ($asistencia) {
                            $asistencia->comentarios = $comentarios;
                            $asistencia->save();
                        }
                    }
                } else {
                    $atleta = AtletasRegistro::findOne($idAtleta);
                    $nombreAtleta = $atleta ? $atleta->p_nombre . ' ' . $atleta->p_apellido : 'ID ' . $idAtleta;
                    $errores[] = "{$nombreAtleta}: " . $resultado['message'];
                }
            }

            if ($registrosExitosos > 0) {
                Yii::$app->session->setFlash('success', 
                    "✅ Se registró la asistencia correctamente para {$registrosExitosos} atleta(s).");
            }

            if (!empty($errores)) {
                Yii::$app->session->setFlash('error', 
                    "❌ Errores encontrados:<br>" . implode('<br>', $errores));
            }

            return $this->redirect(['registro-multiple', 'id_escuela' => $idEscuela]);
        }

        // Si hay una escuela seleccionada, obtener sus atletas
        if ($idEscuelaSeleccionada) {
            $atletas = AtletasRegistro::find()
                ->where(['eliminado' => false, 'id_escuela' => $idEscuelaSeleccionada])
                ->orderBy(['p_nombre' => SORT_ASC, 'p_apellido' => SORT_ASC])
                ->all();
        }

        return $this->render('registro-multiple', [
            'model' => $model,
            'atletas' => $atletas,
            'idEscuelaSeleccionada' => $idEscuelaSeleccionada,
        ]);
    }

    /**
     * Obtener atletas por escuela via AJAX
     */
    public function actionGetAtletasByEscuela($id_escuela)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $atletas = AtletasRegistro::find()
            ->where(['eliminado' => false, 'id_escuela' => $id_escuela])
            ->orderBy(['p_nombre' => SORT_ASC, 'p_apellido' => SORT_ASC])
            ->all();

        $data = [];
        foreach ($atletas as $atleta) {
            $data[] = [
                'id' => $atleta->id,
                'nombre' => $atleta->p_nombre . ' ' . $atleta->p_apellido,
                'identificacion' => $atleta->identificacion,
                'categoria' => $atleta->getCategoriaNombre()
            ];
        }

        return [
            'success' => true,
            'atletas' => $data,
            'total' => count($atletas)
        ];
    }

    /**
     * Obtener asistencias de hoy para una escuela
     */
    public function actionGetAsistenciasHoy($id_escuela)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $asistenciasHoy = Asistencia::find()
            ->select('id_atleta')
            ->where(['fecha_practica' => date('Y-m-d'), 'asistio' => true, 'eliminado' => false, 'id_escuela' => $id_escuela])
            ->column();

        return [
            'success' => true,
            'asistenciasHoy' => $asistenciasHoy
        ];
    }

    /**
     * Obtener información de la escuela para AJAX
     */
    public function actionGetInfoEscuela($id_escuela)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $escuela = Escuela::findOne($id_escuela);
        if (!$escuela) {
            return ['success' => false];
        }

        $totalAtletas = AtletasRegistro::find()
            ->where(['eliminado' => false, 'id_escuela' => $id_escuela])
            ->count();

        $asistenciasHoy = Asistencia::find()
            ->where(['fecha_practica' => date('Y-m-d'), 'asistio' => true, 'eliminado' => false, 'id_escuela' => $id_escuela])
            ->count();

        $html = "
            <h5>{$escuela->nombre}</h5>
            <div class='row text-center mt-3'>
                <div class='col-4'>
                    <div class='bg-primary text-white p-2 rounded'>
                        <h6 class='mb-0'>{$totalAtletas}</h6>
                        <small>Total Atletas</small>
                    </div>
                </div>
                <div class='col-4'>
                    <div class='bg-success text-white p-2 rounded'>
                        <h6 class='mb-0'>{$asistenciasHoy}</h6>
                        <small>Asistencias Hoy</small>
                    </div>
                </div>
                <div class='col-4'>
                    <div class='bg-warning text-white p-2 rounded'>
                        <h6 class='mb-0'>" . ($totalAtletas - $asistenciasHoy) . "</h6>
                        <small>Sin Asistencia</small>
                    </div>
                </div>
            </div>
        ";

        return [
            'success' => true,
            'html' => $html
        ];
    }
}