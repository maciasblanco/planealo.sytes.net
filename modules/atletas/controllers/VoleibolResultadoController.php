<?php

namespace app\modules\atletas\controllers;

use Yii;
use app\models\EvaluacionResultado;
use app\models\search\EvaluacionResultadoSearch;
use app\models\VoleibolSesion;
use app\models\VoleibolSesionAtleta;
use app\models\EvaluacionEstadistica;
use app\models\EvaluacionSesionEstadistica;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

/**
 * VoleibolResultadoController gestiona los resultados (valores) de las estadísticas.
 */
class VoleibolResultadoController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'ingreso-masivo' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista los resultados (filtrables por sesión, atleta, etc.)
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EvaluacionResultadoSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Muestra un resultado específico.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Crea un nuevo resultado (individual).
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new EvaluacionResultado();
        $model->d_creacion = date('Y-m-d H:i:s');
        $model->u_creacion = Yii::$app->user->id;
        $model->dir_ip = Yii::$app->request->userIP;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Ingreso masivo de resultados para una sesión.
     * @param integer $sesion_id
     * @return mixed
     */
    public function actionIngresoMasivo($sesion_id)
    {
        $sesion = VoleibolSesion::findOne($sesion_id);
        if (!$sesion) {
            throw new NotFoundHttpException('Sesión no encontrada.');
        }

        // Obtener atletas participantes de la sesión (con equipo)
        $atletas = VoleibolSesionAtleta::find()
            ->with('atleta')
            ->where(['sesion_id' => $sesion->id])
            ->all();

        // Obtener estadísticas seleccionadas para esta sesión
        $estadisticas = EvaluacionSesionEstadistica::find()
            ->with('estadistica')
            ->where(['id_sesion' => $sesion->id])
            ->all();
        $estadisticasIds = ArrayHelper::getColumn($estadisticas, 'id_estadistica');
        $estadisticasModel = EvaluacionEstadistica::findAll($estadisticasIds);

        // Obtener valores existentes para esta sesión (para precargar)
        $existentes = EvaluacionResultado::find()
            ->where(['id_sesion' => $sesion->id])
            ->all();
        $valoresExistentes = [];
        foreach ($existentes as $e) {
            $valoresExistentes[$e->id_atleta][$e->id_estadistica][$e->set_numero ?: 0] = $e->valor_numerico;
        }

        if (Yii::$app->request->isPost) {
            $datos = Yii::$app->request->post('resultado', []);
            $sets = Yii::$app->request->post('set_numero', []);
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Eliminar resultados existentes de esta sesión (opcional: actualizar en lugar de borrar)
                EvaluacionResultado::deleteAll(['id_sesion' => $sesion->id]);

                foreach ($datos as $idAtleta => $estadisticasValores) {
                    foreach ($estadisticasValores as $idEstadistica => $valoresPorSet) {
                        foreach ($valoresPorSet as $setIdx => $valor) {
                            if ($valor !== '' && is_numeric($valor)) {
                                $setNumero = isset($sets[$idAtleta][$idEstadistica][$setIdx]) ? $sets[$idAtleta][$idEstadistica][$setIdx] : null;
                                $model = new EvaluacionResultado();
                                $model->id_sesion = $sesion->id;
                                $model->id_atleta = $idAtleta;
                                $model->id_estadistica = $idEstadistica;
                                $model->valor_numerico = $valor;
                                $model->set_numero = $setNumero ?: null;
                                $model->d_creacion = date('Y-m-d H:i:s');
                                $model->u_creacion = Yii::$app->user->id;
                                $model->dir_ip = Yii::$app->request->userIP;
                                if (!$model->save()) {
                                    throw new \Exception('Error al guardar resultado: ' . print_r($model->errors, true));
                                }
                            }
                        }
                    }
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Resultados guardados correctamente.');
                return $this->redirect(['/atletas/voleibol-sesion/view', 'id' => $sesion->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Hubo un error al guardar: ' . $e->getMessage());
            }
        }

        return $this->render('ingreso-masivo', [
            'sesion' => $sesion,
            'atletas' => $atletas,
            'estadisticas' => $estadisticasModel,
            'valoresExistentes' => $valoresExistentes,
        ]);
    }

    /**
     * Actualiza un resultado existente.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->d_update = date('Y-m-d H:i:s');
        $model->u_update = Yii::$app->user->id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Elimina (soft delete) un resultado.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->eliminado = true;
        $model->save();

        return $this->redirect(['index']);
    }

    /**
     * Encuentra el modelo EvaluacionResultado basado en su clave primaria.
     * @param integer $id
     * @return EvaluacionResultado the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EvaluacionResultado::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('La página solicitada no existe.');
    }
}