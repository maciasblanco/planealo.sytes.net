<?php
namespace app\components;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Menu;

/**
 * Widget de menú dinámico basado en la tabla seguridad.menu
 * Compatible con RBAC y estructura jerárquica completa
 */
class MenuWidget extends Widget
{
    public $options = [];
    public $mobileMode = false;
    public $rootOnly = false;
    
    private $menuItems = [];
    private $userPermissions = [];
    
    public function init()
    {
        parent::init();
        $this->loadUserPermissions();
        $this->loadMenuItems();
    }
    
    public function run()
    {
        $filteredItems = $this->filterItemsByPermission($this->menuItems);
        
        if (empty($filteredItems)) {
            return '';
        }
        
        return $this->renderMenu($filteredItems);
    }
    
    /**
     * Carga permisos del usuario actual
     */
    private function loadUserPermissions()
    {
        if (Yii::$app->user->isGuest) {
            $this->userPermissions = [];
            return;
        }
        
        $auth = Yii::$app->authManager;
        $userId = Yii::$app->user->id;
        $username = Yii::$app->user->identity->username;
        
        Yii::info("Cargando permisos para usuario ID: $userId, Username: $username", 'menu');
        
        // ACCESO TOTAL para usuarios con cualquiera de estos roles
        $superuserRoles = ['admin', 'administrador', 'superusuario'];
        
        // Verificar si tiene alguno de los roles de superusuario
        foreach ($superuserRoles as $role) {
            if ($this->userHasRole($userId, $role)) {
                Yii::info("Usuario tiene rol superusuario: $role - Otorgando acceso total", 'menu');
                $this->userPermissions = ['*']; // Permiso especial para superusuarios
                return;
            }
        }
        
        // Para usuario ID=1 (por si acaso)
        if ($userId == 1) {
            Yii::info("Usuario ID=1 - Otorgando acceso total", 'menu');
            $this->userPermissions = ['*'];
            return;
        }
        
        Yii::info("Usuario no tiene rol superusuario. Obteniendo permisos específicos...", 'menu');
        
        // Obtener permisos directos
        $directPermissions = $auth->getPermissionsByUser($userId);
        Yii::info("Permisos directos: " . count($directPermissions), 'menu');
        
        // Obtener permisos a través de roles
        $userRoles = $auth->getRolesByUser($userId);
        Yii::info("Roles del usuario: " . implode(', ', array_keys($userRoles)), 'menu');
        
        $rolePermissions = [];
        foreach ($userRoles as $role) {
            $permissions = $auth->getPermissionsByRole($role->name);
            $rolePermissions = array_merge($rolePermissions, $permissions);
            Yii::info("Permisos del rol {$role->name}: " . count($permissions), 'menu');
        }
        
        // Combinar todos los permisos
        $allPermissions = array_merge($directPermissions, $rolePermissions);
        $this->userPermissions = array_keys($allPermissions);
        
        Yii::info("Permisos totales del usuario: " . implode(', ', $this->userPermissions), 'menu');
    }
    
    /**
     * Verifica si un usuario tiene un rol específico
     */
    private function userHasRole($userId, $roleName)
    {
        try {
            $auth = Yii::$app->authManager;
            $roles = $auth->getRolesByUser($userId);
            
            $hasRole = isset($roles[$roleName]);
            
            if ($hasRole) {
                Yii::info("Usuario $userId SÍ tiene rol: $roleName", 'menu');
            } else {
                Yii::info("Usuario $userId NO tiene rol: $roleName. Roles disponibles: " . implode(', ', array_keys($roles)), 'menu');
            }
            
            return $hasRole;
        } catch (\Exception $e) {
            Yii::error("Error verificando rol {$roleName} para usuario {$userId}: " . $e->getMessage(), 'menu');
            return false;
        }
    }
    
    /**
     * Carga items del menú desde la base de datos (ESTRUCTURA COMPLETA)
     */
    private function loadMenuItems()
    {
        // Obtener todos los items activos
        $items = Menu::find()
            ->where(['active' => true])
            ->orderBy(['nivel' => SORT_ASC, 'order' => SORT_ASC])
            ->all();
        
        // Primero, construir un mapa de todos los items
        $itemMap = [];
        foreach ($items as $item) {
            $itemMap[$item->id] = [
                'id' => $item->id,
                'label' => $this->formatLabel($item),
                'url' => $item->route ? $this->parseRoute($item->route) : '#',
                'icon' => $item->icon,
                'permission' => $item->permission,
                'parent' => $item->parent,
                'nivel' => $item->nivel,
                'items' => [] // Inicializar array vacío para hijos
            ];
        }
        
        // Luego, construir la jerarquía
        $tree = [];
        foreach ($itemMap as $id => &$item) {
            if ($item['parent'] === null) {
                // Es un item raíz
                $tree[] = &$item;
            } else {
                // Es un hijo, agregarlo al padre
                if (isset($itemMap[$item['parent']])) {
                    $itemMap[$item['parent']]['items'][] = &$item;
                } else {
                    // El padre no existe, agregarlo como raíz (por seguridad)
                    $tree[] = &$item;
                }
            }
        }
        
        $this->menuItems = $tree;
        
        // Log para depuración
        Yii::info("Total items cargados: " . count($items), 'menu');
        Yii::info("Items raíz: " . count($tree), 'menu');
        $this->logTreeStructure($tree);
    }
    
    /**
     * Registra la estructura del árbol para depuración
     */
    private function logTreeStructure($items, $level = 0)
    {
        foreach ($items as $item) {
            $indent = str_repeat('  ', $level);
            Yii::info($indent . "├─ " . strip_tags($item['label']) . " (ID: {$item['id']}, Nivel: {$item['nivel']}, Padre: " . ($item['parent'] ?? 'NULL') . ")", 'menu');
            
            if (!empty($item['items'])) {
                $this->logTreeStructure($item['items'], $level + 1);
            }
        }
    }
    
    /**
     * Formatea el label con icono si existe
     */
    private function formatLabel($menuItem)
    {
        $label = $menuItem->name;
        
        if ($menuItem->icon) {
            if ($this->mobileMode) {
                $label = Html::tag('i', '', ['class' => $menuItem->icon . ' me-2']) . $label;
            } else {
                $label = Html::tag('i', '', ['class' => $menuItem->icon]) . 
                         ($menuItem->nivel == 0 ? ' ' . $label : ' ' . $label);
            }
        }
        
        return $label;
    }
    
    /**
     * Parsea la ruta del menú
     */
    private function parseRoute($route)
    {
        if (empty($route)) {
            return '#';
        }
        
        // Si ya es una URL completa
        if (strpos($route, 'http') === 0 || strpos($route, '/') === 0) {
            return $route;
        }
        
        // Convertir "controller/action" a array para Url::to
        $routeParts = explode('/', $route);
        if (count($routeParts) >= 2) {
            return ['/' . $route];
        }
        
        return ['/' . $route];
    }
    
    /**
     * Filtra items por permiso (RECURSIVO PARA TODOS LOS NIVELES)
     */
    private function filterItemsByPermission($items)
    {
        $filtered = [];
        
        foreach ($items as $item) {
            // DEBUG: Mostrar información del item
            Yii::info("Procesando menú: " . strip_tags($item['label']) . " | Permiso requerido: " . ($item['permission'] ?? 'Ninguno'), 'menu');
            
            // Si es superusuario (permiso '*'), mostrar todo
            if (in_array('*', $this->userPermissions)) {
                Yii::info("Usuario tiene acceso total (*). Mostrando: " . strip_tags($item['label']), 'menu');
                // Filtrar recursivamente los hijos
                if (!empty($item['items'])) {
                    $item['items'] = $this->filterItemsByPermission($item['items']);
                }
                $filtered[] = $item;
                continue;
            }
            
            // Verificar permiso específico
            $hasPermission = true;
            if (!empty($item['permission'])) {
                $hasPermission = in_array($item['permission'], $this->userPermissions);
                
                if ($hasPermission) {
                    Yii::info("Usuario SÍ tiene permiso: {$item['permission']} para: " . strip_tags($item['label']), 'menu');
                } else {
                    Yii::info("Usuario NO tiene permiso: {$item['permission']} para: " . strip_tags($item['label']), 'menu');
                }
            }
            
            if (!$hasPermission) {
                continue;
            }
            
            // Filtrar subitems recursivamente
            if (!empty($item['items'])) {
                $item['items'] = $this->filterItemsByPermission($item['items']);
                
                // Si después de filtrar no hay subitems y es un item padre sin URL propia, omitir
                if (empty($item['items']) && (!isset($item['url']) || $item['url'] == '#')) {
                    Yii::info("Omitiendo menú sin subitems y sin URL: " . strip_tags($item['label']), 'menu');
                    continue;
                }
            }
            
            $filtered[] = $item;
            Yii::info("Menú agregado: " . strip_tags($item['label']), 'menu');
        }
        
        return $filtered;
    }
    
    /**
     * Renderiza el menú completo
     */
    private function renderMenu($items)
    {
        if ($this->mobileMode) {
            return $this->renderMobileMenu($items);
        }
        
        return $this->renderDesktopMenu($items);
    }
    
    /**
     * Renderiza menú para desktop (SOPORTE MULTINIVEL)
     */
    private function renderDesktopMenu($items)
    {
        $html = Html::beginTag('ul', array_merge(['class' => 'navbar-nav me-auto'], $this->options));
        
        foreach ($items as $item) {
            $html .= $this->renderDesktopMenuItem($item);
        }
        
        $html .= Html::endTag('ul');
        return $html;
    }
    
    /**
     * Renderiza un item del menú desktop (RECURSIVO)
     */
    private function renderDesktopMenuItem($item, $isSubmenu = false)
    {
        $hasChildren = !empty($item['items']);
        $isActive = $this->isItemActive($item);
        
        $liClass = $isSubmenu ? '' : 'nav-item';
        $linkClass = $isSubmenu ? 'dropdown-item' : 'nav-link';
        
        if ($hasChildren) {
            $liClass .= $isSubmenu ? ' dropdown-submenu dropend' : ' dropdown';
            $linkClass .= ' dropdown-toggle';
        }
        
        if ($isActive) {
            $liClass .= ' active';
            $linkClass .= ' active';
        }
        
        // ✅ AGREGAR CLASE POR ID - SOLUCIÓN PRECISA
        $liClass .= ' menu-id-' . ($item['id'] ?? '0');
        
        // ✅ ESPECÍFICO PARA HERRAMIENTAS (ID 162)
        if (($item['id'] ?? 0) == 162) {
            $liClass .= ' menu-herramientas menu-herramientas-id-162';
        }
        
        // ✅ AGREGAR ATRIBUTO data-menu-id PARA JAVASCRIPT
        $liAttributes = ['class' => trim($liClass), 'data-menu-id' => $item['id'] ?? '0'];
        
        // Si es Herramientas, agregar atributo específico
        if (($item['id'] ?? 0) == 162) {
            $liAttributes['data-menu-name'] = 'herramientas';
            $liAttributes['data-menu-tools'] = 'true';
        }
        
        $html = Html::beginTag('li', $liAttributes);
        
        $linkOptions = [
            'class' => $linkClass,
            'title' => strip_tags($item['label'])
        ];
        
        if ($hasChildren) {
            $linkOptions['data-bs-toggle'] = 'dropdown';
            $linkOptions['aria-expanded'] = 'false';
            $linkOptions['role'] = 'button';
            
            // Para submenús anidados
            if ($isSubmenu) {
                $linkOptions['data-bs-auto-close'] = 'outside';
                $linkOptions['data-bs-offset'] = '[0,0]';
            }
        }
        
        // ✅ Agregar atributos data al enlace también
        $linkOptions['data-menu-id'] = $item['id'] ?? '0';
        if (($item['id'] ?? 0) == 162) {
            $linkOptions['data-menu-tools'] = 'true';
            $linkOptions['data-menu-herramientas'] = 'true';
        }
        
        $html .= Html::a($item['label'], $item['url'], $linkOptions);
        
        // Renderizar hijos si existen (RECURSIVO)
        if ($hasChildren) {
            $dropdownClass = $isSubmenu ? 'dropdown-menu dropdown-submenu' : 'dropdown-menu';
            
            // ✅ Agregar atributo data al dropdown
            $dropdownAttributes = ['class' => $dropdownClass];
            $dropdownAttributes['data-parent-menu-id'] = $item['id'] ?? '0';
            
            if (($item['id'] ?? 0) == 162) {
                $dropdownAttributes['data-parent-menu-tools'] = 'true';
            }
            
            $html .= Html::beginTag('ul', $dropdownAttributes);
            
            foreach ($item['items'] as $child) {
                $html .= $this->renderDesktopMenuItem($child, true);
            }
            
            $html .= Html::endTag('ul');
        }
        
        $html .= Html::endTag('li');
        return $html;
    }
    
    /**
     * Renderiza menú para móvil
     */
    private function renderMobileMenu($items)
    {
        $html = Html::beginTag('div', ['class' => 'mobile-menu']);
        
        foreach ($items as $item) {
            $html .= $this->renderMobileMenuItem($item);
        }
        
        $html .= Html::endTag('div');
        return $html;
    }
    
    /**
     * Renderiza un item del menú móvil (RECURSIVO)
     */
    private function renderMobileMenuItem($item, $level = 0)
    {
        $hasChildren = !empty($item['items']);
        $isActive = $this->isItemActive($item);
        
        $itemClass = 'mobile-menu-item';
        $linkClass = 'mobile-menu-link';
        
        if ($isActive) {
            $linkClass .= ' active';
        }
        
        if ($level > 0) {
            $itemClass .= ' mobile-submenu-item level-' . $level;
            $linkClass .= ' mobile-submenu-link';
        }
        
        // ✅ AGREGAR CLASE POR ID
        $itemClass .= ' menu-id-' . ($item['id'] ?? '0');
        
        // ✅ ESPECÍFICO PARA HERRAMIENTAS (ID 162)
        if (($item['id'] ?? 0) == 162) {
            $itemClass .= ' menu-herramientas menu-herramientas-id-162';
        }
        
        $divAttributes = [
            'class' => $itemClass,
            'data-menu-id' => $item['id'] ?? '0'
        ];
        
        if (($item['id'] ?? 0) == 162) {
            $divAttributes['data-menu-name'] = 'herramientas';
            $divAttributes['data-menu-tools'] = 'true';
        }
        
        $html = Html::beginTag('div', $divAttributes);
        
        $linkOptions = [
            'class' => $linkClass,
            'title' => strip_tags($item['label'])
        ];
        
        // ✅ Agregar atributos data al enlace móvil
        $linkOptions['data-menu-id'] = $item['id'] ?? '0';
        if (($item['id'] ?? 0) == 162) {
            $linkOptions['data-menu-tools'] = 'true';
            $linkOptions['data-menu-herramientas'] = 'true';
        }
        
        if ($hasChildren) {
            $uniqueId = uniqid();
            $linkOptions['data-bs-toggle'] = 'collapse';
            $linkOptions['data-bs-target'] = '#mobile-submenu-' . $item['id'] . '-' . $uniqueId;
            $linkOptions['aria-expanded'] = 'false';
            $linkOptions['aria-controls'] = 'mobile-submenu-' . $item['id'] . '-' . $uniqueId;
        }
        
        $html .= Html::a($item['label'], $item['url'], $linkOptions);
        
        // Renderizar subitems recursivamente
        if ($hasChildren) {
            $uniqueId = uniqid();
            $submenuAttributes = [
                'id' => 'mobile-submenu-' . $item['id'] . '-' . $uniqueId,
                'class' => 'collapse mobile-submenu',
                'data-bs-parent' => $level > 0 ? '#mobile-submenu-' . $item['parent'] : null
            ];
            
            // ✅ Agregar atributo data al submenú móvil
            $submenuAttributes['data-parent-menu-id'] = $item['id'] ?? '0';
            if (($item['id'] ?? 0) == 162) {
                $submenuAttributes['data-parent-menu-tools'] = 'true';
            }
            
            $html .= Html::beginTag('div', $submenuAttributes);
            
            foreach ($item['items'] as $child) {
                $html .= $this->renderMobileMenuItem($child, $level + 1);
            }
            
            $html .= Html::endTag('div');
        }
        
        $html .= Html::endTag('div');
        return $html;
    }
    
    /**
     * Verifica si un item del menú está activo
     */
    private function isItemActive($item)
    {
        if (!isset($item['url']) || $item['url'] == '#') {
            return false;
        }
        
        $currentRoute = Yii::$app->controller->route;
        $currentUrl = Url::current();
        
        // Si es un array de ruta
        if (is_array($item['url'])) {
            $itemRoute = ltrim($item['url'][0], '/');
            return strpos($currentRoute, $itemRoute) === 0;
        }
        
        // Si es una URL string
        $itemUrl = $item['url'];
        return strpos($currentUrl, $itemUrl) !== false;
    }
}