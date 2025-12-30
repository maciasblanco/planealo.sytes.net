<?php
namespace app\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/ged.css',
        'font_ico/bootstrap-icons.css',
        'css/leaflet/leaflet.css'
    ];
    
    public $js = [
        'js/leaflet/leaflet.js',
        'js/ged.js',
        'js/utils/debug-utils.js',
        'js/ged-init.js',
        // ❌ NO cargar reportes-module.js aquí (se cargará dinámicamente)
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
    
    // ✅ Método para cargar reportes SOLO cuando se necesite
    public static function addReportes($view)
    {
        $view->registerJsFile('@web/js/modules/reportes-module.js', [
            'depends' => [self::className()],
            'position' => \yii\web\View::POS_END
        ]);
        
        $view->registerJs("
            // Esperar a que el módulo se cargue
            setTimeout(function() {
                if (typeof window.reportesModule !== 'undefined') {
                    console.log('✅ ReportesModule cargado e inicializado');
                    window.reportesModule.init();
                } else {
                    console.error('❌ ReportesModule no se cargó correctamente');
                }
            }, 500);
        ", \yii\web\View::POS_END);
    }
    
    public static function addMap($view)
    {
        $view->registerJs("
            if (typeof L !== 'undefined' && L.Icon && L.Icon.Default) {
                L.Icon.Default.imagePath = '/images/leaflet/';
            }
        ", \yii\web\View::POS_HEAD);
    }
}