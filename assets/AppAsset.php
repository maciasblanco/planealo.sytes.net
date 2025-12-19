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
        'css/ged.css', // ÚNICO ARCHIVO CSS UNIFICADO
        // OPCIONAL: Cargar módulos individualmente si prefieres
        //'css/modules/core/ged-core.css',
        //'css/modules/core/ged-utilities.css',
        //'css/modules/modules/ged-modulo-escuelas.css',
        //'css/modules/modules/ged-modulo-tienda.css',
        //'css/modules/modules/ged-modulo-landing.css',
        //'css/modules/modules/ged-modulo-dashboard.css',
        //'css/modules/responsive/ged-responsive.css',
        'font_ico/bootstrap-icons.css',
        'css/mapa-escuelas.css',
        'css/reportes.css',
        'css/navbar.css',
        'css/ged-offcanvas.css', // ✅ NUEVO: Offcanvas CSS
    ];
    
    public $js = [
        // Sistema principal GED (incluye NavbarManager, OffCanvasSidebar, etc.)
        'js/ged.js',
        
        // Módulos adicionales específicos
        'js/modules/horario-selector.js',
        'js/modules/mapa-module.js',
        'js/modules/reportes-module.js',
        'js/modules/tienda-module.js', // Opcional
        // Utilidades de debug
        'js/utils/debug-utils.js',
        
        // Inicialización global
        'js/ged-init.js',
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset', // Para JS de Bootstrap
    ];
    
    // Para cargar solo en páginas específicas
    public static function addMap($view)
    {
        $view->registerCssFile('@web/css/mapa-escuelas.css', ['depends' => [AppAsset::class]]);
        $view->registerJsFile('@web/js/mapa-escuelas-show.js', ['depends' => [AppAsset::class]]);
    }
}