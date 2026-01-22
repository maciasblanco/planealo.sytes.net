<?php
namespace app\components;

use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Menu;

/**
 * Widget inteligente para generar menús dinámicos basados en permisos
 * Este widget genera menús principales y adaptativos según el rol del usuario
 */
class MenuWidget extends Widget
{
    /**
     * @var array $options Opciones HTML para el contenedor
     */
    public $options = [];
    
    /**
     * @var bool $mobileMode Si está en modo móvil
     */
    public $mobileMode = false;
    
    /**
     * @var bool $rootOnly Si solo debe mostrar items raíz
     */
    public $rootOnly = false;
    
    /**
     * @var string $itemClass Clase CSS para items del menú
     */
    public $itemClass = 'nav-item';
    
    /**
     * @var string $linkClass Clase CSS para enlaces del menú
     */
    public $linkClass = 'nav-link';
    
    /**
     * @var array $excludeModules Módulos a excluir
     */
    public $excludeModules = [];
    
    /**
     * @var array $userPermissions Permisos del usuario
     */
    private $userPermissions = [];
    
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        // Obtener permisos del usuario
        $this->loadUserPermissions();
        
        // Configurar opciones por defecto
        if ($this->mobileMode) {
            $this->options['class'] = isset($this->options['class']) 
                ? $this->options['class'] . ' mobile-nav-menu' 
                : 'mobile-nav-menu';
        } else {
            $this->options['class'] = isset($this->options['class']) 
                ? $this->options['class'] . ' main-navigation' 
                : 'main-navigation';
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // Obtener items del menú según el rol
        $menuItems = $this->getMenuItems();
        
        // Filtrar items según permisos
        $filteredItems = $this->filterItemsByPermission($menuItems);
        
        // Si no hay items, retornar vacío
        if (empty($filteredItems)) {
            return '';
        }
        
        return $this->renderMenu($filteredItems);
    }
    
    /**
     * Carga los permisos del usuario actual
     */
    private function loadUserPermissions()
    {
        if (Yii::$app->user->isGuest) {
            $this->userPermissions = ['acceder_publico'];
            return;
        }
        
        $auth = Yii::$app->authManager;
        $userId = Yii::$app->user->id;
        
        // Obtener todos los permisos directos
        $directPermissions = $auth->getPermissionsByUser($userId);
        
        // Obtener permisos a través de roles
        $userRoles = $auth->getRolesByUser($userId);
        $rolePermissions = [];
        
        foreach ($userRoles as $role) {
            $permissions = $auth->getPermissionsByRole($role->name);
            $rolePermissions = array_merge($rolePermissions, $permissions);
        }
        
        // Combinar todos los permisos
        $allPermissions = array_merge($directPermissions, $rolePermissions);
        
        // Extraer solo los nombres
        $this->userPermissions = array_keys($allPermissions);
    }
    
    /**
     * Obtiene los items del menú según la configuración
     * @return array
     */
    private function getMenuItems()
    {
        if ($this->mobileMode) {
            return $this->getMobileMenuItems();
        }
        
        return $this->getDesktopMenuItems();
    }
    
    /**
     * Obtiene items del menú para desktop
     * @return array
     */
    private function getDesktopMenuItems()
    {
        $items = [];
        
        // INICIO
        $items[] = [
            'label' => '<i class="bi bi-house-door"></i>',
            'url' => ['/site/index'],
            'title' => 'Inicio',
            'active' => $this->isActiveRoute('site/index'),
            'permission' => null, // Acceso público
        ];
        
        // MÓDULOS PRINCIPALES
        $modules = $this->getAvailableModules();
        
        foreach ($modules as $module => $config) {
            if (in_array($module, $this->excludeModules)) {
                continue;
            }
            
            $items[] = [
                'label' => '<i class="bi ' . $config['icon'] . '"></i> <span class="menu-text">' . $config['title'] . '</span>',
                'url' => $config['url'],
                'title' => $config['title'],
                'active' => $this->isActiveModule($module),
                'permission' => $config['permission'],
                'items' => $config['hasSubmenu'] ? $this->getModuleSubmenu($module) : null,
                'options' => ['class' => 'dropdown'],
                'linkOptions' => [
                    'class' => 'dropdown-toggle',
                    'data-bs-toggle' => 'dropdown',
                    'aria-expanded' => 'false'
                ],
                'dropDownOptions' => ['class' => 'dropdown-menu']
            ];
        }
        
        // ADMINISTRACIÓN (solo para usuarios con permisos)
        $adminItems = $this->getAdminMenuItems();
        if (!empty($adminItems)) {
            $items[] = [
                'label' => '<i class="bi bi-gear"></i> <span class="menu-text">Administración</span>',
                'url' => '#',
                'title' => 'Administración',
                'active' => $this->isActiveRoute(['admin/', 'rbac/', 'programador/', 'gii/']),
                'permission' => 'acceder_admin',
                'items' => $adminItems,
                'options' => ['class' => 'dropdown'],
                'linkOptions' => [
                    'class' => 'dropdown-toggle',
                    'data-bs-toggle' => 'dropdown',
                    'aria-expanded' => 'false'
                ],
                'dropDownOptions' => ['class' => 'dropdown-menu']
            ];
        }
        
        // AYUDA
        $items[] = [
            'label' => '<i class="bi bi-question-circle"></i>',
            'url' => ['/site/help'],
            'title' => 'Ayuda',
            'active' => $this->isActiveRoute('site/help'),
            'permission' => null,
        ];
        
        return $items;
    }
    
    /**
     * Obtiene items del menú para móvil
     * @return array
     */
    private function getMobileMenuItems()
    {
        $items = [];
        
        // INICIO
        $items[] = [
            'label' => '<i class="bi bi-house-door me-3"></i> Inicio',
            'url' => ['/site/index'],
            'active' => $this->isActiveRoute('site/index'),
            'permission' => null,
        ];
        
        // MÓDULOS PRINCIPALES
        $modules = $this->getAvailableModules();
        
        foreach ($modules as $module => $config) {
            if (in_array($module, $this->excludeModules)) {
                continue;
            }
            
            $items[] = [
                'label' => '<i class="bi ' . $config['icon'] . ' me-3"></i> ' . $config['title'],
                'url' => $config['url'],
                'active' => $this->isActiveModule($module),
                'permission' => $config['permission'],
            ];
        }
        
        // ADMINISTRACIÓN
        $adminItems = $this->getAdminMenuItems();
        if (!empty($adminItems)) {
            $items[] = [
                'label' => '<i class="bi bi-gear me-3"></i> Administración',
                'url' => '#',
                'active' => $this->isActiveRoute(['admin/', 'rbac/', 'programador/', 'gii/']),
                'permission' => 'acceder_admin',
                'items' => $adminItems,
            ];
        }
        
        // AYUDA
        $items[] = [
            'label' => '<i class="bi bi-question-circle me-3"></i> Ayuda',
            'url' => ['/site/help'],
            'active' => $this->isActiveRoute('site/help'),
            'permission' => null,
        ];
        
        // CONTACTO
        $items[] = [
            'label' => '<i class="bi bi-envelope me-3"></i> Contacto',
            'url' => ['/site/contact'],
            'active' => $this->isActiveRoute('site/contact'),
            'permission' => null,
        ];
        
        return $items;
    }
    
    /**
     * Obtiene los módulos disponibles según permisos
     * @return array
     */
    private function getAvailableModules()
    {
        $modules = [
            'atletas' => [
                'title' => 'Atletas',
                'icon' => 'bi-people',
                'url' => ['/atletas/default/index'],
                'permission' => 'acceder_atletas',
                'hasSubmenu' => true,
            ],
            'tienda' => [
                'title' => 'MarketPlace',
                'icon' => 'bi-shop',
                'url' => ['/tienda/default/index'],
                'permission' => 'acceder_tienda',
                'hasSubmenu' => true,
            ],
            'escuela_club' => [
                'title' => 'Escuela/Club',
                'icon' => 'bi-building',
                'url' => ['/escuela_club/default/index'],
                'permission' => 'acceder_escuela',
                'hasSubmenu' => true,
            ],
            'aportes' => [
                'title' => 'Aportes',
                'icon' => 'bi-wallet2',
                'url' => ['/aportes/default/index'],
                'permission' => 'acceder_aportes',
                'hasSubmenu' => true,
            ],
            'reportes' => [
                'title' => 'Reportes',
                'icon' => 'bi-file-bar-graph',
                'url' => ['/reportes/default/index'],
                'permission' => 'acceder_reportes',
                'hasSubmenu' => true,
            ],
        ];
        
        return $modules;
    }
    
    /**
     * Obtiene submenú para un módulo
     * @param string $module Nombre del módulo
     * @return array
     */
    private function getModuleSubmenu($module)
    {
        $submenus = [
            'atletas' => [
                [
                    'label' => 'Lista de Atletas',
                    'url' => ['/atletas/atleta/index'],
                    'permission' => 'ver_atletas',
                ],
                [
                    'label' => 'Nuevo Atleta',
                    'url' => ['/atletas/atleta/create'],
                    'permission' => 'crear_atleta',
                ],
                ['label' => '', 'options' => ['class' => 'dropdown-divider']],
                [
                    'label' => 'Categorías',
                    'url' => ['/atletas/categoria/index'],
                    'permission' => 'gestionar_categorias',
                ],
                [
                    'label' => 'Disciplinas',
                    'url' => ['/atletas/disciplina/index'],
                    'permission' => 'gestionar_disciplinas',
                ],
            ],
            'tienda' => [
                [
                    'label' => 'Catálogo',
                    'url' => ['/tienda/producto/index'],
                    'permission' => 'ver_productos',
                ],
                [
                    'label' => 'Nuevo Producto',
                    'url' => ['/tienda/producto/create'],
                    'permission' => 'crear_producto',
                ],
                ['label' => '', 'options' => ['class' => 'dropdown-divider']],
                [
                    'label' => 'Mi Carrito',
                    'url' => ['/tienda/carrito/index'],
                    'permission' => 'acceder_carrito',
                ],
                [
                    'label' => 'Mis Compras',
                    'url' => ['/tienda/compra/index'],
                    'permission' => 'ver_mis_compras',
                ],
            ],
            'escuela_club' => [
                [
                    'label' => 'Información',
                    'url' => ['/escuela_club/escuela/informacion'],
                    'permission' => 'ver_informacion_escuela',
                ],
                [
                    'label' => 'Personal',
                    'url' => ['/escuela_club/personal/index'],
                    'permission' => 'ver_personal',
                ],
                [
                    'label' => 'Eventos',
                    'url' => ['/escuela_club/evento/index'],
                    'permission' => 'ver_eventos',
                ],
                ['label' => '', 'options' => ['class' => 'dropdown-divider']],
                [
                    'label' => 'Horarios',
                    'url' => ['/escuela_club/horario/index'],
                    'permission' => 'ver_horarios',
                ],
                [
                    'label' => 'Galería',
                    'url' => ['/escuela_club/galeria/index'],
                    'permission' => 'ver_galeria',
                ],
            ],
            'aportes' => [
                [
                    'label' => 'Mis Aportes',
                    'url' => ['/aportes/aporte/mis-aportes'],
                    'permission' => 'ver_mis_aportes',
                ],
                [
                    'label' => 'Registrar Aporte',
                    'url' => ['/aportes/aporte/create'],
                    'permission' => 'crear_aporte',
                ],
                ['label' => '', 'options' => ['class' => 'dropdown-divider']],
                [
                    'label' => 'Tipos de Aporte',
                    'url' => ['/aportes/tipo/index'],
                    'permission' => 'gestionar_tipos_aporte',
                ],
                [
                    'label' => 'Recibos',
                    'url' => ['/aportes/recibo/index'],
                    'permission' => 'ver_recibos',
                ],
            ],
            'reportes' => [
                [
                    'label' => 'Dashboard',
                    'url' => ['/reportes/default/dashboard'],
                    'permission' => 'ver_dashboard',
                ],
                ['label' => '', 'options' => ['class' => 'dropdown-divider']],
                [
                    'label' => 'Reporte de Atletas',
                    'url' => ['/reportes/atleta/index'],
                    'permission' => 'generar_reporte_atletas',
                ],
                [
                    'label' => 'Reporte Financiero',
                    'url' => ['/reportes/finanza/index'],
                    'permission' => 'generar_reporte_finanzas',
                ],
                [
                    'label' => 'Reporte de Tienda',
                    'url' => ['/reportes/tienda/index'],
                    'permission' => 'generar_reporte_tienda',
                ],
            ],
        ];
        
        return $submenus[$module] ?? [];
    }
    
    /**
     * Obtiene items del menú de administración
     * @return array
     */
    private function getAdminMenuItems()
    {
        $items = [];
        
        // ADMINISTRACIÓN DE USUARIOS
        if (Yii::$app->user->can('gestionar_usuarios')) {
            $items[] = [
                'label' => '<i class="bi bi-people me-2"></i> Usuarios',
                'url' => ['/admin/user/index'],
                'permission' => 'gestionar_usuarios',
            ];
        }
        
        // RBAC - ROLES Y PERMISOS
        if (Yii::$app->user->can('acceder_rbac')) {
            $items[] = [
                'label' => '<i class="bi bi-shield-check me-2"></i> Roles y Permisos',
                'url' => ['/rbac/role/index'],
                'permission' => 'acceder_rbac',
            ];
        }
        
        // PROGRAMADOR (GII y herramientas de desarrollo)
        if (Yii::$app->user->can('acceder_programador')) {
            $items[] = [
                'label' => '<i class="bi bi-code-slash me-2"></i> Programador',
                'url' => ['/programador/index'],
                'permission' => 'acceder_programador',
            ];
            
            if (Yii::$app->user->can('acceder_gii')) {
                $items[] = [
                    'label' => '<i class="bi bi-magic me-2"></i> Generador de Código',
                    'url' => ['/gii'],
                    'permission' => 'acceder_gii',
                    'linkOptions' => ['target' => '_blank'],
                ];
            }
        }
        
        // CONFIGURACIÓN DEL SISTEMA
        if (Yii::$app->user->can('gestionar_configuracion')) {
            $items[] = ['label' => '', 'options' => ['class' => 'dropdown-divider']];
            
            $items[] = [
                'label' => '<i class="bi bi-gear-wide me-2"></i> Configuración',
                'url' => ['/admin/config/index'],
                'permission' => 'gestionar_configuracion',
            ];
        }
        
        return $items;
    }
    
    /**
     * Filtra items del menú según permisos
     * @param array $items
     * @return array
     */
    private function filterItemsByPermission($items)
    {
        $filtered = [];
        
        foreach ($items as $item) {
            // Verificar si el item requiere permiso
            if (isset($item['permission']) && $item['permission'] !== null) {
                if (!in_array($item['permission'], $this->userPermissions)) {
                    continue; // El usuario no tiene permiso, omitir item
                }
            }
            
            // Filtrar subitems si existen
            if (isset($item['items']) && is_array($item['items'])) {
                $filteredSubitems = $this->filterItemsByPermission($item['items']);
                
                // Si después de filtrar no hay subitems y el item principal no tiene URL directa, omitirlo
                if (empty($filteredSubitems) && (!isset($item['url']) || $item['url'] === '#')) {
                    continue;
                }
                
                $item['items'] = $filteredSubitems;
            }
            
            $filtered[] = $item;
        }
        
        return $filtered;
    }
    
    /**
     * Renderiza el menú
     * @param array $items
     * @return string
     */
    private function renderMenu($items)
    {
        if ($this->mobileMode) {
            return $this->renderMobileMenu($items);
        }
        
        return $this->renderDesktopMenu($items);
    }
    
    /**
     * Renderiza el menú para desktop
     * @param array $items
     * @return string
     */
    private function renderDesktopMenu($items)
    {
        $html = Html::beginTag('ul', $this->options);
        
        foreach ($items as $item) {
            $html .= $this->renderMenuItem($item, 'desktop');
        }
        
        $html .= Html::endTag('ul');
        
        return $html;
    }
    
    /**
     * Renderiza el menú para móvil
     * @param array $items
     * @return string
     */
    private function renderMobileMenu($items)
    {
        $html = '';
        
        foreach ($items as $item) {
            $html .= $this->renderMenuItem($item, 'mobile');
        }
        
        return $html;
    }
    
    /**
     * Renderiza un item del menú
     * @param array $item
     * @param string $mode 'desktop' o 'mobile'
     * @return string
     */
    private function renderMenuItem($item, $mode = 'desktop')
    {
        $hasChildren = isset($item['items']) && !empty($item['items']);
        
        if ($mode === 'desktop') {
            return $this->renderDesktopMenuItem($item, $hasChildren);
        } else {
            return $this->renderMobileMenuItem($item, $hasChildren);
        }
    }
    
    /**
     * Renderiza item para desktop
     * @param array $item
     * @param bool $hasChildren
     * @return string
     */
    private function renderDesktopMenuItem($item, $hasChildren)
    {
        $liClass = $this->itemClass;
        $linkClass = $this->linkClass;
        
        // Agregar clase active si corresponde
        if (isset($item['active']) && $item['active']) {
            $liClass .= ' active';
            $linkClass .= ' active';
        }
        
        // Agregar clase dropdown si tiene hijos
        if ($hasChildren) {
            $liClass .= ' dropdown';
            $linkClass .= ' dropdown-toggle';
        }
        
        // Agregar clases personalizadas del item
        if (isset($item['options']['class'])) {
            $liClass .= ' ' . $item['options']['class'];
        }
        
        $html = Html::beginTag('li', ['class' => $liClass]);
        
        // Renderizar enlace
        $linkOptions = [
            'class' => $linkClass,
            'title' => $item['title'] ?? strip_tags($item['label']),
        ];
        
        if ($hasChildren) {
            $linkOptions['data-bs-toggle'] = 'dropdown';
            $linkOptions['aria-expanded'] = 'false';
            $linkOptions['role'] = 'button';
        }
        
        // Agregar opciones de link personalizadas
        if (isset($item['linkOptions'])) {
            $linkOptions = array_merge($linkOptions, $item['linkOptions']);
        }
        
        $html .= Html::a($item['label'], $item['url'] ?? '#', $linkOptions);
        
        // Renderizar hijos si existen
        if ($hasChildren) {
            $dropdownClass = 'dropdown-menu';
            if (isset($item['dropDownOptions']['class'])) {
                $dropdownClass .= ' ' . $item['dropDownOptions']['class'];
            }
            
            $html .= Html::beginTag('ul', ['class' => $dropdownClass]);
            
            foreach ($item['items'] as $child) {
                $html .= $this->renderDesktopMenuItem($child, false);
            }
            
            $html .= Html::endTag('ul');
        }
        
        $html .= Html::endTag('li');
        
        return $html;
    }
    
    /**
     * Renderiza item para móvil
     * @param array $item
     * @param bool $hasChildren
     * @return string
     */
    private function renderMobileMenuItem($item, $hasChildren)
    {
        $itemClass = 'nav-item';
        $linkClass = 'nav-link';
        
        if (isset($item['active']) && $item['active']) {
            $itemClass .= ' active';
            $linkClass .= ' active';
        }
        
        if ($hasChildren) {
            $itemClass .= ' dropdown';
            $linkClass .= ' dropdown-toggle';
        }
        
        $html = Html::beginTag('li', ['class' => $itemClass]);
        
        $linkOptions = ['class' => $linkClass];
        
        if ($hasChildren) {
            $linkOptions['data-bs-toggle'] = 'collapse';
            $linkOptions['data-bs-target'] = '#mobile-submenu-' . uniqid();
            $linkOptions['aria-expanded'] = 'false';
            $linkOptions['aria-controls'] = 'mobile-submenu';
        }
        
        $html .= Html::a($item['label'], $item['url'] ?? '#', $linkOptions);
        
        if ($hasChildren) {
            $submenuId = 'mobile-submenu-' . uniqid();
            $html .= Html::beginTag('div', [
                'id' => $submenuId,
                'class' => 'collapse mobile-submenu',
            ]);
            
            $html .= Html::beginTag('ul', ['class' => 'nav flex-column ms-3']);
            
            foreach ($item['items'] as $child) {
                $html .= $this->renderMobileMenuItem($child, false);
            }
            
            $html .= Html::endTag('ul');
            $html .= Html::endTag('div');
        }
        
        $html .= Html::endTag('li');
        
        return $html;
    }
    
    /**
     * Verifica si una ruta está activa
     * @param string|array $routes Ruta(s) a verificar
     * @return bool
     */
    private function isActiveRoute($routes)
    {
        $currentRoute = Yii::$app->controller->route;
        
        if (is_array($routes)) {
            foreach ($routes as $route) {
                if (strpos($currentRoute, $route) === 0) {
                    return true;
                }
            }
            return false;
        }
        
        return strpos($currentRoute, $routes) === 0;
    }
    
    /**
     * Verifica si el módulo actual está activo
     * @param string $module Nombre del módulo
     * @return bool
     */
    private function isActiveModule($module)
    {
        $currentModule = Yii::$app->controller->module->id ?? '';
        return $currentModule === $module;
    }
}