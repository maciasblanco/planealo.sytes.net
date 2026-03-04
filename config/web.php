<?php

return [
    'id' => 'GED',
    'name' => 'Escuela Polideportiva y Cultural San Agustín',
    'language' => 'es',
    'timeZone' => 'America/Caracas',
    'basePath' => 'C:\\xampp\\htdocs\\planealo_desarrollo',
    'bootstrap' => [
        'log',
        // debug y gii se cargan condicionalmente en el bloque YII_ENV_DEV
    ],
    'layout' => 'main',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'log' => [
            'traceLevel' => 3,
            'targets' => [
                [
                    'class' => 'yii\\log\\FileTarget',
                    'levels' => [
                        'error',
                        'warning',
                    ],
                ],
            ],
        ],
        'request' => [
            'cookieValidationKey' => 'mjbvsistemas-ged-voleibol-06012025',
        ],
        'mobileDetect' => [
            'class' => 'app\\components\\MobileDetectComponent',
        ],
        'escuelaSession' => [
            'class' => 'app\\components\\EscuelaSession',
        ],
        'cache' => [
            'class' => 'yii\\caching\\FileCache',
        ],
        'user' => [
            'class' => 'app\\components\\User',
            'identityClass' => 'app\\models\\User',
            'enableAutoLogin' => false,
            'loginUrl' => ['site/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@app/mail',
            'useFileTransport' => false,
            'transport' => [
                'scheme' => 'smtp',
                'host' => 'smtp.gmail.com',
                'username' => 'maciasjblancov@gmail.com',
                'password' => 'efum glzt mtui oaki',
                'port' => 465,
                'encryption' => 'ssl',
            ],
        ],
        'authManager' => [
            'class' => 'yii\\rbac\\DbManager',
            'itemTable' => 'seguridad.auth_item',
            'itemChildTable' => 'seguridad.auth_item_child',
            'assignmentTable' => 'seguridad.auth_assignment',
            'ruleTable' => 'seguridad.auth_rule',
            'defaultRoles' => ['invitado'], // ← Asigna rol 'invitado' a usuarios no autenticados
        ],
        'db' => [
            'class' => 'yii\\db\\Connection',
            'dsn' => 'pgsql:host=localhost;dbname=escuelas_deportivas',
            'username' => 'postgres',
            'password' => '*m4c145',
            'charset' => 'utf8',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
            'schemaMap' => [
                'pgsql' => [
                    'class' => 'yii\\db\\pgsql\\Schema',
                    'defaultSchema' => 'seguridad',
                ],
            ],
            'attributes' => [
                20 => false,
                17 => false,
                3 => 2,
                19 => 2,
            ],
            'enableQueryCache' => true,
            'queryCacheDuration' => 300,
            'queryCache' => 'cache',
            'enableLogging' => true,
            'enableProfiling' => true,
            'commandClass' => 'yii\\db\\Command',
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\\i18n\\PhpMessageSource',
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
        'assetManager' => [
            'bundles' => [
                'yii\\bootstrap5\\BootstrapAsset' => [
                    'css' => [
                        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
                    ],
                    'js' => [
                        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
                    ],
                    'depends' => [
                        'yii\\web\\YiiAsset',
                    ],
                ],
                'yii\\web\\JqueryAsset' => [
                    'jsOptions' => [
                        'position' => 1,
                    ],
                ],
            ],
            'appendTimestamp' => true,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'verify-email-first' => 'site/verify-email-first',
                'validate-code/<token:[\\w\\-]+>' => 'site/validate-code',
                'change-password-first' => 'site/change-password-first',
                'resend-code/<token:[\\w\\-]+>' => 'site/resend-code',
                'site/debug-menu' => 'site/debug-menu',
                'site/test-menu-widget' => 'site/test-menu-widget',
                'site/clear-cache' => 'site/clear-cache',
                'debug/menu' => 'debug/menu',
                'site/get-mobile-menu' => 'site/get-mobile-menu',
                'tasa-dolar' => 'tasa-dolar/index',
                'tasa-dolar/actualizar' => 'tasa-dolar/actualizar',
                'mi-perfil' => 'perfil/mi-informacion',
                'mis-deudas' => 'perfil/mis-deudas',
                'mis-representados' => 'perfil/mis-representados',
                'mi-perfil/<id:\\d+>' => 'perfil/mi-informacion',
                'mis-deudas/<id:\\d+>' => 'perfil/mis-deudas',
                '' => 'site/index',
                'login' => 'site/login',
                'logout' => 'site/logout',
                'atleta/dashboard' => 'reportes-atletas/dashboard',
                'atleta/asistencia' => 'reportes-atletas/asistencia',
                'atleta/pagos' => 'reportes-atletas/pagos',
                'representante/dashboard' => 'reportes-representantes/dashboard',
                'representante/mis-atletas' => 'reportes-representantes/atletas-representados',
                'representante/estado-pagos' => 'reportes-representantes/estado-pagos',
                '<controller:\\w+>/<action:\\w+>/<id:\\d+>' => '<controller>/<action>',
                '<controller:\\w+>/<action:\\w+>' => '<controller>/<action>',
                'municipio/get-by-edo/<edo:\\d+>' => 'municipio/get-by-edo',
                'parroquia/get-by-muni/<muni:\\d+>' => 'parroquia/get-by-muni',
                'parroquia/get-by-muni-cod/<muni:\\w+>' => 'parroquia/get-by-muni-cod',
                'escuela/pre-registro' => 'escuela-club/escuela-pre-registro/pre-registro',
                'escuela/completar-registro/<id:\\d+>' => 'escuela-club/escuela-pre-registro/completar-registro',
                'escuela/pendientes' => 'escuela-club/escuela-pre-registro/pendientes',
                'escuela/aprobar/<id:\\d+>' => 'escuela-club/escuela-pre-registro/aprobar',
                'escuela/rechazar/<id:\\d+>' => 'escuela-club/escuela-pre-registro/rechazar',
                'escuela/obtener-coordenadas' => 'escuela-club/escuela-pre-registro/obtener-coordenadas',
                'escuela-club/escuela-registro/<action:\\w+>' => 'escuela-club/escuela-registro/<action>',
                'escuela-club/escuela-registro/<action:\\w+>/<id:\\d+>' => 'escuela-club/escuela-registro/<action>',
                'select-escuela/<id:\\d+>' => 'escuela-club/escuela-registro/select-escuela',
                'clear-escuela' => 'escuela-club/escuela-registro/clear-escuela',
                'escuela-validacion/pendientes' => 'escuela-club/escuela-validacion/pendientes',
                'escuela-validacion/aprobar/<id:\\d+>' => 'escuela-club/escuela-validacion/aprobar',
                'escuela-validacion/rechazar/<id:\\d+>' => 'escuela-club/escuela-validacion/rechazar',
                'tienda' => 'tienda/default/index',
                'tienda/marketplace' => 'tienda/marketplace/index',
                'tienda/registro-vendedor' => 'tienda/default/registro-vendedor',
                'tienda/dashboard-vendedor' => 'tienda/default/dashboard-vendedor',
                'tienda/buscar' => 'tienda/marketplace/buscar',
                'tienda/categoria/<id:\\d+>' => 'tienda/marketplace/categoria',
                'tienda/producto/<id:\\d+>' => 'tienda/marketplace/producto',
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
        'admin' => [
            'class' => 'mdm\\admin\\Module',
            'viewPath' => '@app/views/admin',
            'mainLayout' => '@app/views/layouts/main.php',
            'controllerMap' => [
                'route' => 'app\\controllers\\RouteController',
            ],
        ],
        'acces' => [
            'class' => 'app\\modules\\acces\\acces',
            'controllerNamespace' => 'app\\modules\\acces\\controllers',
        ],
        'tienda' => [
            'class' => 'app\\modules\\tienda\\tienda',
            'controllerNamespace' => 'app\\modules\\tienda\\controllers',
        ],
        'atletas' => [
            'class' => 'app\\modules\\atletas\\atletas',
            'controllerNamespace' => 'app\\modules\\atletas\\controllers',
        ],
        'escuela_club' => [
            'class' => 'app\\modules\\escuela_club\\escuela_club',
            'controllerNamespace' => 'app\\modules\\escuela_club\\controllers',
        ],
        'ged' => [
            'class' => 'app\\modules\\ged\\ged',
            'controllerNamespace' => 'app\\modules\\ged\\controllers',
        ],
        'aportes' => [
            'class' => 'app\\modules\\aportes\\aportes',
            'controllerNamespace' => 'app\\modules\\aportes\\controllers',
        ],
        'reportes' => [
            'class' => 'app\\modules\\reportes\\reportes',
            'controllerNamespace' => 'app\\modules\\reportes\\controllers',
        ],
    ],

    'container' => [
        'definitions' => [
            'mdm\admin\models\Menu' => 'app\models\Menu',
        ],
    ],

    'params' => [
        'mdm.admin.configs' => [
            'menuTable' => 'seguridad.menu',
            'userTable' => 'seguridad.user',
        ],
        'adminEmail' => 'admin@example.com',
        'senderEmail' => 'noreply@example.com',
        'senderName' => 'Example.com mailer',
        'brand' => [
            'brandLogo' => 'Club Voleivol Aves Voladoras',
            'acronimoBrandLogo' => 'C.V Aves Voladoras',
        ],
        'tienda' => [
            'maxProductosPorTienda' => 100,
            'comisionVenta' => 3,
            'monedaPredeterminada' => 'USD',
        ],
        'auth' => [
            'verificationCodeExpiry' => 900,
            'maxVerificationAttempts' => 3,
            'passwordHistoryLimit' => 5,
            'sessionTimeout' => 1800,
        ],
    ],

    // ========== FILTRO DE ACCESO CORREGIDO ==========
    'as access' => [
        'class' => 'yii\filters\AccessControl',
        'rules' => [
            // 1. Superusuario (ID 1) siempre permitido
            [
                'allow' => true,
                'matchCallback' => function ($rule, $action) {
                    return !Yii::$app->user->isGuest && Yii::$app->user->id == 1;
                },
            ],
            // 2. Acciones públicas explícitas
            [
                'allow' => true,
                'matchCallback' => function ($rule, $action) {
                    $route = $action->getUniqueId(); // ✅ Ruta completa con módulos
                    $allowActions = [
                        'site/index', 'site/login', 'site/logout', 'site/error', 'site/about',
                        'site/contact', 'site/captcha', 'site/verify-email-first',
                        'site/validate-code', 'site/change-password-first', 'site/resend-code',
                        'tienda/marketplace/index', 'tienda/marketplace/buscar',
                        'tienda/marketplace/categoria', 'tienda/marketplace/producto',
                        'municipio/get-by-edo', 'parroquia/get-by-muni', 'parroquia/get-by-muni-cod',
                        'tasa-dolar/index', 'admin/user/signup', 'admin/user/request-password-reset',
                        'admin/user/reset-password', 'site/debug-menu', 'site/test-menu-widget',
                        'site/clear-cache', 'site/get-mobile-menu', 'debug/menu',
                        'admin/default/login', 'admin/default/error',
                    ];
                    return in_array($route, $allowActions);
                },
            ],
            // 3. Acceso basado en permisos RBAC para CUALQUIER usuario (invitado o autenticado)
            [
                'allow' => true,
                'matchCallback' => function ($rule, $action) {
                    $route = $action->getUniqueId(); // ✅ Ruta completa
                    return Yii::$app->user->can($route);
                },
            ],
        ],
    ],
    // =================================================
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['bootstrap'][] = 'gii';

    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['*'],
    ];

    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['*'],
    ];
}