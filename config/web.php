<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'GED',
    'name' => 'Escuela Polideportiva y Cultural San Agustín',
    'language' => 'es',
    'timeZone' => 'America/Caracas',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'layout' => 'main',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

   'components' => [

        'request' => [
            'cookieValidationKey' => 'mjbvsistemas-ged-voleibol-06012025',
        ],
        'mobileDetect' => [
            'class' => 'app\components\MobileDetectComponent',
        ],
        'escuelaSession' => [
            'class' => 'app\components\EscuelaSession',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false, // ✅ IMPORTANTE: Deshabilitado para seguridad
            'loginUrl' => ['site/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true, // ✅ PARA DESARROLLO: true (guarda emails en archivos)
            // 'useFileTransport' => false, // ✅ PARA PRODUCCIÓN: false (habilitar SMTP)
            
            // ✅ CONFIGURACIÓN SMTP PARA PRODUCCIÓN (DESCOMENTAR Y CONFIGURAR)
            /*
            'transport' => [
                'scheme' => 'smtps',
                'host' => 'smtp.gmail.com',
                'username' => 'tu-email@gmail.com',
                'password' => 'tu-password-app',
                'port' => 465,
                'dsn' => 'smtps://tu-email@gmail.com:tu-password-app@smtp.gmail.com:465',
            ],
            */
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'itemTable' => 'seguridad.auth_item',
            'itemChildTable' => 'seguridad.auth_item_child',
            'assignmentTable' => 'seguridad.auth_assignment',
            'ruleTable' => 'seguridad.auth_rule',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        
        // ✅ CONFIGURACIÓN DE I18N PARA TRADUCCIONES
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'es',
                    'fileMap' => [
                        'app' => 'app.php',
                        'app/auth' => 'auth.php',
                        'app/error' => 'error.php',
                    ],
                ],
            ],
        ],
        
        // ✅ CONFIGURACIÓN DE AssetManager PARA Bootstrap 5
        'assetManager' => [
            'bundles' => [
                'yii\bootstrap5\BootstrapAsset' => [
                    'css' => [
                        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
                    ],
                    'js' => [
                        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
                    ],
                    'depends' => ['yii\web\YiiAsset']
                ],
                'yii\web\JqueryAsset' => [
                    'jsOptions' => [
                        'position' => \yii\web\View::POS_HEAD
                    ],
                ],
            ],
            'appendTimestamp' => true,
        ],
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // ✅ NUEVAS RUTAS DEL FLUJO DE AUTENTICACIÓN SEGURO
                'verify-email-first' => 'site/verify-email-first',
                'validate-code/<token:[\w\-]+>' => 'site/validate-code',
                'change-password-first' => 'site/change-password-first',
                'resend-code/<token:[\w\-]+>' => 'site/resend-code',
                
                // Rutas existentes
                'site/debug-menu' => 'site/debug-menu',
                'site/test-menu-widget' => 'site/test-menu-widget',
                'site/clear-cache' => 'site/clear-cache',
                'debug/menu' => 'debug/menu',
                'site/get-mobile-menu' => 'site/get-mobile-menu',
                'tasa-dolar' => 'tasa-dolar/index',
                'tasa-dolar/actualizar' => 'tasa-dolar/actualizar',
                // NUEVAS RUTAS PARA PERFIL
                'mi-perfil' => 'perfil/mi-informacion',
                'mis-deudas' => 'perfil/mis-deudas',
                'mis-representados' => 'perfil/mis-representados',
                'mi-perfil/<id:\d+>' => 'perfil/mi-informacion',
                'mis-deudas/<id:\d+>' => 'perfil/mis-deudas',
                // Ruta principal
                '' => 'site/index',
                
                // Login correcto
                'login' => 'site/login',
                'logout' => 'site/logout',
                
                // Rutas para atletas
                'atleta/dashboard' => 'reportes-atletas/dashboard',
                'atleta/asistencia' => 'reportes-atletas/asistencia',
                'atleta/pagos' => 'reportes-atletas/pagos',
                
                // Rutas para representantes  
                'representante/dashboard' => 'reportes-representantes/dashboard',
                'representante/mis-atletas' => 'reportes-representantes/atletas-representados',
                'representante/estado-pagos' => 'reportes-representantes/estado-pagos',
                
                // Reglas por defecto
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                        // Rutas existentes para dropdowns
                'municipio/get-by-edo/<edo:\d+>' => 'municipio/get-by-edo',
                'parroquia/get-by-muni/<muni:\d+>' => 'parroquia/get-by-muni',
                'parroquia/get-by-muni-cod/<muni:\w+>' => 'parroquia/get-by-muni-cod',
                
                // Nuevas rutas para el sistema de escuelas
                'escuela/pre-registro' => 'escuela-club/escuela-pre-registro/pre-registro',
                'escuela/completar-registro/<id:\d+>' => 'escuela-club/escuela-pre-registro/completar-registro',
                'escuela/pendientes' => 'escuela-club/escuela-pre-registro/pendientes',
                'escuela/aprobar/<id:\d+>' => 'escuela-club/escuela-pre-registro/aprobar',
                'escuela/rechazar/<id:\d+>' => 'escuela-club/escuela-pre-registro/rechazar',
                'escuela/obtener-coordenadas' => 'escuela-club/escuela-pre-registro/obtener-coordenadas',
                
                // Rutas del CRUD normal
                'escuela-club/escuela-registro/<action:\w+>' => 'escuela-club/escuela-registro/<action>',
                'escuela-club/escuela-registro/<action:\w+>/<id:\d+>' => 'escuela-club/escuela-registro/<action>',
                // ✅ NUEVAS RUTAS PARA SELECCIÓN DE ESCUELA
                'select-escuela/<id:\d+>' => 'escuela-club/escuela-registro/select-escuela',
                'clear-escuela' => 'escuela-club/escuela-registro/clear-escuela',
                // ✅ NUEVAS RUTAS PARA VALIDACIÓN DE ESCUELA
                'escuela-validacion/pendientes' => 'escuela-club/escuela-validacion/pendientes',
                'escuela-validacion/aprobar/<id:\d+>' => 'escuela-club/escuela-validacion/aprobar',
                'escuela-validacion/rechazar/<id:\d+>' => 'escuela-club/escuela-validacion/rechazar',
                // Reglas para el módulo tienda
                'tienda' => 'tienda/default/index',
                'tienda/marketplace' => 'tienda/marketplace/index',
                'tienda/registro-vendedor' => 'tienda/default/registro-vendedor',
                'tienda/dashboard-vendedor' => 'tienda/default/dashboard-vendedor',
                'tienda/buscar' => 'tienda/marketplace/buscar',
                'tienda/categoria/<id:\d+>' => 'tienda/marketplace/categoria',
                'tienda/producto/<id:\d+>' => 'tienda/marketplace/producto',
                // Reportes
                'reportes' => 'reportes/default/index',
                'reportes/atletas' => 'reportes/reportes/atletas',
                'reportes/estadisticas-atleta' => 'reportes/reportes/estadisticas-atleta',
                'reportes/deudas-pendientes' => 'reportes/reportes/deudas-pendientes',
                'reportes/asistencias' => 'reportes/reportes/asistencias',
                'reportes/exportar-pdf/<reporte>' => 'reportes/reportes/exportar-pdf',
                'reportes/exportar-excel/<reporte>' => 'reportes/reportes/exportar-excel',
            ],
        ],
    ],

    'modules' => [
        //rbac security
        'admin' => [
            'class' => 'mdm\admin\Module',
            'mainLayout' => '@app/views/layouts/mainAdminlte.php',
        ],
        //modulo de acceso al sistema
        'acces' => [
            'class' => 'app\modules\acces\acces',
            'controllerNamespace' => 'app\modules\acces\controllers',
        ],
        //modulo de acceso al sistema
        'tienda' => [
            'class' => 'app\modules\tienda\tienda',
            'controllerNamespace' => 'app\modules\tienda\controllers',
        ],
        'atletas' => [
            'class' => 'app\modules\atletas\atletas',
            'controllerNamespace' => 'app\modules\atletas\controllers',
        ],
        //'epcSanAgustin' => [
        //    'class' => 'app\modules\escuela_club\epcSanAgustin\epcSanAgustin',
        //    'controllerNamespace' => 'app\modules\acces\controllers',
        //],
        'escuela_club' => [
            'class' => 'app\modules\escuela_club\escuela_club',
            'controllerNamespace' => 'app\modules\escuela_club\controllers',
        ],
        'ged' => [
            'class' => 'app\modules\ged\ged',
            'controllerNamespace' => 'app\modules\ged\controllers',
        ],
        'aportes' => [
            'class' => 'app\modules\aportes\aportes',
            'controllerNamespace' => 'app\modules\aportes\controllers',
        ],
        'reportes' => [
            'class' => 'app\modules\reportes\reportes',
            'controllerNamespace' => 'app\modules\reportes\controllers',
        ],
        
    ],

    'on beforeRequest' => function () {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
    },
    
    'params' => array_merge($params, [
        'tienda' => [
            'maxProductosPorTienda' => 100,
            'comisionVenta' => 3, // 3% de comisión
            'monedaPredeterminada' => 'USD',
        ],
        // ✅ PARÁMETROS PARA AUTENTICACIÓN SEGURA
        'auth' => [
            'verificationCodeExpiry' => 900, // 15 minutos en segundos
            'maxVerificationAttempts' => 3,
            'passwordHistoryLimit' => 5, // Número de contraseñas anteriores a verificar
            'sessionTimeout' => 1800, // 30 minutos de inactividad
        ],
    ]),
    
    'as access' => [
        'class' => 'mdm\admin\components\AccessControl',
        'allowActions' => [
            // ✅ PERMITIR ACCESO A LAS RUTAS DE AUTENTICACIÓN SIN LOGIN
            'site/login',
            'site/logout',
            'site/verify-email-first',
            'site/validate-code',
            'site/change-password-first',
            'site/resend-code',
            'site/error',
            
            // Otras rutas públicas
            'tienda/marketplace/*',
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
            'parroquia/get-by-muni-cod',
            
            /*'ged/*',
            'site/*',
            'tienda/*',
            'admin/user/signup',
            'admin/user/request-password-reset',
            'admin/user/reset-password',*/
            'admin/*'
        ]
    ],

];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => [
            '201.209.14.141', 
            '127.0.0.1', 
            '::1', 
            '192.168.1.120',
            'localhost',
            'planealo.sytes.net',
            '*.sytes.net',
            '192.168.1.*',
            '10.0.*.*',
        ],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => [
            '201.209.14.141', 
            '127.0.0.1', 
            '::1', 
            '192.168.1.120',
            'localhost',
            'planealo.sytes.net',
            '*.sytes.net',
            '192.168.1.*',
            '10.0.*.*',
        ],
    ];
}

return $config;