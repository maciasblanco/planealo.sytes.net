<?php

namespace app\modules\atletas\controllers;

use Yii;
use app\models\VoleibolSesion;
use app\models\VoleibolSet;
use app\models\VoleibolSesionAtleta;
use app\models\VoleibolAlineacion;
use app\models\VoleibolSustitucion;
use app\models\VoleibolEvento;
use app\models\EvaluacionEstadistica;
use app\models\EvaluacionSesionEstadistica;
use app\modules\atletas\models\VoleibolSesionSearch;
use app\models\AtletasRegistro;
use app\models\Escuela;
use app\models\EncargadoEscuela;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
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
                    'sumar-punto' => ['POST'],
                    'rotar' => ['POST'],
                    'sustituir' => ['POST'],
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
                ->andWhere(['equipo_afectado' => 'A']) // Suponiendo que 'A' es el equipo local
                ->sum('puntos');
            $puntosB = VoleibolEvento::find()
                ->joinWith('tipoEvento')
                ->where(['set_id' => $setActivo->id])
                ->andWhere(['equipo_afectado' => 'B']) // Suponiendo que 'B' es el equipo visitante
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

    // ============================================================
    // NUEVAS ACCIONES PARA MARCADOR EN VIVO
    // ============================================================

    /**
     * Configura la alineación inicial de un set.
     * @param int $id ID de la sesión
     * @param int|null $set ID del set (si es null, se usa el activo)
     * @return mixed
     */
    public function actionAlineacion($id, $set = null)
    {
        $sesion = $this->findModel($id);
        if ($set === null) {
            $set = $sesion->setActivo;
            if (!$set) {
                Yii::$app->session->setFlash('error', 'No hay un set activo. Inicie un set primero.');
                return $this->redirect(['view', 'id' => $id]);
            }
        } else {
            $set = VoleibolSet::findOne($set);
            if (!$set || $set->sesion_id != $id) {
                throw new NotFoundHttpException('Set no encontrado.');
            }
        }

        // Obtener atletas asignados a la sesión con equipo
        $atletasSesion = VoleibolSesionAtleta::find()
            ->with('atleta')
            ->where(['sesion_id' => $id])
            ->all();

        $atletasPorEquipo = [
            'A' => [],
            'B' => [],
        ];
        foreach ($atletasSesion as $sa) {
            $atletasPorEquipo[$sa->equipo][] = $sa->atleta;
        }

        // Obtener alineaciones ya guardadas para este set
        $alineaciones = VoleibolAlineacion::find()
            ->where(['sesion_id' => $id, 'set_id' => $set->id])
            ->indexBy(function ($row) {
                return $row['equipo'] . '_' . $row['posicion'];
            })
            ->all();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $equipos = ['A', 'B'];
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Eliminar alineaciones anteriores del set
                VoleibolAlineacion::deleteAll(['sesion_id' => $id, 'set_id' => $set->id]);

                foreach ($equipos as $eq) {
                    $posiciones = $post['alineacion'][$eq] ?? [];
                    // Debe haber exactamente 6 posiciones
                    if (count($posiciones) != 6) {
                        throw new \Exception("El equipo {$eq} debe tener 6 jugadores en cancha.");
                    }
                    foreach ($posiciones as $pos => $atletaId) {
                        if (empty($atletaId)) {
                            throw new \Exception("La posición {$pos} del equipo {$eq} no puede estar vacía.");
                        }
                        $alineacion = new VoleibolAlineacion();
                        $alineacion->sesion_id = $id;
                        $alineacion->set_id = $set->id;
                        $alineacion->equipo = $eq;
                        $alineacion->atleta_id = $atletaId;
                        $alineacion->posicion = $pos;
                        $alineacion->created_by = Yii::$app->user->id;
                        if (!$alineacion->save()) {
                            throw new \Exception('Error al guardar alineación: ' . print_r($alineacion->errors, true));
                        }
                    }
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Alineación guardada correctamente.');
                return $this->redirect(['marcador', 'id' => $id, 'set' => $set->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('alineacion', [
            'sesion' => $sesion,
            'set' => $set,
            'atletasPorEquipo' => $atletasPorEquipo,
            'alineaciones' => $alineaciones,
        ]);
    }

    /**
     * Muestra el marcador en vivo del set.
     * @param int $id
     * @param int|null $set
     * @return string
     */
    public function actionMarcador($id, $set = null)
    {
        $sesion = $this->findModel($id);
        if ($set === null) {
            $set = $sesion->setActivo;
            if (!$set) {
                Yii::$app->session->setFlash('error', 'No hay un set activo.');
                return $this->redirect(['view', 'id' => $id]);
            }
        } else {
            $set = VoleibolSet::findOne($set);
            if (!$set || $set->sesion_id != $id) {
                throw new NotFoundHttpException('Set no encontrado.');
            }
        }

        // Obtener alineación actual
        $alineaciones = VoleibolAlineacion::find()
            ->with('atleta')
            ->where(['sesion_id' => $id, 'set_id' => $set->id])
            ->all();

        // Organizar por equipo y posición
        $alineacion = ['A' => [], 'B' => []];
        foreach ($alineaciones as $a) {
            $alineacion[$a->equipo][$a->posicion] = $a->atleta;
        }

        // Contar sustituciones usadas por equipo en este set
        $sustitucionesUsadas = [
            'A' => VoleibolSustitucion::find()->where(['sesion_id' => $id, 'set_id' => $set->id, 'equipo' => 'A'])->count(),
            'B' => VoleibolSustitucion::find()->where(['sesion_id' => $id, 'set_id' => $set->id, 'equipo' => 'B'])->count(),
        ];
        $maxSustituciones = 6;

        // Atletas en banca (asignados a la sesión pero no en alineación)
        $atletasEnSesion = VoleibolSesionAtleta::find()
            ->with('atleta')
            ->where(['sesion_id' => $id])
            ->all();
        $banca = ['A' => [], 'B' => []];
        $idsEnCancha = [];
        foreach ($alineaciones as $a) {
            $idsEnCancha[] = $a->atleta_id;
        }
        foreach ($atletasEnSesion as $sa) {
            if (!in_array($sa->atleta_id, $idsEnCancha)) {
                $banca[$sa->equipo][] = $sa->atleta;
            }
        }

        return $this->render('marcador', [
            'sesion' => $sesion,
            'set' => $set,
            'alineacion' => $alineacion,
            'sustitucionesUsadas' => $sustitucionesUsadas,
            'maxSustituciones' => $maxSustituciones,
            'banca' => $banca,
        ]);
    }

    /**
     * Acción AJAX para sumar un punto a un equipo.
     * @return array
     */
    public function actionSumarPunto()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $setId = Yii::$app->request->post('set_id');
        $equipo = Yii::$app->request->post('equipo');

        $set = VoleibolSet::findOne($setId);
        if (!$set) {
            return ['success' => false, 'error' => 'Set no encontrado.'];
        }

        // Incrementar puntos del equipo
        if ($equipo == 'A') {
            $set->puntos_a += 1;
        } elseif ($equipo == 'B') {
            $set->puntos_b += 1;
        } else {
            return ['success' => false, 'error' => 'Equipo inválido.'];
        }

        // Registrar evento (opcional)
        $evento = new VoleibolEvento();
        $evento->sesion_id = $set->sesion_id;
        $evento->set_id = $set->id;
        // Buscar tipo de evento "Punto" (asumimos que existe con codigo 'PUNTO')
        $tipoEvento = \app\models\VoleibolTipoEvento::findOne(['codigo' => 'PUNTO']);
        if ($tipoEvento) {
            $evento->tipo_evento_id = $tipoEvento->id;
        }
        $evento->created_by = Yii::$app->user->id;
        $evento->save();

        // Verificar si el set terminó según reglas FIV
        $puntosMinimos = 25;
        if ($set->numero == 5) {
            $puntosMinimos = 15;
        }
        $diferencia = abs($set->puntos_a - $set->puntos_b);
        $terminado = ($set->puntos_a >= $puntosMinimos || $set->puntos_b >= $puntosMinimos) && $diferencia >= 2;

        if ($terminado) {
            $set->estado = 'F';
            $set->ganador = $set->puntos_a > $set->puntos_b ? 'A' : 'B';
            $set->save();

            // Crear siguiente set si corresponde (máximo 5)
            $totalSets = VoleibolSet::find()->where(['sesion_id' => $set->sesion_id])->count();
            if ($totalSets < 5) {
                $nuevoSet = $this->crearNuevoSet($set->sesion);
                $nuevoSetId = $nuevoSet ? $nuevoSet->id : null;
            } else {
                // Finalizar sesión
                $sesion = VoleibolSesion::findOne($set->sesion_id);
                $sesion->estado = 'F';
                $sesion->save();
                $nuevoSetId = null;
            }

            return [
                'success' => true,
                'terminado' => true,
                'ganador' => $set->ganador,
                'puntos_a' => $set->puntos_a,
                'puntos_b' => $set->puntos_b,
                'nuevo_set_id' => $nuevoSetId ?? null,
                'mensaje' => 'Set finalizado. ' . ($nuevoSetId ? 'Nuevo set creado.' : 'Sesión finalizada.'),
            ];
        } else {
            $set->save();
            return [
                'success' => true,
                'terminado' => false,
                'puntos_a' => $set->puntos_a,
                'puntos_b' => $set->puntos_b,
            ];
        }
    }

    /**
     * Acción AJAX para rotar las posiciones de un equipo.
     */
    public function actionRotar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $setId = Yii::$app->request->post('set_id');
        $equipo = Yii::$app->request->post('equipo');

        $set = VoleibolSet::findOne($setId);
        if (!$set) {
            return ['success' => false, 'error' => 'Set no encontrado.'];
        }

        // Obtener alineación actual ordenada por posición
        $alineaciones = VoleibolAlineacion::find()
            ->where(['sesion_id' => $set->sesion_id, 'set_id' => $set->id, 'equipo' => $equipo])
            ->orderBy(['posicion' => SORT_ASC])
            ->all();

        if (count($alineaciones) != 6) {
            return ['success' => false, 'error' => 'Debe haber 6 jugadores en cancha para rotar.'];
        }

        // Rotación cíclica: posición 1 -> 6, 2 -> 1, 3 -> 2, 4 -> 3, 5 -> 4, 6 -> 5
        $atletas = [];
        foreach ($alineaciones as $a) {
            $atletas[$a->posicion] = $a->atleta_id;
        }
        $nuevasPosiciones = [
            1 => $atletas[2],
            2 => $atletas[3],
            3 => $atletas[4],
            4 => $atletas[5],
            5 => $atletas[6],
            6 => $atletas[1],
        ];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($nuevasPosiciones as $pos => $atletaId) {
                $model = VoleibolAlineacion::findOne([
                    'sesion_id' => $set->sesion_id,
                    'set_id' => $set->id,
                    'equipo' => $equipo,
                    'posicion' => $pos,
                ]);
                if (!$model) {
                    $model = new VoleibolAlineacion();
                    $model->sesion_id = $set->sesion_id;
                    $model->set_id = $set->id;
                    $model->equipo = $equipo;
                    $model->posicion = $pos;
                }
                $model->atleta_id = $atletaId;
                $model->created_by = Yii::$app->user->id;
                if (!$model->save()) {
                    throw new \Exception('Error al guardar rotación.');
                }
            }
            $transaction->commit();
            return ['success' => true, 'mensaje' => 'Rotación aplicada.'];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Acción AJAX para realizar una sustitución.
     */
    public function actionSustituir()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $setId = Yii::$app->request->post('set_id');
        $equipo = Yii::$app->request->post('equipo');
        $atletaSaleId = Yii::$app->request->post('atleta_sale_id');
        $atletaEntraId = Yii::$app->request->post('atleta_entra_id');

        $set = VoleibolSet::findOne($setId);
        if (!$set) {
            return ['success' => false, 'error' => 'Set no encontrado.'];
        }

        // Verificar límite de sustituciones
        $sustitucionesUsadas = VoleibolSustitucion::find()
            ->where(['sesion_id' => $set->sesion_id, 'set_id' => $set->id, 'equipo' => $equipo])
            ->count();
        if ($sustitucionesUsadas >= 6) {
            return ['success' => false, 'error' => 'Límite de 6 sustituciones por set alcanzado.'];
        }

        // Verificar que el atleta que sale está en cancha y el que entra no
        $enCancha = VoleibolAlineacion::find()
            ->where(['sesion_id' => $set->sesion_id, 'set_id' => $set->id, 'equipo' => $equipo])
            ->andWhere(['atleta_id' => $atletaSaleId])
            ->one();
        if (!$enCancha) {
            return ['success' => false, 'error' => 'El atleta seleccionado para salir no está en cancha.'];
        }

        $yaEnCancha = VoleibolAlineacion::find()
            ->where(['sesion_id' => $set->sesion_id, 'set_id' => $set->id, 'equipo' => $equipo])
            ->andWhere(['atleta_id' => $atletaEntraId])
            ->exists();
        if ($yaEnCancha) {
            return ['success' => false, 'error' => 'El atleta que entra ya está en cancha.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Registrar sustitución
            $sust = new VoleibolSustitucion();
            $sust->sesion_id = $set->sesion_id;
            $sust->set_id = $set->id;
            $sust->equipo = $equipo;
            $sust->atleta_sale_id = $atletaSaleId;
            $sust->atleta_entra_id = $atletaEntraId;
            $sust->created_by = Yii::$app->user->id;
            if (!$sust->save()) {
                throw new \Exception('Error al guardar sustitución.');
            }

            // Actualizar alineación: cambiar el atleta en la posición que ocupaba el que sale
            $enCancha->atleta_id = $atletaEntraId;
            $enCancha->save();

            $transaction->commit();
            return ['success' => true, 'mensaje' => 'Sustitución realizada.'];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Acción AJAX para obtener la alineación actual de un equipo en un set.
     */
    public function actionObtenerAlineacion($sesion_id, $set_id, $equipo)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $alineaciones = VoleibolAlineacion::find()
            ->with('atleta')
            ->where(['sesion_id' => $sesion_id, 'set_id' => $set_id, 'equipo' => $equipo])
            ->orderBy(['posicion' => SORT_ASC])
            ->all();

        $data = [];
        foreach ($alineaciones as $a) {
            $data[] = [
                'posicion' => $a->posicion,
                'atleta_id' => $a->atleta_id,
                'nombre' => $a->atleta->p_nombre . ' ' . $a->atleta->p_apellido,
            ];
        }
        return ['success' => true, 'alineacion' => $data];
    }

    // ============================================================
    // MÉTODOS PROTEGIDOS
    // ============================================================

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