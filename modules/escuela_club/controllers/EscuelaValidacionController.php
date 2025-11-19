<?php
// [file name]: controllers/EscuelaValidacionController.php

namespace app\modules\escuela_club\controllers;

use Yii;
use app\models\Escuela;
use app\modules\escuela_club\models\EscuelaRegistroSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * EscuelaValidacionController implementa la Fase 3: Validación de Escuelas/Clubes
 */
class EscuelaValidacionController extends Controller
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
                        'roles' => ['@'], // Solo usuarios autenticados
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'aprobar' => ['POST'],
                    'rechazar' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista de escuelas pendientes de aprobación - Vista principal de validación
     */
    public function actionPendientes()
    {
        $searchModel = new EscuelaRegistroSearch();
        $dataProvider = $searchModel->searchPendientes(Yii::$app->request->queryParams);

        return $this->render('pendientes', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Aprobar una escuela pendiente
     */
    public function actionAprobar($id)
    {
        $model = $this->findModel($id);
        
        // Verificar que esté en estado pendiente
        if ($model->estado_registro !== Escuela::ESTADO_PENDIENTE) {
            Yii::$app->session->setFlash('error', 
                'Esta escuela no está pendiente de aprobación. Estado actual: ' . $model->getEstadoRegistroLabel()
            );
            return $this->redirect(['pendientes']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model->estado_registro = Escuela::ESTADO_APROBADO;
            $model->fecha_aprobacion = new \yii\db\Expression('NOW()');
            // ✅ CORREGIDO: Usar aprobado_por en lugar de id_usuario_aprobacion
            $model->aprobado_por = Yii::$app->user->id;
            
            // Obtener comentarios del POST o usar valor por defecto
            $comentarios = Yii::$app->request->post('comentarios', 'Escuela aprobada por el administrador');
            $model->comentarios_aprobacion = $comentarios;

            if ($model->save()) {
                $transaction->commit();
                
                // TODO: Enviar notificación por email al encargado
                // $this->enviarNotificacionAprobacion($model);
                
                Yii::$app->session->setFlash('success', 
                    'Escuela <strong>' . $model->nombre . '</strong> aprobada exitosamente.'
                );
            } else {
                throw new \Exception('Error al guardar la escuela: ' . $this->getModelErrors($model));
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Error al aprobar la escuela: ' . $e->getMessage());
            Yii::error('Error en actionAprobar: ' . $e->getMessage());
        }

        return $this->redirect(['pendientes']);
    }

    /**
     * Rechazar una escuela pendiente
     */
    public function actionRechazar($id)
    {
        $model = $this->findModel($id);
        
        // Verificar que esté en estado pendiente
        if ($model->estado_registro !== Escuela::ESTADO_PENDIENTE) {
            Yii::$app->session->setFlash('error', 
                'Esta escuela no está pendiente de aprobación. Estado actual: ' . $model->getEstadoRegistroLabel()
            );
            return $this->redirect(['pendientes']);
        }

        if (Yii::$app->request->isPost) {
            $comentarios = Yii::$app->request->post('comentarios', 'Escuela rechazada por el administrador');
            
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $model->estado_registro = Escuela::ESTADO_RECHAZADO;
                $model->fecha_aprobacion = new \yii\db\Expression('NOW()');
                // ✅ CORREGIDO: Usar aprobado_por en lugar de id_usuario_aprobacion
                $model->aprobado_por = Yii::$app->user->id;
                $model->comentarios_aprobacion = $comentarios;

                if ($model->save()) {
                    $transaction->commit();
                    
                    // TODO: Enviar notificación por email al encargado
                    // $this->enviarNotificacionRechazo($model);
                    
                    Yii::$app->session->setFlash('warning', 
                        'Escuela <strong>' . $model->nombre . '</strong> rechazada.'
                    );
                } else {
                    throw new \Exception('Error al guardar la escuela: ' . $this->getModelErrors($model));
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Error al rechazar la escuela: ' . $e->getMessage());
                Yii::error('Error en actionRechazar: ' . $e->getMessage());
            }

            return $this->redirect(['pendientes']);
        }

        // Mostrar formulario de rechazo
        return $this->render('rechazar', [
            'model' => $model,
        ]);
    }

    /**
     * Vista rápida de escuela para validación (modal)
     */
    public function actionVistaRapida($id)
    {
        $model = $this->findModel($id);
        
        return $this->renderAjax('_vista_rapida', [
            'model' => $model,
        ]);
    }

    /**
     * Estadísticas de validación
     */
    public function actionEstadisticas()
    {
        $estadisticas = [
            'total_pendientes' => Escuela::find()
                ->where(['estado_registro' => Escuela::ESTADO_PENDIENTE])
                ->andWhere(['eliminado' => false])
                ->count(),
            'total_aprobadas' => Escuela::find()
                ->where(['estado_registro' => Escuela::ESTADO_APROBADO])
                ->andWhere(['eliminado' => false])
                ->count(),
            'total_rechazadas' => Escuela::find()
                ->where(['estado_registro' => Escuela::ESTADO_RECHAZADO])
                ->andWhere(['eliminado' => false])
                ->count(),
            'total_pre_registro' => Escuela::find()
                ->where(['estado_registro' => Escuela::ESTADO_PRE_REGISTRO])
                ->andWhere(['eliminado' => false])
                ->count(),
        ];

        return $this->render('estadisticas', [
            'estadisticas' => $estadisticas,
        ]);
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
     * Obtener errores de modelo formateados
     * @param \yii\db\ActiveRecord $model
     * @return string
     */
    private function getModelErrors($model)
    {
        $errors = [];
        foreach ($model->errors as $attribute => $errorMessages) {
            $errors[] = $attribute . ': ' . implode(', ', $errorMessages);
        }
        return implode('; ', $errors);
    }

    /**
     * Enviar notificación de aprobación (placeholder para implementación futura)
     * @param Escuela $model
     */
    private function enviarNotificacionAprobacion($model)
    {
        // TODO: Implementar envío de email
        // Yii::$app->mailer->compose('aprobacion-escuela', ['model' => $model])
        //     ->setTo($model->email)
        //     ->setSubject('Su escuela ha sido aprobada - ' . Yii::$app->name)
        //     ->send();
        
        Yii::info('Notificación de aprobación para escuela: ' . $model->nombre);
    }

    /**
     * Enviar notificación de rechazo (placeholder para implementación futura)
     * @param Escuela $model
     */
    private function enviarNotificacionRechazo($model)
    {
        // TODO: Implementar envío de email
        // Yii::$app->mailer->compose('rechazo-escuela', ['model' => $model])
        //     ->setTo($model->email)
        //     ->setSubject('Solicitud de escuela rechazada - ' . Yii::$app->name)
        //     ->send();
        
        Yii::info('Notificación de rechazo para escuela: ' . $model->nombre);
    }
}