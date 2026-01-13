<?php
namespace app\components;

use yii\base\Widget;
use yii\db\Query;
use yii\helpers\Html;
use yii\helpers\Url;
use Yii;

class ModuleMenuWidget extends Widget
{
    public $moduleName; // 'atletas', 'tienda', 'escuela_club'
    public $currentRoute;
    
    // Cache duration en segundos
    public $cacheDuration = 3600;
    
    public function init()
    {
        parent::init();
        if (empty($this->currentRoute)) {
            $this->currentRoute = Yii::$app->controller->route;
        }
    }
    
    public function run()
    {
        // 1. MAPEAR MÓDULO A ID DE MENÚ
        $moduleMap = [
            'atletas' => 167,      // Atletas
            'escuela_club' => 164, // Escuela/Club
            'tienda' => 177,       // MarketPlace
            'aportes' => 172,      // Aportes
            'reportes' => 175,     // Reportes
        ];
        
        // Validar módulo
        if (!isset($moduleMap[$this->moduleName])) {
            return '';
        }
        
        $parentId = $moduleMap[$this->moduleName];
        
        // Validar parentId
        if (!is_numeric($parentId) || $parentId <= 0) {
            Yii::warning("Parent ID inválido para módulo {$this->moduleName}: $parentId", __METHOD__);
            return '';
        }
        
        // 2. OBTENER HIJOS DEL MÓDULO
        $menuItems = $this->getModuleMenuItems($parentId);
        
        if (empty($menuItems)) {
            return '';
        }
        
        // 3. OBTENER TÍTULO UNA VEZ
        $moduleTitle = $this->getModuleTitle();
        
        // 4. RENDERIZAR SIDEBAR
        return $this->renderModuleSidebar($menuItems, $moduleTitle);
    }
    
    protected function getModuleMenuItems($parentId)
    {
        $cacheKey = "module_menu_{$parentId}_" . Yii::$app->user->id;
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($parentId) {
            try {
                $query = new Query();
                return $query->select(['id', 'name', 'route', '"order"'])
                    ->from('seguridad.menu')
                    ->where(['parent' => $parentId])
                    ->andWhere(['active' => true]) // Solo items activos
                    ->orderBy(['"order"' => SORT_ASC])
                    ->all();
            } catch (\Exception $e) {
                Yii::error('Error obteniendo menú del módulo: ' . $e->getMessage(), __METHOD__);
                return [];
            }
        }, $this->cacheDuration);
    }
    
    protected function renderModuleSidebar($items, $moduleTitle)
    {
        $html = '
        <nav class="sidebar-module-nav" aria-label="Menú del módulo ' . Html::encode($moduleTitle) . '">
            <div class="sidebar-sticky">
                <h6 class="sidebar-heading text-muted">
                    <span>' . Html::encode($moduleTitle) . '</span>
                </h6>
                
                <ul class="nav flex-column">';
        
        foreach ($items as $item) {
            $isActive = $this->isItemActive($item['route'] ?? '');
            $url = !empty($item['route']) ? 
                Url::to([Html::encode($item['route'])]) : '#';
            $label = Html::encode($item['name'] ?? 'Sin nombre');
            
            $html .= '
                <li class="nav-item">
                    <a class="nav-link ' . ($isActive ? 'active' : '') . '" 
                       href="' . $url . '"
                       ' . (empty($item['route']) ? 'tabindex="-1" aria-disabled="true"' : '') . '>
                        <i class="fas fa-angle-right me-2"></i>
                        ' . $label . '
                    </a>
                </li>';
        }
        
        $html .= '
                </ul>
            </div>
        </nav>';
        
        return $html;
    }
    
    protected function isItemActive($itemRoute)
    {
        if (empty($itemRoute) || empty($this->currentRoute)) {
            return false;
        }
        
        // Comparación exacta
        if ($this->currentRoute === $itemRoute) {
            return true;
        }
        
        // Para rutas que son prefijos (ej: 'atletas/' y 'atletas/registro')
        $currentParts = explode('/', $this->currentRoute);
        $itemParts = explode('/', $itemRoute);
        
        return !empty($currentParts[0]) && !empty($itemParts[0]) && 
               $currentParts[0] === $itemParts[0];
    }
    
    protected function getModuleTitle()
    {
        $titles = [
            'atletas' => 'Atletas',
            'escuela_club' => 'Escuela/Club',
            'tienda' => 'MarketPlace',
            'aportes' => 'Aportes',
            'reportes' => 'Reportes',
        ];
        
        return $titles[$this->moduleName] ?? 'Módulo';
    }

}