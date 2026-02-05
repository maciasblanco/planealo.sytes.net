<?php

namespace app\modules\atletas\controllers;

use app\models\AtletasRegistro;
use app\models\RegistroRepresentantes;
use app\models\CategoriaAtletas;
use app\modules\atletas\models\AtletasRegistroSearch;
use app\models\User;
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
                        
                        // ✅ CREAR USUARIO AUTOMÁTICO PARA REPRESENTANTE
                        $this->crearUsuarioParaPersona($representantesRegistrosModel, 'representante');
                        
                    } else {
                        $model->id_representante = $encontraCIRepresentante->id;
                        
                        // ✅ VERIFICAR SI EL REPRESENTANTE EXISTENTE TIENE USUARIO
                        if (!$encontraCIRepresentante->user_id) {
                            $this->crearUsuarioParaPersona($encontraCIRepresentante, 'representante');
                        }
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
                        // ✅ CREAR USUARIO AUTOMÁTICO PARA ATLETA
                        $this->crearUsuarioParaPersona($model, 'atleta');
                        
                        $transaction->commit();
                        
                        Yii::$app->session->setFlash('success', '✅ Atleta registrado exitosamente. Usuario creado automáticamente con cédula como username y clave: 12345-aves');
                        return $this->redirect(['index', 
                            'id' => $id, 
                            'nombre' => $nombre,
                        ]);
                    } else {
                        // Error al guardar atleta
                        $transaction->rollBack();
                        Yii::error('Error al guardar atleta: ' . json_encode($model->getErrors()), 'atletas');
                        Yii::$app->session->setFlash('error', '❌ Error al guardar el atleta: ' . json_encode($model->getErrors()));
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', '❌ Error en el proceso de registro: ' . $e->getMessage());
                    Yii::error('Error en actionCreate: ' . $e->getMessage(), 'atletas');
                }
            } else {
                Yii::$app->session->setFlash('error', '❌ Error al cargar los datos del formulario.');
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
                Yii::$app->session->setFlash('error', '❌ Error al actualizar el atleta: ' . json_encode($model->getErrors()));
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

    /**
     * =========================================================================
     * MÉTODOS PARA CREACIÓN AUTOMÁTICA DE USUARIOS
     * =========================================================================
     */

    /**
     * Crear usuario para persona (atleta o representante)
     * Basado en el mismo método de MigrarUsuariosController.php
     */
    private function crearUsuarioParaPersona($persona, $tipo)
    {
        try {
            $cedula = (string)$persona->identificacion;
            
            if (empty($cedula)) {
                Yii::error("Cédula vacía para {$tipo}", 'atletas');
                return null;
            }
            
            // 1. Verificar si ya existe usuario con esta cédula COMO USERNAME
            $username = $cedula;
            $usuarioExistente = User::findByUsername($username);
            
            if ($usuarioExistente) {
                Yii::info("Usuario ya existe para {$tipo}: {$usuarioExistente->username}", 'atletas');
                
                // Actualizar user_id en la persona si no lo tiene
                if (!$persona->user_id) {
                    $persona->user_id = $usuarioExistente->id;
                    $persona->save(false);
                }
                
                // Asignar rol si no lo tiene
                $this->asignarRolSeguro($usuarioExistente->id, $tipo);
                
                return $usuarioExistente;
            }
            
            // 2. Crear usuario con CAMPOS MÍNIMOS
            $user = new User();
            $user->username = $username;
            $user->cedula = $cedula;
            $user->email = $cedula . '@temporal.com';
            $user->status = User::STATUS_ACTIVE;
            
            // SOLO campos que sabemos que existen:
            $user->setPassword('12345-aves');
            $user->generateAuthKey();
            $user->created_at = time();
            $user->updated_at = time();
            
            // Agregar nombre completo si el modelo lo soporta
            if ($user->hasAttribute('nombre')) {
                $nombreCompleto = $this->obtenerNombreCompleto($persona);
                $user->nombre = $nombreCompleto;
            }
            
            if ($user->save()) {
                Yii::info("✅ Usuario creado para {$tipo}: {$user->username}", 'atletas');
                
                // Asignar rol
                $this->asignarRolSeguro($user->id, $tipo);
                
                // Actualizar user_id en la persona usando SQL directo para evitar behaviors
                $this->actualizarUserIdPersona($persona, $user->id, $tipo);
                
                return $user;
            } else {
                Yii::error("❌ ERRORES al crear usuario para {$tipo}: " . json_encode($user->getErrors()), 'atletas');
                return null;
            }
            
        } catch (\Exception $e) {
            Yii::error("❌ EXCEPCIÓN en crearUsuarioParaPersona: " . $e->getMessage(), 'atletas');
            return null;
        }
    }
    
    /**
     * Obtener nombre completo de la persona
     */
    private function obtenerNombreCompleto($persona)
    {
        if ($persona instanceof AtletasRegistro || $persona instanceof RegistroRepresentantes) {
            $nombre = trim($persona->p_nombre . ' ' . 
                         ($persona->s_nombre ? $persona->s_nombre . ' ' : '') .
                         $persona->p_apellido . ' ' .
                         ($persona->s_apellido ? $persona->s_apellido : ''));
            return $nombre;
        }
        return '';
    }
    
    /**
     * Actualizar user_id en la persona usando SQL directo
     */
    private function actualizarUserIdPersona($persona, $userId, $tipo)
    {
        try {
            $db = Yii::$app->db;
            
            if ($tipo === 'atleta') {
                $command = $db->createCommand('
                    UPDATE atletas.registro 
                    SET user_id = :user_id 
                    WHERE id = :id
                ', [
                    ':user_id' => $userId,
                    ':id' => $persona->id
                ]);
            } else { // representante
                $command = $db->createCommand('
                    UPDATE atletas.registro_representantes 
                    SET user_id = :user_id 
                    WHERE id = :id
                ', [
                    ':user_id' => $userId,
                    ':id' => $persona->id
                ]);
            }
            
            $command->execute();
            Yii::info("✅ user_id actualizado para {$tipo} ID {$persona->id}: {$userId}", 'atletas');
            return true;
            
        } catch (\Exception $e) {
            Yii::error("❌ Error al actualizar user_id para {$tipo}: " . $e->getMessage(), 'atletas');
            return false;
        }
    }
    
    /**
     * Asignar rol de manera segura
     */
    private function asignarRolSeguro($userId, $rol)
    {
        try {
            $auth = Yii::$app->authManager;
            if ($auth === null) {
                Yii::warning("⚠️ authManager no está configurado", 'atletas');
                return false;
            }
            
            // Verificar si el rol existe
            $role = $auth->getRole($rol);
            if (!$role) {
                Yii::warning("⚠️ El rol '{$rol}' no existe", 'atletas');
                return false;
            }
            
            // Verificar si ya tiene asignado el rol
            $existingAssignment = $auth->getAssignment($role->name, $userId);
            if ($existingAssignment) {
                Yii::info("✅ Usuario {$userId} ya tiene rol '{$rol}'", 'atletas');
                return true;
            }
            
            // Asignar rol
            $auth->assign($role, $userId);
            Yii::info("✅ Rol '{$rol}' asignado al usuario {$userId}", 'atletas');
            return true;
            
        } catch (\Exception $e) {
            Yii::error("❌ Error asignando rol '{$rol}': " . $e->getMessage(), 'atletas');
            return false;
        }
    }

    /**
     * Acción para forzar creación de usuario para un atleta específico
     * (Útil para testing o corrección de datos)
     */
    public function actionCrearUsuario($id)
    {
        $atleta = $this->findModel($id);
        
        if ($this->crearUsuarioParaPersona($atleta, 'atleta')) {
            Yii::$app->session->setFlash('success', '✅ Usuario creado para atleta ID: ' . $id);
        } else {
            Yii::$app->session->setFlash('error', '❌ Error creando usuario para atleta');
        }
        
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Acción para verificar estado de usuario de un atleta
     */
    public function actionVerificarUsuario($id)
    {
        $atleta = $this->findModel($id);
        
        $usuario = null;
        if ($atleta->user_id) {
            $usuario = User::findOne($atleta->user_id);
        } else {
            // Buscar por cédula
            $usuario = User::findByUsername($atleta->identificacion);
        }
        
        $resultado = [
            'atleta' => [
                'id' => $atleta->id,
                'cedula' => $atleta->identificacion,
                'nombre' => $atleta->p_nombre . ' ' . $atleta->p_apellido,
                'user_id' => $atleta->user_id,
            ],
            'usuario' => $usuario ? [
                'id' => $usuario->id,
                'username' => $usuario->username,
                'email' => $usuario->email,
                'status' => $usuario->status,
                'creado' => date('Y-m-d H:i:s', $usuario->created_at),
            ] : null,
        ];
        
        return $this->asJson($resultado);
    }

    /**
     * Acción para asignar roles a todos los atletas y representantes existentes
     * (Similar al comando migrar-usuarios, pero desde la web)
     */
    public function actionMigrarTodos()
    {
        set_time_limit(300); // 5 minutos
        
        $contadorAtletas = 0;
        $contadorRepresentantes = 0;
        $erroresAtletas = 0;
        $erroresRepresentantes = 0;
        
        // Migrar representantes
        $representantes = RegistroRepresentantes::find()->all();
        foreach ($representantes as $representante) {
            if ($this->crearUsuarioParaPersona($representante, 'representante')) {
                $contadorRepresentantes++;
            } else {
                $erroresRepresentantes++;
            }
        }
        
        // Migrar atletas
        $atletas = AtletasRegistro::find()->all();
        foreach ($atletas as $atleta) {
            if ($this->crearUsuarioParaPersona($atleta, 'atleta')) {
                $contadorAtletas++;
            } else {
                $erroresAtletas++;
            }
        }
        
        Yii::$app->session->setFlash('success', 
            "✅ Migración completada:<br>" .
            "Atletas: {$contadorAtletas} migrados, {$erroresAtletas} errores<br>" .
            "Representantes: {$contadorRepresentantes} migrados, {$erroresRepresentantes} errores<br>" .
            "Total: " . ($contadorAtletas + $contadorRepresentantes) . " usuarios creados"
        );
        
        return $this->redirect(['index']);
    }
}