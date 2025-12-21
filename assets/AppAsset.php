<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/ged.css',
        'font_ico/bootstrap-icons.css',
        'css/mapa-escuelas.css',
        'css/reportes.css',
        //'css/navbar.css',
        //'css/ged-offcanvas.css',
    ];
    
    public $js = [
        // ✅ ORDEN CORRECTO: Primero módulo OffCanvas, luego sistema principal
        'js/modules/gedOffCanvas-module.js',  // PRIMERO - Define window.OffCanvasSidebar
        
        // Sistema principal GED (depende del módulo OffCanvas)
        'js/ged.js',
        
        // Módulos adicionales
        'js/modules/horario-selector.js',
        'js/modules/mapa-module.js',
        'js/modules/reportes-module.js',
        'js/modules/tienda-module.js',
        
        // Utilidades
        'js/utils/debug-utils.js',
        
        // Inicialización global (DEBE SER EL ÚLTIMO)
        'js/ged-init.js',
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
    
    public static function addMap($view)
    {
        $view->registerCssFile('@web/css/mapa-escuelas.css', ['depends' => [AppAsset::class]]);
        $view->registerJsFile('@web/js/mapa-escuelas-show.js', ['depends' => [AppAsset::class]]);
    }
}