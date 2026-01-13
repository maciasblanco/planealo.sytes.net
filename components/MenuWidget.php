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

    public function init()
    {
        parent::init();
        
        $this->mobileMode = false;
        
        if (isset($this->options['mobileMode'])) {
            $this->mobileMode = (bool)$this->options['mobileMode'];
        }
    }

    public function run()
    {
        try {
            // ✅ CONSULTAR TODOS LOS MENÚS PARA DEBUG
            self::debugAllMenus();
            
            // ✅ OBTENER MENÚ CON SOLO 2 NIVELES
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
     * ✅ OBTENER ITEMS DEL MENÚ - SOLO 2 NIVELES
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
            Yii::debug('MenuWidget - Consulta ejecutada. Items: ' . count($items) . ' para parentId: ' . ($parentId ?: 'null'));
            
        } catch (\Exception $e) {
            Yii::error('MenuWidget DB ERROR: ' . $e->getMessage(), __METHOD__);
            return [];
        }

        $menuItems = [];

        foreach ($items as $item) {
            // ✅ VERIFICAR SI DEBE MOSTRARSE
            if (!$this->shouldShowMenuItem($item)) {
                Yii::debug('MenuWidget - Omitiendo item: ' . $item['name'] . ' (no visible)');
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
                // ✅ VERIFICAR SI EL HIJO DEBE MOSTRARSE
                if (!$this->shouldShowMenuItem($child)) {
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
     * ✅ VERIFICAR SI UN ITEM DEBE MOSTRARSE
     */
    protected function shouldShowMenuItem($item)
    {
        // ✅ 1. SI ES PÚBLICO, MOSTRAR SIEMPRE
        if ($this->isPublicMenuItem($item)) {
            return true;
        }
        
        // ✅ 2. SI ES USUARIO GUEST, SOLO MOSTRAR PÚBLICOS
        if (Yii::$app->user->isGuest) {
            return false;
        }
        
        // ✅ 3. VERIFICAR PERMISOS RBAC
        return $this->checkMenuItemPermission($item);
    }

    /**
     * ✅ VERIFICAR SI ES PÚBLICO
     */
    protected function isPublicMenuItem($item)
    {
        // IDs DE MENÚS PÚBLICOS
        $publicMenuIds = [177, 179, 178]; // MarketPlace y sus hijos
        
        if (isset($item['id']) && in_array($item['id'], $publicMenuIds)) {
            return true;
        }
        
        // RUTAS PÚBLICAS
        $publicRoutes = [
            'site/index',
            'site/login',
            'site/logout',
            'site/signup',
            'site/about',
            'site/contact',
            'tienda/marketplace/index',
            'tienda/producto/create',
            'ged/default/index',
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
        ];
        
        if (!empty($item['route']) && in_array($item['route'], $publicRoutes)) {
            return true;
        }
        
        // PATRONES DE RUTAS PÚBLICAS
        $publicPatterns = ['tienda/', 'marketplace', 'site/', 'ged/default'];
        foreach ($publicPatterns as $pattern) {
            if (!empty($item['route']) && strpos($item['route'], $pattern) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * ✅ VERIFICAR PERMISOS RBAC
     */
    protected function checkMenuItemPermission($item)
    {
        // ✅ SI NO TIENE RUTA O ES CONTENEDOR, PERMITIR
        if (empty($item['route']) || $item['route'] == '#') {
            return true;
        }
        
        try {
            $route = $item['route'];
            
            // ✅ VERIFICAR PERMISO DIRECTO
            if (Yii::$app->user->can($route)) {
                return true;
            }
            
            // ✅ VERIFICAR POR PATRÓN
            $routeParts = explode('/', $route);
            if (count($routeParts) >= 2) {
                $modulePattern = $routeParts[0] . '/*';
                if (Yii::$app->user->can($modulePattern)) {
                    return true;
                }
                
                $controllerPattern = $routeParts[0] . '/' . $routeParts[1] . '/*';
                if (Yii::$app->user->can($controllerPattern)) {
                    return true;
                }
            }
            
            // ✅ VERIFICAR ROLES DE ADMINISTRADOR
            $adminRoles = ['admin', 'administrator', 'superadmin'];
            foreach ($adminRoles as $role) {
                if (Yii::$app->user->can($role)) {
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Yii::error('Permission check error: ' . $e->getMessage(), __METHOD__);
            return false;
        }
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
     * ✅ RENDERIZAR PARA DESKTOP (2 NIVELES)
     */
    protected function renderMenuForDesktop($menuItems)
    {
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
     * ✅ RENDERIZAR PARA MÓVIL (OFFCANVAS)
     */
    protected function renderMenuForMobile($menuItems)
    {
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
     * ✅ MENÚ DE RESERVA
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
            </li>';
            
        // ✅ MARKETPLACE SIEMPRE VISIBLE
        $menuItems .= '
            <li class="nav-item">
                <a class="' . ($this->mobileMode ? 'nav-link' : 'nav-link text-white') . '" href="' . Url::to(['/tienda/marketplace']) . '">
                    <i class="fas fa-store me-1"></i>Marketplace
                </a>
            </li>';
            
        if (Yii::$app->user->isGuest) {
            $menuItems .= '
            <li class="nav-item">
                <a class="' . ($this->mobileMode ? 'nav-link' : 'nav-link text-white') . '" href="' . Url::to(['/site/login']) . '">
                    <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                </a>
            </li>';
        } else {
            $menuItems .= '
            <li class="nav-item">
                ' . Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) . '
                ' . Html::submitButton(
                    '<i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión',
                    ['class' => $this->mobileMode ? 'nav-link btn btn-link' : 'nav-link text-white btn btn-link']
                ) . '
                ' . Html::endForm() . '
            </li>';
        }
        
        $menuItems .= '</ul>';
        
        return $menuItems;
    }
    
    /**
     * ✅ DEBUG: VER TODOS LOS MENÚS DISPONIBLES
     */
    public static function debugAllMenus()
    {
        try {
            $query = new Query();
            $allMenus = $query->select([
                    'm.id', 
                    'm.name', 
                    'm.route', 
                    'm.parent', 
                    'm."order" as menu_order'
                ])
                ->from('seguridad.menu m')
                ->orderBy('m.parent ASC, m."order" ASC')
                ->all();
            
            Yii::debug('=== TODOS LOS MENÚS EN LA BD ===', __METHOD__);
            foreach ($allMenus as $menu) {
                Yii::debug(sprintf(
                    'ID: %d | Nombre: %s | Ruta: %s | Parent: %s',
                    $menu['id'],
                    $menu['name'],
                    $menu['route'] ?: 'null',
                    $menu['parent'] ?: 'null'
                ), __METHOD__);
            }
            
            return $allMenus;
        } catch (\Exception $e) {
            Yii::error('ERROR obteniendo menús: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }
}