<?php

namespace app\components;

use yii\base\Widget;
use yii\db\Query;
use yii\db\Expression;
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

    public function getMenuItems($parentId = null)
    {
        try {
            $db = Yii::$app->db;
            if (!$db) {
                return [];
            }
            
            $query = new Query();
            
            if ($parentId === null) {
                $query->where(['parent' => null]);
            } else {
                $query->where(['parent' => $parentId]);
            }
            
            $query->select([
                'id', 
                'name', 
                'route', 
                'parent', 
                '"order" as menu_order',
                'data'
            ])
            ->from('seguridad.menu')
            ->orderBy(new Expression('COALESCE("order", 99999) ASC'));
            
            $items = $query->all();
            
        } catch (\Exception $e) {
            Yii::error('MenuWidget DB Error: ' . $e->getMessage(), __METHOD__);
            return [];
        }

        $menuItems = [];

        foreach ($items as $item) {
            $route = $item['route'] ?? '';
            $name = $item['name'] ?? '';
            $id = $item['id'] ?? '';
            $data = $item['data'] ?? '{}';
            
            // Verificar visibilidad basada en autenticación y datos JSON
            if (!$this->isMenuItemVisible($item)) {
                continue;
            }
            
            $childItems = $this->getMenuItems($id);
            
            $url = '#';
            if (!empty($route) && $route !== '#') {
                $url = [$route];
            }
            
            $menuItem = [
                'id' => $id,
                'label' => $name,
                'url' => $url,
                'items' => $childItems,
                'visible' => true,
                'route' => $route,
                'data' => $data
            ];

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }

    /**
     * Determina si un ítem de menú debe mostrarse basado en:
     * 1. Estado de autenticación del usuario
     * 2. Configuración en el campo 'data' (JSON)
     * 3. Permisos RBAC (para usuarios autenticados)
     */
    private function isMenuItemVisible($item)
    {
        $route = $item['route'] ?? '';
        $data = $item['data'] ?? '{}';
        
        // Extraer configuración del campo data
        $isPublic = false;
        $icon = '';
        
        if ($data !== '{}') {
            try {
                $dataArray = json_decode($data, true);
                $isPublic = isset($dataArray['public']) && $dataArray['public'] === true;
                $icon = $dataArray['icon'] ?? '';
            } catch (\Exception $e) {
                // Si hay error en el JSON, considerar como no público
                $isPublic = false;
            }
        }
        
        // Usuarios invitados: solo pueden ver ítems públicos
        if (Yii::$app->user->isGuest) {
            return $isPublic;
        }
        
        // Usuarios autenticados:
        // 1. Si es público, siempre visible
        if ($isPublic) {
            return true;
        }
        
        // 2. Si no es público, verificar permisos RBAC
        if (!empty($route) && $route !== '#') {
            $normalizedRoute = $this->normalizeRoute($route);
            
            // Verificar si el usuario tiene permiso para esta ruta
            // o tiene permiso global (/*)
            if (Yii::$app->user->can($normalizedRoute) || Yii::$app->user->can('/*')) {
                return true;
            }
        }
        
        // 3. Si no tiene ruta específica (es un contenedor), mostrar si tiene hijos visibles
        // Esto se maneja recursivamente en getMenuItems()
        
        return false;
    }

    /**
     * Normaliza una ruta para verificación de permisos RBAC
     */
    private function normalizeRoute($route)
    {
        // Remover slash inicial si existe
        $route = ltrim($route, '/');
        
        // Para permisos RBAC, usar la ruta tal cual
        return $route;
    }

    public function renderNavbarMenu($items, $level = 0, $isDropdown = false)
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

    public function renderOffcanvasMenu($items, $level = 0)
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

    public function isMenuItemActive($item)
    {
        if (!isset(Yii::$app->controller)) {
            return false;
        }
        
        $currentRoute = Yii::$app->controller->route;
        $itemRoute = $item['route'] ?? '';
        
        if (!empty($itemRoute) && $itemRoute !== '#') {
            $normalizedCurrent = ltrim($currentRoute, '/');
            $normalizedItem = ltrim($itemRoute, '/');
            
            // Verificación exacta
            if ($normalizedCurrent === $normalizedItem) {
                return true;
            }
            
            // Verificar si la ruta actual comienza con la ruta del ítem
            // (para manejar subrutas como "tienda/producto/ver" bajo "tienda/producto")
            if (strpos($normalizedCurrent . '/', $normalizedItem . '/') === 0) {
                return true;
            }
        }
        
        return false;
    }

    public function getMenuItemIcon($item, $level)
    {
        $itemData = $item['data'] ?? '{}';
        
        if ($itemData !== '{}') {
            try {
                $dataArray = json_decode($itemData, true);
                if (isset($dataArray['icon'])) {
                    return '<i class="fas fa-fw ' . $dataArray['icon'] . '"></i>';
                }
            } catch (\Exception $e) {
                // Ignorar error
            }
        }
        
        // Iconos por defecto basados en nivel
        $defaultIcons = [
            0 => 'fa-home',      // Nivel raíz
            1 => 'fa-folder',    // Primer nivel anidado
            2 => 'fa-file-alt',  // Segundo nivel anidado
            3 => 'fa-circle',    // Tercer nivel y superiores
        ];
        
        $iconClass = $defaultIcons[$level] ?? 'fa-circle';
        return '<i class="fas fa-fw ' . $iconClass . '"></i>';
    }

    public function renderFallbackMenu()
    {
        $html = '
        <div class="fallback-menu">
            <ul class="navbar-nav main-navigation w-100">
                <li class="nav-item">
                    <a class="nav-link" href="' . Url::to(['/']) . '">
                        <i class="fas fa-fw fa-home"></i> Inicio
                    </a>
                </li>';
                
        $html .= '
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-fw fa-store"></i> Marketplace
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="' . Url::to(['/tienda/marketplace']) . '">Vitrina</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/tienda/producto/create']) . '">Inventario</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/tienda/marketplace/buscar']) . '">Buscar Productos</a></li>
                    </ul>
                </li>';
                
        $html .= '
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-fw fa-running"></i> Deportes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="' . Url::to(['/ged/default/index']) . '">GED Sistema</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/atletas/atletas-registro/create']) . '">Registrar Atleta</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/atletas/asistencia/registro-multiple']) . '">Asistencia</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/aportes/aportes/index']) . '">Aportes</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="' . Url::to(['/site/about']) . '">
                        <i class="fas fa-fw fa-info-circle"></i> Acerca de
                    </a>
                </li>';
                
        if (Yii::$app->user->isGuest) {
            $html .= '
                <li class="nav-item">
                    <a class="nav-link" href="' . Url::to(['/site/login']) . '">
                        <i class="fas fa-fw fa-sign-in-alt"></i> Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="' . Url::to(['/site/signup']) . '">
                        <i class="fas fa-fw fa-user-plus"></i> Registro
                    </a>
                </li>';
        } else {
            $html .= '
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-fw fa-tools"></i> Herramientas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="' . Url::to(['/gii']) . '">Gii</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/admin/menu/index']) . '">Menús</a></li>
                        <li><a class="dropdown-item" href="' . Url::to(['/admin/user/index']) . '">Usuarios</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="' . Url::to(['/site/mi-cuenta']) . '">
                        <i class="fas fa-fw fa-user-cog"></i> Mi Cuenta
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="' . Url::to(['/site/logout']) . '" data-method="post">
                        <i class="fas fa-fw fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>';
        }
        
        $html .= '
            </ul>
        </div>';
        
        return $html;
    }
    
    /**
     * Método estático para obtener estructura del menú (para debug)
     */
    public static function debugMenuStructure()
    {
        try {
            $query = new Query();
            $allMenus = $query->select([
                    'id', 
                    'name', 
                    'route', 
                    'parent', 
                    '"order" as menu_order',
                    'data'
                ])
                ->from('seguridad.menu')
                ->orderBy(new Expression('COALESCE("order", 99999) ASC'))
                ->all();
            
            return $allMenus;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Método estático para forzar recarga del menú
     */
    public static function forceReload()
    {
        // Eliminar caché si existe
        if (Yii::$app->cache) {
            $cacheKey = 'menu_cache_' . (Yii::$app->user->isGuest ? 'guest' : Yii::$app->user->id);
            Yii::$app->cache->delete($cacheKey);
        }
    }
    
    /**
     * Método para debug del menú - muestra todos los ítems con su estado
     */
    public static function debugMenuData()
    {
        try {
            $db = Yii::$app->db;
            
            // Ver todos los ítems
            $query = new Query();
            $allItems = $query->select(['id', 'name', 'route', 'parent', '"order"', 'data'])
                ->from('seguridad.menu')
                ->orderBy(new Expression('COALESCE("order", 99999) ASC'))
                ->all();
            
            $output = "<h3>Todos los ítems en BD (" . count($allItems) . ")</h3>";
            $output .= "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Ruta</th><th>Parent</th><th>Orden</th><th>Data (JSON)</th><th>¿Público?</th></tr>";
            
            foreach ($allItems as $item) {
                $data = $item['data'] ?? '{}';
                $isPublic = false;
                $icon = '';
                
                if ($data !== '{}' && !empty($data)) {
                    try {
                        $dataArray = json_decode($data, true);
                        $isPublic = isset($dataArray['public']) && $dataArray['public'] === true;
                        $icon = $dataArray['icon'] ?? '';
                    } catch (\Exception $e) {
                        $isPublic = false;
                    }
                }
                
                $output .= "<tr>";
                $output .= "<td>{$item['id']}</td>";
                $output .= "<td>{$item['name']}</td>";
                $output .= "<td>" . ($item['route'] ?? '') . "</td>";
                $output .= "<td>" . ($item['parent'] ?: 'Raíz') . "</td>";
                $output .= "<td>" . ($item['order'] ?? '') . "</td>";
                $output .= "<td><pre>" . htmlspecialchars($data) . "</pre></td>";
                $output .= "<td>" . ($isPublic ? '✅ Sí' . ($icon ? " (icon: $icon)" : '') : '❌ No') . "</td>";
                $output .= "</tr>";
            }
            $output .= "</table>";
            
            return $output;
            
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}