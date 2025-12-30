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
                        'actions' => ['logout', 'mi-cuenta', 'testcss'],
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
     * Landing page pública - CON PREVENCIÓN DE BUCLE COMPLETA
     *
     * @return string
     */
    public function actionIndex()
    {
        // ✅ PREVENCIÓN DE BUCLE: Verificar si ya estamos autenticados en página de login
        $currentRoute = Yii::$app->controller->route;
        
        // ✅ SI ESTÁ AUTENTICADO Y TRATA DE ACCEDER A LOGIN, REDIRIGIR AL INDEX
        if (!Yii::$app->user->isGuest && $currentRoute === 'site/login') {
            return $this->redirect(['site/index']);
        }
        
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
        
        // ✅ VERIFICACIÓN EXTRA: Si ya estamos en una página del sistema GED, no redirigir
        $currentRoute = Yii::$app->controller->route;
        if (strpos($currentRoute, 'ged/') === 0) {
            // Ya estamos en el sistema GED, no redirigir
            return $this->redirect(['/ged/default/index']);
        }
        
        // ✅ REDIRECCIÓN SEGURA: Usar nombre de ruta en lugar de URL completa
        // Esto no revela la estructura interna al usuario
        
        // Registrar el acceso en logs para auditoría
        Yii::info("Usuario " . Yii::$app->user->identity->username . 
                  " accede al sistema desde IP: " . Yii::$app->request->userIP, 'security');
        
        // Redirigir al punto de entrada del módulo GED
        return $this->redirect(['/ged/default/index']);
    }

    /**
     * ✅ Login action - CON PREVENCIÓN DE BUCLE MEJORADA
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
            // Verificar si debe cambiar contraseña temporal
            $user = Yii::$app->user->identity;
            if ($user && method_exists($user, 'debeCambiarPassword') && $user->debeCambiarPassword()) {
                Yii::$app->session->setFlash('warning', 
                    'Debe cambiar su contraseña temporal antes de continuar.');
                return $this->redirect(['/site/cambiar-password']);
            }
            
            Yii::$app->session->setFlash('success', 'Sesión iniciada correctamente.');
            
            // ✅ PREVENIR REDIRECCIÓN A LOGIN DESPUÉS DE LOGIN
            // Si la URL anterior es login o index, redirigir a acceder-sistema
            $returnUrl = Yii::$app->request->referrer;
            if (!$returnUrl || strpos($returnUrl, 'login') !== false || strpos($returnUrl, 'index') !== false) {
                return $this->redirect(['/site/acceder-sistema']);
            }
            
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * ✅ Cierra sesión y también limpia la escuela - SIN BUCLE
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
        
        // ✅ SIEMPRE REDIRIGIR AL INDEX, NUNCA AL LOGIN
        return $this->redirect(['site/index']);
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
                Yii::info("Usuario {$user->username} cambió su contraseña temporal", 'security');
                
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
     * ✅ MÉTODO ADICIONAL: goHome personalizado para prevenir bucle
     * Sobrescribe el método goHome() para asegurar que siempre redirija al index
     */
    public function actionTestcss()
    {
        // ✅ SIEMPRE REDIRIGIR AL INDEX, NUNCA AL LOGIN
        // Para debug: var_dump("entre a test"); die();
        return $this->render('test-css');
    }

    /**
     * Acción para probar los módulos JavaScript
     * @return string
    */
    public function actionTestJs()
    {
        return $this->render('test-js');
    }
    
    /**
     * ✅ MÉTODO ADICIONAL: Verificar si hay bucle de redirección
     * Se puede llamar desde JavaScript para debug
     */
     public function actionCheckRedirectLoop()
    {
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
        
        // Verificar base de datos
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
        
        // 1. VERIFICAR CONEXIÓN A BD
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
            echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
        // 2. VERIFICAR TABLA DE MENÚS
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
                
                // Contar registros
                $count = \Yii::$app->db->createCommand("SELECT COUNT(*) FROM seguridad.menu")->queryScalar();
                echo "<p>Total registros: " . $count . "</p>";
                
                // Mostrar estructura
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
                    echo "<td>" . $col['column_name'] . "</td>";
                    echo "<td>" . $col['data_type'] . "</td>";
                    echo "<td>" . $col['is_nullable'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } else {
                echo "<p class='error'>❌ Tabla 'seguridad.menu' NO existe</p>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
        // 3. MOSTRAR CONTENIDO DE LA TABLA
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
                    echo "<td>" . $menu['id'] . "</td>";
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
            echo "<p class='error'>❌ Error al leer datos: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
        // 4. VERIFICAR USUARIO
        echo "<div class='section'>";
        echo "<h2>4. Usuario Actual</h2>";
        
        if (\Yii::$app->user->isGuest) {
            echo "<p class='warning'>👤 Usuario: <strong>INVITADO</strong></p>";
        } else {
            $user = \Yii::$app->user->identity;
            echo "<p class='success'>👤 Usuario: <strong>" . $user->username . "</strong></p>";
            echo "<p>ID: " . $user->id . "</p>";
            
            // Verificar permisos
            echo "<h4>Permisos RBAC:</h4>";
            $auth = \Yii::$app->authManager;
            $permissions = $auth->getPermissionsByUser($user->id);
            
            if (empty($permissions)) {
                echo "<p class='warning'>⚠️ El usuario no tiene permisos asignados</p>";
            } else {
                echo "<ul>";
                foreach ($permissions as $permission) {
                    echo "<li>" . $permission->name . "</li>";
                }
                echo "</ul>";
            }
        }
        echo "</div>";
        
        // 5. PROBAR MenuWidget
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
            echo "<p class='error'>❌ Error en MenuWidget: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        echo "</div>";
        
        // Probar función getMenuItems directamente
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
                    echo "Item {$index}: " . $item['label'] . " (" . ($item['route'] ?? '#') . ")\n";
                    if (!empty($item['items'])) {
                        foreach ($item['items'] as $childIndex => $child) {
                            echo "  ├─ Child {$childIndex}: " . $child['label'] . " (" . ($child['route'] ?? '#') . ")\n";
                        }
                    }
                }
                echo "</pre>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        echo "</div>";
        
        // 6. VERIFICAR RUTAS PÚBLICAS (CORREGIDO - sin llamar a isPublicRoute)
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
            // ✅ CORRECCIÓN: Verificar rutas públicas usando params en lugar de MenuWidget
            $publicRoutes = \Yii::$app->params['publicRoutes'] ?? [];
            $isPublic = in_array($route, $publicRoutes);
            
            echo "<tr>";
            echo "<td>" . $route . "</td>";
            echo "<td>" . $desc . "</td>";
            echo "<td class='" . ($isPublic ? "success" : "error") . "'>" . ($isPublic ? "✅ SÍ" : "❌ NO") . "</td>";
            echo "<td>";
            echo "<button class='test-btn' onclick=\"window.open('/" . $route . "', '_blank')\">Probar ruta</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Mostrar rutas públicas configuradas
        echo "<div class='debug-box'>";
        echo "<h4>Rutas públicas configuradas en params:</h4>";
        $publicRoutes = \Yii::$app->params['publicRoutes'] ?? [];
        echo "<pre>" . htmlspecialchars(print_r($publicRoutes, true)) . "</pre>";
        
        // Verificar allowActions también
        echo "<h4>Rutas en allowActions:</h4>";
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
        echo "<pre>" . htmlspecialchars(print_r($allowActions, true)) . "</pre>";
        echo "</div>";
        echo "</div>";
        
        // 7. BOTONES DE ACCIÓN
        echo "<div class='section'>";
        echo "<h2>7. Acciones</h2>";
        
        echo "<button class='test-btn' onclick='window.location.reload()'>🔄 Recargar</button>";
        echo "<button class='test-btn' onclick='window.open(\"/\", \"_blank\")'>🏠 Ir al inicio</button>";
        echo "<button class='test-btn' onclick='clearCache()'>🧹 Limpiar caché</button>";
        echo "<button class='test-btn' onclick='testMenuWidget()'>🧪 Probar MenuWidget</button>";
        
        echo "<script>
            function clearCache() {
                fetch('/site/clear-cache')
                    .then(response => response.text())
                    .then(data => {
                        alert('Cache limpiado');
                        window.location.reload();
                    })
                    .catch(error => console.error('Error:', error));
            }
            
            function testMenuWidget() {
                fetch('/site/test-menu-widget')
                    .then(response => response.text())
                    .then(data => {
                        alert('Test completado. Ver consola para detalles.');
                        console.log('Test MenuWidget:', data);
                    })
                    .catch(error => console.error('Error:', error));
            }
        </script>";
        echo "</div>";
        
        echo "</body></html>";
        exit;
    }

    public function actionTestMenuWidget()
    {
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
        if (Yii::$app->cache) {
            Yii::$app->cache->flush();
        }
        
        // Limpiar caché de menú específico
        \app\components\MenuWidget::forceReload();
        
        echo "Cache limpiado";
        exit;
    }
    
    /**
     * Método auxiliar para verificar si una ruta es pública
     * (reemplaza al método eliminado de MenuWidget)
     */
    private function isRoutePublic($route)
    {
        $route = ltrim($route, '/');
        
        // Rutas públicas por defecto (deberían venir de params)
        $publicRoutes = Yii::$app->params['publicRoutes'] ?? [
            'site/index',
            'site/login',
            'site/logout',
            'site/about',
            'site/contact',
            'site/signup',
            'site/request-password-reset',
            'site/reset-password',
            'site/cambiar-password',
            'site/mi-cuenta',
            'site/acceder-sistema',
            
            'ged/default/index',
            
            'tienda/marketplace',
            'tienda/marketplace/index',
            'tienda/marketplace/buscar',
            'tienda/marketplace/categoria',
            
            'admin/user/signup',
            'admin/user/request-password-reset',
            'admin/user/reset-password',
            
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
            'parroquia/get-by-muni-cod',
        ];
        
        return in_array($route, $publicRoutes);
    }
}