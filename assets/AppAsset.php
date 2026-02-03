<?php
// /assets/AppAsset.php
// VERSIÓN OPTIMIZADA - CARGA SOLO ARCHIVOS CONSOLIDADOS

namespace app\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        // ✅ CSS CONSOLIDADO - REEMPLAZA 7 ARCHIVOS
        'css/ged.css',
        
        // Bootstrap 5 (CDN o local)
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
        
        // Font Awesome (si se usa)
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        
        // Estilos adicionales específicos (si existen)
        // 'css/estilos-adicionales.css',
    ];
    
    public $js = [
        // ✅ JS CONSOLIDADO - REEMPLAZA 8 ARCHIVOS
        'js/ged-consolidated.js',
        
        // Bootstrap 5 Bundle con Popper
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        
        // jQuery (si es necesario para legacy)
        // 'https://code.jquery.com/jquery-3.6.0.min.js',
        
        // Scripts adicionales específicos (si existen)
        // 'js/scripts-adicionales.js',
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        // 'yii\bootstrap5\BootstrapAsset', // Si no usas CDN
    ];
    
    // Opcional: Cargar assets en footer para mejor rendimiento
    public $jsOptions = [
        'position' => \yii\web\View::POS_END
    ];
    
    public $cssOptions = [
        'media' => 'all'
    ];
}