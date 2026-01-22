<?php
namespace app\components;

use yii\base\Widget;
use yii\db\Query;
use yii\helpers\Html;
use yii\helpers\Url;
use Yii;

class MenuWidget extends Widget
{
    public $parentId = null;
    public $options = [];
    public $mobileMode = false;
    public $maxDepth = 2; // ✅ SOLO 2 NIVELES
    public $excludeRoutes = []; // ✅ RUTAS A EXCLUIR

    public function init()
    {
        parent::init();
        
        $this->mobileMode = false;
        
        if (isset($this->options['mobileMode'])) {
            $this->mobileMode = (bool)$this->options['mobileMode'];
        }
        
        // ✅ RUTAS A EXCLUIR (Login/Registro ya están en navbar-control-section)
        $this->excludeRoutes = [
            'site/login',
            'site/signup',
            'site/logout'
        ];
        
        if (isset($this->options['excludeRoutes'])) {
            $this->excludeRoutes = array_merge($this->excludeRoutes, $this->options['excludeRoutes']);
        }
    }

    public function run()
    {
        try {
            // ✅ OBTENER MENÚ CON SOLO 2 NIVELES (excluyendo Login/Registro)
            $menuItems = $this->getMenuItems($this->parentId);
            
            if (empty($menuItems)) {
                Yii::warning('MenuWidget: No se encontraron items, usando fallback');
                return $this->renderFallbackMenu();
            }
            
            // ✅ DETERMINAR CÓMO RENDERIZAR
            if ($this->mobileMode) {
                return $this->renderMenuForMobile($menuItems);
            }
            
            return $this->renderMenuForDesktop($menuItems);
        } catch (\Exception $e) {
            Yii::error('MenuWidget ERROR: ' . $e->getMessage(), __METHOD__);
            return $this->renderFallbackMenu();
        }
    }

    /**
     * ✅ OBTENER ITEMS DEL MENÚ - SOLO 2 NIVELES (excluyendo Login/Registro)
     */
    protected function getMenuItems($parentId = null)
    {
        try {
            $db = Yii::$app->db;
            if (!$db || $db->getIsActive() === false) {
                Yii::warning('MenuWidget: Base de datos no disponible');
                return [];
            }
            
            // ✅ CONSULTA PARA NIVEL 0 (parentId = null) O NIVEL 1 (parentId = id_padre)
            $query = new Query();
            
            $query->select([
                'm.id', 
                'm.name', 
                'm.route', 
                'm.parent', 
                'm."order" as menu_order',
                'm.data'
            ])
            ->from('seguridad.menu m')
            ->where(['m.parent' => $parentId])
            ->orderBy('m."order" ASC, m.name ASC');
            
            $items = $query->all();
            
        } catch (\Exception $e) {
            Yii::error('MenuWidget DB ERROR: ' . $e->getMessage(), __METHOD__);
            return [];
        }

        $menuItems = [];

        foreach ($items as $item) {
            // ✅ VERIFICAR SI DEBE EXCLUIRSE (Login/Registro)
            if ($this->shouldExcludeMenuItem($item)) {
                continue;
            }
            
            // ✅ OBTENER HIJOS (NIVEL 1) - SOLO PARA NIVEL 0
            $childItems = [];
            if ($parentId === null) {
                $childItems = $this->getChildItemsForLevel1($item['id']);
            }
            
            // ✅ CONSTRUIR ITEM
            $menuItem = [
                'id' => $item['id'],
                'label' => $item['name'],
                'url' => $this->getMenuItemUrl($item),
                'items' => $childItems,
                'hasChildren' => !empty($childItems),
                'route' => $item['route'],
                'data' => $item['data'],
                'icon' => $this->extractIcon($item['data']),
                'visible' => true
            ];

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }
    
    /**
     * ✅ VERIFICAR SI UN ITEM DEBE EXCLUIRSE (Login/Registro)
     */
    protected function shouldExcludeMenuItem($item)
    {
        if (empty($item['route'])) {
            return false;
        }
        
        // ✅ EXCLUIR LOGIN, REGISTRO, LOGOUT (ya están en navbar-control-section)
        $excludeRoutes = $this->excludeRoutes;
        
        foreach ($excludeRoutes as $excludeRoute) {
            if ($item['route'] === $excludeRoute) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * ✅ OBTENER HIJOS PARA NIVEL 1 (SOLO UN NIVEL)
     */
    protected function getChildItemsForLevel1($parentId)
    {
        try {
            $query = new Query();
            $childItems = $query->select([
                'm.id', 
                'm.name', 
                'm.route', 
                'm.parent', 
                'm."order" as menu_order',
                'm.data'
            ])
            ->from('seguridad.menu m')
            ->where(['m.parent' => $parentId])
            ->orderBy('m."order" ASC, m.name ASC')
            ->all();
            
            $menuItems = [];
            
            foreach ($childItems as $child) {
                // ✅ VERIFICAR SI EL HIJO DEBE EXCLUIRSE
                if ($this->shouldExcludeMenuItem($child)) {
                    continue;
                }
                
                // ✅ NO OBTENER NIETOS (SOLO 2 NIVELES)
                $menuItems[] = [
                    'id' => $child['id'],
                    'label' => $child['name'],
                    'url' => $this->getMenuItemUrl($child),
                    'items' => [], // ✅ SIN MÁS HIJOS
                    'hasChildren' => false,
                    'route' => $child['route'],
                    'data' => $child['data'],
                    'icon' => $this->extractIcon($child['data'])
                ];
            }
            
            return $menuItems;
            
        } catch (\Exception $e) {
            Yii::error('Error obteniendo hijos: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * ✅ SIMPLIFICADO: SIEMPRE MOSTRAR EL ITEM (los filtros ya se hicieron en shouldExcludeMenuItem)
     */
    protected function shouldShowMenuItem($item)
    {
        // ✅ SI EL ITEM TIENE RUTA VACÍA O ES '#', ES UN CONTENEDOR, MOSTRAR SIEMPRE
        if (empty($item['route']) || $item['route'] == '#') {
            return true;
        }
        
        // ✅ PARA USUARIOS GUEST: PERMITIR ALGUNAS RUTAS PÚBLICAS
        if (Yii::$app->user->isGuest) {
            return $this->isPublicMenuItem($item);
        }
        
        // ✅ PARA USUARIOS AUTENTICADOS: MOSTRAR TODOS (los permisos se manejan en los controladores)
        return true;
    }

    /**
     * ✅ VERIFICAR SI ES PÚBLICO - MÁS FLEXIBLE
     */
    protected function isPublicMenuItem($item)
    {
        // IDs DE MENÚS PÚBLICOS
        $publicMenuIds = [177, 179, 178]; // MarketPlace y sus hijos
        
        if (isset($item['id']) && in_array($item['id'], $publicMenuIds)) {
            return true;
        }
        
        // RUTAS PÚBLICAS (ampliadas)
        $publicRoutes = [
            'site/index',
            'site/about',
            'site/contact',
            'tienda/marketplace/index',
            'tienda/producto/create',
            'tienda/marketplace/view',
            'tienda/producto/index',
            'ged/default/index',
            'ged/default/select-escuela',
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
            'escuela-club/default/index',
            'escuela-club/escuela/view',
            'atletas/atleta/index',
            'reportes/default/index',
            'aportes/default/index',
        ];
        
        if (!empty($item['route']) && in_array($item['route'], $publicRoutes)) {
            return true;
        }
        
        // PATRONES DE RUTAS PÚBLICAS (ampliados)
        $publicPatterns = [
            'tienda/', 
            'marketplace', 
            'site/', 
            'ged/', 
            'escuela-club/',
            'atletas/',
            'reportes/',
            'aportes/'
        ];
        
        foreach ($publicPatterns as $pattern) {
            if (!empty($item['route']) && strpos($item['route'], $pattern) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * ✅ OBTENER URL DEL ITEM
     */
    protected function getMenuItemUrl($item)
    {
        if (empty($item['route']) || $item['route'] === '#') {
            return '#';
        }
        
        return [$item['route']];
    }

    /**
     * ✅ EXTRAER ICONO DE LOS DATOS
     */
    protected function extractIcon($data)
    {
        if (empty($data)) {
            return null;
        }
        
        try {
            if (strpos($data, 'faIcon') !== false) {
                preg_match('/faIcon["\']\s*=>\s*["\']([^"\']+)["\']/', $data, $matches);
                if (!empty($matches[1])) {
                    return $matches[1];
                }
            }
        } catch (\Exception $e) {
            // Ignorar errores de parseo
        }
        
        return null;
    }

    /**
     * ✅ RENDERIZAR PARA DESKTOP (2 NIVELES) - SIN LOGIN/REGISTRO
     */
    protected function renderMenuForDesktop($menuItems)
    {
        if (empty($menuItems)) {
            return $this->renderFallbackMenu();
        }
        
        $menuClass = isset($this->options['class']) ? $this->options['class'] : 'navbar-nav';
        $html = '<ul class="' . $menuClass . '">';
        
        foreach ($menuItems as $item) {
            $hasChildren = !empty($item['items']);
            $url = $item['url'] == '#' ? '#' : Url::to($item['url']);
            $label = Html::encode($item['label']);
            $icon = $item['icon'] ? Html::tag('i', '', ['class' => $item['icon'] . ' me-1']) : '';
            
            if ($hasChildren) {
                // ✅ NIVEL 1: DROPDOWN CON HIJOS
                $childrenHtml = '';
                foreach ($item['items'] as $child) {
                    $childUrl = $child['url'] == '#' ? '#' : Url::to($child['url']);
                    $childLabel = Html::encode($child['label']);
                    $childIcon = $child['icon'] ? Html::tag('i', '', ['class' => $child['icon'] . ' me-1']) : '';
                    
                    $childrenHtml .= '
                    <li>
                        <a class="dropdown-item" href="' . $childUrl . '">
                            ' . $childIcon . $childLabel . '
                        </a>
                    </li>';
                }
                
                $html .= '
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="' . $url . '" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                       ' . $icon . $label . '
                    </a>
                    <ul class="dropdown-menu">
                        ' . $childrenHtml . '
                    </ul>
                </li>';
            } else {
                // ✅ NIVEL 1: ITEM SIMPLE
                $html .= '
                <li class="nav-item">
                    <a class="nav-link text-white" href="' . $url . '">
                        ' . $icon . $label . '
                    </a>
                </li>';
            }
        }
        
        $html .= '</ul>';
        return $html;
    }

    /**
     * ✅ RENDERIZAR PARA MÓVIL (OFFCANVAS) - SIN LOGIN/REGISTRO
     */
    protected function renderMenuForMobile($menuItems)
    {
        if (empty($menuItems)) {
            return $this->renderFallbackMenu();
        }
        
        $menuClass = isset($this->options['class']) ? $this->options['class'] : 'navbar-nav flex-column w-100';
        $html = '<ul class="' . $menuClass . '">';
        
        foreach ($menuItems as $item) {
            $hasChildren = !empty($item['items']);
            $url = $item['url'] == '#' ? '#' : Url::to($item['url']);
            $label = Html::encode($item['label']);
            $icon = $item['icon'] ? Html::tag('i', '', ['class' => $item['icon'] . ' me-2']) : '';
            
            if ($hasChildren) {
                // ✅ NIVEL 1: ACORDEÓN CON HIJOS
                $itemId = 'mobile-submenu-' . $item['id'];
                $childrenHtml = '';
                foreach ($item['items'] as $child) {
                    $childUrl = $child['url'] == '#' ? '#' : Url::to($child['url']);
                    $childLabel = Html::encode($child['label']);
                    $childIcon = $child['icon'] ? Html::tag('i', '', ['class' => $child['icon'] . ' me-2']) : '';
                    
                    $childrenHtml .= '
                    <li class="nav-item">
                        <a class="nav-link ps-4" href="' . $childUrl . '">
                            ' . $childIcon . $childLabel . '
                        </a>
                    </li>';
                }
                
                $html .= '
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#' . $itemId . '" 
                       data-bs-toggle="collapse" role="button" 
                       aria-expanded="false" aria-controls="' . $itemId . '">
                       ' . $icon . $label . '
                       <span class="float-end"><i class="fas fa-chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="' . $itemId . '">
                        <ul class="nav flex-column">
                            ' . $childrenHtml . '
                        </ul>
                    </div>
                </li>';
            } else {
                // ✅ NIVEL 1: ITEM SIMPLE
                $html .= '
                <li class="nav-item">
                    <a class="nav-link" href="' . $url . '">
                        ' . $icon . $label . '
                    </a>
                </li>';
            }
        }
        
        $html .= '</ul>';
        
        return $html;
    }

    /**
     * ✅ MENÚ DE RESERVA - SIN LOGIN/REGISTRO
     */
    protected function renderFallbackMenu()
    {
        $menuClass = $this->mobileMode ? 'navbar-nav flex-column' : 'navbar-nav';
        
        $menuItems = '
        <ul class="' . $menuClass . '">
            <li class="nav-item">
                <a class="' . ($this->mobileMode ? 'nav-link' : 'nav-link text-white') . '" href="' . Url::to(['/']) . '">
                    <i class="fas fa-home me-1"></i>Inicio
                </a>
            </li>
            
            <li class="nav-item">
                <a class="' . ($this->mobileMode ? 'nav-link' : 'nav-link text-white') . '" href="' . Url::to(['/tienda/marketplace']) . '">
                    <i class="fas fa-store me-1"></i>Marketplace
                </a>
            </li>
            
            <li class="nav-item">
                <a class="' . ($this->mobileMode ? 'nav-link' : 'nav-link text-white') . '" href="' . Url::to(['/ged/default/index']) . '">
                    <i class="fas fa-school me-1"></i>GED Sistema
                </a>
            </li>';
        
        // ❌ NO INCLUIR LOGIN/REGISTRO - YA ESTÁN EN navbar-control-section
        
        $menuItems .= '</ul>';
        
        return $menuItems;
    }
}