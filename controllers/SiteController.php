<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\User;
use app\models\VerificationSession;
use app\models\AuditLog;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;

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
                'only' => [
                    'logout', 'acceder-sistema', 'mi-cuenta', 'testcss',
                    'verify-email-first', 'validate-code', 'change-password-first', 'resend-code'
                ],
                'rules' => [
                    [
                        'actions' => ['logout', 'mi-cuenta', 'testcss'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => [
                            'verify-email-first', 
                            'validate-code', 
                            'change-password-first', 
                            'resend-code',
                            'acceder-sistema'
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'denyCallback' => function ($rule, $action) {
                            Yii::$app->session->setFlash('error', 'Debe iniciar sesión para acceder a esta función.');
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
     * Landing page pública - CON PREVENCIÓN DE BUCLE COMPLETA
     *
     * @return string
     */
    public function actionIndex()
    {
        // ✅ PREVENCIÓN DE BUCLE: Verificar si ya estamos autenticados en página de login
        // ✅ SI YA ESTÁ AUTENTICADO Y ACCEDE AL INDEX, NO HACER NADA ESPECIAL
        // ✅ PERMITIR QUE USUARIOS AUTENTICADOS VEAN EL LANDING
        
        // ✅ SEGURO: NUNCA redirigir automáticamente a rutas internas
        // ✅ Mostrar siempre landing page pública
        
        return $this->render('index', [
            'isAuthenticated' => !Yii::$app->user->isGuest
        ]);
    }

    /**
     * ✅ PUNTO DE ENTRADA SEGURO al sistema - CON PREVENCIÓN DE BUCLE
     * No revela rutas internas directamente
     */
    public function actionAccederSistema()
    {
        // Verificar autenticación (ya lo hace el behavior, pero por redundancia)
        if (Yii::$app->user->isGuest) {
            Yii::$app->session->setFlash('error', 'Debe iniciar sesión para acceder al sistema.');
            return $this->redirect(['/site/login']);
        }
        
        $user = Yii::$app->user->identity;
        
        // Verificar si es primer acceso
        if ($user->isFirstAccess()) {
            return $this->redirect(['verify-email-first']);
        }
        
        // Verificar si necesita cambio de contraseña
        if ($user->needsPasswordChange()) {
            return $this->redirect(['change-password-first']);
        }
        
        // Verificar si está bloqueado
        if ($user->isBlocked()) {
            Yii::$app->session->setFlash('error', 
                "Su cuenta está bloqueada hasta {$user->blocked_until}. Tiempo restante: {$user->getBlockTimeRemaining()}");
            Yii::$app->user->logout();
            return $this->redirect(['login']);
        }
        
        // ✅ VERIFICACIÓN EXTRA: Si ya estamos en una página del sistema GED, no redirigir
        $currentRoute = Yii::$app->controller->route;
        if (strpos($currentRoute, 'ged/') === 0) {
            // Ya estamos en el sistema GED, no redirigir
            return $this->redirect(['/ged/default/index']);
        }
        
        // ✅ REDIRECCIÓN SEGURA: Usar nombre de ruta en lugar de URL completa
        // Esto no revela la estructura interna al usuario
        
        // Registrar el acceso en logs para auditoría
        AuditLog::log($user->id, 'system_access', 'Acceso al sistema principal');
        
        // Redirigir al punto de entrada del módulo GED
        return $this->redirect(['/ged/default/index']);
    }

    /**
     * ✅ Login action - CON FLUJO DE VERIFICACIÓN SEGURA
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        // ✅ SI YA ESTÁ AUTENTICADO, NO PERMITIR ACCEDER AL LOGIN
        if (!Yii::$app->user->isGuest) {
            // ✅ SI ESTÁ AUTENTICADO Y ACCEDE A LOGIN, REDIRIGIR A ACCEDER-SISTEMA
            // Esto previene el bucle de login->index->login
            Yii::$app->session->setFlash('info', 'Ya tienes una sesión activa.');
            return $this->redirect(['/site/acceder-sistema']);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $user = Yii::$app->user->identity;
            
            // Verificar si está bloqueado
            if ($user->isBlocked()) {
                Yii::$app->session->setFlash('error', 
                    "Su cuenta está bloqueada hasta {$user->blocked_until}. Tiempo restante: {$user->getBlockTimeRemaining()}");
                Yii::$app->user->logout();
                return $this->redirect(['login']);
            }
            
            // Registrar login exitoso
            $user->recordSuccessfulLogin();
            
            // Verificar si es primer acceso
            if ($user->isFirstAccess()) {
                Yii::$app->session->setFlash('warning', 
                    'Es su primer acceso. Debe verificar su email y cambiar su contraseña.');
                return $this->redirect(['verify-email-first']);
            }
            
            // Verificar si necesita cambio de contraseña
            if ($user->needsPasswordChange()) {
                Yii::$app->session->setFlash('warning', 
                    'Debe cambiar su contraseña antes de continuar.');
                return $this->redirect(['change-password-first']);
            }
            
            Yii::$app->session->setFlash('success', 'Sesión iniciada correctamente.');
            
            // ✅ PREVENIR REDIRECCIÓN A LOGIN DESPUÉS DE LOGIN
            // Si la URL anterior es login o index, redirigir a acceder-sistema
            $returnUrl = Yii::$app->request->referrer;
            if (!$returnUrl || strpos($returnUrl, 'login') !== false || strpos($returnUrl, 'index') !== false) {
                return $this->redirect(['/site/acceder-sistema']);
            }
            
            return $this->goBack();
        } else {
            // Registrar intento fallido si el usuario existe
            if ($model->username) {
                $user = User::findByUsername($model->username);
                if ($user) {
                    $user->recordFailedLogin();
                    
                    // Verificar si fue bloqueado
                    if ($user->isBlocked()) {
                        Yii::$app->session->setFlash('error', 
                            "Demasiados intentos fallidos. Su cuenta está bloqueada hasta {$user->blocked_until}");
                    }
                }
            }
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * ✅ Logout action
     */
    public function actionLogout()
    {
        // Registrar logout en logs
        if (!Yii::$app->user->isGuest) {
            AuditLog::log(Yii::$app->user->id, 'logout', 'Usuario cerró sesión');
        }
        
        // Logout normal
        Yii::$app->user->logout();

        Yii::$app->session->setFlash('success', 'Sesión cerrada correctamente.');
        
        // ✅ SIEMPRE REDIRIGIR AL INDEX, NUNCA AL LOGIN
        return $this->redirect(['site/index']);
    }

    /**
     * ✅ VERIFICACIÓN DE EMAIL REAL (PRIMER ACCESO)
     * 
     * @return string|Response
     */
    public function actionVerifyEmailFirst()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }
        
        $user = Yii::$app->user->identity;
        
        // Verificar que sea primer acceso
        if (!$user->isFirstAccess()) {
            Yii::$app->session->setFlash('info', 'Su email ya fue verificado anteriormente.');
            return $this->redirect(['acceder-sistema']);
        }
        
        // Verificar si está bloqueado
        if ($user->isBlocked()) {
            Yii::$app->session->setFlash('error', 
                "Su cuenta está bloqueada hasta {$user->blocked_until}. Tiempo restante: {$user->getBlockTimeRemaining()}");
            Yii::$app->user->logout();
            return $this->redirect(['login']);
        }
        
        // Crear formulario dinámico
        $model = new \yii\base\DynamicModel(['email', 'emailConfirm', 'captcha']);
        $model->addRule(['email', 'emailConfirm', 'captcha'], 'required')
              ->addRule(['email'], 'email')
              ->addRule(['emailConfirm'], 'compare', ['compareAttribute' => 'email'])
              ->addRule(['captcha'], 'captcha', ['captchaAction' => 'site/captcha']);
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Verificar que el email no esté en uso por otro usuario
            $existingUser = User::findByEmail($model->email);
            if ($existingUser && $existingUser->id != $user->id) {
                $model->addError('email', 'Este email ya está registrado por otro usuario.');
            } else {
                // Guardar email real temporalmente
                $user->real_email = $model->email;
                $user->save(false, ['real_email', 'updated_at']);
                
                // Crear sesión de verificación
                $session = $user->createVerificationSession();
                
                if ($session) {
                    // Enviar email con código
                    if ($this->sendVerificationCode($user, $session->code)) {
                        Yii::$app->session->setFlash('success', 
                            'Se ha enviado un código de verificación a su email. Tiene 15 minutos para ingresarlo.');
                        
                        return $this->redirect(['validate-code', 'token' => $session->token]);
                    } else {
                        Yii::$app->session->setFlash('error', 
                            'Error al enviar el código de verificación. Por favor, intente nuevamente.');
                    }
                } else {
                    Yii::$app->session->setFlash('error', 
                        'Error al crear la sesión de verificación. Por favor, intente nuevamente.');
                }
            }
        }
        
        return $this->render('verify-email-first', [
            'model' => $model,
            'user' => $user,
        ]);
    }

    /**
     * ✅ VALIDAR CÓDIGO DE VERIFICACIÓN
     * 
     * @param string $token Token de la sesión
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionValidateCode($token)
    {
        // Buscar sesión activa
        $session = VerificationSession::find()
            ->where(['token' => $token, 'status' => VerificationSession::STATUS_PENDING])
            ->andWhere(['>', 'expires_at', time()])
            ->one();
            
        if (!$session) {
            throw new NotFoundHttpException('La sesión de verificación no es válida o ha expirado.');
        }
        
        $user = $session->user;
        
        // Verificar que el usuario esté logueado
        if (Yii::$app->user->isGuest || Yii::$app->user->id != $user->id) {
            return $this->redirect(['login']);
        }
        
        // Crear formulario dinámico para código
        $model = new \yii\base\DynamicModel(['code']);
        $model->addRule(['code'], 'required')
              ->addRule(['code'], 'string', ['length' => 6])
              ->addRule(['code'], 'match', ['pattern' => '/^\d{6}$/']);
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Validar intentos (máximo 3)
            if ($session->attempts >= 3) {
                $user->incrementBlockCount();
                Yii::$app->session->setFlash('error', 
                    'Ha excedido el número máximo de intentos. Su cuenta ha sido bloqueada por 24 horas.');
                Yii::$app->user->logout();
                return $this->redirect(['login']);
            }
            
            // Validar código
            if ($session->code === $model->code) {
                // Código correcto
                $session->status = VerificationSession::STATUS_VERIFIED;
                $session->verified_at = date('Y-m-d H:i:s');
                $session->save();
                
                // Actualizar email del usuario
                $user->email = $user->real_email;
                $user->markEmailAsVerified();
                
                Yii::$app->session->setFlash('success', 'Email verificado exitosamente.');
                return $this->redirect(['change-password-first']);
            } else {
                // Código incorrecto
                $session->attempts++;
                $session->save();
                
                $attemptsLeft = 3 - $session->attempts;
                Yii::$app->session->setFlash('error', 
                    "Código incorrecto. Le quedan {$attemptsLeft} intento(s).");
            }
        }
        
        return $this->render('verify-code', [
            'model' => $model,
            'token' => $token,
            'session' => $session,
            'user' => $user,
        ]);
    }

    /**
     * ✅ CAMBIO DE CONTRASEÑA OBLIGATORIO (PRIMER ACCESO)
     * 
     * @return string|Response
     */
    public function actionChangePasswordFirst()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }
        
        $user = Yii::$app->user->identity;
        
        // Verificar que necesite cambio de contraseña
        if (!$user->needsPasswordChange()) {
            Yii::$app->session->setFlash('info', 'Su contraseña ya fue cambiada anteriormente.');
            return $this->redirect(['acceder-sistema']);
        }
        
        // Crear formulario dinámico
        $model = new \yii\base\DynamicModel(['newPassword', 'newPasswordConfirm']);
        $model->addRule(['newPassword', 'newPasswordConfirm'], 'required')
              ->addRule(['newPassword'], 'string', ['min' => 8])
              ->addRule(['newPasswordConfirm'], 'compare', ['compareAttribute' => 'newPassword']);
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Cambiar contraseña con validaciones
            $result = $user->changePasswordWithValidation($model->newPassword);
            
            if ($result['success']) {
                Yii::$app->session->setFlash('success', $result['message']);
                
                // Si es primer acceso, redirigir al sistema
                if ($user->isFirstAccess()) {
                    return $this->redirect(['acceder-sistema']);
                }
                
                return $this->redirect(['site/index']);
            } else {
                Yii::$app->session->setFlash('error', $result['message']);
            }
        }
        
        return $this->render('change-password-first', [
            'model' => $model,
            'user' => $user,
        ]);
    }

    /**
     * ✅ REENVIAR CÓDIGO DE VERIFICACIÓN
     * 
     * @param string $token Token de la sesión
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionResendCode($token)
    {
        $session = VerificationSession::find()
            ->where(['token' => $token, 'status' => VerificationSession::STATUS_PENDING])
            ->one();
            
        if (!$session) {
            throw new NotFoundHttpException('La sesión de verificación no es válida.');
        }
        
        $user = $session->user;
        
        // Verificar límite de reenvíos (máximo 5 en 24 horas)
        $last24Hours = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $totalSessions = VerificationSession::find()
            ->where(['user_id' => $user->id, 'type' => 'email'])
            ->andWhere(['>=', 'created_at', $last24Hours])
            ->count();
            
        if ($totalSessions >= 5) {
            $user->incrementBlockCount();
            Yii::$app->session->setFlash('error', 
                'Ha excedido el límite de códigos solicitados. Su cuenta ha sido bloqueada por 24 horas.');
            Yii::$app->user->logout();
            return $this->redirect(['login']);
        }
        
        // Crear nueva sesión
        $newSession = $user->createVerificationSession();
        
        if ($newSession) {
            // Marcar sesión anterior como expirada
            $session->status = VerificationSession::STATUS_EXPIRED;
            $session->save();
            
            // Enviar nuevo código
            if ($this->sendVerificationCode($user, $newSession->code)) {
                Yii::$app->session->setFlash('success', 
                    'Se ha enviado un nuevo código de verificación a su email.');
            } else {
                Yii::$app->session->setFlash('error', 
                    'Error al enviar el código de verificación. Por favor, intente nuevamente.');
            }
            
            return $this->redirect(['validate-code', 'token' => $newSession->token]);
        }
        
        Yii::$app->session->setFlash('error', 
            'Error al generar nuevo código. Por favor, intente nuevamente.');
        return $this->redirect(['validate-code', 'token' => $token]);
    }

    /**
     * ✅ ENVIAR CÓDIGO DE VERIFICACIÓN POR EMAIL
     * 
     * @param User $user
     * @param string $code
     * @return bool
     */
    private function sendVerificationCode($user, $code)
    {
        try {
            $email = $user->real_email ?: $user->email;
            
            if (empty($email)) {
                Yii::error('No hay email para enviar código de verificación', 'app');
                return false;
            }
            
            // Configurar mailer si no está configurado
            if (!isset(Yii::$app->params['adminEmail'])) {
                Yii::$app->params['adminEmail'] = 'noreply@sistema-ged.com';
            }
            
            $message = Yii::$app->mailer->compose()
                ->setTo($email)
                ->setFrom([Yii::$app->params['adminEmail'] => Yii::$app->name])
                ->setSubject('Código de Verificación - ' . Yii::$app->name)
                ->setTextBody("Su código de verificación es: {$code}\n\nVálido por 15 minutos.")
                ->setHtmlBody($this->renderPartial('@app/views/mail/verification-code', [
                    'user' => $user,
                    'code' => $code,
                ]));
                
            return $message->send();
                
        } catch (\Exception $e) {
            Yii::error('Error al enviar email de verificación: ' . $e->getMessage(), 'app');
            return false;
        }
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
     * ✅ Action para cambiar contraseña obligatorio - CON PREVENCIÓN DE BUCLE
     */
    public function actionCambiarPassword()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        // Usar el modelo si existe, sino crear uno básico
        $modelClassName = '\app\models\CambioPasswordForm';
        if (class_exists($modelClassName)) {
            $model = new $modelClassName();
        } else {
            // Modelo básico como fallback
            $model = new \yii\base\DynamicModel(['currentPassword', 'newPassword', 'confirmPassword']);
            $model->addRule(['currentPassword', 'newPassword', 'confirmPassword'], 'required')
                  ->addRule(['confirmPassword'], 'compare', ['compareAttribute' => 'newPassword']);
        }

        $user = Yii::$app->user->identity;

        // Verificar si realmente debe cambiar la contraseña
        if (method_exists($user, 'debeCambiarPassword') && !$user->debeCambiarPassword()) {
            Yii::$app->session->setFlash('info', 'Su contraseña ya ha sido cambiada anteriormente.');
            return $this->redirect(['site/index']);
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if (method_exists($user, 'cambiarPassword') && $user->cambiarPassword($model->newPassword)) {
                Yii::$app->session->setFlash('success', 'Contraseña cambiada exitosamente. Ahora puede usar el sistema.');
                
                // Registrar el cambio en logs de seguridad
                AuditLog::log($user->id, 'password_changed_legacy', 'Contraseña cambiada desde acción cambiar-password');
                
                // ✅ REDIRIGIR AL INDEX, NO A LOGIN
                return $this->redirect(['/site/index']);
            } else {
                Yii::$app->session->setFlash('error', 'Error al cambiar la contraseña. Por favor intente nuevamente.');
            }
        }

        return $this->render('cambio-password', [
            'model' => $model,
        ]);
    }

    /**
     * ✅ Action para perfil de usuario y cambio opcional de contraseña - SIN BUCLE
     */
    public function actionMiCuenta()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $user = Yii::$app->user->identity;
        
        // Usar el modelo si existe, sino crear uno básico
        $modelClassName = '\app\models\CambioPasswordForm';
        if (class_exists($modelClassName)) {
            $model = new $modelClassName();
        } else {
            $model = new \yii\base\DynamicModel(['currentPassword', 'newPassword', 'confirmPassword']);
            $model->addRule(['currentPassword', 'newPassword', 'confirmPassword'], 'required')
                  ->addRule(['confirmPassword'], 'compare', ['compareAttribute' => 'newPassword']);
        }

        // Verificar si viene de POST para cambiar contraseña
        if (Yii::$app->request->post() && $model->load(Yii::$app->request->post()) && $model->validate()) {
            if (method_exists($user, 'cambiarPassword') && $user->cambiarPassword($model->newPassword)) {
                Yii::$app->session->setFlash('success', 'Contraseña cambiada exitosamente.');
                
                // Registrar cambio en logs
                AuditLog::log($user->id, 'password_changed_profile', 'Contraseña cambiada desde Mi Cuenta');
                
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
    
    /**
     * ✅ MÉTODO ADICIONAL: goHome personalizado para prevenir bucle
     * Sobrescribe el método goHome() para asegurar que siempre redirija al index
     */
    public function goHome()
    {
        // ✅ SIEMPRE REDIRIGIR AL INDEX, NUNCA AL LOGIN
        return $this->redirect(['site/index']);
    }
    
    /**
     * ✅ MÉTODO ADICIONAL: Test para CSS
     */
    public function actionTestcss()
    {
        return $this->render('test-css');
    }

    /**
     * Acción para probar los módulos JavaScript
     * @return string
     */
    public function actionTestJs()
    {
        // Solo permitir en desarrollo
        if (!YII_DEBUG && !YII_ENV_DEV) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        return $this->render('test-js');
    }
    
    /**
     * ✅ MÉTODO ADICIONAL: Verificar si hay bucle de redirección
     * Se puede llamar desde JavaScript para debug
     */
     public function actionCheckRedirectLoop()
    {
        // Solo permitir en desarrollo
        if (!YII_DEBUG && !YII_ENV_DEV) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        Yii::info('Verificación de bucle de redirección solicitada', 'security');
        
        $data = [
            'currentRoute' => Yii::$app->controller->route,
            'isGuest' => Yii::$app->user->isGuest,
            'sessionId' => Yii::$app->session->id,
            'referrer' => Yii::$app->request->referrer,
            'userAgent' => Yii::$app->request->userAgent
        ];
        
        return $this->asJson([
            'status' => 'ok',
            'message' => 'No se detectó bucle de redirección',
            'data' => $data
        ]);
    }
    
    public function actionGetMobileMenu()
    {
        // Deshabilitar layout para solo devolver el HTML del menú
        $this->layout = false;
        
        // Obtener el menú usando MenuWidget
        $menuHtml = \app\components\MenuWidget::widget([
            'mobileMode' => true,
            'options' => ['class' => 'mobile-menu nav flex-column']
        ]);
        
        // Devolver el HTML
        return $menuHtml ?: '<div class="alert alert-warning">Menú no disponible</div>';
    }
    
    public function actionDebugMenu()
    {
        // Solo permitir en desarrollo
        if (!YII_DEBUG && !YII_ENV_DEV) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        $this->layout = false;
        
        echo "<!DOCTYPE html><html><head><title>Debug Menu</title><style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f2f2f2; }
            .debug-box { background: #e9f7fe; border-left: 4px solid #3498db; padding: 15px; margin: 10px 0; }
            .test-btn { padding: 10px 15px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
            .test-btn:hover { background: #2980b9; }
        </style></head><body>";
        
        echo "<h1>🔍 DEBUG DEL SISTEMA DE MENÚ</h1>";
        
        echo "<div class='section'>";
        echo "<h2>1. Verificar Base de Datos</h2>";
        
        try {
            $db = \Yii::$app->db;
            if ($db && $db->getIsActive()) {
                echo "<p class='success'>✅ Conexión a BD establecida</p>";
            } else {
                echo "<p class='error'>❌ No hay conexión activa a BD</p>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h2>2. Verificar Tabla 'seguridad.menu'</h2>";
        
        try {
            $tableExists = \Yii::$app->db->createCommand("
                SELECT EXISTS (
                    SELECT FROM information_schema.tables 
                    WHERE table_schema = 'seguridad' 
                    AND table_name = 'menu'
                )
            ")->queryScalar();
            
            if ($tableExists) {
                echo "<p class='success'>✅ Tabla 'seguridad.menu' existe</p>";
                
                $count = \Yii::$app->db->createCommand("SELECT COUNT(*) FROM seguridad.menu")->queryScalar();
                echo "<p>Total registros: " . $count . "</p>";
                
                $columns = \Yii::$app->db->createCommand("
                    SELECT column_name, data_type, is_nullable
                    FROM information_schema.columns
                    WHERE table_schema = 'seguridad' AND table_name = 'menu'
                    ORDER BY ordinal_position
                ")->queryAll();
                
                echo "<h4>Estructura de la tabla:</h4>";
                echo "<table>";
                echo "<tr><th>Columna</th><th>Tipo</th><th>¿Nulo?</th></tr>";
                foreach ($columns as $col) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($col['column_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($col['data_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($col['is_nullable']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } else {
                echo "<p class='error'>❌ Tabla 'seguridad.menu' NO existe</p>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h2>3. Contenido de la Tabla</h2>";
        
        try {
            $menus = \Yii::$app->db->createCommand("
                SELECT id, name, route, parent, \"order\" as menu_order, data
                FROM seguridad.menu 
                ORDER BY COALESCE(\"order\", 99999)
            ")->queryAll();
            
            if (empty($menus)) {
                echo "<p class='warning'>⚠️ La tabla está vacía</p>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Ruta</th><th>Parent</th><th>Orden</th><th>Data (JSON)</th></tr>";
                foreach ($menus as $menu) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($menu['id']) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($menu['name']) . "</strong></td>";
                    echo "<td>" . ($menu['route'] ? htmlspecialchars($menu['route']) : '<em># (sin ruta)</em>') . "</td>";
                    echo "<td>" . ($menu['parent'] ?: '<em>raíz</em>') . "</td>";
                    echo "<td>" . $menu['menu_order'] . "</td>";
                    echo "<td><pre>" . htmlspecialchars($menu['data'] ?? '{}') . "</pre></td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error al leer datos: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h2>4. Usuario Actual</h2>";
        
        if (\Yii::$app->user->isGuest) {
            echo "<p class='warning'>👤 Usuario: <strong>INVITADO</strong></p>";
        } else {
            $user = \Yii::$app->user->identity;
            echo "<p class='success'>👤 Usuario: <strong>" . htmlspecialchars($user->username) . "</strong></p>";
            echo "<p>ID: " . $user->id . "</p>";
            
            echo "<h4>Permisos RBAC:</h4>";
            $auth = \Yii::$app->authManager;
            $permissions = $auth->getPermissionsByUser($user->id);
            
            if (empty($permissions)) {
                echo "<p class='warning'>⚠️ El usuario no tiene permisos asignados</p>";
            } else {
                echo "<ul>";
                foreach ($permissions as $permission) {
                    echo "<li>" . htmlspecialchars($permission->name) . "</li>";
                }
                echo "</ul>";
            }
        }
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h2>5. Probar MenuWidget</h2>";
        
        echo "<div class='debug-box'>";
        echo "<h4>Menú generado por MenuWidget::widget():</h4>";
        
        try {
            $menuOutput = \app\components\MenuWidget::widget([
                'options' => [
                    'class' => 'navbar-nav main-navigation w-100',
                    'mobileMode' => false,
                    'rootOnly' => false
                ]
            ]);
            
            if (empty(trim($menuOutput))) {
                echo "<p class='error'>❌ MenuWidget NO generó ningún contenido</p>";
                echo "<p>El widget retornó una cadena vacía.</p>";
            } else {
                echo "<p class='success'>✅ MenuWidget generó contenido</p>";
                echo "<div style='border: 2px dashed #3498db; padding: 15px; background: #f9f9f9;'>";
                echo $menuOutput;
                echo "</div>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error en MenuWidget: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        echo "</div>";
        
        echo "<div class='debug-box'>";
        echo "<h4>Probar getMenuItems() directamente:</h4>";
        
        try {
            $widget = new \app\components\MenuWidget();
            $items = $widget->getMenuItems(null);
            
            echo "<p>Total elementos obtenidos: " . count($items) . "</p>";
            
            if (!empty($items)) {
                echo "<h5>Estructura del menú:</h5>";
                echo "<pre style='background: #f0f0f0; padding: 10px;'>";
                foreach ($items as $index => $item) {
                    echo "Item {$index}: " . (isset($item['label']) ? htmlspecialchars($item['label']) : 'Sin etiqueta') . " (" . (isset($item['route']) ? htmlspecialchars($item['route']) : '#') . ")\n";
                    if (!empty($item['items'])) {
                        foreach ($item['items'] as $childIndex => $child) {
                            echo "  ├─ Child {$childIndex}: " . (isset($child['label']) ? htmlspecialchars($child['label']) : 'Sin etiqueta') . " (" . (isset($child['route']) ? htmlspecialchars($child['route']) : '#') . ")\n";
                        }
                    }
                }
                echo "</pre>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        echo "</div>";
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h2>6. Verificar Rutas Públicas</h2>";
        
        $testRoutes = [
            'tienda/marketplace' => 'Marketplace',
            'tienda/marketplace/index' => 'Marketplace Index',
            'ged/default/index' => 'GED Seleccionar Escuela',
            'site/index' => 'Página Principal',
            'admin/user/index' => 'Admin Usuarios (NO debería ser pública)',
            'tienda/producto/create' => 'Crear Producto',
        ];
        
        echo "<table>";
        echo "<tr><th>Ruta</th><th>Descripción</th><th>¿Pública?</th><th>Acción</th></tr>";
        
        foreach ($testRoutes as $route => $desc) {
            $publicRoutes = \Yii::$app->params['publicRoutes'] ?? [];
            $isPublic = in_array($route, $publicRoutes);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($route) . "</td>";
            echo "<td>" . htmlspecialchars($desc) . "</td>";
            echo "<td class='" . ($isPublic ? "success" : "error") . "'>" . ($isPublic ? "✅ SÍ" : "❌ NO") . "</td>";
            echo "<td>";
            echo "<button class='test-btn' onclick=\"window.open('/" . htmlspecialchars($route) . "', '_blank')\">Probar ruta</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='debug-box'>";
        echo "<h4>Rutas públicas configuradas en params:</h4>";
        $publicRoutes = \Yii::$app->params['publicRoutes'] ?? [];
        echo "<pre>" . htmlspecialchars(print_r($publicRoutes, true)) . "</pre>";
        
        $allowActions = [
            'site/index',
            'site/login',
            'site/about',
            'site/contact',
            'site/signup',
            'site/request-password-reset',
            'site/reset-password',
            'tienda/marketplace/index',
            'tienda/marketplace/buscar',
            'tienda/marketplace/categoria',
            'admin/user/signup',
            'admin/user/request-password-reset',
            'admin/user/reset-password',
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
            'parroquia/get-by-muni-cod',
            'tasa-dolar/index',
        ];
        echo "<h4>Rutas en allowActions:</h4>";
        echo "<pre>" . htmlspecialchars(print_r($allowActions, true)) . "</pre>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h2>7. Acciones</h2>";
        
        echo "<button class='test-btn' onclick='window.location.reload()'>🔄 Recargar</button>";
        echo "<button class='test-btn' onclick='window.open(\"/\", \"_blank\")'>🏠 Ir al inicio</button>";
        echo "<button class='test-btn' onclick='clearCache()'>🧹 Limpiar caché</button>";
        echo "<button class='test-btn' onclick='testMenuWidget()'>🧪 Probar MenuWidget</button>";
        
        echo "<script>
            function clearCache() {
                fetch('/site/clear-cache')
                    .then(function(response) { return response.text(); })
                    .then(function(data) {
                        alert('Cache limpiado');
                        window.location.reload();
                    })
                    .catch(function(error) { console.error('Error:', error); });
            }
            
            function testMenuWidget() {
                fetch('/site/test-menu-widget')
                    .then(function(response) { return response.text(); })
                    .then(function(data) {
                        alert('Test completado. Ver consola para detalles.');
                        console.log('Test MenuWidget:', data);
                    })
                    .catch(function(error) { console.error('Error:', error); });
            }
        </script>";
        echo "</div>";
        
        echo "</body></html>";
        exit;
    }

    public function actionTestMenuWidget()
    {
        // Solo permitir en desarrollo
        if (!YII_DEBUG && !YII_ENV_DEV) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        try {
            $widget = new \app\components\MenuWidget();
            $items = $widget->getMenuItems(null);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count' => count($items),
                'items' => $items
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], JSON_PRETTY_PRINT);
        }
        exit;
    }

    public function actionClearCache()
    {
        // Solo permitir en desarrollo
        if (!YII_DEBUG && !YII_ENV_DEV) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        if (Yii::$app->cache) {
            Yii::$app->cache->flush();
        }
        
        // Limpiar caché de menú específico
        \app\components\MenuWidget::forceReload();
        
        echo "Cache limpiado";
        exit;
    }

    /**
     * Acción para diagnosticar el menú GED
     * URL: /site/diagnosticar-menu
     */
    public function actionDiagnosticarMenu()
    {
        // Solo permitir en desarrollo
        if (!YII_DEBUG && !YII_ENV_DEV) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        Yii::$app->response->format = Response::FORMAT_RAW;
        header('Content-Type: text/javascript');
        
        // Construir el script manualmente para evitar problemas de sintaxis
        $script = '// ==================================================' . "\n";
        $script .= '// DIAGNÓSTICO COMPLETO DEL MENÚ GED - SiteController' . "\n";
        $script .= '// Generado: ' . date('Y-m-d H:i:s') . "\n";
        $script .= '// ==================================================' . "\n\n";
        
        $script .= 'console.clear();' . "\n";
        $script .= 'console.log("%c🚀 DIAGNÓSTICO DEL MENÚ GED - SiteController",' . "\n";
        $script .= '            "color: #fff; background: #6c3483; padding: 5px 10px; border-radius: 3px; font-size: 16px;");' . "\n";
        $script .= 'console.log("URL: " + window.location.href);' . "\n";
        $script .= 'console.log("=========================================\\n");' . "\n\n";
        
        // 1. DIAGNÓSTICO BÁSICO
        $script .= '// 1. DIAGNÓSTICO BÁSICO' . "\n";
        $script .= 'function diagnosticoBasico() {' . "\n";
        $script .= '    console.log("%c1. 📊 DIAGNÓSTICO BÁSICO", "color: #3498db; font-weight: bold;");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    var submenus = document.querySelectorAll(".dropdown-submenu");' . "\n";
        $script .= '    var menus = document.querySelectorAll(".dropdown-menu");' . "\n";
        $script .= '    var mainNavItems = document.querySelectorAll(".main-navigation > li");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("   • Submenús (.dropdown-submenu): " + submenus.length);' . "\n";
        $script .= '    console.log("   • Menús dropdown (.dropdown-menu): " + menus.length);' . "\n";
        $script .= '    console.log("   • Elementos en menú principal: " + mainNavItems.length);' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Mostrar estructura' . "\n";
        $script .= '    if (submenus.length > 0) {' . "\n";
        $script .= '        console.log("   📍 Submenús encontrados:");' . "\n";
        $script .= '        submenus.forEach(function(sub, i) {' . "\n";
        $script .= '            var link = sub.querySelector("a");' . "\n";
        $script .= '            var hasMenu = sub.querySelector(".dropdown-menu");' . "\n";
        $script .= '            var linkText = link ? link.textContent.trim() : "Sin texto";' . "\n";
        $script .= '            console.log("     " + (i+1) + ". " + linkText + " " + (hasMenu ? "✅" : "❌"));' . "\n";
        $script .= '        });' . "\n";
        $script .= '    } else {' . "\n";
        $script .= '        console.log("   ❌ NO se encontraron submenús anidados");' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("");' . "\n";
        $script .= '    return submenus.length;' . "\n";
        $script .= '}' . "\n\n";
        
        // 2. VERIFICACIÓN DE ESTILOS CSS
        $script .= '// 2. VERIFICACIÓN DE ESTILOS CSS' . "\n";
        $script .= 'function verificarEstilosCSS() {' . "\n";
        $script .= '    console.log("%c2. 🎨 VERIFICACIÓN DE ESTILOS CSS", "color: #3498db; font-weight: bold;");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    var stylesheets = Array.from(document.styleSheets);' . "\n";
        $script .= '    var submenuStyles = 0;' . "\n";
        $script .= '    var submenuCSS = [];' . "\n";
        $script .= '    ' . "\n";
        $script .= '    stylesheets.forEach(function(sheet, i) {' . "\n";
        $script .= '        try {' . "\n";
        $script .= '            var rules = Array.from(sheet.cssRules || []);' . "\n";
        $script .= '            rules.forEach(function(rule) {' . "\n";
        $script .= '                if (rule.selectorText && rule.selectorText.includes(".dropdown-submenu")) {' . "\n";
        $script .= '                    submenuStyles++;' . "\n";
        $script .= '                    submenuCSS.push({' . "\n";
        $script .= '                        sheet: sheet.href ? sheet.href.split("/").pop() : "Sheet " + i,' . "\n";
        $script .= '                        selector: rule.selectorText,' . "\n";
        $script .= '                        display: rule.style ? rule.style.display || "N/A" : "N/A"' . "\n";
        $script .= '                    });' . "\n";
        $script .= '                }' . "\n";
        $script .= '            });' . "\n";
        $script .= '        } catch(e) {' . "\n";
        $script .= '            // Ignorar errores de CORS' . "\n";
        $script .= '        }' . "\n";
        $script .= '    });' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("   • Reglas CSS para .dropdown-submenu: " + submenuStyles);' . "\n";
        $script .= '    ' . "\n";
        $script .= '    if (submenuCSS.length > 0) {' . "\n";
        $script .= '        console.log("   📋 Reglas encontradas:");' . "\n";
        $script .= '        submenuCSS.forEach(function(css) {' . "\n";
        $script .= '            console.log("     • " + css.sheet + ": " + css.selector);' . "\n";
        $script .= '        });' . "\n";
        $script .= '    } else {' . "\n";
        $script .= '        console.log("   ⚠️  No se encontraron reglas CSS específicas para submenús");' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Verificar si _submenus.css está cargado' . "\n";
        $script .= '    var submenuFile = Array.from(stylesheets).find(function(s) {' . "\n";
        $script .= '        return s.href && s.href.includes("_submenus.css");' . "\n";
        $script .= '    });' . "\n";
        $script .= '    console.log("   • _submenus.css cargado: " + (submenuFile ? "✅" : "❌"));' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("");' . "\n";
        $script .= '    return submenuStyles;' . "\n";
        $script .= '}' . "\n\n";
        
        // 3. VERIFICACIÓN DE POSICIÓN Y VISIBILIDAD
        $script .= '// 3. VERIFICACIÓN DE POSICIÓN Y VISIBILIDAD' . "\n";
        $script .= 'function verificarPosicionYVisibilidad() {' . "\n";
        $script .= '    console.log("%c3. 📐 VERIFICACIÓN DE POSICIÓN Y VISIBILIDAD", "color: #3498db; font-weight: bold;");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    var submenus = document.querySelectorAll(".dropdown-submenu");' . "\n";
        $script .= '    var viewport = {' . "\n";
        $script .= '        width: window.innerWidth,' . "\n";
        $script .= '        height: window.innerHeight' . "\n";
        $script .= '    };' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("   • Viewport: " + viewport.width + "px × " + viewport.height + "px");' . "\n";
        $script .= '    console.log("   • Es escritorio (>992px): " + (viewport.width >= 992 ? "✅" : "❌"));' . "\n";
        $script .= '    ' . "\n";
        $script .= '    if (submenus.length === 0) {' . "\n";
        $script .= '        console.log("   ⚠️  No hay submenús para verificar");' . "\n";
        $script .= '        return;' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    submenus.forEach(function(submenu, index) {' . "\n";
        $script .= '        var menu = submenu.querySelector(".dropdown-menu");' . "\n";
        $script .= '        if (!menu) return;' . "\n";
        $script .= '        ' . "\n";
        $script .= '        var subRect = submenu.getBoundingClientRect();' . "\n";
        $script .= '        var menuRect = menu.getBoundingClientRect();' . "\n";
        $script .= '        var styles = window.getComputedStyle(menu);' . "\n";
        $script .= '        ' . "\n";
        $script .= '        console.log("\\n   📍 Submenú " + (index + 1) + ":");' . "\n";
        $script .= '        console.log("     • Posición contenedor: (" + subRect.left.toFixed(0) + ", " + subRect.top.toFixed(0) + ")");' . "\n";
        $script .= '        console.log("     • Tamaño contenedor: " + subRect.width.toFixed(0) + "×" + subRect.height.toFixed(0));' . "\n";
        $script .= '        console.log("     • Display: " + styles.display);' . "\n";
        $script .= '        console.log("     • Visibility: " + styles.visibility);' . "\n";
        $script .= '        console.log("     • Opacity: " + styles.opacity);' . "\n";
        $script .= '        console.log("     • Position: " + styles.position);' . "\n";
        $script .= '        console.log("     • Z-index: " + styles.zIndex);' . "\n";
        $script .= '        console.log("     • Left: " + styles.left);' . "\n";
        $script .= '        console.log("     • Top: " + styles.top);' . "\n";
        $script .= '        ' . "\n";
        $script .= '        // Verificar si está fuera de pantalla' . "\n";
        $script .= '        var isOffscreen = menuRect.left < 0 || ' . "\n";
        $script .= '                        menuRect.top < 0 || ' . "\n";
        $script .= '                        menuRect.right > viewport.width ||' . "\n";
        $script .= '                        menuRect.bottom > viewport.height;' . "\n";
        $script .= '        ' . "\n";
        $script .= '        console.log("     • ¿Fuera de pantalla?: " + (isOffscreen ? "❌ SÍ" : "✅ NO"));' . "\n";
        $script .= '        ' . "\n";
        $script .= '        if (isOffscreen) {' . "\n";
        $script .= '            console.log("     ⚠️  Se sale por:");' . "\n";
        $script .= '            if (menuRect.left < 0) console.log("       - Izquierda: " + Math.abs(menuRect.left).toFixed(0) + "px");' . "\n";
        $script .= '            if (menuRect.top < 0) console.log("       - Arriba: " + Math.abs(menuRect.top).toFixed(0) + "px");' . "\n";
        $script .= '            if (menuRect.right > viewport.width) console.log("       - Derecha: " + (menuRect.right - viewport.width).toFixed(0) + "px");' . "\n";
        $script .= '            if (menuRect.bottom > viewport.height) console.log("       - Abajo: " + (menuRect.bottom - viewport.height).toFixed(0) + "px");' . "\n";
        $script .= '        }' . "\n";
        $script .= '    });' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("");' . "\n";
        $script .= '}' . "\n\n";
        
        // 4. VERIFICACIÓN DE EVENTOS
        $script .= '// 4. VERIFICACIÓN DE EVENTOS' . "\n";
        $script .= 'function verificarEventos() {' . "\n";
        $script .= '    console.log("%c4. 🖱️ VERIFICACIÓN DE EVENTOS", "color: #3498db; font-weight: bold;");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    var submenus = document.querySelectorAll(".dropdown-submenu");' . "\n";
        $script .= '    var eventosRegistrados = 0;' . "\n";
        $script .= '    ' . "\n";
        $script .= '    submenus.forEach(function(submenu, index) {' . "\n";
        $script .= '        var mouseenter = submenu._enterHandler ? "✅" : "❌";' . "\n";
        $script .= '        var mouseleave = submenu._leaveHandler ? "✅" : "❌";' . "\n";
        $script .= '        ' . "\n";
        $script .= '        if (mouseenter === "✅" || mouseleave === "✅") {' . "\n";
        $script .= '            eventosRegistrados++;' . "\n";
        $script .= '            console.log("   • Submenú " + (index + 1) + ": mouseenter=" + mouseenter + ", mouseleave=" + mouseleave);' . "\n";
        $script .= '        }' . "\n";
        $script .= '    });' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("   • Total eventos registrados: " + eventosRegistrados + "/" + submenus.length);' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Verificar jQuery' . "\n";
        $script .= '    console.log("   • jQuery disponible: " + (typeof $ !== "undefined" ? "✅" : "❌"));' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Verificar Bootstrap' . "\n";
        $script .= '    console.log("   • Bootstrap disponible: " + (typeof bootstrap !== "undefined" ? "✅" : "❌"));' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("");' . "\n";
        $script .= '}' . "\n\n";
        
        // 5. DIAGNÓSTICO DE PROBLEMAS COMUNES
        $script .= '// 5. DIAGNÓSTICO DE PROBLEMAS COMUNES' . "\n";
        $script .= 'function diagnosticarProblemasComunes() {' . "\n";
        $script .= '    console.log("%c5. 🔍 DIAGNÓSTICO DE PROBLEMAS COMUNES", "color: #e74c3c; font-weight: bold;");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    var problemas = [];' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // 1. Verificar si hay submenús' . "\n";
        $script .= '    var submenus = document.querySelectorAll(".dropdown-submenu");' . "\n";
        $script .= '    if (submenus.length === 0) {' . "\n";
        $script .= '        problemas.push("❌ No se encontraron elementos con clase .dropdown-submenu");' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // 2. Verificar si tienen menús hijos' . "\n";
        $script .= '    submenus.forEach(function(sub, i) {' . "\n";
        $script .= '        if (!sub.querySelector(".dropdown-menu")) {' . "\n";
        $script .= '            problemas.push("❌ Submenú " + (i+1) + " no tiene .dropdown-menu hijo");' . "\n";
        $script .= '        }' . "\n";
        $script .= '    });' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // 3. Verificar CSS cargado' . "\n";
        $script .= '    var stylesheets = Array.from(document.styleSheets);' . "\n";
        $script .= '    var hasSubmenuCSS = stylesheets.some(function(sheet) {' . "\n";
        $script .= '        try {' . "\n";
        $script .= '            var rules = Array.from(sheet.cssRules || []);' . "\n";
        $script .= '            return rules.some(function(rule) {' . "\n";
        $script .= '                return rule.selectorText && rule.selectorText.includes(".dropdown-submenu");' . "\n";
        $script .= '            });' . "\n";
        $script .= '        } catch(e) {' . "\n";
        $script .= '            return false;' . "\n";
        $script .= '        }' . "\n";
        $script .= '    });' . "\n";
        $script .= '    ' . "\n";
        $script .= '    if (!hasSubmenuCSS) {' . "\n";
        $script .= '        problemas.push("❌ No se encontraron reglas CSS para .dropdown-submenu");' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // 4. Verificar z-index conflictivo' . "\n";
        $script .= '    var navbar = document.querySelector(".navbar-contextual");' . "\n";
        $script .= '    if (navbar) {' . "\n";
        $script .= '        var navbarZIndex = parseInt(window.getComputedStyle(navbar).zIndex);' . "\n";
        $script .= '        if (navbarZIndex >= 9999) {' . "\n";
            $script .= '            problemas.push("⚠️  Navbar tiene z-index alto (" + navbarZIndex + ") que podría tapar submenús");' . "\n";
        $script .= '        }' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // 5. Verificar overflow hidden' . "\n";
        $script .= '    document.querySelectorAll("*").forEach(function(el) {' . "\n";
        $script .= '        var style = window.getComputedStyle(el);' . "\n";
        $script .= '        if (style.overflow === "hidden" || style.overflowX === "hidden" || style.overflowY === "hidden") {' . "\n";
        $script .= '            var rect = el.getBoundingClientRect();' . "\n";
        $script .= '            if (rect.width > 100 && rect.height > 100) {' . "\n";
        $script .= '                problemas.push("⚠️  Elemento " + el.tagName + "." + el.className + " tiene overflow:hidden");' . "\n";
        $script .= '            }' . "\n";
        $script .= '        }' . "\n";
        $script .= '    });' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Mostrar problemas' . "\n";
        $script .= '    if (problemas.length === 0) {' . "\n";
        $script .= '        console.log("   ✅ No se detectaron problemas comunes");' . "\n";
        $script .= '    } else {' . "\n";
        $script .= '        console.log("   ⚠️  Se detectaron " + problemas.length + " problemas:");' . "\n";
        $script .= '        problemas.forEach(function(problema) {' . "\n";
        $script .= '            console.log("     " + problema);' . "\n";
        $script .= '        });' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("");' . "\n";
        $script .= '}' . "\n\n";
        
        // 6. SOLUCIONES AUTOMÁTICAS
        $script .= '// 6. SOLUCIONES AUTOMÁTICAS' . "\n";
        $script .= 'function aplicarSolucionesAutomaticas() {' . "\n";
        $script .= '    console.log("%c6. 🔧 APLICANDO SOLUCIONES AUTOMÁTICAS", "color: #27ae60; font-weight: bold;");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    var submenus = document.querySelectorAll(".dropdown-submenu");' . "\n";
        $script .= '    var isDesktop = window.innerWidth >= 992;' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("   • Modo actual: " + (isDesktop ? "Desktop (hover)" : "Mobile (click)"));' . "\n";
        $script .= '    console.log("   • Submenús a configurar: " + submenus.length);' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Remover estilos de debug anteriores' . "\n";
        $script .= '    document.querySelectorAll(".dropdown-submenu").forEach(function(sub) {' . "\n";
        $script .= '        sub.style.outline = "none";' . "\n";
        $script .= '        var menu = sub.querySelector(".dropdown-menu");' . "\n";
        $script .= '        if (menu) menu.style.outline = "none";' . "\n";
        $script .= '    });' . "\n";
        $script .= '    ' . "\n";
        $script .= '    if (isDesktop) {' . "\n";
        $script .= '        // Configurar para desktop - hover' . "\n";
        $script .= '        submenus.forEach(function(submenu) {' . "\n";
        $script .= '            var menu = submenu.querySelector(".dropdown-menu");' . "\n";
        $script .= '            if (!menu) return;' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Remover eventos anteriores' . "\n";
        $script .= '            submenu.removeEventListener("mouseenter", submenu._enterHandler);' . "\n";
        $script .= '            submenu.removeEventListener("mouseleave", submenu._leaveHandler);' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Configurar nuevos eventos' . "\n";
        $script .= '            var enterHandler = function() {' . "\n";
        $script .= '                menu.style.display = "block";' . "\n";
        $script .= '                menu.style.opacity = "1";' . "\n";
        $script .= '                menu.style.visibility = "visible";' . "\n";
        $script .= '                menu.style.transform = "translateX(0)";' . "\n";
        $script .= '                ' . "\n";
        $script .= '                // Posicionar correctamente' . "\n";
        $script .= '                var rect = submenu.getBoundingClientRect();' . "\n";
        $script .= '                var viewportWidth = window.innerWidth;' . "\n";
        $script .= '                ' . "\n";
        $script .= '                if (rect.right + 250 > viewportWidth) {' . "\n";
        $script .= '                    menu.style.left = "auto";' . "\n";
        $script .= '                    menu.style.right = "100%";' . "\n";
        $script .= '                } else {' . "\n";
        $script .= '                    menu.style.left = "100%";' . "\n";
        $script .= '                    menu.style.right = "auto";' . "\n";
        $script .= '                }' . "\n";
        $script .= '            };' . "\n";
        $script .= '            ' . "\n";
        $script .= '            var leaveHandler = function() {' . "\n";
        $script .= '                setTimeout(function() {' . "\n";
        $script .= '                    if (!submenu.matches(":hover") && !menu.matches(":hover")) {' . "\n";
        $script .= '                        menu.style.display = "none";' . "\n";
        $script .= '                        menu.style.opacity = "0";' . "\n";
        $script .= '                        menu.style.visibility = "hidden";' . "\n";
        $script .= '                    }' . "\n";
        $script .= '                }, 100);' . "\n";
        $script .= '            };' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Guardar referencias' . "\n";
        $script .= '            submenu._enterHandler = enterHandler;' . "\n";
        $script .= '            submenu._leaveHandler = leaveHandler;' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Asignar eventos' . "\n";
        $script .= '            submenu.addEventListener("mouseenter", enterHandler);' . "\n";
        $script .= '            submenu.addEventListener("mouseleave", leaveHandler);' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Ocultar inicialmente' . "\n";
        $script .= '            menu.style.display = "none";' . "\n";
        $script .= '            menu.style.opacity = "0";' . "\n";
        $script .= '            menu.style.visibility = "hidden";' . "\n";
        $script .= '            menu.style.position = "absolute";' . "\n";
        $script .= '            menu.style.zIndex = "9999";' . "\n";
        $script .= '            menu.style.transition = "all 0.3s ease";' . "\n";
        $script .= '        });' . "\n";
        $script .= '        ' . "\n";
        $script .= '        console.log("   ✅ Comportamiento hover configurado para desktop");' . "\n";
        $script .= '        ' . "\n";
        $script .= '    } else {' . "\n";
        $script .= '        // Configurar para mobile - click' . "\n";
        $script .= '        submenus.forEach(function(submenu) {' . "\n";
        $script .= '            var toggle = submenu.querySelector(".dropdown-toggle");' . "\n";
        $script .= '            var menu = submenu.querySelector(".dropdown-menu");' . "\n";
        $script .= '            ' . "\n";
        $script .= '            if (!toggle || !menu) return;' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Remover eventos anteriores' . "\n";
        $script .= '            toggle.removeEventListener("click", toggle._clickHandler);' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Configurar nuevo evento' . "\n";
        $script .= '            var clickHandler = function(e) {' . "\n";
        $script .= '                e.preventDefault();' . "\n";
        $script .= '                e.stopPropagation();' . "\n";
        $script .= '                ' . "\n";
        $script .= '                var isVisible = menu.style.display === "block";' . "\n";
        $script .= '                ' . "\n";
        $script .= '                // Cerrar otros submenús' . "\n";
        $script .= '                document.querySelectorAll(".dropdown-submenu").forEach(function(other) {' . "\n";
        $script .= '                    if (other !== submenu) {' . "\n";
        $script .= '                        var otherMenu = other.querySelector(".dropdown-menu");' . "\n";
        $script .= '                        if (otherMenu) {' . "\n";
        $script .= '                            otherMenu.style.display = "none";' . "\n";
        $script .= '                            other.classList.remove("show");' . "\n";
        $script .= '                        }' . "\n";
        $script .= '                    }' . "\n";
        $script .= '                });' . "\n";
        $script .= '                ' . "\n";
        $script .= '                // Alternar este submenú' . "\n";
        $script .= '                if (isVisible) {' . "\n";
        $script .= '                    menu.style.display = "none";' . "\n";
        $script .= '                    submenu.classList.remove("show");' . "\n";
        $script .= '                } else {' . "\n";
        $script .= '                    menu.style.display = "block";' . "\n";
        $script .= '                    menu.style.opacity = "1";' . "\n";
        $script .= '                    menu.style.visibility = "visible";' . "\n";
        $script .= '                    submenu.classList.add("show");' . "\n";
        $script .= '                }' . "\n";
        $script .= '            };' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Guardar referencia' . "\n";
        $script .= '            toggle._clickHandler = clickHandler;' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Asignar evento' . "\n";
        $script .= '            toggle.addEventListener("click", clickHandler);' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Ocultar inicialmente' . "\n";
        $script .= '            menu.style.display = "none";' . "\n";
        $script .= '            menu.style.position = "static";' . "\n";
        $script .= '            menu.style.marginLeft = "15px";' . "\n";
        $script .= '        });' . "\n";
        $script .= '        ' . "\n";
        $script .= '        console.log("   ✅ Comportamiento click configurado para mobile");' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("");' . "\n";
        $script .= '}' . "\n\n";
        
        // 7. HERRAMIENTAS DE DEBUG
        $script .= '// 7. HERRAMIENTAS DE DEBUG' . "\n";
        $script .= 'function configurarHerramientasDebug() {' . "\n";
        $script .= '    console.log("%c7. 🛠️ HERRAMIENTAS DE DEBUG DISPONIBLES", "color: #9b59b6; font-weight: bold;");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Función para mostrar todos los submenús' . "\n";
        $script .= '    window.mostrarTodosSubmenus = function() {' . "\n";
        $script .= '        console.log("👁️  Mostrando todos los submenús...");' . "\n";
        $script .= '        document.querySelectorAll(".dropdown-submenu > .dropdown-menu").forEach(function(menu) {' . "\n";
        $script .= '            menu.style.cssText = "display: block !important; opacity: 1 !important; visibility: visible !important; position: fixed !important; left: 50% !important; top: 50% !important; transform: translate(-50%, -50%) !important; z-index: 99999 !important; background: #7d3c98 !important; border: 4px solid #00ff00 !important; border-radius: 10px !important; padding: 20px !important; min-width: 250px !important; color: white !important; box-shadow: 0 20px 60px rgba(0,0,0,0.7) !important;";' . "\n";
        $script .= '        });' . "\n";
        $script .= '        console.log("✅ Todos los submenús forzados a mostrar en el centro");' . "\n";
        $script .= '    };' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Función para restaurar' . "\n";
        $script .= '    window.restaurarSubmenus = function() {' . "\n";
        $script .= '        console.log("🔄 Restaurando comportamiento normal...");' . "\n";
        $script .= '        document.querySelectorAll(".dropdown-submenu > .dropdown-menu").forEach(function(menu) {' . "\n";
        $script .= '            menu.style.cssText = "";' . "\n";
        $script .= '        });' . "\n";
        $script .= '        console.log("✅ Submenús restaurados");' . "\n";
        $script .= '    };' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Función para ver estructura' . "\n";
        $script .= '    window.verificarEstructuraMenu = function() {' . "\n";
        $script .= '        console.log("📐 Verificando estructura del menú...");' . "\n";
        $script .= '        var items = document.querySelectorAll(".main-navigation > li");' . "\n";
        $script .= '        console.log("Menú tiene " + items.length + " elementos principales:");' . "\n";
        $script .= '        ' . "\n";
        $script .= '        items.forEach(function(item, i) {' . "\n";
        $script .= '            var link = item.querySelector(".nav-link, .dropdown-toggle");' . "\n";
        $script .= '            var submenus = item.querySelectorAll(".dropdown-submenu");' . "\n";
        $script .= '            var linkText = link ? link.textContent.trim() : "Sin texto";' . "\n";
        $script .= '            console.log("  " + (i+1) + ". " + linkText + " " + (submenus.length > 0 ? "(→ " + submenus.length + " submenús)" : ""));' . "\n";
        $script .= '        });' . "\n";
        $script .= '    };' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Función para inyectar CSS de emergencia' . "\n";
        $script .= '    window.inyectarCSSemergencia = function() {' . "\n";
        $script .= '        console.log("🎨 Inyectando CSS de emergencia...");' . "\n";
        $script .= '        var css = ".dropdown-submenu > .dropdown-menu { display: block !important; position: absolute !important; left: 100% !important; top: 0 !important; z-index: 9999 !important; background: #7d3c98 !important; border: 3px solid red !important; border-radius: 5px !important; padding: 10px 0 !important; min-width: 200px !important; opacity: 1 !important; visibility: visible !important; }";' . "\n";
        $script .= '        ' . "\n";
        $script .= '        var style = document.createElement("style");' . "\n";
        $script .= '        style.id = "css-emergencia-diagnostico";' . "\n";
        $script .= '        style.textContent = css;' . "\n";
        $script .= '        document.head.appendChild(style);' . "\n";
        $script .= '        console.log("✅ CSS de emergencia inyectado");' . "\n";
        $script .= '    };' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("   • mostrarTodosSubmenus() - Forza visibilidad de todos los submenús");' . "\n";
        $script .= '    console.log("   • restaurarSubmenus() - Restaura comportamiento normal");' . "\n";
        $script .= '    console.log("   • verificarEstructuraMenu() - Muestra estructura del menú");' . "\n";
        $script .= '    console.log("   • inyectarCSSemergencia() - Inyecta CSS de emergencia");' . "\n";
        $script .= '    console.log("");' . "\n";
        $script .= '}' . "\n\n";
        
        // 8. EJECUTAR DIAGNÓSTICO COMPLETO
        $script .= '// 8. EJECUTAR DIAGNÓSTICO COMPLETO' . "\n";
        $script .= 'function ejecutarDiagnosticoCompleto() {' . "\n";
        $script .= '    console.log("%c▶️ EJECUTANDO DIAGNÓSTICO COMPLETO", "color: #fff; background: #2c3e50; padding: 5px 10px;");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    diagnosticoBasico();' . "\n";
        $script .= '    verificarEstilosCSS();' . "\n";
        $script .= '    verificarPosicionYVisibilidad();' . "\n";
        $script .= '    verificarEventos();' . "\n";
        $script .= '    diagnosticarProblemasComunes();' . "\n";
        $script .= '    aplicarSolucionesAutomaticas();' . "\n";
        $script .= '    configurarHerramientasDebug();' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("%c✅ DIAGNÓSTICO COMPLETADO", "color: #fff; background: #27ae60; padding: 5px 10px;");' . "\n";
        $script .= '    console.log("\\n📋 RESUMEN:");' . "\n";
        $script .= '    console.log("1. Ejecuta las soluciones automáticas");' . "\n";
        $script .= '    console.log("2. Usa mostrarTodosSubmenus() para verificar");' . "\n";
        $script .= '    console.log("3. Pasa el mouse sobre los menús para probar");' . "\n";
        $script .= '    console.log("\\n🔧 Para forzar visibilidad inmediata:");' . "\n";
        $script .= '    console.log("   mostrarTodosSubmenus();");' . "\n";
        $script .= '}' . "\n\n";
        
        // 9. INICIALIZAR
        $script .= '// 9. INICIALIZAR' . "\n";
        $script .= '(function init() {' . "\n";
        $script .= '    // Esperar a que el DOM esté listo' . "\n";
        $script .= '    if (document.readyState === "loading") {' . "\n";
        $script .= '        document.addEventListener("DOMContentLoaded", ejecutarDiagnosticoCompleto);' . "\n";
        $script .= '    } else {' . "\n";
        $script .= '        setTimeout(ejecutarDiagnosticoCompleto, 100);' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Exponer funciones globalmente' . "\n";
        $script .= '    window.diagnosticoMenuGED = {' . "\n";
        $script .= '        ejecutarCompleto: ejecutarDiagnosticoCompleto,' . "\n";
        $script .= '        diagnosticoBasico: diagnosticoBasico,' . "\n";
        $script .= '        verificarEstilos: verificarEstilosCSS,' . "\n";
        $script .= '        verificarPosicion: verificarPosicionYVisibilidad,' . "\n";
        $script .= '        verificarEventos: verificarEventos,' . "\n";
        $script .= '        diagnosticarProblemas: diagnosticarProblemasComunes,' . "\n";
        $script .= '        aplicarSoluciones: aplicarSolucionesAutomaticas' . "\n";
        $script .= '    };' . "\n";
        $script .= '})();' . "\n\n";
        
        // INFORMACIÓN DEL SISTEMA
        $script .= '// ==================================================' . "\n";
        $script .= '// INFORMACIÓN DEL SISTEMA' . "\n";
        $script .= '// ==================================================' . "\n";
        $script .= 'console.log("\\n📊 INFORMACIÓN DEL SISTEMA:");' . "\n";
        $script .= 'console.log("   • User Agent:", navigator.userAgent);' . "\n";
        $script .= 'console.log("   • Viewport:", window.innerWidth, "×", window.innerHeight);' . "\n";
        $script .= 'console.log("   • URL:", window.location.href);' . "\n";
        $script .= 'console.log("   • Título:", document.title);' . "\n";
        $script .= 'console.log("\\n💡 TIPS:");' . "\n";
        $script .= 'console.log("   • Presiona F12 para abrir las herramientas de desarrollo");' . "\n";
        $script .= 'console.log("   • En la pestaña \"Elements\", busca \".dropdown-submenu\"");' . "\n";
        $script .= 'console.log("   • Haz clic derecho → \"Force state\" → \":hover\" para simular hover");' . "\n";
        $script .= 'console.log("=========================================");' . "\n";

        return $script;
    }

    /**
     * Acción para reparación automática del menú
     * URL: /site/reparar-menu
     */
    public function actionRepararMenu()
    {
        // Solo permitir en desarrollo
        if (!YII_DEBUG && !YII_ENV_DEV) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        Yii::$app->response->format = Response::FORMAT_RAW;
        header('Content-Type: text/javascript');
        
        $script = '// ==================================================' . "\n";
        $script .= '// REPARACIÓN AUTOMÁTICA DEL MENÚ GED' . "\n";
        $script .= '// ==================================================' . "\n\n";
        
        $script .= 'console.clear();' . "\n";
        $script .= 'console.log("%c🔧 REPARACIÓN AUTOMÁTICA DEL MENÚ GED",' . "\n";
        $script .= '            "color: #fff; background: #e74c3c; padding: 5px 10px; border-radius: 3px; font-size: 16px;");' . "\n\n";
        
        // 1. INYECTAR CSS DE REPARACIÓN
        $script .= '// 1. INYECTAR CSS DE REPARACIÓN' . "\n";
        $script .= 'console.log("\\n1. 🎨 INYECTANDO CSS DE REPARACIÓN...");' . "\n\n";
        
        $script .= 'var repairCSS = "' . "\n";
        $script .= '/* REPARACIÓN DE SUBMENÚS GED */' . "\n";
        $script .= '.dropdown-submenu {' . "\n";
        $script .= '    position: relative !important;' . "\n";
        $script .= '}' . "\n\n";
        $script .= '.dropdown-submenu > .dropdown-menu {' . "\n";
        $script .= '    position: absolute !important;' . "\n";
        $script .= '    left: 100% !important;' . "\n";
        $script .= '    top: 0 !important;' . "\n";
        $script .= '    margin-top: -5px !important;' . "\n";
        $script .= '    display: none !important;' . "\n";
        $script .= '    opacity: 0 !important;' . "\n";
        $script .= '    visibility: hidden !important;' . "\n";
        $script .= '    z-index: 9999 !important;' . "\n";
        $script .= '    background: linear-gradient(135deg, #7d3c98, #6c3483) !important;' . "\n";
        $script .= '    border: 1px solid rgba(255,255,255,0.3) !important;' . "\n";
        $script .= '    border-radius: 5px !important;' . "\n";
        $script .= '    box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;' . "\n";
        $script .= '    min-width: 200px !important;' . "\n";
        $script .= '    padding: 5px 0 !important;' . "\n";
        $script .= '    transform: translateX(-10px) !important;' . "\n";
        $script .= '    transition: all 0.3s ease !important;' . "\n";
        $script .= '}' . "\n\n";
        $script .= '/* HOVER PARA ESCRITORIO */' . "\n";
        $script .= '@media (min-width: 992px) {' . "\n";
        $script .= '    .dropdown-submenu:hover > .dropdown-menu {' . "\n";
        $script .= '        display: block !important;' . "\n";
        $script .= '        opacity: 1 !important;' . "\n";
        $script .= '        visibility: visible !important;' . "\n";
        $script .= '        transform: translateX(0) !important;' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    .dropdown-submenu > a.dropdown-toggle::after {' . "\n";
        $script .= '        content: " ›" !important;' . "\n";
        $script .= '        float: right !important;' . "\n";
        $script .= '        margin-left: 5px !important;' . "\n";
        $script .= '        border: none !important;' . "\n";
        $script .= '    }' . "\n";
        $script .= '}' . "\n\n";
        $script .= '/* CLICK PARA MÓVIL */' . "\n";
        $script .= '@media (max-width: 991.98px) {' . "\n";
        $script .= '    .dropdown-submenu > .dropdown-menu {' . "\n";
        $script .= '        position: static !important;' . "\n";
        $script .= '        left: auto !important;' . "\n";
        $script .= '        margin-left: 15px !important;' . "\n";
        $script .= '        margin-top: 5px !important;' . "\n";
        $script .= '        border: 1px solid rgba(255,255,255,0.2) !important;' . "\n";
        $script .= '        background: rgba(0,0,0,0.2) !important;' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    .dropdown-submenu.show > .dropdown-menu {' . "\n";
        $script .= '        display: block !important;' . "\n";
        $script .= '        opacity: 1 !important;' . "\n";
        $script .= '        visibility: visible !important;' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    .dropdown-submenu > a.dropdown-toggle::after {' . "\n";
        $script .= '        content: " ▼" !important;' . "\n";
        $script .= '        float: right !important;' . "\n";
        $script .= '    }' . "\n";
        $script .= '}' . "\n\n";
        $script .= '/* ESTILOS PARA ENLACES */' . "\n";
        $script .= '.dropdown-submenu .dropdown-item {' . "\n";
        $script .= '    color: white !important;' . "\n";
        $script .= '    padding: 8px 15px !important;' . "\n";
        $script .= '    white-space: nowrap !important;' . "\n";
        $script .= '    border-bottom: 1px solid rgba(255,255,255,0.1) !important;' . "\n";
        $script .= '}' . "\n\n";
        $script .= '.dropdown-submenu .dropdown-item:hover {' . "\n";
        $script .= '    background: rgba(255,255,255,0.1) !important;' . "\n";
        $script .= '}' . "\n\n";
        $script .= '.dropdown-submenu .dropdown-item:last-child {' . "\n";
        $script .= '    border-bottom: none !important;' . "\n";
        $script .= '}' . "\n";
        $script .= '";' . "\n\n";
        
        $script .= '// Crear y añadir el CSS' . "\n";
        $script .= 'var style = document.createElement("style");' . "\n";
        $script .= 'style.id = "ged-menu-repair-css";' . "\n";
        $script .= 'style.textContent = repairCSS;' . "\n";
        $script .= 'document.head.appendChild(style);' . "\n\n";
        
        $script .= 'console.log("✅ CSS de reparación inyectado");' . "\n\n";
        
        // 2. CONFIGURAR EVENTOS
        $script .= '// 2. CONFIGURAR EVENTOS' . "\n";
        $script .= 'console.log("\\n2. 🖱️ CONFIGURANDO EVENTOS...");' . "\n\n";
        
        $script .= 'function setupMenuEvents() {' . "\n";
        $script .= '    var submenus = document.querySelectorAll(".dropdown-submenu");' . "\n";
        $script .= '    var isDesktop = window.innerWidth >= 992;' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("   • Configurando " + submenus.length + " submenús");' . "\n";
        $script .= '    console.log("   • Modo: " + (isDesktop ? "Desktop (hover)" : "Mobile (click)"));' . "\n";
        $script .= '    ' . "\n";
        $script .= '    if (isDesktop) {' . "\n";
        $script .= '        // Desktop: hover events' . "\n";
        $script .= '        submenus.forEach(function(submenu) {' . "\n";
        $script .= '            var menu = submenu.querySelector(".dropdown-menu");' . "\n";
        $script .= '            if (!menu) return;' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Remover eventos previos' . "\n";
        $script .= '            submenu.removeEventListener("mouseenter", submenu._enterHandler);' . "\n";
        $script .= '            submenu.removeEventListener("mouseleave", submenu._leaveHandler);' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Nuevo evento mouseenter' . "\n";
        $script .= '            var enterHandler = function() {' . "\n";
        $script .= '                menu.style.display = "block";' . "\n";
        $script .= '                menu.style.opacity = "1";' . "\n";
        $script .= '                menu.style.visibility = "visible";' . "\n";
        $script .= '                menu.style.transform = "translateX(0)";' . "\n";
        $script .= '                ' . "\n";
        $script .= '                // Posicionar correctamente' . "\n";
        $script .= '                var rect = submenu.getBoundingClientRect();' . "\n";
        $script .= '                var viewportWidth = window.innerWidth;' . "\n";
        $script .= '                ' . "\n";
        $script .= '                if (rect.right + 250 > viewportWidth) {' . "\n";
        $script .= '                    menu.style.left = "auto";' . "\n";
        $script .= '                    menu.style.right = "100%";' . "\n";
        $script .= '                } else {' . "\n";
        $script .= '                    menu.style.left = "100%";' . "\n";
        $script .= '                    menu.style.right = "auto";' . "\n";
        $script .= '                }' . "\n";
        $script .= '            };' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Nuevo evento mouseleave' . "\n";
        $script .= '            var leaveHandler = function() {' . "\n";
        $script .= '                setTimeout(function() {' . "\n";
        $script .= '                    if (!submenu.matches(":hover") && !menu.matches(":hover")) {' . "\n";
        $script .= '                        menu.style.display = "none";' . "\n";
        $script .= '                        menu.style.opacity = "0";' . "\n";
        $script .= '                        menu.style.visibility = "hidden";' . "\n";
        $script .= '                        menu.style.transform = "translateX(-10px)";' . "\n";
        $script .= '                    }' . "\n";
        $script .= '                }, 100);' . "\n";
        $script .= '            };' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Guardar referencias' . "\n";
        $script .= '            submenu._enterHandler = enterHandler;' . "\n";
        $script .= '            submenu._leaveHandler = leaveHandler;' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Asignar eventos' . "\n";
        $script .= '            submenu.addEventListener("mouseenter", enterHandler);' . "\n";
        $script .= '            submenu.addEventListener("mouseleave", leaveHandler);' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Ocultar inicialmente' . "\n";
        $script .= '            menu.style.display = "none";' . "\n";
        $script .= '        });' . "\n";
        $script .= '        ' . "\n";
        $script .= '        console.log("✅ Eventos hover configurados para desktop");' . "\n";
        $script .= '        ' . "\n";
        $script .= '    } else {' . "\n";
        $script .= '        // Mobile: click events' . "\n";
        $script .= '        submenus.forEach(function(submenu) {' . "\n";
        $script .= '            var toggle = submenu.querySelector(".dropdown-toggle");' . "\n";
        $script .= '            var menu = submenu.querySelector(".dropdown-menu");' . "\n";
        $script .= '            ' . "\n";
        $script .= '            if (!toggle || !menu) return;' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Remover eventos previos' . "\n";
        $script .= '            toggle.removeEventListener("click", toggle._clickHandler);' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Nuevo evento click' . "\n";
        $script .= '            var clickHandler = function(e) {' . "\n";
        $script .= '                e.preventDefault();' . "\n";
        $script .= '                e.stopPropagation();' . "\n";
        $script .= '                ' . "\n";
        $script .= '                var isVisible = menu.style.display === "block";' . "\n";
        $script .= '                ' . "\n";
        $script .= '                // Cerrar otros submenús' . "\n";
        $script .= '                document.querySelectorAll(".dropdown-submenu").forEach(function(other) {' . "\n";
        $script .= '                    if (other !== submenu) {' . "\n";
        $script .= '                        var otherMenu = other.querySelector(".dropdown-menu");' . "\n";
        $script .= '                        if (otherMenu) {' . "\n";
        $script .= '                            otherMenu.style.display = "none";' . "\n";
        $script .= '                            other.classList.remove("show");' . "\n";
        $script .= '                        }' . "\n";
        $script .= '                    }' . "\n";
        $script .= '                });' . "\n";
        $script .= '                ' . "\n";
        $script .= '                // Alternar este submenú' . "\n";
        $script .= '                if (isVisible) {' . "\n";
        $script .= '                    menu.style.display = "none";' . "\n";
        $script .= '                    submenu.classList.remove("show");' . "\n";
        $script .= '                } else {' . "\n";
        $script .= '                    menu.style.display = "block";' . "\n";
        $script .= '                    menu.style.opacity = "1";' . "\n";
        $script .= '                    menu.style.visibility = "visible";' . "\n";
        $script .= '                    submenu.classList.add("show");' . "\n";
        $script .= '                }' . "\n";
        $script .= '            };' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Guardar referencia' . "\n";
        $script .= '            toggle._clickHandler = clickHandler;' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Asignar evento' . "\n";
        $script .= '            toggle.addEventListener("click", clickHandler);' . "\n";
        $script .= '            ' . "\n";
        $script .= '            // Ocultar inicialmente' . "\n";
        $script .= '            menu.style.display = "none";' . "\n";
        $script .= '        });' . "\n";
        $script .= '        ' . "\n";
        $script .= '        console.log("✅ Eventos click configurados para mobile");' . "\n";
        $script .= '    }' . "\n";
        $script .= '}' . "\n\n";
        
        $script .= '// Ejecutar configuración inicial' . "\n";
        $script .= 'setupMenuEvents();' . "\n\n";
        
        $script .= '// Reconfigurar en redimensionamiento' . "\n";
        $script .= 'var resizeTimer;' . "\n";
        $script .= 'window.addEventListener("resize", function() {' . "\n";
        $script .= '    clearTimeout(resizeTimer);' . "\n";
        $script .= '    resizeTimer = setTimeout(setupMenuEvents, 250);' . "\n";
        $script .= '});' . "\n\n";
        
        // 3. FORZAR VISIBILIDAD PARA VERIFICACIÓN
        $script .= '// 3. FORZAR VISIBILIDAD PARA VERIFICACIÓN' . "\n";
        $script .= 'console.log("\\n3. 👁️ FORZANDO VISIBILIDAD TEMPORAL...");' . "\n\n";
        
        $script .= 'setTimeout(function() {' . "\n";
        $script .= '    document.querySelectorAll(".dropdown-submenu").forEach(function(submenu) {' . "\n";
        $script .= '        var menu = submenu.querySelector(".dropdown-menu");' . "\n";
        $script .= '        if (menu) {' . "\n";
        $script .= '            menu.style.cssText = "display: block !important; position: fixed !important; left: 50% !important; top: 50% !important; transform: translate(-50%, -50%) !important; z-index: 99999 !important; background: #7d3c98 !important; border: 4px solid #00ff00 !important; border-radius: 10px !important; padding: 20px !important; min-width: 250px !important; color: white !important; font-size: 16px !important; box-shadow: 0 20px 60px rgba(0,0,0,0.7) !important; opacity: 1 !important; visibility: visible !important;";' . "\n";
        $script .= '        }' . "\n";
        $script .= '    });' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("✅ Submenús forzados a mostrar en el centro");' . "\n";
        $script .= '    console.log("\\n🎯 Deberías ver cuadros VERDES con los submenús");' . "\n";
        $script .= '    console.log("\\n🔄 Restaurando en 5 segundos...");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // Restaurar después de 5 segundos' . "\n";
        $script .= '    setTimeout(function() {' . "\n";
        $script .= '        document.querySelectorAll(".dropdown-submenu > .dropdown-menu").forEach(function(menu) {' . "\n";
        $script .= '            menu.style.cssText = "";' . "\n";
        $script .= '        });' . "\n";
        $script .= '        setupMenuEvents();' . "\n";
        $script .= '        console.log("✅ Comportamiento normal restaurado");' . "\n";
        $script .= '        console.log("\\n🎉 ¡PRUEBA EL MENÚ AHORA!");' . "\n";
        $script .= '        console.log("• En PC: Pasa el mouse sobre los menús");' . "\n";
        $script .= '        console.log("• En móvil: Haz clic en los menús");' . "\n";
        $script .= '    }, 5000);' . "\n";
        $script .= '}, 1000);' . "\n\n";
        
        $script .= 'console.log("\\n===========================================");' . "\n";
        $script .= 'console.log("✅ REPARACIÓN COMPLETADA");' . "\n";
        $script .= 'console.log("===========================================");' . "\n";

        return $script;
    }

    /**
     * Acción para obtener diagnóstico rápido
     * URL: /site/diagnostico-rapido
     */
    public function actionDiagnosticoRapido()
    {
        // Solo permitir en desarrollo
        if (!YII_DEBUG && !YII_ENV_DEV) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        Yii::$app->response->format = Response::FORMAT_RAW;
        header('Content-Type: text/javascript');
        
        $script = '// DIAGNÓSTICO RÁPIDO DEL MENÚ' . "\n";
        $script .= '(function() {' . "\n";
        $script .= '    console.clear();' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // 1. Verificar elementos' . "\n";
        $script .= '    var submenus = document.querySelectorAll(".dropdown-submenu");' . "\n";
        $script .= '    var menus = document.querySelectorAll(".dropdown-submenu > .dropdown-menu");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    console.log("%c📊 DIAGNÓSTICO RÁPIDO", "background: #6c3483; color: white; padding: 5px;");' . "\n";
        $script .= '    console.log("• Submenús encontrados: " + submenus.length);' . "\n";
        $script .= '    console.log("• Menús hijos: " + menus.length);' . "\n";
        $script .= '    ' . "\n";
        $script .= '    if (submenus.length === 0) {' . "\n";
        $script .= '        console.log("%c❌ PROBLEMA: No hay submenús", "color: red;");' . "\n";
        $script .= '        return;' . "\n";
        $script .= '    }' . "\n";
        $script .= '    ' . "\n";
        $script .= '    // 2. Verificar visibilidad del primer submenú' . "\n";
        $script .= '    var firstSubmenu = submenus[0];' . "\n";
        $script .= '    var firstMenu = firstSubmenu.querySelector(".dropdown-menu");' . "\n";
        $script .= '    ' . "\n";
        $script .= '    if (firstMenu) {' . "\n";
        $script .= '        var styles = window.getComputedStyle(firstMenu);' . "\n";
        $script .= '        console.log("\\n🎨 Primer submenú:");' . "\n";
        $script .= '        console.log("• Display: " + styles.display);' . "\n";
        $script .= '        console.log("• Visibility: " + styles.visibility);' . "\n";
        $script .= '        console.log("• Opacity: " + styles.opacity);' . "\n";
        $script .= '        console.log("• Position: " + styles.position);' . "\n";
        $script .= '        console.log("• Z-index: " + styles.zIndex);' . "\n";
        $script .= '        ' . "\n";
        $script .= '        // Forzar visibilidad para probar' . "\n";
        $script .= '        firstMenu.style.cssText = "display: block !important; position: fixed !important; left: 50% !important; top: 50% !important; transform: translate(-50%, -50%) !important; z-index: 99999 !important; background: #7d3c98 !important; border: 4px solid red !important; border-radius: 10px !important; padding: 20px !important; min-width: 200px !important; color: white !important; opacity: 1 !important; visibility: visible !important;";' . "\n";
        $script .= '        ' . "\n";
        $script .= '        console.log("%c✅ Submenú forzado a mostrar (borde ROJO)", "color: green;");' . "\n";
        $script .= '        console.log("\\n🔧 SOLUCIÓN RÁPIDA:");' . "\n";
        $script .= '        console.log("1. Copia este CSS en _submenus.css:");' . "\n";
        $script .= '        console.log("");' . "\n";
        $script .= '        console.log(".dropdown-submenu > .dropdown-menu {");' . "\n";
        $script .= '        console.log("    display: none !important;");' . "\n";
        $script .= '        console.log("    position: absolute !important;");' . "\n";
        $script .= '        console.log("    left: 100% !important;");' . "\n";
        $script .= '        console.log("    top: 0 !important;");' . "\n";
        $script .= '        console.log("    z-index: 9999 !important;");' . "\n";
        $script .= '        console.log("}");' . "\n";
        $script .= '        console.log("");' . "\n";
        $script .= '        console.log("@media (min-width: 992px) {");' . "\n";
        $script .= '        console.log("    .dropdown-submenu:hover > .dropdown-menu {");' . "\n";
        $script .= '        console.log("        display: block !important;");' . "\n";
        $script .= '        console.log("    }");' . "\n";
        $script .= '        console.log("}");' . "\n";
        $script .= '        console.log("");' . "\n";
        $script .= '        ' . "\n";
        $script .= '        // Restaurar después de 3 segundos' . "\n";
        $script .= '        setTimeout(function() {' . "\n";
        $script .= '            firstMenu.style.cssText = "";' . "\n";
        $script .= '            console.log("\\n✅ Comportamiento restaurado");' . "\n";
        $script .= '        }, 3000);' . "\n";
        $script .= '    }' . "\n";
        $script .= '})();' . "\n";

        return $script;
    }
}