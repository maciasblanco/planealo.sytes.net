<?php

namespace app\modules\ged\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\Escuela;

class DefaultController extends Controller
{
    /**
     * ✅ COMPORTAMIENTOS DEL CONTROLADOR - BLINDAJE GED
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        //'roles' => ['@'], // Solo usuarios autenticados
                    ],
                ],
            ],
        ];
    }

    /**
     * ✅ ACTION INDEX - CORREGIDO SIN BUCLES
     * Maneja la lógica principal de redirección
     */
    public function actionIndex()
    {
        $session = Yii::$app->session;
        $request = Yii::$app->request;

        // ✅ OBTENER DATOS PARA LA VISTA PRINCIPAL
        $todasLasEscuelas = Escuela::find()
            ->where(['eliminado' => false])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        $datosEscuelas = $todasLasEscuelas;

        // ✅ EVITAR BUCLE - Verificar si ya estamos en proceso de redirección
        if ($session->get('en_redireccion_ged')) {
            $session->remove('en_redireccion_ged');
            return $this->render('index', [
                'todasLasEscuelas' => $todasLasEscuelas,
                'datosEscuelas' => $datosEscuelas,
            ]);
        }

        // ✅ OBTENER ESCUELA DESDE DIFERENTES FUENTES (prioridad: parámetro > sesión)
        $id_escuela_param = $request->get('id_escuela');
        $id_escuela_sesion = $session->get('id_escuela');
        
        $id_escuela_final = null;
        $origen = 'sesion';

        // ✅ VALIDACIÓN DE PARÁMETROS VS SESIÓN
        if (!empty($id_escuela_param)) {
            // Verificar que el parámetro sea válido
            if ($this->validarEscuela($id_escuela_param)) {
                $id_escuela_final = $id_escuela_param;
                $origen = 'parametro';
            }
        } elseif (!empty($id_escuela_sesion)) {
            // Verificar que la escuela en sesión siga existiendo
            if ($this->validarEscuela($id_escuela_sesion)) {
                $id_escuela_final = $id_escuela_sesion;
            } else {
                // ❌ Escuela eliminada - limpiar sesión
                $this->limpiarSesionEscuela();
                Yii::$app->session->setFlash('warning', 
                    'La escuela anterior fue eliminada. Seleccione una nueva escuela.');
            }
        }

        // ✅ LÓGICA DE REDIRECCIÓN SEGURA
        if (empty($id_escuela_final)) {
            // No hay escuela válida - redirigir a selección
            $session->set('en_redireccion_ged', true);
            return $this->redirect(['select-escuela']);
        }

        // ✅ ACTUALIZAR SESIÓN SI ES NECESARIO
        if ($origen === 'parametro') {
            $this->actualizarSesionEscuela($id_escuela_final);
        }

        // ✅ RENDERIZAR LA VISTA PRINCIPAL DEL GED (landing page)
        return $this->render('index', [
            'todasLasEscuelas' => $todasLasEscuelas,
            'datosEscuelas' => $datosEscuelas,
        ]);
    }

    /**
     * ✅ ACTION SELECT-ESCUELA - MEJORADO
     * Selección de escuela con validaciones robustas
     */
    public function actionSelectEscuela()
    {
        $session = Yii::$app->session;
        $request = Yii::$app->request;

        // ✅ LIMPIAR BANDERA DE REDIRECCIÓN
        $session->remove('en_redireccion_ged');

        // ✅ OBTENER LISTA DE ESCUELAS ACTIVAS (no eliminadas)
        $escuelas = Escuela::find()
            ->where(['eliminado' => false])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        if (empty($escuelas)) {
            Yii::$app->session->setFlash('error', 
                'No hay escuelas disponibles en el sistema.');
            return $this->render('select-escuela', ['escuelas' => []]);
        }

        // ✅ MANEJO DE FORMULARIO POST
        if ($request->isPost) {
            $id_escuela = $request->post('id_escuela');
            
            if (empty($id_escuela)) {
                Yii::$app->session->setFlash('error', 
                    'Debe seleccionar una escuela.');
                return $this->refresh();
            }

            // ✅ VALIDAR ESCUELA SELECCIONADA
            if (!$this->validarEscuela($id_escuela)) {
                Yii::$app->session->setFlash('error', 
                    'La escuela seleccionada no existe o fue eliminada.');
                return $this->refresh();
            }

            // ✅ ACTUALIZAR SESIÓN Y REDIRIGIR
            $this->actualizarSesionEscuela($id_escuela);
            
            Yii::$app->session->setFlash('success', 
                'Escuela seleccionada correctamente. Redirigiendo...');
            
            // ✅ REDIRECCIÓN SEGURA ANTI-BUCLE
            $session->set('en_redireccion_ged', true);
            return $this->redirect(['index']);
        }

        return $this->render('select-escuela', [
            'escuelas' => $escuelas,
        ]);
    }

    /**
     * ✅ ACTION CAMBIAR-ESCUELA - NUEVO
     * Permite cambiar de escuela limpiando la sesión actual
     */
    public function actionCambiarEscuela()
    {
        $this->limpiarSesionEscuela();
        Yii::$app->session->setFlash('info', 
            'Seleccione una nueva escuela para continuar.');
        return $this->redirect(['select-escuela']);
    }

    /**
     * ✅ VALIDAR ESCUELA - MÉTODO AUXILIAR
     * Verifica que una escuela exista y no esté eliminada
     */
    private function validarEscuela($id_escuela)
    {
        if (empty($id_escuela) || !is_numeric($id_escuela)) {
            return false;
        }

        $escuela = Escuela::find()
            ->where(['id' => $id_escuela, 'eliminado' => false])
            ->one();

        return $escuela !== null;
    }

    /**
     * ✅ ACTUALIZAR SESIÓN ESCUELA - MÉTODO AUXILIAR
     * Actualiza la sesión con los datos de la escuela
     */
    private function actualizarSesionEscuela($id_escuela)
    {
        $escuela = Escuela::findOne($id_escuela);
        
        if ($escuela && $escuela->eliminado == false) {
            $session = Yii::$app->session;
            $session->set('id_escuela', $escuela->id);
            $session->set('nombre_escuela', $escuela->nombre);
            $session->set('escuela_activa', true);
            $session->set('escuela_ultima_actualizacion', time());
            
            // ✅ MANTENER COMPATIBILIDAD CON SISTEMA LEGACY
            $session->set('idEscuela', $escuela->id);
            $session->set('nombreEscuela', $escuela->nombre);
        }
    }

    /**
     * ✅ LIMPIAR SESIÓN ESCUELA - MÉTODO AUXILIAR MEJORADO
     * Elimina todos los datos de escuela de la sesión de forma segura
     */
    private function limpiarSesionEscuela()
    {
        $session = Yii::$app->session;
        
        // ✅ NUEVO SISTEMA GED
        $session->remove('id_escuela');
        $session->remove('nombre_escuela');
        $session->remove('escuela_activa');
        $session->remove('escuela_ultima_actualizacion');
        $session->remove('en_redireccion_ged');
        
        // ✅ SISTEMA LEGACY (compatibilidad)
        $session->remove('idEscuela');
        $session->remove('nombreEscuela');
        
        // ✅ LIMPIAR CUALQUIER OTRA VARIABLE RELACIONADA
        $allSessionVars = array_keys($_SESSION);
        foreach ($allSessionVars as $key) {
            if (strpos($key, 'escuela') !== false || strpos($key, 'school') !== false) {
                $session->remove($key);
            }
        }
        
        Yii::info('Sesión de escuela limpiada completamente', 'ged');
    }

    /**
     * ✅ ACTION VERIFICAR-SESION - NUEVO
     * Endpoint para verificar el estado de la sesión (útil para AJAX)
     */
    public function actionVerificarSesion()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $session = Yii::$app->session;
        $id_escuela = $session->get('id_escuela');
        $escuela_valida = $this->validarEscuela($id_escuela);
        
        return [
            'sesion_activa' => !empty($id_escuela),
            'escuela_valida' => $escuela_valida,
            'id_escuela' => $id_escuela,
            'nombre_escuela' => $session->get('nombre_escuela'),
            'timestamp' => time()
        ];
    }

    /**
     * ✅ ACTION RESET-SESION - NUEVO
     * Para desarrollo/testing - limpia toda la sesión GED
     */
    public function actionResetSesion()
    {
        $this->limpiarSesionEscuela();
        Yii::$app->session->destroy();
        
        Yii::$app->session->setFlash('success', 
            'Sesión GED reiniciada completamente.');
        return $this->redirect(['select-escuela']);
    }

    /**
     * ✅ MÉTODOS LEGACY - MANTENIDOS PARA COMPATIBILIDAD
     */

    /**
     * Acción específica para landing page de escuela (legacy)
     */
    public function actionEscuela($id = null, $nombre = null)
    {
        // Validación segura
        if (!$id || !$nombre) {
            throw new \yii\web\BadRequestHttpException('Parámetros inválidos');
        }
        
        $escuela = Escuela::findOne(['id' => $id, 'eliminado' => false]);
        if (!$escuela) {
            throw new \yii\web\NotFoundHttpException('Escuela no encontrada');
        }
        
        // ✅ USAR NUEVO SISTEMA DE SESIÓN
        $this->actualizarSesionEscuela($escuela->id);
        
        $this->layout = 'escuelas';
        
        return $this->render('escuela', [
            'escuela' => $escuela,
            'estadisticas' => $this->obtenerEstadisticasEscuela($id),
            'horarios' => $this->obtenerHorariosEscuela($id),
            'canchas' => $this->obtenerCanchasEscuela($id),
            'deportes' => $this->obtenerDeportesEscuela($id),
        ]);
    }

    /**
     * Acción para cambiar de escuela mediante AJAX (legacy)
     */
    public function actionCambiarEscuelaOld($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $escuela = Escuela::findOne(['id' => $id, 'eliminado' => false]);
        if ($escuela) {
            // ✅ USAR NUEVO SISTEMA DE SESIÓN
            $this->actualizarSesionEscuela($escuela->id);
            
            return [
                'success' => true,
                'id' => $escuela->id,
                'nombre' => $escuela->nombre,
                'redirectUrl' => Yii::$app->urlManager->createUrl(['/ged/default/escuela', 'id' => $escuela->id, 'nombre' => $escuela->nombre])
            ];
        }
        
        return ['success' => false, 'message' => 'Escuela no encontrada o eliminada'];
    }

    /**
     * Acción para búsqueda de escuelas con autocompletado
     */
    public function actionSearchSchools($q)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        // Búsqueda inteligente en múltiples campos
        $escuelas = Escuela::find()
            ->select(['id', 'nombre', 'direccion_administrativa'])
            ->where(['or',
                ['like', 'nombre', $q],
                ['like', 'direccion_administrativa', $q]
            ])
            ->andWhere(['eliminado' => false])
            ->orderBy('nombre')
            ->limit(10)
            ->asArray()
            ->all();
        
        return $escuelas;
    }

    /**
     * Acción para búsqueda de escuelas (legacy)
     */
    public function actionBuscarEscuelas($q = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Escuela::find()
            ->where(['eliminado' => false])
            ->andWhere(['like', 'nombre', $q])
            ->orderBy('nombre')
            ->limit(20);

        $escuelas = $query->all();
        $results = [];
        
        foreach ($escuelas as $escuela) {
            $results[] = [
                'id' => $escuela->id,
                'text' => $escuela->nombre,
                'direccion' => $escuela->direccion_administrativa ?? 'Dirección no disponible',
                'logo' => $escuela->getLogoUrl() ?? Yii::getAlias('@web') . '/img/logos/escuelas/default.png'
            ];
        }
        
        return ['results' => $results];
    }

    /**
     * Obtener estadísticas de la escuela
     */
    private function obtenerEstadisticasEscuela($idEscuela)
    {
        // ✅ USANDO MODELOS DEL ESQUEMA atletas
        return [
            'total_atletas' => \app\models\AtletasRegistro::find()->where(['id_escuela' => $idEscuela, 'eliminado' => false])->count(),
            'atletas_activos' => \app\models\AtletasRegistro::find()->where(['id_escuela' => $idEscuela, 'estado' => 'activo'])->count(),
            'total_aportes' => \app\models\AportesSemanales::find()
                ->joinWith(['atleta' => function($query) use ($idEscuela) {
                    $query->andWhere(['id_escuela' => $idEscuela]);
                }])
                ->count(),
            'total_representantes' => \app\models\RegistroRepresentantes::find()->where(['id_escuela' => $idEscuela, 'eliminado' => false])->count(),
        ];
    }

    /**
     * Obtener horarios de la escuela
     */
    private function obtenerHorariosEscuela($idEscuela)
    {
        // Ejemplo - adapta según tus modelos
        return [
            [
                'deporte' => 'Voleibol',
                'dias' => 'Lunes, Miércoles, Viernes',
                'horario' => '3:00 PM - 5:00 PM',
                'grupo' => 'Sub-15'
            ],
            [
                'deporte' => 'Basketbol',
                'dias' => 'Martes, Jueves',
                'horario' => '4:00 PM - 6:00 PM',
                'grupo' => 'Sub-17'
            ]
        ];
    }

    /**
     * Obtener canchas de la escuela
     */
    private function obtenerCanchasEscuela($idEscuela)
    {
        // Ejemplo - adapta según tus modelos
        return [
            [
                'nombre' => 'Cancha Principal',
                'tipo' => 'Voleibol/Basketbol',
                'estado' => 'Disponible',
                'capacidad' => '200 personas'
            ]
        ];
    }

    /**
     * Obtener deportes de la escuela
     */
    private function obtenerDeportesEscuela($idEscuela)
    {
        // Ejemplo - adapta según tus modelos
        return [
            'Voleibol' => ['activo' => true, 'categoria' => 'Sub-15, Sub-17'],
            'Basketbol' => ['activo' => true, 'categoria' => 'Sub-13, Sub-15, Sub-17'],
            'Fútbol' => ['activo' => true, 'categoria' => 'Sub-13, Sub-15']
        ];
    }
    /**
     * ✅ ACTION CERRAR-ESCUELA - NUEVO
     * Cierra la escuela actual sin cerrar la sesión del usuario
     */
    public function actionCerrarEscuela()
    {
        $this->limpiarSesionEscuela();
        
        Yii::$app->session->setFlash('success', 
            'Escuela cerrada correctamente. Puedes seleccionar otra escuela.');
        
        return $this->redirect(['select-escuela']);
    }

    /**
     * ✅ ACTION SET-SCHOOL - PARA EL BUSCADOR AJAX
     * Establece la escuela desde el buscador (ya existe pero la mejoramos)
     */
    public function actionSetSchool()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $request = Yii::$app->request;
        $schoolId = $request->post('schoolId');
        $schoolName = $request->post('schoolName');
        
        if (empty($schoolId)) {
            return ['success' => false, 'message' => 'ID de escuela no proporcionado'];
        }
        
        // Validar que la escuela existe y no está eliminada
        $escuela = Escuela::find()
            ->where(['id' => $schoolId, 'eliminado' => false])
            ->one();
            
        if (!$escuela) {
            return ['success' => false, 'message' => 'La escuela no existe o fue eliminada'];
        }
        
        // Actualizar sesión
        $this->actualizarSesionEscuela($schoolId);
        
        return [
            'success' => true, 
            'message' => 'Escuela seleccionada: ' . $escuela->nombre,
            'schoolName' => $escuela->nombre
        ];
    }
    /**
     * Limpiar selección de escuela
     */
    public function actionClearEscuela()
    {
        Yii::$app->session->remove('id_escuela');
        Yii::$app->session->remove('nombre_escuela');
        
        Yii::$app->session->setFlash('success', 'Selección de escuela eliminada');
        return $this->redirect(Yii::$app->request->referrer ?: ['ged/default/index']);
    }

}