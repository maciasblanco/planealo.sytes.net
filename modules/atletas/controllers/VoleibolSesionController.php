<?php

namespace app\modules\atletas\controllers;

use Yii;
use app\models\VoleibolSesion;
use app\models\VoleibolSet;
use app\models\VoleibolSesionAtleta;
use app\models\VoleibolEvento;
use app\models\EvaluacionEstadistica;
use app\models\EvaluacionSesionEstadistica;
use app\modules\atletas\models\VoleibolSesionSearch;
use app\models\AtletasRegistro;
use app\models\Escuela;
use app\models\EncargadoEscuela;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

/**
 * VoleibolSesionController implementa las acciones CRUD para VoleibolSesion.
 */
class VoleibolSesionController extends Controller
{
    /**
     * {@inheritdoc}
     */
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
                    'finalizar' => ['POST'],
                    'agregar-atleta' => ['POST'],
                    'quitar-atleta' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista todas las sesiones de voleibol.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new VoleibolSesionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Filtrar por escuela del usuario (si es entrenador)
        $userId = Yii::$app->user->id;
        $encargado = EncargadoEscuela::findOne(['user_id' => $userId]);
        if ($encargado) {
            $searchModel->escuela_id = $encargado->id_escuela;
            $dataProvider->query->andWhere(['escuela_id' => $encargado->id_escuela]);
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Muestra una sesión específica.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $setActivo = $model->setActivo;

        // Si no hay set activo, crear el primero (solo si la sesión está activa)
        if (!$setActivo && $model->estado == 'A') {
            $setActivo = $this->crearNuevoSet($model);
        }

        // Obtener estadísticas seleccionadas para esta sesión
        $estadisticasSeleccionadas = $model->estadisticasSeleccionadas;

        // Obtener atletas por equipo
        $atletasEquipoA = VoleibolSesionAtleta::find()
            ->with('atleta')
            ->where(['sesion_id' => $model->id, 'equipo' => 'A'])
            ->all();
        $atletasEquipoB = VoleibolSesionAtleta::find()
            ->with('atleta')
            ->where(['sesion_id' => $model->id, 'equipo' => 'B'])
            ->all();

        return $this->render('view', [
            'model' => $model,
            'setActivo' => $setActivo,
            'estadisticasSeleccionadas' => $estadisticasSeleccionadas,
            'atletasEquipoA' => $atletasEquipoA,
            'atletasEquipoB' => $atletasEquipoB,
        ]);
    }

    /**
     * Crea una nueva sesión.
     * Si la creación es exitosa, redirige a la vista de gestión de atletas.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new VoleibolSesion();
        $model->fecha = date('Y-m-d');
        $model->created_by = Yii::$app->user->id;

        // Asignar escuela automática si el usuario es entrenador de una sola escuela
        $userId = Yii::$app->user->id;
        $encargado = EncargadoEscuela::findOne(['user_id' => $userId]);
        if ($encargado) {
            $model->escuela_id = $encargado->id_escuela;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['gestionar-atletas', 'id' => $model->id]);
        }

        // Lista de escuelas para el dropdown (solo las que el usuario puede gestionar)
        if ($encargado) {
            $escuelas = [$encargado->id_escuela => $encargado->escuela->nombre];
        } else {
            // Si es admin, mostrar todas
            $escuelas = ArrayHelper::map(Escuela::find()->all(), 'id', 'nombre');
        }

        return $this->render('create', [
            'model' => $model,
            'escuelas' => $escuelas,
        ]);
    }

    /**
     * Actualiza una sesión existente.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $escuelas = ArrayHelper::map(Escuela::find()->all(), 'id', 'nombre');

        return $this->render('update', [
            'model' => $model,
            'escuelas' => $escuelas,
        ]);
    }

    /**
     * Elimina (soft delete) una sesión.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->eliminado = true;
        $model->save();

        return $this->redirect(['index']);
    }

    /**
     * Gestiona los atletas participantes de una sesión (asignar/equipos).
     * @param integer $id
     * @return mixed
     */
    public function actionGestionarAtletas($id)
    {
        $sesion = $this->findModel($id);

        // Lista de atletas de la escuela (y categoría si aplica)
        $query = AtletasRegistro::find()
            ->where(['id_escuela' => $sesion->escuela_id, 'eliminado' => false]);
        if ($sesion->categoria_id) {
            $query->andWhere(['id_categoria' => $sesion->categoria_id]);
        }
        // ✅ CORRECCIÓN: Ordenar por las columnas reales de la tabla
        $atletas = $query->orderBy(['p_apellido' => SORT_ASC, 'p_nombre' => SORT_ASC])->all();

        // Atletas ya asignados
        $asignados = VoleibolSesionAtleta::find()
            ->where(['sesion_id' => $sesion->id])
            ->indexBy('atleta_id')
            ->all();

        if (Yii::$app->request->isPost) {
            $equiposPost = Yii::$app->request->post('equipo', []);
            // Procesar cada atleta
            foreach ($atletas as $atleta) {
                $atletaId = $atleta->id;
                $equipo = isset($equiposPost[$atletaId]) ? $equiposPost[$atletaId] : null;
                $asignado = isset($asignados[$atletaId]) ? $asignados[$atletaId] : null;

                if ($equipo && in_array($equipo, ['A', 'B'])) {
                    if (!$asignado) {
                        $nuevo = new VoleibolSesionAtleta();
                        $nuevo->sesion_id = $sesion->id;
                        $nuevo->atleta_id = $atletaId;
                        $nuevo->equipo = $equipo;
                        $nuevo->save();
                    } elseif ($asignado->equipo != $equipo) {
                        $asignado->equipo = $equipo;
                        $asignado->save();
                    }
                } else {
                    if ($asignado) {
                        $asignado->delete();
                    }
                }
            }
            Yii::$app->session->setFlash('success', 'Participantes actualizados.');
            return $this->redirect(['view', 'id' => $sesion->id]);
        }

        return $this->render('gestionar-atletas', [
            'sesion' => $sesion,
            'atletas' => $atletas,
            'asignados' => $asignados,
        ]);
    }

    /**
     * Acción AJAX para finalizar un set y pasar al siguiente.
     * @return mixed
     */
    public function actionFinalizarSet()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id = Yii::$app->request->post('id');
        $set = VoleibolSet::findOne($id);
        if (!$set) {
            return ['success' => false, 'error' => 'Set no encontrado.'];
        }

        // Validar reglas del set (puntos mínimos, diferencia 2) según categoría
        if (!$this->validarReglasSet($set)) {
            return ['success' => false, 'error' => 'El set no cumple las reglas de puntuación.'];
        }

        $set->estado = 'F';
        $set->ganador = ($set->puntos_a > $set->puntos_b) ? 'A' : 'B';
        if ($set->save()) {
            // Crear siguiente set
            $nuevoSet = $this->crearNuevoSet($set->sesion);
            return ['success' => true, 'nuevoSet' => $nuevoSet ? $nuevoSet->id : null];
        }
        return ['success' => false, 'error' => 'Error al finalizar set.'];
    }

    /**
     * Acción AJAX para deshacer el último evento registrado.
     */
    public function actionDeshacerEvento()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $sesionId = Yii::$app->request->post('sesion_id');
        $sesion = VoleibolSesion::findOne($sesionId);
        if (!$sesion) {
            return ['success' => false, 'error' => 'Sesión no encontrada.'];
        }
        $setActivo = $sesion->setActivo;
        if (!$setActivo) {
            return ['success' => false, 'error' => 'No hay set activo.'];
        }
        $ultimoEvento = VoleibolEvento::find()
            ->where(['set_id' => $setActivo->id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if (!$ultimoEvento) {
            return ['success' => false, 'error' => 'No hay eventos para deshacer.'];
        }
        if ($ultimoEvento->delete()) {
            // Recalcular puntos del set
            $puntosA = VoleibolEvento::find()
                ->joinWith('tipoEvento')
                ->where(['set_id' => $setActivo->id])
                ->andWhere(['equipo_afectado' => 'P'])
                ->sum('puntos');
            $puntosB = VoleibolEvento::find()
                ->joinWith('tipoEvento')
                ->where(['set_id' => $setActivo->id])
                ->andWhere(['equipo_afectado' => 'C'])
                ->sum('puntos');
            $setActivo->puntos_a = $puntosA ?: 0;
            $setActivo->puntos_b = $puntosB ?: 0;
            $setActivo->save();
            return ['success' => true, 'puntos_a' => $setActivo->puntos_a, 'puntos_b' => $setActivo->puntos_b];
        }
        return ['success' => false, 'error' => 'Error al eliminar evento.'];
    }

    /**
     * Acción AJAX para seleccionar estadísticas a evaluar en la sesión.
     */
    public function actionSeleccionarEstadisticas($id)
    {
        $sesion = $this->findModel($id);
        $estadisticasDisponibles = EvaluacionEstadistica::find()->where(['activo' => true])->all();

        if (Yii::$app->request->isPost) {
            $seleccionadas = Yii::$app->request->post('estadisticas', []);
            // Eliminar relaciones anteriores
            EvaluacionSesionEstadistica::deleteAll(['id_sesion' => $sesion->id]);
            // Insertar nuevas
            foreach ($seleccionadas as $idEstadistica) {
                $rel = new EvaluacionSesionEstadistica();
                $rel->id_sesion = $sesion->id;
                $rel->id_estadistica = $idEstadistica;
                $rel->save();
            }
            Yii::$app->session->setFlash('success', 'Estadísticas actualizadas.');
            return $this->redirect(['view', 'id' => $sesion->id]);
        }

        $seleccionadasActuales = ArrayHelper::map($sesion->estadisticasSeleccionadas, 'id', 'id');

        return $this->render('seleccionar-estadisticas', [
            'sesion' => $sesion,
            'estadisticasDisponibles' => $estadisticasDisponibles,
            'seleccionadasActuales' => $seleccionadasActuales,
        ]);
    }

    /**
     * Encuentra el modelo VoleibolSesion basado en su clave primaria.
     * Si no lo encuentra, lanza una excepción 404.
     * @param integer $id
     * @return VoleibolSesion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = VoleibolSesion::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('La página solicitada no existe.');
    }

    /**
     * Crea un nuevo set para una sesión.
     * @param VoleibolSesion $sesion
     * @return VoleibolSet|null
     */
    protected function crearNuevoSet($sesion)
    {
        $maxSet = VoleibolSet::find()->where(['sesion_id' => $sesion->id])->max('numero');
        $nuevoSet = new VoleibolSet();
        $nuevoSet->sesion_id = $sesion->id;
        $nuevoSet->numero = $maxSet ? $maxSet + 1 : 1;
        $nuevoSet->estado = 'A';
        // ✅ CORRECCIÓN: Asignar valores por defecto a puntos_a y puntos_b (no nulos)
        $nuevoSet->puntos_a = 0;
        $nuevoSet->puntos_b = 0;
        return $nuevoSet->save() ? $nuevoSet : null;
    }

    /**
     * Valida las reglas de puntuación de un set según la categoría.
     * @param VoleibolSet $set
     * @return bool
     */
    protected function validarReglasSet($set)
    {
        $categoria = $set->sesion->categoria;
        $puntosMinimos = 25; // por defecto
        if ($categoria && stripos($categoria->nombre_venezuela, 'semillita') !== false) {
            $puntosMinimos = 21;
        }
        if ($set->numero == 5) {
            $puntosMinimos = 15;
        }
        $diferencia = abs($set->puntos_a - $set->puntos_b);
        return ($set->puntos_a >= $puntosMinimos || $set->puntos_b >= $puntosMinimos) && $diferencia >= 2;
    }
}