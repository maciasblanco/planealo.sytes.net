<?php
// /assets/AppAsset.php - VERSIÓN CORREGIDA PARA YII2
namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/ged.css',
        'font_ico/bootstrap-icons.css',
    ];
    
    public $js = [
        'js/ged-consolidated.js',
        'js/loadSelect2.js',
        'js/jsBootstrap5/bootstrap.bundle.min.js'
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
    
    public $jsOptions = [
        'position' => View::POS_END,
    ];
    
    // ✅ NOTA: Yii2 maneja automáticamente:
    // - jQuery (a través de yii\web\JqueryAsset)
    // - Bootstrap CSS/JS (a través de yii\bootstrap5\BootstrapAsset/PluginAsset)
    // - yii.activeForm.js (cuando se usa ActiveForm en vistas)
}