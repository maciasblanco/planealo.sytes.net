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
    //'defaultRoute' =>'site/login',  // Default controller when no specific one is set in the URL
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

   'components' => [

        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
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
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
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
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'tasa-dolar' => 'tasa-dolar/index',
                'tasa-dolar/actualizar' => 'tasa-dolar/actualizar',
                // NUEVAS RUTAS PARA PERFIL
                ' mi-perfil' => 'perfil/mi-informacion',
                'mis-deudas' => 'perfil/mis-deudas',
                'mis-representados' => 'perfil/mis-representados',
                'mi-perfil/<id:\d+>' => 'perfil/mi-informacion',
                'mis-deudas/<id:\d+>' => 'perfil/mis-deudas',
                // Ruta principal
                '' => 'site/index',
                
                // Login correcto
                'login' => 'site/login',
                
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
            ],
        ],
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'jsOptions' => [
                        'position' => \yii\web\View::POS_HEAD
                    ],
                ],
                //'dmstr\web\AdminLteAsset' => [
                //'skin' => 'skin-black',
                //],
            ],
        ],
    ],

    'modules' => [
        //rbac security
        'admin' => [
            'class' => 'mdm\admin\Module',
            //'layout' => 'left-menu',
            'mainLayout' => '@app/views/layouts/mainAdminlte.php',
        ],
        //modulo de acceso al sistema
        'acces' => [
            'class' => 'app\modules\acces\acces',
        ],
        'atletas' => [
            'class' => 'app\modules\atletas\atletas',
        ],
        'epcSanAgustin' => [
            'class' => 'app\modules\escuela_club\epcSanAgustin\epcSanAgustin',
        ],
        'escuela_club' => [
            'class' => 'app\modules\escuela_club\escuela_club',
        ],
        'ged' => [
            'class' => 'app\modules\ged\ged',
        ],
        'aportes' => [
            'class' => 'app\modules\aportes\aportes',
        ],
        
    ],

    // ⭐⭐⭐ CORRECCIÓN: COMPORTAMIENTO GLOBAL DEBE ESTAR EN NIVEL SUPERIOR ⭐⭐⭐
    //'as escuelaRequired' => [
    //'class' => 'app\components\EscuelaRequiredFilter',
    //],
    /** 
     * ACTIVAR ERRORES PARA DIAGNOSTICAR
    */
        'on beforeRequest' => function () {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        },
    /** 
     * aqui termina el codigo de prueba
    */
    'params' => $params,
        'as access' => [
        'class' => 'mdm\admin\components\AccessControl',
        'allowActions' => [
            'site/logout',
            //'site/index',
            //'site/error',
            //'site/sidebar',
            //'site/contact',
            //'site/about',
            'ged/*',
            'site/*',
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
            'admin/user/signup',
            'admin/user/request-password-reset',
            'admin/user/reset-password',
            //'*',
        ]
    ],

];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // ✅ CONFIGURACIÓN CORREGIDA - AGREGAR DOMINIO planealo.sytes.net
        'allowedIPs' => [
            '201.209.14.141', 
            '127.0.0.1', 
            '::1', 
            '192.168.1.120',
            'localhost',
            'planealo.sytes.net',
            '*.sytes.net',
            // Agregar rangos de red local adicionales
            '192.168.1.*',
            '10.0.*.*',
        ],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // ✅ MISMAS IPs PARA GII
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