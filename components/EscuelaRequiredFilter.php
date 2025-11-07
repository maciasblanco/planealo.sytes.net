<?php

namespace app\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

class EscuelaRequiredFilter extends ActionFilter
{
    /**
     * ✅ MIDDLEWARE MEJORADO - CON ANTI-BUCLE Y ESQUEMA atletas
     */
    public function beforeAction($action)
    {
        // ✅ EXCLUIR ACCIONES QUE NO REQUIEREN ESCUELA
        $excluirAcciones = [
            'ged/default/select-escuela', 
            'ged/default/verificar-sesion', 
            'ged/default/reset-sesion', 
            'ged/default/cambiar-escuela',
            'ged/default/index',
            'ged/default/escuela'
        ];
        
        $rutaActual = $action->controller->module ? 
            $action->controller->module->id . '/' . $action->controller->id . '/' . $action->id :
            $action->controller->id . '/' . $action->id;

        if (in_array($rutaActual, $excluirAcciones)) {
            return parent::beforeAction($action);
        }

        $session = Yii::$app->session;
        $id_escuela = $session->get('id_escuela');

        // ✅ VERIFICAR SI HAY ESCUELA VÁLIDA
        if (empty($id_escuela)) {
            // Evitar bucle de redirección
            if (!$session->get('en_redireccion_ged')) {
                $session->set('en_redireccion_ged', true);
                Yii::$app->session->setFlash('error', 
                    'Debe seleccionar una escuela para acceder a esta funcionalidad.');
                Yii::$app->response->redirect(['/ged/default/select-escuela'])->send();
                exit;
            }
            return false;
        }

        // ✅ VERIFICAR QUE LA ESCUELA EXISTA Y NO ESTÉ ELIMINADA
        $escuela = \app\models\Escuela::find()
            ->where(['id' => $id_escuela, 'eliminado' => false])
            ->exists();

        if (!$escuela) {
            $this->limpiarSesionEscuela();
            Yii::$app->session->setFlash('error', 
                'La escuela seleccionada ya no está disponible.');
            Yii::$app->response->redirect(['/ged/default/select-escuela'])->send();
            exit;
        }

        // ✅ QUITAR BANDERA DE REDIRECCIÓN SI EXISTE
        $session->remove('en_redireccion_ged');

        return parent::beforeAction($action);
    }

    /**
     * ✅ LIMPIAR SESIÓN ESCUELA
     */
    private function limpiarSesionEscuela()
    {
        $session = Yii::$app->session;
        // ✅ NUEVO SISTEMA
        $session->remove('id_escuela');
        $session->remove('nombre_escuela');
        $session->remove('escuela_activa');
        $session->remove('escuela_ultima_actualizacion');
        
        // ✅ SISTEMA LEGACY
        $session->remove('idEscuela');
        $session->remove('nombreEscuela');
    }
}