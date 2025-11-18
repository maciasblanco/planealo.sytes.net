<?php

namespace app\modules\escuela_club\controllers;

use Yii;
use app\models\Escuela;
use app\models\EncargadoEscuela;
use app\modules\escuela_club\models\EscuelaSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use app\models\Municipio;
use app\models\Parroquia;
use yii\web\UploadedFile;

/**
 * EscuelaRegistroController implements the CRUD actions for Escuela model.
 */
class EscuelaRegistroController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    // REGLA NUEVA: Pre-registro accesible sin autenticación
                    [
                        'actions' => ['pre-registro', 'completar-registro'],
                        'allow' => true,
                        'roles' => ['?', '@'], // ← CAMBIO CLAVE: '?' = usuarios anónimos
                    ],
                    // REGLA EXISTENTE: Acciones administrativas requieren auth
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'dashboard', 'municipios-por-estado', 'parroquias-por-municipio', 'select-escuela', 'clear-escuela'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Escuela models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EscuelaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Escuela model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $estadisticas = $model->getEstadisticasBasicas();

        return $this->render('view', [
            'model' => $model,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * Creates a new Escuela model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Escuela();
        $encargado = new EncargadoEscuela();

        if ($model->load(Yii::$app->request->post()) && $encargado->load(Yii::$app->request->post())) {
            $model->logoFile = UploadedFile::getInstance($model, 'logoFile');
            
            // Validar que el encargado no esté activo en otra escuela
            if (EncargadoEscuela::estaActivoEnOtraEscuela($encargado->identificacion)) {
                Yii::$app->session->setFlash('error', 'Esta cédula ya está registrada como encargado en otra escuela activa.');
            } else {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($model->save()) {
                        // Asignar la escuela al encargado y guardar
                        $encargado->id_escuela = $model->id;
                        
                        if ($encargado->save()) {
                            $transaction->commit();
                            Yii::$app->session->setFlash('success', 'Escuela/Club creado exitosamente.');
                            return $this->redirect(['view', 'id' => $model->id]);
                        } else {
                            throw new \Exception('Error al guardar los datos del encargado: ' . $this->getModelErrors($encargado));
                        }
                    } else {
                        throw new \Exception('Error al guardar la escuela: ' . $this->getModelErrors($model));
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Error al crear la escuela/club: ' . $e->getMessage());
                    Yii::error('Error en actionCreate: ' . $e->getMessage());
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'encargado' => $encargado,
        ]);
    }

    /**
     * Updates an existing Escuela model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $encargado = $model->encargado ?: new EncargadoEscuela();

        if ($model->load(Yii::$app->request->post()) && $encargado->load(Yii::$app->request->post())) {
            $model->logoFile = UploadedFile::getInstance($model, 'logoFile');
            
            // Validar que el encargado no esté activo en otra escuela (si es nuevo o cambió cédula)
            if ($encargado->isNewRecord || $encargado->isAttributeChanged('identificacion')) {
                if (EncargadoEscuela::estaActivoEnOtraEscuela($encargado->identificacion, $encargado->id)) {
                    Yii::$app->session->setFlash('error', 'Esta cédula ya está registrada como encargado en otra escuela activa.');
                    return $this->refresh();
                }
            }
            
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    // Guardar/actualizar encargado
                    if ($encargado->isNewRecord) {
                        $encargado->id_escuela = $model->id;
                    }
                    
                    if ($encargado->save()) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'Escuela/Club actualizado exitosamente.');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        throw new \Exception('Error al guardar los datos del encargado: ' . $this->getModelErrors($encargado));
                    }
                } else {
                    throw new \Exception('Error al guardar la escuela: ' . $this->getModelErrors($model));
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Error al actualizar la escuela/club: ' . $e->getMessage());
                Yii::error('Error en actionUpdate: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'encargado' => $encargado,
        ]);
    }

    /**
     * Deletes an existing Escuela model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->eliminado = true;
        
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Escuela/Club eliminado exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al eliminar la escuela/club.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Dashboard de la escuela/club con estadísticas
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDashboard($id)
    {
        $model = $this->findModel($id);
        
        $estadisticas = $model->getEstadisticasBasicas();
        $asistenciaMensual = $this->getAsistenciaMensual($id);
        $estadoAportes = $this->getEstadoAportes($id);

        return $this->render('dashboard', [
            'model' => $model,
            'estadisticas' => $estadisticas,
            'asistenciaMensual' => $asistenciaMensual,
            'estadoAportes' => $estadoAportes,
        ]);
    }

    /**
     * Obtiene municipios por estado para AJAX
     * @return json
     */
    public function actionMunicipiosPorEstado()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $estadoId = Yii::$app->request->get('estado_id');
        
        if ($estadoId) {
            $municipios = Municipio::find()
                ->where(['id_estado' => $estadoId, 'eliminado' => false])
                ->orderBy('municipio')
                ->all();
                
            return ArrayHelper::map($municipios, 'id', 'municipio');
        }
        
        return [];
    }

    /**
     * Obtiene parroquias por municipio para AJAX
     * @return json
     */
    public function actionParroquiasPorMunicipio()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $municipioId = Yii::$app->request->get('municipio_id');
        
        if ($municipioId) {
            $parroquias = Parroquia::find()
                ->where(['id_municipio' => $municipioId, 'eliminado' => false])
                ->orderBy('parroquia')
                ->all();
                
            return ArrayHelper::map($parroquias, 'id', 'parroquia');
        }
        
        return [];
    }

    /**
     * Seleccionar escuela para trabajar en sesión
     */
    public function actionSelectEscuela($id)
    {
        $model = $this->findModel($id);
        
        if (!$model->isAprobado()) {
            Yii::$app->session->setFlash('error', 'Solo se pueden seleccionar escuelas aprobadas.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }
        
        Yii::$app->session->set('id_escuela', $model->id);
        Yii::$app->session->set('nombre_escuela', $model->nombre);
        
        Yii::$app->session->setFlash('success', 
            'Escuela seleccionada: <strong>' . $model->nombre . '</strong>. ' .
            'Ahora puede realizar operaciones dentro de esta escuela.'
        );
        
        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    /**
     * Limpiar selección de escuela
     */
    public function actionClearEscuela()
    {
        Yii::$app->session->remove('id_escuela');
        Yii::$app->session->remove('nombre_escuela');
        
        Yii::$app->session->setFlash('info', 'Se ha deseleccionado la escuela. Puede seleccionar otra cuando lo necesite.');
        
        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    /**
     * Finds the Escuela model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Escuela the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Escuela::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La escuela/club solicitado no existe.');
    }

    /**
     * Obtiene estadísticas de asistencia mensual
     * @param integer $escuelaId
     * @return array
     */
    protected function getAsistenciaMensual($escuelaId)
    {
        // TODO: Implementar según el módulo de asistencia
        return [
            'enero' => 85, 'febrero' => 78, 'marzo' => 92,
            'abril' => 88, 'mayo' => 90, 'junio' => 82,
        ];
    }

    /**
     * Obtiene estado de aportes
     * @param integer $escuelaId
     * @return array
     */
    protected function getEstadoAportes($escuelaId)
    {
        // TODO: Implementar según el módulo de aportes
        return [
            'pendientes' => 15, 'pagados' => 45, 
            'cancelados' => 2, 'total' => 62,
        ];
    }

    /**
     * Pre-registro de escuela/club - Etapa 1
     * @return mixed
     */
    public function actionPreRegistro()
    {
        $model = new Escuela();
        $encargado = new EncargadoEscuela();
        
        // Establecer estado inicial como pre-registro
        $model->estado_registro = Escuela::ESTADO_PRE_REGISTRO;
        $model->eliminado = false;

        if ($model->load(Yii::$app->request->post()) && $encargado->load(Yii::$app->request->post())) {
            
            // Validar que el encargado no esté activo en otra escuela
            if (EncargadoEscuela::estaActivoEnOtraEscuela($encargado->identificacion)) {
                Yii::$app->session->setFlash('error', 'Esta cédula ya está registrada como encargado en otra escuela activa.');
            } else {
                // **CORRECCIÓN: Asignar direccion_administrativa si está vacía**
                if (empty($model->direccion_administrativa)) {
                    $model->direccion_administrativa = $model->direccion_practicas;
                }

                // Validar ambos modelos
                $valid = $model->validate(['nombre', 'tipo_entidad', 'telefono', 'email', 'id_estado', 'id_municipio', 'id_parroquia', 'direccion_practicas', 'direccion_administrativa']);
                $valid = $encargado->validate(['p_nombre', 'p_apellido', 'identificacion', 'fn', 'sexo', 'cell']) && $valid;

                if ($valid) {
                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        if ($model->save(false)) {
                            // Asignar la escuela al encargado y guardar
                            $encargado->id_escuela = $model->id;
                            
                            if ($encargado->save(false)) {
                                $transaction->commit();
                                Yii::$app->session->setFlash('success', 'Pre-registro completado. Ahora complete la información adicional.');
                                return $this->redirect(['completar-registro', 'id' => $model->id]);
                            } else {
                                $transaction->rollBack();
                                Yii::$app->session->setFlash('error', 'Error al guardar los datos del encargado: ' . $this->getModelErrors($encargado));
                            }
                        } else {
                            $transaction->rollBack();
                            Yii::$app->session->setFlash('error', 'Error al guardar el pre-registro en la base de datos: ' . $this->getModelErrors($model));
                        }
                    } catch (\Exception $e) {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'Error al guardar el pre-registro: ' . $e->getMessage());
                        Yii::error('Error en actionPreRegistro: ' . $e->getMessage());
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Error en el pre-registro. Verifique los datos.');
                    Yii::error('Errores de validación en pre-registro: ' . print_r($model->errors, true));
                    Yii::error('Errores de validación en encargado: ' . print_r($encargado->errors, true));
                }
            }
        }

        return $this->render('pre-registro', [
            'model' => $model,
            'encargado' => $encargado,
        ]);
    }

    /**
     * Completar registro - Etapa 2
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionCompletarRegistro($id)
    {
        $model = $this->findModel($id);

        // Verificar que esté en estado pre-registro
        if ($model->estado_registro !== Escuela::ESTADO_PRE_REGISTRO) {
            Yii::$app->session->setFlash('error', 'Esta escuela ya ha completado su registro.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->load(Yii::$app->request->post())) {
            $model->logoFile = UploadedFile::getInstance($model, 'logoFile');
            
            // Procesar upload de logo usando el método del modelo
            if ($model->logoFile) {
                if (!$model->uploadLogo()) {
                    Yii::$app->session->setFlash('error', 'Error al subir el logo. Verifique el archivo.');
                    return $this->refresh();
                }
            }
            
            // Cambiar estado a pendiente de aprobación
            $model->estado_registro = Escuela::ESTADO_PENDIENTE;
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Registro completado exitosamente. La escuela está pendiente de aprobación.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Error al completar el registro: ' . $this->getModelErrors($model));
                Yii::error('Errores de validación en completar-registro: ' . print_r($model->errors, true));
            }
        }

        return $this->render('completar-registro', [
            'model' => $model,
        ]);
    }

    /**
     * Obtener errores de modelo formateados
     */
    private function getModelErrors($model)
    {
        $errors = [];
        foreach ($model->errors as $attribute => $errorMessages) {
            $errors[] = $attribute . ': ' . implode(', ', $errorMessages);
        }
        return implode('; ', $errors);
    }
}