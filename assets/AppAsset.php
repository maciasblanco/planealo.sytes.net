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
        'font_ico/bootstrap-icons.css',
        'css/mapa-escuelas.css',
        'css/reportes.css',
        'css/navbar.css',
        'css/ged-offcanvas.css', // ✅ NUEVO: Offcanvas CSS
    ];
    
    public $js = [
        'js/ged.js', // JS ya unificado
        'js/dropdowns-dependientes.js',
        'js/mapa-escuela.js',
        'js/horarioSelector.js',
        'js/mapa-escuelas-show.js',
        'js/tienda.js',
        'js/reportes.js',
        'js/ged-offcanvas.js', // ✅ NUEVO: Offcanvas JS
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