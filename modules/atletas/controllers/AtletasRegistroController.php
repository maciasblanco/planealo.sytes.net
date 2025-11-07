<?php

namespace app\modules\atletas\controllers;

use app\models\AtletasRegistro;
use app\models\RegistroRepresentantes;
use app\models\CategoriaAtletas;
use app\modules\atletas\models\AtletasRegistroSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;
use yii\db\Transaction;

/**
 * AtletasRegistroController implements the CRUD actions for AtletasRegistro model.
 */
class AtletasRegistroController extends Controller
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
            ]
        );
    }

    /**
     * Lists all AtletasRegistro models.
     *
     * @return string
     */
    public function actionIndex($id = 0, $nombre = null)
    {
        $searchModel = new AtletasRegistroSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);
        
        // Configurar paginación para mejor navegación
        $dataProvider->pagination->pageSize = 20;
        
        $this->layout = 'escuelas'; 
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'id' => $id, 
            'nombre' => $nombre,
        ]);
    }

    /**
     * Displays a single AtletasRegistro model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $this->layout = 'escuelas'; 
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new AtletasRegistro model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate($id = null, $nombre = null)
    {
        $model = new AtletasRegistro();
        $this->layout = 'escuelas';
        
        // ✅ ASIGNAR ESCUELA DESDE SESIÓN AL CREAR MODELO
        $session = Yii::$app->session;
        $model->id_escuela = $session->get('id_escuela');
        $id_escuela_sesion = $session->get('id_escuela');
        
        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                
                // ✅ VERIFICAR QUE id_escuela ESTÉ PRESENTE
                if (empty($model->id_escuela)) {
                    $model->id_escuela = $id_escuela_sesion;
                }
                
                // Iniciar transacción para asegurar consistencia de datos
                $transaction = Yii::$app->db->beginTransaction();
                
                try {
                    $representantesRegistrosModel = new RegistroRepresentantes();
                    
                    // Verificar cédula de representante registrada
                    $encontraCIRepresentante = RegistroRepresentantes::find()
                        ->where(["identificacion" => $model["identificacion_representante"]])
                        ->one();
                        
                    if ($encontraCIRepresentante == NULL) {
                        // 🆕 ASIGNAR ESCUELA AL REPRESENTANTE (CORRECCIÓN DEL ERROR)
                        $representantesRegistrosModel->id_escuela = $model->id_escuela;
                        $representantesRegistrosModel->p_nombre = $model["p_nombre_representante"];
                        $representantesRegistrosModel->s_nombre = $model["s_nombre_representante"];
                        $representantesRegistrosModel->p_apellido = $model["p_apellido_representante"];
                        $representantesRegistrosModel->s_apellido = $model["s_apellido_representante"];
                        $representantesRegistrosModel->id_nac = $model["id_nac_representante"];
                        $representantesRegistrosModel->identificacion = $model["identificacion_representante"];
                        $representantesRegistrosModel->cell = $model["cell_representante"];
                        $representantesRegistrosModel->u_creacion = (int)Yii::$app->user->id;
                        $representantesRegistrosModel->d_creacion = date("Y-m-d H:i:s");
                        $representantesRegistrosModel->u_update = (int)Yii::$app->user->id;
                        $representantesRegistrosModel->d_update = date("Y-m-d H:i:s");
                        
                        if (!$representantesRegistrosModel->save()) {
                            // Error al guardar representante
                            $transaction->rollBack();
                            Yii::$app->session->setFlash('error', 'Error al guardar el representante: ' . json_encode($representantesRegistrosModel->getErrors()));
                            return $this->render('create', [
                                'model' => $model,
                                'id' => $id, 
                                'nombre' => $nombre,
                            ]);
                        }
                        
                        $idRepresentanteAtleta = $representantesRegistrosModel->id;
                        $model->id_representante = $idRepresentanteAtleta;
                    } else {
                        $model->id_representante = $encontraCIRepresentante->id;
                    }
                    
                    // 🆕 ASIGNAR VALORES POR DEFECTO PARA CAMPOS OPCIONALES
                    if (empty($model->id_alergias)) $model->id_alergias = null;
                    if (empty($model->id_enfermedades)) $model->id_enfermedades = null;
                    if (empty($model->id_discapacidad)) $model->id_discapacidad = null;
                    if (empty($model->peso)) $model->peso = null;
                    if (empty($model->telf)) $model->telf = null;
                    if (empty($model->telf_emergencia2)) $model->telf_emergencia2 = null;
                    if (empty($model->s_nombre)) $model->s_nombre = null;
                    if (empty($model->s_apellido)) $model->s_apellido = null;
                    
                    // 🆕 ASIGNAR FECHA DE CREACIÓN
                    $model->d_creacion = date("Y-m-d H:i:s");
                    $model->u_creacion = (int)Yii::$app->user->id;
                    $model->dir_ip = Yii::$app->request->userIP;
                    $model->eliminado = false;
                    
                    if ($model->save()) { 
                        $transaction->commit();
                        
                        Yii::$app->session->setFlash('success', 'Atleta registrado exitosamente.');
                        return $this->redirect(['index', 
                            'id' => $id, 
                            'nombre' => $nombre,
                        ]);
                    } else {
                        // Error al guardar atleta
                        $transaction->rollBack();
                        Yii::error('Error al guardar atleta: ' . json_encode($model->getErrors()), 'atletas');
                        Yii::$app->session->setFlash('error', 'Error al guardar el atleta: ' . json_encode($model->getErrors()));
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Error en el proceso de registro: ' . $e->getMessage());
                    Yii::error('Error en actionCreate: ' . $e->getMessage(), 'atletas');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Error al cargar los datos del formulario.');
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'id' => $id, 
            'nombre' => $nombre,
        ]);
    }

    /**
     * Updates an existing AtletasRegistro model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $this->layout = 'escuelas';

        if ($this->request->isPost && $model->load($this->request->post())) {
            // Actualizar fecha de modificación
            $model->d_update = date("Y-m-d H:i:s");
            $model->u_update = (int)Yii::$app->user->id;
            
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Error al actualizar el atleta: ' . json_encode($model->getErrors()));
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing AtletasRegistro model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the AtletasRegistro model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return AtletasRegistro the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AtletasRegistro::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Acción AJAX para calcular categoría - VERSIÓN ORIGINAL FUNCIONAL
     */
    public function actionCalcularCategoria()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $edad = Yii::$app->request->post('edad');
        
        if ($edad === null || $edad === '') {
            return ['success' => false, 'categoria' => 'SIN CATEGORÍA'];
        }
        
        $edad = (int)$edad;
        
        // Intentar diferentes formas de buscar la categoría activa
        $categoria = CategoriaAtletas::find()
            ->where('edad_minima <= :edad AND edad_maxima >= :edad', [':edad' => $edad])
            ->andWhere(['activo' => true])
            ->one();
        
        // Si no encuentra, intentar con activo = 1 (por si es booleano)
        if (!$categoria) {
            $categoria = CategoriaAtletas::find()
                ->where('edad_minima <= :edad AND edad_maxima >= :edad', [':edad' => $edad])
                ->andWhere(['activo' => 1])
                ->one();
        }
        
        // Si aún no encuentra, intentar sin condición de activo
        if (!$categoria) {
            $categoria = CategoriaAtletas::find()
                ->where('edad_minima <= :edad AND edad_maxima >= :edad', [':edad' => $edad])
                ->one();
        }
        
        if ($categoria) {
            return [
                'success' => true, 
                'categoria' => $categoria->nombre . ' (' . $categoria->nombre_venezuela . ')'
            ];
        }
        
        return ['success' => false, 'categoria' => 'SIN CATEGORÍA'];
    }
}