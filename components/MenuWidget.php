<?php
namespace app\components;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Menu;

/**
 * Widget de menú dinámico basado en la tabla seguridad.menu
 * ✅ CORREGIDO: Genera estructura correcta para móvil con collapses de Bootstrap
 * ✅ DESKTOP: Dropdowns anidados para más de 2 niveles
 * ✅ CORRECCIÓN: Eliminados ítems redundantes (Login/Registro/Mi Cuenta) del menú principal
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
        
        if ($this->mobileMode) {
            return $this->renderMobileMenu($filteredItems);
        }
        
        return $this->renderDesktopMenu($filteredItems);
    }
    
    private function loadUserPermissions()
    {
        if (Yii::$app->user->isGuest) {
            $this->userPermissions = ['site_index', 'site_login', 'site_contact', 'site_about'];
            return;
        }
        
        $auth = Yii::$app->authManager;
        $userId = Yii::$app->user->id;
        
        // ACCESO TOTAL para superusuarios
        $superuserRoles = ['admin', 'administrador', 'superusuario'];
        foreach ($superuserRoles as $role) {
            if ($this->userHasRole($userId, $role)) {
                $this->userPermissions = ['*'];
                return;
            }
        }
        
        if ($userId == 1) {
            $this->userPermissions = ['*'];
            return;
        }
        
        // Permisos normales
        $directPermissions = $auth->getPermissionsByUser($userId);
        $userRoles = $auth->getRolesByUser($userId);
        
        $rolePermissions = [];
        foreach ($userRoles as $role) {
            $permissions = $auth->getPermissionsByRole($role->name);
            $rolePermissions = array_merge($rolePermissions, $permissions);
        }
        
        $allPermissions = array_merge($directPermissions, $rolePermissions);
        $this->userPermissions = array_keys($allPermissions);
        
        $this->userPermissions = array_merge($this->userPermissions, [
            'site_index', 'site_logout', 'perfil_mi_informacion'
        ]);
    }
    
    private function userHasRole($userId, $roleName)
    {
        try {
            $auth = Yii::$app->authManager;
            $roles = $auth->getRolesByUser($userId);
            return isset($roles[$roleName]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function loadMenuItems()
    {
        $items = Menu::find()
            ->where(['active' => true])
            ->orderBy(['nivel' => SORT_ASC, 'order' => SORT_ASC])
            ->all();
        
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
                'show_as_public_container' => (bool)$item->show_as_public_container,
                'items' => []
            ];
        }
        
        $tree = [];
        foreach ($itemMap as $id => &$item) {
            if ($item['parent'] === null) {
                $tree[] = &$item;
            } else {
                if (isset($itemMap[$item['parent']])) {
                    $itemMap[$item['parent']]['items'][] = &$item;
                } else {
                    $tree[] = &$item;
                }
            }
        }
        
        $this->menuItems = $tree;
    }
    
    private function formatLabel($menuItem)
    {
        $label = $menuItem->name;
        
        if ($menuItem->icon) {
            if ($this->mobileMode) {
                $label = Html::tag('i', '', ['class' => $menuItem->icon . ' me-2']) . $label;
            } else {
                $label = Html::tag('i', '', ['class' => $menuItem->icon]) . ' ' . $label;
            }
        }
        
        return $label;
    }
    
    private function parseRoute($route)
    {
        if (empty($route)) {
            return '#';
        }
        
        if (strpos($route, 'http') === 0 || strpos($route, '/') === 0) {
            return $route;
        }
        
        $routeParts = explode('/', $route);
        if (count($routeParts) >= 2) {
            return ['/' . $route];
        }
        
        return ['/' . $route];
    }
    
    private function filterItemsByPermission($items)
    {
        $filtered = [];
        
        foreach ($items as $item) {
            // ⛔ EXCLUSIÓN: Ítems que deben aparecer solo en el control de usuario
            // ID 184: Login, ID 185: Registro, ID 186: Mi Cuenta
            if (in_array($item['id'] ?? 0, [184, 185, 186])) {
                continue;
            }
            
            // SUPERUSUARIO: Mostrar todo
            if (in_array('*', $this->userPermissions)) {
                if (!empty($item['items'])) {
                    $item['items'] = $this->filterItemsByPermission($item['items']);
                }
                $filtered[] = $item;
                continue;
            }
            
            $isItemAuthorized = empty($item['permission']) || 
                               in_array($item['permission'], $this->userPermissions);
            
            // Filtrar hijos
            $authorizedChildren = [];
            if (!empty($item['items'])) {
                $authorizedChildren = $this->filterItemsByPermission($item['items']);
            }
            
            if ($isItemAuthorized) {
                // ✅ CASO NORMAL: Item autorizado por RBAC
                $item['items'] = $authorizedChildren;
                $filtered[] = $item;
                
            } elseif (!empty($authorizedChildren) && ($item['id'] == 177)) {
                // ✅ CASO ESPECIAL: SOLO MarketPlace (ID 177) como contenedor público
                $publicContainer = $item;
                $publicContainer['items'] = $authorizedChildren;
                $publicContainer['url'] = '#';
                $publicContainer['is_public_container'] = true;
                $filtered[] = $publicContainer;
                
            } elseif (!empty($authorizedChildren) && ($item['id'] == 162 || $item['id'] == 163)) {
                // ⚠️ Herramientas o Gestión Deportiva SIN permiso pero CON hijos autorizados
                // POR SEGURIDAD: NO MOSTRAR
                continue;
                
            } else {
                // ❌ NO MOSTRAR: Sin permiso y sin hijos autorizados
                continue;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Renderiza menú para desktop (dropdowns anidados)
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
        
        $liClass .= ' menu-id-' . ($item['id'] ?? '0');
        
        if (($item['id'] ?? 0) == 162) {
            $liClass .= ' menu-herramientas menu-herramientas-id-162';
        }
        
        if (($item['id'] ?? 0) == 177 && ($item['is_public_container'] ?? false)) {
            $liClass .= ' menu-public-container';
            $linkClass .= ' public-container';
        }
        
        $liAttributes = ['class' => trim($liClass), 'data-menu-id' => $item['id'] ?? '0'];
        
        if (($item['id'] ?? 0) == 162) {
            $liAttributes['data-menu-name'] = 'herramientas';
            $liAttributes['data-menu-tools'] = 'true';
        }
        
        if (($item['id'] ?? 0) == 177 && ($item['is_public_container'] ?? false)) {
            $liAttributes['data-public-container'] = 'true';
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
            
            if ($isSubmenu) {
                $linkOptions['data-bs-auto-close'] = 'outside';
                $linkOptions['data-bs-offset'] = '[0,0]';
            }
        }
        
        $linkOptions['data-menu-id'] = $item['id'] ?? '0';
        if (($item['id'] ?? 0) == 162) {
            $linkOptions['data-menu-tools'] = 'true';
            $linkOptions['data-menu-herramientas'] = 'true';
        }
        
        if (($item['is_public_container'] ?? false) && ($item['url'] ?? '#') == '#') {
            $linkOptions['href'] = '#';
            $linkOptions['onclick'] = 'return false;';
            $linkOptions['style'] = 'cursor: default;';
        } else {
            $linkOptions['href'] = $item['url'];
        }
        
        $html .= Html::a($item['label'], $item['url'], $linkOptions);
        
        // Renderizar hijos si existen
        if ($hasChildren) {
            $dropdownClass = $isSubmenu ? 'dropdown-menu dropdown-submenu' : 'dropdown-menu';
            
            $dropdownAttributes = ['class' => $dropdownClass];
            $dropdownAttributes['data-parent-menu-id'] = $item['id'] ?? '0';
            
            if (($item['id'] ?? 0) == 162) {
                $dropdownAttributes['data-parent-menu-tools'] = 'true';
            }
            
            if (($item['id'] ?? 0) == 177 && ($item['is_public_container'] ?? false)) {
                $dropdownAttributes['data-parent-public-container'] = 'true';
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
     * ✅ CORREGIDO: Renderiza menú para móvil con estructura de collapse
     */
    private function renderMobileMenu($items)
    {
        $html = Html::beginTag('div', ['class' => 'mobile-menu']);
        
        foreach ($items as $item) {
            $html .= $this->renderMobileMenuItem($item, 0);
        }
        
        $html .= Html::endTag('div');
        return $html;
    }
    
    /**
     * ✅ CORREGIDO: Renderiza item móvil con collapse para submenús
     * ✅ USAR IDs ESTABLES para collapses (sin uniqid())
     */
    private function renderMobileMenuItem($item, $level = 0, $parentPath = '')
    {
        $hasChildren = !empty($item['items']);
        $isActive = $this->isItemActive($item);
        
        $itemClass = 'mobile-menu-item';
        $linkClass = 'mobile-menu-link nav-link';
        
        if ($isActive) {
            $linkClass .= ' active';
        }
        
        if ($level > 0) {
            $itemClass .= ' mobile-submenu-item level-' . $level;
            $linkClass .= ' mobile-submenu-link';
        }
        
        $itemClass .= ' menu-id-' . ($item['id'] ?? '0');
        
        if (($item['id'] ?? 0) == 162) {
            $itemClass .= ' menu-herramientas menu-herramientas-id-162';
        }
        
        if (($item['id'] ?? 0) == 177 && ($item['is_public_container'] ?? false)) {
            $itemClass .= ' menu-public-container';
            $linkClass .= ' public-container';
        }
        
        $divAttributes = [
            'class' => $itemClass,
            'data-menu-id' => $item['id'] ?? '0'
        ];
        
        if (($item['id'] ?? 0) == 162) {
            $divAttributes['data-menu-name'] = 'herramientas';
            $divAttributes['data-menu-tools'] = 'true';
        }
        
        if (($item['id'] ?? 0) == 177 && ($item['is_public_container'] ?? false)) {
            $divAttributes['data-public-container'] = 'true';
        }
        
        $html = Html::beginTag('div', $divAttributes);
        
        $linkOptions = [
            'class' => $linkClass,
            'title' => strip_tags($item['label'])
        ];
        
        $linkOptions['data-menu-id'] = $item['id'] ?? '0';
        if (($item['id'] ?? 0) == 162) {
            $linkOptions['data-menu-tools'] = 'true';
            $linkOptions['data-menu-herramientas'] = 'true';
        }
        
        // Si es contenedor público (sin URL funcional)
        if (($item['is_public_container'] ?? false) && ($item['url'] ?? '#') == '#') {
            $linkOptions['href'] = '#';
            $linkOptions['onclick'] = 'return false;';
            $linkOptions['style'] = 'cursor: default;';
        } else {
            $linkOptions['href'] = $item['url'];
        }
        
        // ✅ CORRECCIÓN CRÍTICA: IDs ESTABLES para collapses
        // Generar ID único pero estable basado en el ID del menú y nivel
        $itemId = $item['id'] ?? '0';
        $collapseId = 'mobile-menu-' . $itemId . '-' . $level . '-' . substr(md5($parentPath . $itemId), 0, 8);
        
        if ($hasChildren) {
            $linkOptions['data-bs-toggle'] = 'collapse';
            $linkOptions['data-bs-target'] = '#' . $collapseId;
            $linkOptions['aria-expanded'] = 'false';
            $linkOptions['aria-controls'] = $collapseId;
            $linkOptions['role'] = 'button';
            
            // Agregar flecha indicadora
            $item['label'] .= ' <span class="float-end"><i class="fas fa-chevron-down"></i></span>';
        }
        
        $html .= Html::a($item['label'], $item['url'], $linkOptions);
        
        // ✅ Renderizar subitems con collapse de Bootstrap
        if ($hasChildren) {
            $submenuAttributes = [
                'id' => $collapseId,
                'class' => 'collapse mobile-submenu'
            ];
            
            // ✅ CORRECCIÓN: Configurar data-bs-parent SOLO para el primer nivel
            // Bootstrap necesita saber el contenedor padre para cerrar otros collapses
            if ($level === 0) {
                // Primer nivel: apuntar al contenedor principal
                $submenuAttributes['data-bs-parent'] = '.mobile-menu';
            } else {
                // Niveles anidados: apuntar al collapse padre inmediato
                $submenuAttributes['data-bs-parent'] = '#' . $parentPath;
            }
            
            $submenuAttributes['data-parent-menu-id'] = $item['id'] ?? '0';
            if (($item['id'] ?? 0) == 162) {
                $submenuAttributes['data-parent-menu-tools'] = 'true';
            }
            
            if (($item['id'] ?? 0) == 177 && ($item['is_public_container'] ?? false)) {
                $submenuAttributes['data-parent-public-container'] = 'true';
            }
            
            $html .= Html::beginTag('div', $submenuAttributes);
            
            foreach ($item['items'] as $child) {
                // Pasar el ID del collapse actual como parentPath para los hijos
                $html .= $this->renderMobileMenuItem($child, $level + 1, $collapseId);
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
        
        if (is_array($item['url'])) {
            $itemRoute = ltrim($item['url'][0], '/');
            return strpos($currentRoute, $itemRoute) === 0;
        }
        
        $itemUrl = $item['url'];
        return strpos($currentUrl, $itemUrl) !== false;
    }
}