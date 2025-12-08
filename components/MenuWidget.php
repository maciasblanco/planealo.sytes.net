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

    public function init()
    {
        parent::init();
        
        // ✅ DETECCIÓN SIMPLIFICADA
        $this->mobileMode = false;
        
        if (isset($this->options['mobileMode'])) {
            $this->mobileMode = (bool)$this->options['mobileMode'];
        }
        
        if (isset($this->options['class'])) {
            $this->menuClass = $this->options['class'];
        }
    }

    public function run()
    {
        try {
            // ✅ DEBUG VISIBLE
            echo '<!-- MenuWidget - parentId: ' . ($this->parentId ?: 'null') . ' -->' . "\n";
            
            $menuItems = $this->getMenuItems($this->parentId);
            
            if (empty($menuItems)) {
                echo '<!-- MenuWidget - No items, usando fallback -->' . "\n";
                return $this->renderFallbackMenu();
            }
            
            echo '<!-- MenuWidget - Items encontrados: ' . count($menuItems) . ' -->' . "\n";
            
            // ✅ SIEMPRE USAR EL NUEVO MÉTODO OFF-CANVAS UNIFICADO
            return $this->renderOffcanvasMenu($menuItems);
            
        } catch (\Exception $e) {
            echo '<!-- MenuWidget ERROR: ' . htmlspecialchars($e->getMessage()) . ' -->' . "\n";
            return $this->renderFallbackMenu();
        }
    }

    /**
     * ✅ OBTENER ITEMS DEL MENÚ - CORREGIDO PARA POSTGRESQL
     */
    protected function getMenuItems($parentId = null)
    {
        try {
            $db = Yii::$app->db;
            if (!$db || $db->getIsActive() === false) {
                echo '<!-- MenuWidget - Base de datos no disponible -->' . "\n";
                return [];
            }
            
            // ✅ CONSULTA CORREGIDA PARA POSTGRESQL (ESCAPAR "order")
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
            ->orderBy('m."order" ASC');
            
            $items = $query->all();
            
            echo '<!-- MenuWidget - Consulta ejecutada. Items: ' . count($items) . ' -->' . "\n";
            
        } catch (\Exception $e) {
            echo '<!-- MenuWidget DB ERROR: ' . htmlspecialchars($e->getMessage()) . ' -->' . "\n";
            return [];
        }

        $menuItems = [];

        foreach ($items as $item) {
            echo '<!-- Procesando item: ID=' . $item['id'] . ', Nombre=' . $item['name'] . ', Ruta=' . ($item['route'] ?: 'null') . ' -->' . "\n";
            
            // ✅ VERIFICAR SI ES UN MENÚ PÚBLICO (MARKETPLACE)
            $isPublic = $this->isPublicMenuItem($item);
            
            if ($isPublic) {
                echo '<!-- Item es PÚBLICO -->' . "\n";
                $childItems = $this->getMenuItems($item['id']);
                
                $menuItem = [
                    'id' => $item['id'],
                    'label' => $item['name'],
                    'url' => $item['route'] ? [$item['route']] : '#',
                    'items' => $childItems,
                    'visible' => true
                ];

                $menuItems[] = $menuItem;
                continue;
            }
            
            // ✅ PARA USUARIOS NO REGISTRADOS, SOLO MOSTRAR MENÚS PÚBLICOS
            if (Yii::$app->user->isGuest) {
                echo '<!-- Usuario es guest, omitiendo item no público -->' . "\n";
                continue;
            }
            
            // ✅ VERIFICAR PERMISOS RBAC PARA MENÚS NO PÚBLICOS (solo usuarios logueados)
            $hasPermission = $this->checkMenuItemPermission($item);
            
            if (!$hasPermission) {
                echo '<!-- Item SIN permiso -->' . "\n";
                continue;
            }

            $childItems = $this->getMenuItems($item['id']);
            
            $menuItem = [
                'id' => $item['id'],
                'label' => $item['name'],
                'url' => $item['route'] ? [$item['route']] : '#',
                'items' => $childItems,
                'visible' => true
            ];

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }

    /**
     * ✅ VERIFICAR SI UN ITEM DEL MENÚ ES PÚBLICO - MEJORADO
     */
    protected function isPublicMenuItem($item)
    {
        // IDs de menús públicos conocidos
        $publicMenuIds = [177];
        
        if (isset($item['id']) && in_array($item['id'], $publicMenuIds)) {
            return true;
        }
        
        // Verificar por nombre o ruta
        $itemName = strtolower($item['name'] ?? '');
        $itemRoute = $item['route'] ?? '';
        
        echo '<!-- isPublicMenuItem: Nombre="' . $itemName . '", Ruta="' . $itemRoute . '" -->' . "\n";
        
        // Si el nombre contiene palabras clave de marketplace
        $marketplaceKeywords = ['market', 'tienda', 'comercio', 'shop', 'store', 'marketplace'];
        foreach ($marketplaceKeywords as $keyword) {
            if (strpos($itemName, $keyword) !== false) {
                echo '<!-- Encontrada palabra clave marketplace: ' . $keyword . ' -->' . "\n";
                return true;
            }
        }
        
        // Si la ruta pertenece al módulo tienda o marketplace
        $publicRoutePatterns = ['tienda/', 'marketplace', 'shop/', 'store/', 'tienda/default', 'tienda/marketplace'];
        foreach ($publicRoutePatterns as $pattern) {
            if (strpos($itemRoute, $pattern) === 0 || strpos($itemRoute, $pattern) !== false) {
                echo '<!-- Ruta pública detectada por patrón: ' . $pattern . ' -->' . "\n";
                return true;
            }
        }
        
        // Verificar si la ruta es pública usando el método isPublicRoute
        if (!empty($itemRoute) && $itemRoute !== '#' && $this->isPublicRoute($itemRoute)) {
            echo '<!-- Ruta marcada como pública en isPublicRoute -->' . "\n";
            return true;
        }
        
        return false;
    }

    /**
     * ✅ VERIFICAR PERMISOS RBAC - SIMPLIFICADO
     */
    protected function checkMenuItemPermission($item)
    {
        // ✅ PRIMERO VERIFICAR SI ES UN MENÚ PÚBLICO
        if ($this->isPublicMenuItem($item)) {
            echo '<!-- checkMenuItemPermission: Item es público -->' . "\n";
            return true;
        }
        
        // Si no hay ruta definida, es un contenedor
        if (empty($item['route']) || $item['route'] == '#') {
            echo '<!-- checkMenuItemPermission: Es contenedor sin ruta -->' . "\n";
            return true;
        }

        try {
            $route = $item['route'];
            
            // ✅ VERIFICAR RUTAS PÚBLICAS
            if ($this->isPublicRoute($route)) {
                echo '<!-- checkMenuItemPermission: Ruta pública -->' . "\n";
                return true;
            }
            
            // ✅ SI ES USUARIO GUEST, NO PUEDE ACCEDER A RUTAS NO PÚBLICAS
            if (Yii::$app->user->isGuest) {
                echo '<!-- checkMenuItemPermission: Usuario guest, ruta no pública -->' . "\n";
                return false;
            }

            // ✅ VERIFICAR PERMISO CON RBAC
            if (Yii::$app->user->can($route)) {
                echo '<!-- checkMenuItemPermission: Tiene permiso RBAC -->' . "\n";
                return true;
            }
            
            // ✅ VERIFICAR POR PATRÓN
            $routeParts = explode('/', $route);
            if (count($routeParts) >= 2) {
                $modulePattern = $routeParts[0] . '/*';
                if (Yii::$app->user->can($modulePattern)) {
                    echo '<!-- checkMenuItemPermission: Tiene permiso por patrón módulo -->' . "\n";
                    return true;
                }
                
                if (count($routeParts) >= 2) {
                    $controllerPattern = $routeParts[0] . '/' . $routeParts[1] . '/*';
                    if (Yii::$app->user->can($controllerPattern)) {
                        echo '<!-- checkMenuItemPermission: Tiene permiso por patrón controlador -->' . "\n";
                        return true;
                    }
                }
            }

            // ✅ VERIFICAR ROLES DE ADMINISTRADOR
            $adminRoles = ['admin', 'administrator', 'superadmin'];
            foreach ($adminRoles as $role) {
                if (Yii::$app->user->can($role)) {
                    echo '<!-- checkMenuItemPermission: Tiene rol de administrador -->' . "\n";
                    return true;
                }
            }

            echo '<!-- checkMenuItemPermission: NO tiene permiso -->' . "\n";
            return false;

        } catch (\Exception $e) {
            echo '<!-- checkMenuItemPermission ERROR: ' . htmlspecialchars($e->getMessage()) . ' -->' . "\n";
            return false;
        }
    }

    /**
     * ✅ VERIFICAR SI UNA RUTA ES PÚBLICA - EXPANDIDO
     */
    protected function isPublicRoute($route)
    {
        $publicRoutes = [
            'site/index',
            'site/login',
            'site/logout',
            'site/error',
            'site/about',
            'site/contact',
            'site/signup',
            'site/request-password-reset',
            'site/reset-password',
            'admin/user/signup',
            'admin/user/request-password-reset', 
            'admin/user/reset-password',
            'ged/*',
            'site/*',
            
            // ✅ TODAS LAS RUTAS DEL MARKETPLACE Y TIENDA
            'tienda/*',
            'tienda/marketplace/*',
            'tienda/marketplace/index',
            'tienda/marketplace/buscar',
            'tienda/marketplace/categoria',
            'tienda/marketplace/producto',
            'tienda/marketplace/detalle',
            'tienda/default/*',
            'tienda/default/index',
            'tienda/default/registro-vendedor',
            'tienda/default/dashboard-vendedor',
            'tienda/default/carrito',
            'tienda/default/checkout',
            'marketplace/*',
            'shop/*',
            'store/*',
            
            // Otras rutas públicas
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
            'parroquia/get-by-muni-cod',
        ];

        if (in_array($route, $publicRoutes)) {
            return true;
        }

        foreach ($publicRoutes as $publicRoute) {
            if (strpos($publicRoute, '*') !== false) {
                $pattern = preg_quote($publicRoute, '/');
                $pattern = str_replace('\*', '.*', $pattern);
                if (preg_match('/^' . $pattern . '$/', $route)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ✅ NUEVO MÉTODO: RENDERIZAR MENÚ PARA OFF-CANVAS UNIFICADO
     * Soporta hasta 3 niveles usando Bootstrap Collapse
     */
    protected function renderOffcanvasMenu($items, $level = 0)
    {
        if (empty($items)) {
            return '<div class="text-muted p-3">No hay elementos en el menú</div>';
        }
        
        $html = '<ul class="nav flex-column offcanvas-menu level-' . $level . '">';
        
        foreach ($items as $item) {
            $hasChildren = !empty($item['items']);
            $url = $item['url'] == '#' ? '#' : Url::to($item['url']);
            $label = Html::encode($item['label']);
            $itemId = $item['id'] ?? uniqid('menu_', true);
            
            // Determinar si el item está activo (ruta actual)
            $isActive = $this->isMenuItemActive($item);
            $activeClass = $isActive ? ' active' : '';
            
            $html .= '<li class="nav-item menu-item level-' . $level . $activeClass . '">';
            
            if ($hasChildren) {
                // ✅ Elemento con hijos - Niveles 0, 1 y 2 (máximo 3 niveles)
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
                // ✅ Elemento sin hijos
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

    /**
     * ✅ VERIFICAR SI UN ITEM DEL MENÚ ESTÁ ACTIVO (PÁGINA ACTUAL)
     */
    protected function isMenuItemActive($item)
    {
        $currentRoute = Yii::$app->controller->route;
        
        // Si el item tiene una ruta específica
        if (!empty($item['route']) && $item['route'] !== '#') {
            // Comparar rutas exactas
            if ($currentRoute === $item['route']) {
                return true;
            }
            
            // Comparar por patrón (ej: "ged/*")
            $routeParts = explode('/', $item['route']);
            if (count($routeParts) >= 2) {
                // Patrón de módulo/controlador
                $pattern = $routeParts[0] . '/' . $routeParts[1] . '/*';
                if (fnmatch($pattern, $currentRoute)) {
                    return true;
                }
            }
        }
        
        // Verificar recursivamente en hijos
        if (!empty($item['items'])) {
            foreach ($item['items'] as $child) {
                if ($this->isMenuItemActive($child)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * ✅ OBTENER ICONO DEL ITEM DEL MENÚ POR NIVEL
     */
    protected function getMenuItemIcon($item, $level)
    {
        // Iconos por nivel y tipo de menú
        $defaultIcons = [
            0 => 'fa-home',           // Nivel 0 - Principal
            1 => 'fa-folder',         // Nivel 1 - Categoría
            2 => 'fa-file-alt',       // Nivel 2 - Subcategoría
            3 => 'fa-circle',         // Nivel 3 - Item final
        ];
        
        // Iconos específicos por palabras clave
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
        ];
        
        $itemName = strtolower($item['label'] ?? '');
        $itemRoute = $item['route'] ?? '';
        
        // Buscar icono por palabra clave en el nombre
        foreach ($iconMap as $keyword => $icon) {
            if (strpos($itemName, $keyword) !== false) {
                return '<i class="fas fa-fw ' . $icon . '"></i>';
            }
        }
        
        // Buscar icono por palabra clave en la ruta
        foreach ($iconMap as $keyword => $icon) {
            if (strpos($itemRoute, $keyword) !== false) {
                return '<i class="fas fa-fw ' . $icon . '"></i>';
            }
        }
        
        // Icono por defecto según nivel
        $iconClass = $defaultIcons[$level] ?? 'fa-circle';
        return '<i class="fas fa-fw ' . $iconClass . '"></i>';
    }

    /**
     * ✅ MENÚ DE RESPALDO (FALLBACK)
     */
    protected function renderFallbackMenu()
    {
        $html = '
        <ul class="nav flex-column offcanvas-menu">
            <li class="nav-item">
                <a class="nav-link menu-link active" href="' . Url::to(['/']) . '">
                    <i class="fas fa-fw fa-home"></i>
                    <span class="menu-text">Inicio</span>
                </a>
            </li>';
            
        // ✅ SIEMPRE MOSTRAR MARKETPLACE EN EL FALLBACK
        $html .= '
            <li class="nav-item">
                <a class="nav-link menu-link" href="' . Url::to(['/tienda/marketplace']) . '">
                    <i class="fas fa-fw fa-store"></i>
                    <span class="menu-text">Marketplace</span>
                </a>
            </li>';
            
        if (Yii::$app->user->isGuest) {
            $html .= '
            <li class="nav-item">
                <a class="nav-link menu-link" href="' . Url::to(['/site/login']) . '">
                    <i class="fas fa-fw fa-sign-in-alt"></i>
                    <span class="menu-text">Iniciar Sesión</span>
                </a>
            </li>';
        } else {
            $html .= '
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
        
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * ✅ MÉTODO PARA VERIFICAR ESTRUCTURA DEL MENÚ (POSTGRESQL)
     */
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
                ->orderBy('m."order" ASC')
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
    
    /**
     * ✅ MÉTODO PARA VER TODOS LOS MENÚS DISPONIBLES
     */
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
                ->orderBy('m.parent ASC, m."order" ASC')
                ->all();
            
            echo '<!-- === TODOS LOS MENÚS EN LA BD === -->' . "\n";
            foreach ($allMenus as $menu) {
                echo '<!-- ID: ' . $menu['id'] . ' | Nombre: ' . $menu['name'] . ' | Ruta: ' . ($menu['route'] ?: 'null') . ' | Parent: ' . ($menu['parent'] ?: 'null') . ' -->' . "\n";
            }
            echo '<!-- === FIN MENÚS === -->' . "\n";
            
            return $allMenus;
        } catch (\Exception $e) {
            echo '<!-- ERROR obteniendo menús: ' . htmlspecialchars($e->getMessage()) . ' -->' . "\n";
            return [];
        }
    }

    /**
     * ✅ MÉTODO PARA GENERAR MENÚ SIMPLIFICADO (para uso en otras partes)
     */
    public static function getSimpleMenu($parentId = null, $maxDepth = 3)
    {
        $instance = new self();
        $items = $instance->getMenuItems($parentId);
        
        // Limitar profundidad
        $instance->limitMenuDepth($items, 0, $maxDepth);
        
        return $items;
    }
    
    /**
     * ✅ LIMITAR PROFUNDIDAD DEL MENÚ
     */
    protected function limitMenuDepth(&$items, $currentDepth, $maxDepth)
    {
        if ($currentDepth >= $maxDepth) {
            foreach ($items as &$item) {
                $item['items'] = []; // Eliminar hijos si superamos la profundidad máxima
            }
            return;
        }
        
        foreach ($items as &$item) {
            if (!empty($item['items'])) {
                $this->limitMenuDepth($item['items'], $currentDepth + 1, $maxDepth);
            }
        }
    }
}