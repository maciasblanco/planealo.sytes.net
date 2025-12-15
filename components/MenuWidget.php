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
     * ✅ OBTENER ITEMS DEL MENÚ - CORREGIDO PARA USUARIOS INVITADOS
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
            
            $route = $item['route'] ?? '';
            
            // ✅ NUEVA LÓGICA: PRIMERO VERIFICAR SI ES RUTA PÚBLICA (PARA TODOS LOS USUARIOS)
            if (!empty($route) && $route !== '#') {
                if ($this->isPublicRoute($route)) {
                    echo '<!-- Item es PÚBLICO por ruta -->' . "\n";
                    $childItems = $this->getMenuItems($item['id']);
                    
                    $menuItem = [
                        'id' => $item['id'],
                        'label' => $item['name'],
                        'url' => [$route],
                        'items' => $childItems,
                        'visible' => true,
                        'route' => $route
                    ];
                    
                    $menuItems[] = $menuItem;
                    continue;
                }
            }
            
            // ✅ SI LLEGA AQUÍ, LA RUTA NO ES PÚBLICA O ES UN CONTENEDOR
            // PARA USUARIOS GUEST, MOSTRAR SOLO SI ES CONTENEDOR CON HIJOS PÚBLICOS
            if (Yii::$app->user->isGuest) {
                if (empty($route) || $route === '#') {
                    echo '<!-- Guest: Es contenedor, verificar hijos -->' . "\n";
                    $childItems = $this->getMenuItems($item['id']);
                    // Solo mostrar contenedores que tengan al menos un hijo público
                    $hasPublicChild = false;
                    foreach ($childItems as $child) {
                        if (!empty($child['route']) && $this->isPublicRoute($child['route'])) {
                            $hasPublicChild = true;
                            break;
                        }
                    }
                    
                    if ($hasPublicChild || !empty($childItems)) {
                        $menuItem = [
                            'id' => $item['id'],
                            'label' => $item['name'],
                            'url' => '#',
                            'items' => $childItems,
                            'visible' => true,
                            'route' => $route
                        ];
                        $menuItems[] = $menuItem;
                    }
                }
                continue;
            }
            
            // ✅ PARA USUARIOS AUTENTICADOS: VERIFICAR PERMISOS RBAC
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
                'visible' => true,
                'route' => $route
            ];

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }

    /**
     * ✅ VERIFICAR PERMISOS RBAC - CORREGIDO PARA USUARIOS INVITADOS
     */
    protected function checkMenuItemPermission($item)
    {
        $route = $item['route'] ?? '';
        
        // ✅ MODIFICACIÓN: SI ES GUEST Y LA RUTA ES PÚBLICA, PERMITIR
        if (Yii::$app->user->isGuest) {
            if (!empty($route) && $route !== '#' && $this->isPublicRoute($route)) {
                echo '<!-- checkMenuItemPermission: Guest, ruta pública permitida -->' . "\n";
                return true;
            }
            echo '<!-- checkMenuItemPermission: Usuario guest, ruta no pública -->' . "\n";
            return false;
        }
        
        // Si no hay ruta definida, es un contenedor
        if (empty($route) || $route == '#') {
            echo '<!-- checkMenuItemPermission: Es contenedor sin ruta -->' . "\n";
            return true;
        }

        try {
            // ✅ VERIFICAR RUTAS PÚBLICAS (también para usuarios autenticados)
            if ($this->isPublicRoute($route)) {
                echo '<!-- checkMenuItemPermission: Ruta pública -->' . "\n";
                return true;
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
     * ✅ VERIFICAR SI UNA RUTA ES PÚBLICA - EXPANDIDO PARA USUARIOS INVITADOS
     */
    protected function isPublicRoute($route)
    {
        $publicRoutes = [
            // ✅ SITIO PÚBLICO
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
            
            // ✅ MARKETPLACE Y TIENDA (PÚBLICO)
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
            
            // ✅ GED - RUTAS PÚBLICAS (VISUALIZACIÓN)
            'ged/default/index',
            'ged/escuela/*',
            'ged/escuela/ver',
            'ged/escuela/listar',
            'ged/escuela/buscar',
            'ged/deporte/*',
            'ged/deporte/ver',
            'ged/deporte/listar',
            'ged/categoria/*',
            'ged/categoria/ver',
            'ged/*',
            
            // ✅ ADMIN - RUTAS PÚBLICAS DE USUARIO
            'admin/user/signup',
            'admin/user/request-password-reset', 
            'admin/user/reset-password',
            
            // ✅ API Y CONSULTAS PÚBLICAS
            'municipio/get-by-edo',
            'parroquia/get-by-muni',
            'parroquia/get-by-muni-cod',
            'api/*',
            
            // ✅ PÁGINAS DE INFORMACIÓN
            'informacion/*',
            'catalogo/*',
            'galeria/*',
            'noticia/*',
            'evento/*',
            
            // ✅ RUTAS ESPECÍFICAS PARA INVITADOS
            'escuela/ver',
            'escuela/listar',
            'deporte/ver',
            'deporte/listar',
            'categoria/ver',
            'categoria/listar',
            'horario/ver',
            'horario/listar',
        ];

        // Verificación exacta
        if (in_array($route, $publicRoutes)) {
            echo '<!-- isPublicRoute: Ruta exacta encontrada en lista pública -->' . "\n";
            return true;
        }

        // Verificación por patrón con comodines
        foreach ($publicRoutes as $publicRoute) {
            if (strpos($publicRoute, '*') !== false) {
                $pattern = preg_quote($publicRoute, '/');
                $pattern = str_replace('\*', '.*', $pattern);
                $pattern = '/^' . $pattern . '$/';
                
                if (preg_match($pattern, $route)) {
                    echo '<!-- isPublicRoute: Coincide con patrón: ' . $publicRoute . ' -->' . "\n";
                    return true;
                }
            }
        }

        // Verificación por palabras clave públicas
        $publicKeywords = [
            'ver', 'listar', 'buscar', 'index', 'catalogo', 'galeria',
            'informacion', 'noticia', 'evento', 'publico', 'marketplace'
        ];
        
        $routeLower = strtolower($route);
        foreach ($publicKeywords as $keyword) {
            if (strpos($routeLower, $keyword) !== false) {
                echo '<!-- isPublicRoute: Contiene palabra clave pública: ' . $keyword . ' -->' . "\n";
                return true;
            }
        }

        echo '<!-- isPublicRoute: Ruta NO es pública: ' . $route . ' -->' . "\n";
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
            'categoria' => 'fa-tags',
            'categoría' => 'fa-tags',
            'informacion' => 'fa-info-circle',
            'información' => 'fa-info-circle',
            'noticia' => 'fa-newspaper',
            'evento' => 'fa-calendar-check',
            'galeria' => 'fa-images',
            'galería' => 'fa-images',
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
            
        // ✅ MOSTRAR MÁS OPCIONES PÚBLICAS EN FALLBACK
        $html .= '
            <li class="nav-item">
                <a class="nav-link menu-link" href="' . Url::to(['/ged/escuela/listar']) . '">
                    <i class="fas fa-fw fa-school"></i>
                    <span class="menu-text">Escuelas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link menu-link" href="' . Url::to(['/ged/deporte/listar']) . '">
                    <i class="fas fa-fw fa-running"></i>
                    <span class="menu-text">Deportes</span>
                </a>
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