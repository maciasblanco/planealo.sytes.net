<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'acceder-sistema', 'mi-cuenta'],
                'rules' => [
                    [
                        'actions' => ['logout', 'mi-cuenta'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['acceder-sistema'],
                        'allow' => true,
                        'roles' => ['@'],
                        'denyCallback' => function ($rule, $action) {
                            Yii::$app->session->setFlash('error', 'Debe iniciar sesión para acceder al sistema.');
                            return $this->redirect(['/site/login']);
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Landing page pública - NO revela rutas internas
     *
     * @return string
     */
    public function actionIndex()
    {
        // ✅ SEGURO: NUNCA redirigir automáticamente a rutas internas
        // ✅ Mostrar siempre landing page pública
        
        return $this->render('index', [
            'isAuthenticated' => !Yii::$app->user->isGuest
        ]);
    }

    /**
     * ✅ PUNTO DE ENTRADA SEGURO al sistema
     * No revela rutas internas directamente
     */
    public function actionAccederSistema()
    {
        // Verificar autenticación (ya lo hace el behavior, pero por redundancia)
        if (Yii::$app->user->isGuest) {
            Yii::$app->session->setFlash('error', 'Debe iniciar sesión para acceder al sistema.');
            return $this->redirect(['/site/login']);
        }
        
        // ✅ REDIRECCIÓN SEGURA: Usar nombre de ruta en lugar de URL completa
        // Esto no revela la estructura interna al usuario
        
        // Registrar el acceso en logs para auditoría
        Yii::info("Usuario " . Yii::$app->user->identity->username . 
                  " accede al sistema desde IP: " . Yii::$app->request->userIP, 'security');
        
        // Redirigir al punto de entrada del módulo GED
        return $this->redirect(['/ged/default/select-escuela']);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // Verificar si debe cambiar contraseña temporal
            $user = Yii::$app->user->identity;
            if ($user && $user->debeCambiarPassword()) {
                Yii::$app->session->setFlash('warning', 
                    'Debe cambiar su contraseña temporal antes de continuar.');
                return $this->redirect(['/site/cambiar-password']);
            }
            
            Yii::$app->session->setFlash('success', 'Sesión iniciada correctamente.');
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Cierra sesión y también limpia la escuela
     */
    public function actionLogout()
    {
        // Registrar logout en logs
        if (!Yii::$app->user->isGuest) {
            Yii::info("Usuario " . Yii::$app->user->identity->username . 
                      " cierra sesión desde IP: " . Yii::$app->request->userIP, 'security');
        }
        
        // Limpiar escuela antes de hacer logout
        $session = Yii::$app->session;
        $session->remove('id_escuela');
        $session->remove('nombre_escuela');
        $session->remove('idEscuela');
        $session->remove('nombreEscuela');
        
        // Logout normal
        Yii::$app->user->logout();

        Yii::$app->session->setFlash('success', 'Sesión cerrada correctamente.');
        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Action para cambiar contraseña obligatorio
     */
    public function actionCambiarPassword()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $model = new \app\models\CambioPasswordForm();
        $user = Yii::$app->user->identity;

        // Verificar si realmente debe cambiar la contraseña
        if (!$user->debeCambiarPassword()) {
            Yii::$app->session->setFlash('info', 'Su contraseña ya ha sido cambiada anteriormente.');
            return $this->goHome();
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($user->cambiarPassword($model->newPassword)) {
                Yii::$app->session->setFlash('success', 'Contraseña cambiada exitosamente. Ahora puede usar el sistema.');
                
                // Registrar el cambio en logs de seguridad
                Yii::info("Usuario {$user->username} cambió su contraseña temporal", 'security');
                
                // Redirigir al punto de entrada seguro
                return $this->redirect(['/site/acceder-sistema']);
            } else {
                Yii::$app->session->setFlash('error', 'Error al cambiar la contraseña. Por favor intente nuevamente.');
            }
        }

        return $this->render('cambio-password', [
            'model' => $model,
        ]);
    }

    /**
     * Action para perfil de usuario y cambio opcional de contraseña
     */
    public function actionMiCuenta()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $user = Yii::$app->user->identity;
        $model = new \app\models\CambioPasswordForm();

        // Verificar si viene de POST para cambiar contraseña
        if (Yii::$app->request->post() && $model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($user->cambiarPassword($model->newPassword)) {
                Yii::$app->session->setFlash('success', 'Contraseña cambiada exitosamente.');
                
                // Registrar cambio en logs
                Yii::info("Usuario {$user->username} actualizó su contraseña desde Mi Cuenta", 'security');
                
                return $this->refresh();
            } else {
                Yii::$app->session->setFlash('error', 'Error al cambiar la contraseña.');
            }
        }

        return $this->render('mi-cuenta', [
            'user' => $user,
            'model' => $model,
        ]);
    }
}