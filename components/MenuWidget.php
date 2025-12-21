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
    public $menuClass = 'navbar-nav';
    public $mobileMode = false;
    public $rootOnly = false;

    public function init()
    {
        parent::init();
        
        $this->mobileMode = false;
        
        if (isset($this->options['mobileMode'])) {
            $this->mobileMode = (bool)$this->options['mobileMode'];
        }
        
        if (isset($this->options['class'])) {
            $this->menuClass = $this->options['class'];
        }
        
        if (isset($this->options['rootOnly'])) {
            $this->rootOnly = (bool)$this->options['rootOnly'];
        }
    }

    public function run()
    {
        try {
            if ($this->rootOnly) {
                $this->parentId = null;
            }
            
            $menuItems = $this->getMenuItems($this->parentId);
            
            if (empty($menuItems)) {
                return $this->renderFallbackMenu();
            }
            
            $isForNavbar = strpos($this->menuClass, 'navbar-nav') !== false;
            
            if ($isForNavbar && !$this->mobileMode) {
                return $this->renderNavbarMenu($menuItems);
            } else {
                return $this->renderOffcanvasMenu($menuItems);
            }
            
        } catch (\Exception $e) {
            Yii::error('MenuWidget Error: ' . $e->getMessage(), __METHOD__);
            return $this->renderFallbackMenu();
        }
    }

    protected function getMenuItems($parentId = null)
    {
        try {
            $db = Yii::$app->db;
            if (!$db || $db->getIsActive() === false) {
                return [];
            }
            
            $query = new Query();
            
            if ($parentId === null || $parentId === 'NULL' || $parentId === '' || $parentId === 'null') {
                $query->where(['or', 
                    ['m.parent' => null],
                    ['m.parent' => ''],
                    ['m.parent' => 'NULL'],
                    ['m.parent' => 'null']
                ]);
            } else {
                $query->where(['m.parent' => $parentId]);
            }
            
            $query->select([
                'm.id', 
                'm.name', 
                'm.route', 
                'm.parent', 
                'm."order" as menu_order',
                'm.data'
            ])
            ->from('seguridad.menu m')
            ->orderBy('COALESCE(m."order", 99999) ASC');
            
            $items = $query->all();
            
            if (empty($items) && $parentId !== null && $parentId !== 'NULL' && $parentId !== '' && $parentId !== 'null') {
                $queryRoot = new Query();
                $queryRoot->select([
                    'm.id', 
                    'm.name', 
                    'm.route', 
                    'm.parent', 
                    'm."order" as menu_order',
                    'm.data'
                ])
                ->from('seguridad.menu m')
                ->where(['or', 
                    ['m.parent' => null],
                    ['m.parent' => ''],
                    ['m.parent' => 'NULL'],
                    ['m.parent' => 'null']
                ])
                ->orderBy('COALESCE(m."order", 99999) ASC');
                
                $items = $queryRoot->all();
            }
            
        } catch (\Exception $e) {
            Yii::error('MenuWidget DB Error: ' . $e->getMessage(), __METHOD__);
            return [];
        }

        $menuItems = [];

        foreach ($items as $item) {
            $route = $item['route'] ?? '';
            $name = $item['name'] ?? '';
            $id = $item['id'] ?? '';
            
            if (Yii::$app->user->isGuest) {
                if (!empty($route) && $route !== '#') {
                    $normalizedRoute = ltrim($route, '/');
                    
                    if ($this->isPublicRoute($normalizedRoute)) {
                        $childItems = $this->getMenuItems($id);
                        
                        $filteredChildren = [];
                        foreach ($childItems as $child) {
                            $childRoute = $child['route'] ?? '';
                            if (!empty($childRoute) && $childRoute !== '#') {
                                $childNormalizedRoute = ltrim($childRoute, '/');
                                if ($this->isPublicRoute($childNormalizedRoute)) {
                                    $filteredChildren[] = $child;
                                }
                            }
                        }
                        
                        $menuItem = [
                            'id' => $id,
                            'label' => $name,
                            'url' => [$route],
                            'items' => $filteredChildren,
                            'visible' => true,
                            'route' => $route
                        ];
                        
                        $menuItems[] = $menuItem;
                        continue;
                    } else {
                        continue;
                    }
                }
                
                if (empty($route) || $route === '#') {
                    $childItems = $this->getMenuItems($id);
                    
                    $filteredChildren = [];
                    foreach ($childItems as $child) {
                        $childRoute = $child['route'] ?? '';
                        if (!empty($childRoute) && $childRoute !== '#') {
                            $childNormalizedRoute = ltrim($childRoute, '/');
                            if ($this->isPublicRoute($childNormalizedRoute)) {
                                $filteredChildren[] = $child;
                            }
                        }
                    }
                    
                    if (!empty($filteredChildren)) {
                        $menuItem = [
                            'id' => $id,
                            'label' => $name,
                            'url' => '#',
                            'items' => $filteredChildren,
                            'visible' => true,
                            'route' => $route
                        ];
                        $menuItems[] = $menuItem;
                    }
                }
                continue;
            }
            
            $hasPermission = $this->checkMenuItemPermission($item);
            
            if (!$hasPermission) {
                continue;
            }

            $childItems = $this->getMenuItems($id);
            
            $menuItem = [
                'id' => $id,
                'label' => $name,
                'url' => !empty($route) ? [$route] : '#',
                'items' => $childItems,
                'visible' => true,
                'route' => $route
            ];

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }

    protected function checkMenuItemPermission($item)
    {
        $route = $item['route'] ?? '';
        
        if (Yii::$app->user->isGuest) {
            if (!empty($route) && $route !== '#') {
                $normalizedRoute = ltrim($route, '/');
                if ($this->isPublicRoute($normalizedRoute)) {
                    return true;
                }
            }
            return false;
        }
        
        if (empty($route) || $route == '#') {
            return true;
        }

        try {
            $normalizedRoute = ltrim($route, '/');
            if ($this->isPublicRoute($normalizedRoute)) {
                return true;
            }
            
            if (Yii::$app->user->can($route)) {
                return true;
            }
            
            $routeParts = explode('/', $route);
            if (count($routeParts) >= 2) {
                $modulePattern = $routeParts[0] . '/*';
                if (Yii::$app->user->can($modulePattern)) {
                    return true;
                }
                
                if (count($routeParts) >= 2) {
                    $controllerPattern = $routeParts[0] . '/' . $routeParts[1] . '/*';
                    if (Yii::$app->user->can($controllerPattern)) {
                        return true;
                    }
                }
            }

            $adminRoles = ['admin', 'administrator', 'superadmin'];
            foreach ($adminRoles as $role) {
                if (Yii::$app->user->can($role)) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            Yii::error('checkMenuItemPermission Error: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    protected function isPublicRoute($route)
    {
        $route = ltrim($route, '/');
        
        $publicRoutes = [
            'tienda/marketplace/index',
            'tienda/marketplace',
            'tienda/default/index',
            'tienda/producto/create',
            'tienda/marketplace/buscar',
            'tienda/marketplace/categoria',
            'tienda/marketplace/producto',
            'tienda/marketplace/detalle',
            'tienda/default/registro-vendedor',
            'tienda/default/dashboard-vendedor',
            'tienda/default/carrito',
            'tienda/default/checkout',
            'tienda/*',
            'ged/default/index',
            'ged/escuela/ver',
            'ged/escuela/listar',
            'ged/escuela/buscar',
            'ged/deporte/ver',
            'ged/deporte/listar',
            'ged/categoria/ver',
            'ged/categoria/listar',
            'ged/*',
            'escuela_club/escuela-registro/pre-registro',
            'atletas/atletas-registro/create',
            'atletas/asistencia/registro-multiple',
            'aportes/aportes/index',
            'reportes/reportes/atletas',
            'gii',
            'admin/user/index',
            'admin/role/index',
            'admin/permission/index',
            'admin/route/index',
            'admin/rule/index',
            'admin/menu/index',
            'acces/user/signup',
            'admin/assignment/index',
            'site/index',
            'site/login',
            'site/logout',
            'site/error',
            'site/about',
            'site/contact',
            'site/signup',
            'site/request-password-reset',
            'site/reset-password',
            'site/cambiar-password',
            'site/mi-cuenta',
            'site/acceder-sistema',
            'site/*',
            'admin/user/signup',
            'admin/user/request-password-reset', 
            'admin/user/reset-password',
            'admin/user/login',
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
            'parroquia/get-by-muni-cod',
            'api/*',
            'informacion/*',
            'catalogo/*',
            'galeria/*',
            'noticia/*',
            'evento/*',
            'escuela/ver',
            'escuela/listar',
            'deporte/ver',
            'deporte/listar',
            'categoria/ver',
            'categoria/listar',
            'horario/ver',
            'horario/listar',
            'marketplace/*',
            'shop/*',
            'store/*',
        ];

        if (in_array($route, $publicRoutes)) {
            return true;
        }

        foreach ($publicRoutes as $publicRoute) {
            if (strpos($publicRoute, '*') !== false) {
                $pattern = preg_quote($publicRoute, '/');
                $pattern = str_replace('\*', '.*', $pattern);
                $pattern = '/^' . $pattern . '$/';
                
                if (preg_match($pattern, $route)) {
                    return true;
                }
            }
        }

        $publicKeywords = [
            'ver', 'listar', 'buscar', 'index', 'catalogo', 'galeria',
            'informacion', 'noticia', 'evento', 'publico', 'marketplace',
            'tienda', 'shop', 'store', 'login', 'signup', 'registro',
            'acceder', 'about', 'contact'
        ];
        
        $routeLower = strtolower($route);
        foreach ($publicKeywords as $keyword) {
            if (strpos($routeLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function renderNavbarMenu($items, $level = 0, $isDropdown = false)
    {
        if (empty($items)) {
            return '';
        }
        
        if ($isDropdown) {
            $html = '<ul class="dropdown-menu">';
        } elseif ($level === 0) {
            $html = '<ul class="navbar-nav main-navigation w-100">';
        } else {
            $html = '<ul class="dropdown-menu dropdown-submenu">';
        }
        
        foreach ($items as $index => $item) {
            $hasChildren = !empty($item['items']) && count($item['items']) > 0;
            $url = $item['url'] == '#' ? '#' : Url::to($item['url']);
            $label = Html::encode($item['label']);
            $itemId = $item['id'] ?? 'menu_' . $index . '_' . uniqid();
            
            $isActive = $this->isMenuItemActive($item);
            $activeClass = $isActive ? ' active' : '';
            
            if ($hasChildren && $level < 2) {
                if ($level === 0) {
                    $html .= '
                    <li class="nav-item dropdown' . $activeClass . '">
                        <a href="' . $url . '" class="nav-link dropdown-toggle" 
                           id="navbarDropdown' . $itemId . '" 
                           role="button" data-bs-toggle="dropdown" 
                           aria-expanded="false">
                            <span class="menu-text">' . $label . '</span>
                        </a>';
                    
                    $html .= $this->renderNavbarMenu($item['items'], $level + 1, true);
                    $html .= '</li>';
                } else {
                    $html .= '
                    <li class="dropdown-submenu">
                        <a href="' . $url . '" class="dropdown-item dropdown-toggle">
                            ' . $label . '
                            <span class="submenu-indicator">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </a>';
                    
                    $html .= $this->renderNavbarMenu($item['items'], $level + 1, true);
                    $html .= '</li>';
                }
            } else {
                $liClass = $level === 0 ? 'nav-item' : '';
                $aClass = $level === 0 ? 'nav-link' . $activeClass : 'dropdown-item';
                
                $html .= '
                <li class="' . $liClass . $activeClass . '">
                    <a href="' . $url . '" class="' . $aClass . '">
                        ' . $label . '
                    </a>
                </li>';
            }
        }
        
        $html .= '</ul>';
        return $html;
    }

    protected function renderOffcanvasMenu($items, $level = 0)
    {
        if (empty($items)) {
            return '<div class="text-muted p-3">No hay elementos en el menú</div>';
        }
        
        $html = '<ul class="nav flex-column offcanvas-menu level-' . $level . '">';
        
        foreach ($items as $index => $item) {
            $hasChildren = !empty($item['items']) && count($item['items']) > 0;
            $url = $item['url'] == '#' ? '#' : Url::to($item['url']);
            $label = Html::encode($item['label']);
            $itemId = $item['id'] ?? 'oc_menu_' . $index . '_' . uniqid();
            
            $isActive = $this->isMenuItemActive($item);
            $activeClass = $isActive ? ' active' : '';
            
            $html .= '<li class="nav-item menu-item level-' . $level . $activeClass . '">';
            
            if ($hasChildren && $level < 3) {
                $html .= '
                    <a href="#" class="nav-link menu-link has-children' . $activeClass . '" 
                       data-level="' . $level . '" 
                       data-bs-toggle="collapse" 
                       data-bs-target="#submenu-' . $itemId . '"
                       aria-expanded="' . ($isActive ? 'true' : 'false') . '"
                       aria-controls="submenu-' . $itemId . '">
                        <span class="menu-icon">' . $this->getMenuItemIcon($item, $level) . '</span>
                        <span class="menu-text">' . $label . '</span>
                        <span class="submenu-indicator ms-auto">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    </a>
                    <div class="collapse' . ($isActive ? ' show' : '') . '" id="submenu-' . $itemId . '">
                        ' . $this->renderOffcanvasMenu($item['items'], $level + 1) . '
                    </div>';
            } else {
                $html .= '
                    <a href="' . $url . '" class="nav-link menu-link' . $activeClass . '" data-level="' . $level . '">
                        <span class="menu-icon">' . $this->getMenuItemIcon($item, $level) . '</span>
                        <span class="menu-text">' . $label . '</span>
                    </a>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        return $html;
    }

    protected function isMenuItemActive($item)
    {
        $currentRoute = Yii::$app->controller->route;
        $itemRoute = $item['route'] ?? '';
        
        if (!empty($itemRoute) && $itemRoute !== '#') {
            $normalizedCurrent = ltrim($currentRoute, '/');
            $normalizedItem = ltrim($itemRoute, '/');
            
            if ($normalizedCurrent === $normalizedItem) {
                return true;
            }
            
            $routeParts = explode('/', $normalizedItem);
            if (count($routeParts) >= 2) {
                $pattern = $routeParts[0] . '/' . $routeParts[1] . '/*';
                if (fnmatch($pattern, $normalizedCurrent)) {
                    return true;
                }
            }
        }
        
        if (!empty($item['items'])) {
            foreach ($item['items'] as $child) {
                if ($this->isMenuItemActive($child)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    protected function getMenuItemIcon($item, $level)
    {
        $defaultIcons = [
            0 => 'fa-home',
            1 => 'fa-folder',
            2 => 'fa-file-alt',
            3 => 'fa-circle',
        ];
        
        $iconMap = [
            'marketplace' => 'fa-store',
            'tienda' => 'fa-store',
            'shop' => 'fa-store',
            'comercio' => 'fa-store',
            'dashboard' => 'fa-tachometer-alt',
            'usuarios' => 'fa-users',
            'user' => 'fa-users',
            'perfil' => 'fa-user',
            'cuenta' => 'fa-user-cog',
            'config' => 'fa-cog',
            'settings' => 'fa-cog',
            'report' => 'fa-chart-bar',
            'estadística' => 'fa-chart-bar',
            'help' => 'fa-question-circle',
            'ayuda' => 'fa-question-circle',
            'logout' => 'fa-sign-out-alt',
            'salir' => 'fa-sign-out-alt',
            'login' => 'fa-sign-in-alt',
            'ingresar' => 'fa-sign-in-alt',
            'registro' => 'fa-user-plus',
            'signup' => 'fa-user-plus',
            'escuela' => 'fa-school',
            'deporte' => 'fa-running',
            'sports' => 'fa-running',
            'atleta' => 'fa-running',
            'calendar' => 'fa-calendar',
            'horario' => 'fa-calendar-alt',
            'schedule' => 'fa-calendar-alt',
            'pago' => 'fa-credit-card',
            'payment' => 'fa-credit-card',
            'finanza' => 'fa-money-bill-wave',
            'finance' => 'fa-money-bill-wave',
            'categoria' => 'fa-tags',
            'categoría' => 'fa-tags',
            'informacion' => 'fa-info-circle',
            'información' => 'fa-info-circle',
            'noticia' => 'fa-newspaper',
            'evento' => 'fa-calendar-check',
            'galeria' => 'fa-images',
            'galería' => 'fa-images',
            'herramientas' => 'fa-tools',
            'gestión' => 'fa-cogs',
            'gestor' => 'fa-cogs',
            'reportes' => 'fa-chart-pie',
            'estadisticas' => 'fa-chart-pie',
            'programador' => 'fa-code',
            'rbac' => 'fa-shield-alt',
            'setup' => 'fa-cogs',
            'gii' => 'fa-code',
            'inventario' => 'fa-boxes',
            'vitrina' => 'fa-store-alt',
            'asignación' => 'fa-tasks',
            'acceso' => 'fa-key',
        ];
        
        $itemName = strtolower($item['label'] ?? '');
        $itemRoute = $item['route'] ?? '';
        
        foreach ($iconMap as $keyword => $icon) {
            if (strpos($itemName, $keyword) !== false) {
                return '<i class="fas fa-fw ' . $icon . '"></i>';
            }
        }
        
        foreach ($iconMap as $keyword => $icon) {
            if (strpos($itemRoute, $keyword) !== false) {
                return '<i class="fas fa-fw ' . $icon . '"></i>';
            }
        }
        
        $iconClass = $defaultIcons[$level] ?? 'fa-circle';
        return '<i class="fas fa-fw ' . $iconClass . '"></i>';
    }

    protected function renderFallbackMenu()
    {
        $html = '
        <div class="fallback-menu">
            <ul class="nav flex-column offcanvas-menu">
                <li class="nav-item">
                    <a class="nav-link menu-link active" href="' . Url::to(['/']) . '">
                        <i class="fas fa-fw fa-home"></i>
                        <span class="menu-text">Inicio</span>
                    </a>
                </li>';
                
        $html .= '
                <li class="nav-item dropdown">
                    <a class="nav-link menu-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-fw fa-store"></i>
                        <span class="menu-text">Marketplace</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="' . Url::to(['/tienda/marketplace']) . '">Vitrina</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/tienda/producto/create']) . '">Inventario</a></li>
                    </ul>
                </li>';
                
        $html .= '
                <li class="nav-item dropdown">
                    <a class="nav-link menu-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-fw fa-running"></i>
                        <span class="menu-text">Deportes</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="' . Url::to(['/atletas/atletas-registro/create']) . '">Registrar Atleta</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/atletas/asistencia/registro-multiple']) . '">Asistencia</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/aportes/aportes/index']) . '">Aportes</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/reportes/reportes/atletas']) . '">Reportes</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="' . Url::to(['/site/about']) . '">
                        <i class="fas fa-fw fa-info-circle"></i>
                        <span class="menu-text">Acerca de</span>
                    </a>
                </li>';
                
        if (Yii::$app->user->isGuest) {
            $html .= '
                <li class="nav-item">
                    <a class="nav-link menu-link" href="' . Url::to(['/site/login']) . '">
                        <i class="fas fa-fw fa-sign-in-alt"></i>
                        <span class="menu-text">Iniciar Sesión</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="' . Url::to(['/site/signup']) . '">
                        <i class="fas fa-fw fa-user-plus"></i>
                        <span class="menu-text">Registrarse</span>
                    </a>
                </li>';
        } else {
            $html .= '
                <li class="nav-item dropdown">
                    <a class="nav-link menu-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-fw fa-tools"></i>
                        <span class="menu-text">Herramientas</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="' . Url::to(['/gii']) . '">Gii</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/admin/menu/index']) . '">Menús</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/admin/user/index']) . '">Usuarios</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/admin/role/index']) . '">Roles</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="' . Url::to(['/site/mi-cuenta']) . '">
                        <i class="fas fa-fw fa-user-cog"></i>
                        <span class="menu-text">Mi Cuenta</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="' . Url::to(['/site/logout']) . '" data-method="post">
                        <i class="fas fa-fw fa-sign-out-alt"></i>
                        <span class="menu-text">Cerrar Sesión</span>
                    </a>
                </li>';
        }
        
        $html .= '
            </ul>
        </div>';
        
        return $html;
    }
    
    public static function debugMenuStructure()
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
                ->orderBy('COALESCE(m."order", 99999) ASC')
                ->all();
            
            $result = [];
            foreach ($allMenus as $menu) {
                $result[] = $menu;
            }
            
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    public static function getAllMenus()
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
                ->orderBy('COALESCE(m.parent, \'\') ASC, COALESCE(m."order", 99999) ASC')
                ->all();
            
            return $allMenus;
        } catch (\Exception $e) {
            Yii::error('getAllMenus Error: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    public static function getSimpleMenu($parentId = null, $maxDepth = 3)
    {
        $instance = new self();
        $items = $instance->getMenuItems($parentId);
        
        $instance->limitMenuDepth($items, 0, $maxDepth);
        
        return $items;
    }
    
    protected function limitMenuDepth(&$items, $currentDepth, $maxDepth)
    {
        if ($currentDepth >= $maxDepth) {
            foreach ($items as &$item) {
                $item['items'] = [];
            }
            return;
        }
        
        foreach ($items as &$item) {
            if (!empty($item['items'])) {
                $this->limitMenuDepth($item['items'], $currentDepth + 1, $maxDepth);
            }
        }
    }
    
    public static function forceReload()
    {
        if (Yii::$app->cache) {
            Yii::$app->cache->delete('menu_widget_cache');
        }
        
        return self::getAllMenus();
    }
}