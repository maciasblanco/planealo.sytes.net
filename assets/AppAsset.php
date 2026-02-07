<?php
// /assets/AppAsset.php - VERSIÓN CON FALLBACK INTELIGENTE
namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public function init()
    {
        parent::init();
        
        // ✅ VERIFICAR QUÉ ARCHIVOS EXISTEN LOCALMENTE
        $localCss = [];
        $localJs = [];
        
        // Bootstrap CSS - local primero
        if (file_exists(\Yii::getAlias('@webroot/css/bootstrap.min.css'))) {
            $localCss[] = 'css/bootstrap.min.css';
        }
        
        // CSS GED (siempre local)
        $localCss[] = 'css/ged.css';
        
        // Bootstrap Icons (siempre local)
        $localCss[] = 'font_ico/bootstrap-icons.css';
        
        // jQuery - local primero
        if (file_exists(\Yii::getAlias('@webroot/js/jquery-3.6.0.min.js'))) {
            $localJs[] = 'js/jquery-3.6.0.min.js';
        }
        
        // Bootstrap JS Bundle - local primero
        if (file_exists(\Yii::getAlias('@webroot/js/jsBootstrap5/bootstrap.bundle.min.js'))) {
            $localJs[] = 'js/jsBootstrap5/bootstrap.bundle.min.js';
        }
        
        // Nuestros scripts (siempre locales)
        $localJs[] = 'js/ged-consolidated.js';
        $localJs[] = 'js/loadSelect2.js';
        
        $this->css = $localCss;
        $this->js = $localJs;
        
        $this->depends = ['yii\web\YiiAsset'];
        $this->jsOptions = ['position' => View::POS_END];
        
        // ✅ REGISTRAR FALLBACKS SOLO PARA LO QUE FALTA
        $this->registerFallbacks();
    }
    
    private function registerFallbacks()
    {
        $view = \Yii::$app->view;
        
        // Fallback para Bootstrap CSS si no existe local
        if (!file_exists(\Yii::getAlias('@webroot/css/bootstrap.min.css'))) {
            $view->registerCssFile(
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                [
                    'position' => View::POS_HEAD,
                    'integrity' => 'sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM',
                    'crossorigin' => 'anonymous'
                ]
            );
        }
        
        // Fallback para jQuery si no existe local
        if (!file_exists(\Yii::getAlias('@webroot/js/jquery-3.6.0.min.js'))) {
            $view->registerJsFile(
                'https://code.jquery.com/jquery-3.6.0.min.js',
                [
                    'position' => View::POS_HEAD,
                    'integrity' => 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=',
                    'crossorigin' => 'anonymous'
                ]
            );
        }
        
        // Fallback para Bootstrap JS si no existe local
        if (!file_exists(\Yii::getAlias('@webroot/js/jsBootstrap5/bootstrap.bundle.min.js'))) {
            $view->registerJsFile(
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
                [
                    'position' => View::POS_END,
                    'integrity' => 'sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz',
                    'crossorigin' => 'anonymous'
                ]
            );
        }
    }
}