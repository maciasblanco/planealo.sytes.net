<?php
namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Menu;

/**
 * Widget para generar menús de módulos específicos
 * Este widget genera menús contextuales para cada módulo (Atletas, Tienda, etc.)
 */
class ModuleMenuWidget extends Widget
{
    /**
     * @var string $module Nombre del módulo actual
     */
    public $module;
    
    /**
     * @var array $options Opciones HTML para el contenedor
     */
    public $options = [];
    
    /**
     * @var bool $mobileMode Si está en modo móvil
     */
    public $mobileMode = false;
    
    /**
     * @var string $userRole Rol del usuario actual
     */
    private $userRole;
    
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        // Si no se especifica módulo, intentar detectarlo
        if (empty($this->module)) {
            $this->module = Yii::$app->controller->module->id ?? '';
        }
        
        // Obtener rol del usuario
        $this->userRole = $this->getUserRole();
        
        // Configurar opciones por defecto
        if (!isset($this->options['id'])) {
            $this->options['id'] = 'module-menu-' . $this->module;
        }
        
        $this->options['class'] = isset($this->options['class']) 
            ? $this->options['class'] . ' module-sidebar-menu' 
            : 'module-sidebar-menu';
    }
    
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // Si no hay módulo, no mostrar nada
        if (empty($this->module)) {
            return '';
        }
        
        // Obtener menú para el módulo
        $menuItems = $this->getModuleMenuItems();
        
        // Si no hay items, retornar vacío
        if (empty($menuItems)) {
            return '';
        }
        
        return $this->renderMenu($menuItems);
    }
    
    /**
     * Obtiene el rol del usuario actual
     * @return string
     */
    private function getUserRole()
    {
        $user = Yii::$app->user;
        if ($user->isGuest) {
            return 'invitado';
        }
        
        // Obtener el primer rol asignado (simplificado)
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser($user->id);
        
        if (!empty($roles)) {
            return array_keys($roles)[0];
        }
        
        return 'usuario';
    }
    
    /**
     * Obtiene los items del menú para el módulo actual
     * @return array
     */
    private function getModuleMenuItems()
    {
        // Mapeo de módulos a sus menús
        $moduleMenus = [
            'atletas' => $this->getAtletasMenu(),
            'tienda' => $this->getTiendaMenu(),
            'escuela_club' => $this->getEscuelaMenu(),
            'aportes' => $this->getAportesMenu(),
            'reportes' => $this->getReportesMenu(),
        ];
        
        return $moduleMenus[$this->module] ?? [];
    }
    
    /**
     * Menú para el módulo de Atletas
     * @return array
     */
    private function getAtletasMenu()
    {
        $baseUrl = ['/atletas/default/index'];
        $items = [];
        
        // Solo mostrar si tiene permiso
        if (Yii::$app->user->can('acceder_atletas')) {
            $items[] = [
                'label' => '<i class="bi bi-people-fill me-2"></i> Lista de Atletas',
                'url' => ['/atletas/atleta/index'],
                'active' => $this->isActive(['atletas/atleta/index', 'atletas/atleta/view', 'atletas/atleta/create', 'atletas/atleta/update']),
            ];
            
            if (Yii::$app->user->can('gestionar_atletas')) {
                $items[] = [
                    'label' => '<i class="bi bi-plus-circle me-2"></i> Nuevo Atleta',
                    'url' => ['/atletas/atleta/create'],
                    'active' => $this->isActive('atletas/atleta/create'),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-card-checklist me-2"></i> Categorías',
                    'url' => ['/atletas/categoria/index'],
                    'active' => $this->isActive(['atletas/categoria/index', 'atletas/categoria/view', 'atletas/categoria/create', 'atletas/categoria/update']),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-trophy me-2"></i> Disciplinas',
                    'url' => ['/atletas/disciplina/index'],
                    'active' => $this->isActive(['atletas/disciplina/index', 'atletas/disciplina/view', 'atletas/disciplina/create', 'atletas/disciplina/update']),
                ];
            }
            
            $items[] = [
                'label' => '<i class="bi bi-bar-chart me-2"></i> Estadísticas',
                'url' => ['/atletas/default/estadisticas'],
                'active' => $this->isActive('atletas/default/estadisticas'),
                'visible' => Yii::$app->user->can('ver_estadisticas_atletas'),
            ];
            
            $items[] = ['label' => '', 'options' => ['class' => 'divider']];
            
            $items[] = [
                'label' => '<i class="bi bi-printer me-2"></i> Reportes',
                'url' => ['/atletas/reporte/index'],
                'active' => $this->isActive(['atletas/reporte/index', 'atletas/reporte/generar']),
                'visible' => Yii::$app->user->can('generar_reportes_atletas'),
            ];
        }
        
        return $items;
    }
    
    /**
     * Menú para el módulo de Tienda/Marketplace
     * @return array
     */
    private function getTiendaMenu()
    {
        $items = [];
        
        if (Yii::$app->user->can('acceder_tienda')) {
            $items[] = [
                'label' => '<i class="bi bi-shop me-2"></i> Catálogo',
                'url' => ['/tienda/producto/index'],
                'active' => $this->isActive(['tienda/producto/index', 'tienda/producto/view']),
            ];
            
            if (Yii::$app->user->can('gestionar_tienda')) {
                $items[] = [
                    'label' => '<i class="bi bi-bag-plus me-2"></i> Nuevo Producto',
                    'url' => ['/tienda/producto/create'],
                    'active' => $this->isActive('tienda/producto/create'),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-tags me-2"></i> Categorías',
                    'url' => ['/tienda/categoria/index'],
                    'active' => $this->isActive(['tienda/categoria/index', 'tienda/categoria/view', 'tienda/categoria/create', 'tienda/categoria/update']),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-cart-check me-2"></i> Órdenes',
                    'url' => ['/tienda/orden/index'],
                    'active' => $this->isActive(['tienda/orden/index', 'tienda/orden/view', 'tienda/orden/update']),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-box-seam me-2"></i> Inventario',
                    'url' => ['/tienda/inventario/index'],
                    'active' => $this->isActive(['tienda/inventario/index', 'tienda/inventario/ajustar']),
                ];
            }
            
            $items[] = [
                'label' => '<i class="bi bi-cart me-2"></i> Mi Carrito',
                'url' => ['/tienda/carrito/index'],
                'active' => $this->isActive(['tienda/carrito/index', 'tienda/carrito/actualizar']),
            ];
            
            $items[] = [
                'label' => '<i class="bi bi-receipt me-2"></i> Mis Compras',
                'url' => ['/tienda/compra/index'],
                'active' => $this->isActive(['tienda/compra/index', 'tienda/compra/view']),
            ];
            
            if (Yii::$app->user->can('ver_estadisticas_tienda')) {
                $items[] = ['label' => '', 'options' => ['class' => 'divider']];
                
                $items[] = [
                    'label' => '<i class="bi bi-graph-up me-2"></i> Ventas',
                    'url' => ['/tienda/estadistica/ventas'],
                    'active' => $this->isActive('tienda/estadistica/ventas'),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-pie-chart me-2"></i> Reportes',
                    'url' => ['/tienda/reporte/index'],
                    'active' => $this->isActive('tienda/reporte/index'),
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * Menú para el módulo de Escuela/Club
     * @return array
     */
    private function getEscuelaMenu()
    {
        $items = [];
        
        if (Yii::$app->user->can('acceder_escuela')) {
            $items[] = [
                'label' => '<i class="bi bi-house-door me-2"></i> Inicio',
                'url' => ['/escuela_club/default/index'],
                'active' => $this->isActive('escuela_club/default/index'),
            ];
            
            $items[] = [
                'label' => '<i class="bi bi-info-circle me-2"></i> Información',
                'url' => ['/escuela_club/escuela/informacion'],
                'active' => $this->isActive('escuela_club/escuela/informacion'),
            ];
            
            if (Yii::$app->user->can('gestionar_escuela')) {
                $items[] = [
                    'label' => '<i class="bi bi-gear me-2"></i> Configuración',
                    'url' => ['/escuela_club/escuela/configuracion'],
                    'active' => $this->isActive('escuela_club/escuela/configuracion'),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-people me-2"></i> Personal',
                    'url' => ['/escuela_club/personal/index'],
                    'active' => $this->isActive(['escuela_club/personal/index', 'escuela_club/personal/view', 'escuela_club/personal/create', 'escuela_club/personal/update']),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-calendar-event me-2"></i> Eventos',
                    'url' => ['/escuela_club/evento/index'],
                    'active' => $this->isActive(['escuela_club/evento/index', 'escuela_club/evento/view', 'escuela_club/evento/create', 'escuela_club/evento/update']),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-cash-coin me-2"></i> Finanzas',
                    'url' => ['/escuela_club/finanza/index'],
                    'active' => $this->isActive(['escuela_club/finanza/index', 'escuela_club/finanza/reporte']),
                ];
            }
            
            $items[] = [
                'label' => '<i class="bi bi-clock-history me-2"></i> Horarios',
                'url' => ['/escuela_club/horario/index'],
                'active' => $this->isActive(['escuela_club/horario/index', 'escuela_club/horario/view']),
            ];
            
            $items[] = [
                'label' => '<i class="bi bi-images me-2"></i> Galería',
                'url' => ['/escuela_club/galeria/index'],
                'active' => $this->isActive(['escuela_club/galeria/index', 'escuela_club/galeria/view']),
            ];
            
            $items[] = ['label' => '', 'options' => ['class' => 'divider']];
            
            $items[] = [
                'label' => '<i class="bi bi-telephone me-2"></i> Contacto',
                'url' => ['/escuela_club/contacto/index'],
                'active' => $this->isActive('escuela_club/contacto/index'),
            ];
        }
        
        return $items;
    }
    
    /**
     * Menú para el módulo de Aportes
     * @return array
     */
    private function getAportesMenu()
    {
        $items = [];
        
        if (Yii::$app->user->can('acceder_aportes')) {
            $items[] = [
                'label' => '<i class="bi bi-wallet2 me-2"></i> Mis Aportes',
                'url' => ['/aportes/aporte/mis-aportes'],
                'active' => $this->isActive('aportes/aporte/mis-aportes'),
            ];
            
            if (Yii::$app->user->can('gestionar_aportes')) {
                $items[] = [
                    'label' => '<i class="bi bi-cash-stack me-2"></i> Todos los Aportes',
                    'url' => ['/aportes/aporte/index'],
                    'active' => $this->isActive(['aportes/aporte/index', 'aportes/aporte/view']),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-plus-circle me-2"></i> Registrar Aporte',
                    'url' => ['/aportes/aporte/create'],
                    'active' => $this->isActive('aportes/aporte/create'),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-currency-exchange me-2"></i> Tipos de Aporte',
                    'url' => ['/aportes/tipo/index'],
                    'active' => $this->isActive(['aportes/tipo/index', 'aportes/tipo/view', 'aportes/tipo/create', 'aportes/tipo/update']),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-person-badge me-2"></i> Cuotas por Rol',
                    'url' => ['/aportes/cuota/index'],
                    'active' => $this->isActive(['aportes/cuota/index', 'aportes/cuota/view', 'aportes/cuota/create', 'aportes/cuota/update']),
                ];
            }
            
            $items[] = [
                'label' => '<i class="bi bi-receipt me-2"></i> Recibos',
                'url' => ['/aportes/recibo/index'],
                'active' => $this->isActive(['aportes/recibo/index', 'aportes/recibo/generar']),
            ];
            
            if (Yii::$app->user->can('ver_estadisticas_aportes')) {
                $items[] = ['label' => '', 'options' => ['class' => 'divider']];
                
                $items[] = [
                    'label' => '<i class="bi bi-pie-chart-fill me-2"></i> Estadísticas',
                    'url' => ['/aportes/estadistica/index'],
                    'active' => $this->isActive('aportes/estadistica/index'),
                ];
                
                $items[] = [
                    'label' => '<i class="bi bi-file-earmark-bar-graph me-2"></i> Reportes',
                    'url' => ['/aportes/reporte/index'],
                    'active' => $this->isActive('aportes/reporte/index'),
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * Menú para el módulo de Reportes
     * @return array
     */
    private function getReportesMenu()
    {
        $items = [];
        
        if (Yii::$app->user->can('acceder_reportes')) {
            $items[] = [
                'label' => '<i class="bi bi-speedometer2 me-2"></i> Dashboard',
                'url' => ['/reportes/default/dashboard'],
                'active' => $this->isActive('reportes/default/dashboard'),
            ];
            
            $items[] = ['label' => 'Reportes por Módulo', 'options' => ['class' => 'menu-header']];
            
            if (Yii::$app->user->can('ver_reportes_atletas')) {
                $items[] = [
                    'label' => '<i class="bi bi-people me-2"></i> Atletas',
                    'url' => ['/reportes/atleta/index'],
                    'active' => $this->isActive(['reportes/atleta/index', 'reportes/atleta/generar']),
                ];
            }
            
            if (Yii::$app->user->can('ver_reportes_finanzas')) {
                $items[] = [
                    'label' => '<i class="bi bi-cash-coin me-2"></i> Finanzas',
                    'url' => ['/reportes/finanza/index'],
                    'active' => $this->isActive(['reportes/finanza/index', 'reportes/finanza/generar']),
                ];
            }
            
            if (Yii::$app->user->can('ver_reportes_tienda')) {
                $items[] = [
                    'label' => '<i class="bi bi-shop me-2"></i> Tienda',
                    'url' => ['/reportes/tienda/index'],
                    'active' => $this->isActive(['reportes/tienda/index', 'reportes/tienda/generar']),
                ];
            }
            
            if (Yii::$app->user->can('ver_reportes_aportes')) {
                $items[] = [
                    'label' => '<i class="bi bi-wallet2 me-2"></i> Aportes',
                    'url' => ['/reportes/aporte/index'],
                    'active' => $this->isActive(['reportes/aporte/index', 'reportes/aporte/generar']),
                ];
            }
            
            if (Yii::$app->user->can('ver_reportes_escuela')) {
                $items[] = [
                    'label' => '<i class="bi bi-building me-2"></i> Escuela',
                    'url' => ['/reportes/escuela/index'],
                    'active' => $this->isActive(['reportes/escuela/index', 'reportes/escuela/generar']),
                ];
            }
            
            $items[] = ['label' => '', 'options' => ['class' => 'divider']];
            
            $items[] = [
                'label' => '<i class="bi bi-gear me-2"></i> Configuración',
                'url' => ['/reportes/configuracion/index'],
                'active' => $this->isActive('reportes/configuracion/index'),
                'visible' => Yii::$app->user->can('gestionar_reportes'),
            ];
            
            $items[] = [
                'label' => '<i class="bi bi-clock-history me-2"></i> Historial',
                'url' => ['/reportes/historial/index'],
                'active' => $this->isActive('reportes/historial/index'),
                'visible' => Yii::$app->user->can('ver_historial_reportes'),
            ];
        }
        
        return $items;
    }
    
    /**
     * Renderiza el menú
     * @param array $items Items del menú
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
        $html = Html::beginTag('nav', $this->options);
        $html .= Html::beginTag('ul', ['class' => 'nav flex-column']);
        
        foreach ($items as $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }
            
            if (isset($item['options']['class']) && $item['options']['class'] === 'divider') {
                $html .= Html::tag('li', '', ['class' => 'dropdown-divider my-2']);
                continue;
            }
            
            if (isset($item['options']['class']) && $item['options']['class'] === 'menu-header') {
                $html .= Html::tag('li', 
                    Html::tag('span', $item['label'], ['class' => 'menu-header-text']),
                    ['class' => 'nav-item menu-header']
                );
                continue;
            }
            
            $liClass = 'nav-item';
            $aClass = 'nav-link';
            
            if (isset($item['active']) && $item['active']) {
                $liClass .= ' active';
                $aClass .= ' active';
            }
            
            $html .= Html::beginTag('li', ['class' => $liClass]);
            $html .= Html::a($item['label'], $item['url'], [
                'class' => $aClass,
                'title' => strip_tags($item['label'])
            ]);
            $html .= Html::endTag('li');
        }
        
        $html .= Html::endTag('ul');
        $html .= Html::endTag('nav');
        
        return $html;
    }
    
    /**
     * Renderiza el menú para móvil
     * @param array $items
     * @return string
     */
    private function renderMobileMenu($items)
    {
        $html = Html::beginTag('div', ['class' => 'mobile-module-menu']);
        
        $html .= Html::tag('div', 
            Html::tag('h5', 
                '<i class="bi bi-' . $this->getModuleIcon() . ' me-2"></i>' . 
                $this->getModuleTitle($this->module),
                ['class' => 'mb-3']
            ),
            ['class' => 'mobile-menu-header p-3']
        );
        
        $html .= Html::beginTag('div', ['class' => 'mobile-menu-items']);
        
        foreach ($items as $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }
            
            if (isset($item['options']['class']) && $item['options']['class'] === 'divider') {
                continue; // No mostrar divisores en móvil
            }
            
            if (isset($item['options']['class']) && $item['options']['class'] === 'menu-header') {
                $html .= Html::tag('div', 
                    $item['label'],
                    ['class' => 'mobile-menu-section-header']
                );
                continue;
            }
            
            $linkClass = 'mobile-menu-item';
            if (isset($item['active']) && $item['active']) {
                $linkClass .= ' active';
            }
            
            $html .= Html::a(
                $item['label'],
                $item['url'],
                ['class' => $linkClass]
            );
        }
        
        $html .= Html::endTag('div');
        $html .= Html::endTag('div');
        
        return $html;
    }
    
    /**
     * Verifica si una ruta está activa
     * @param string|array $routes Ruta(s) a verificar
     * @return bool
     */
    private function isActive($routes)
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
     * Obtiene el título del módulo
     * @param string $module Nombre del módulo
     * @return string
     */
    private function getModuleTitle($module)
    {
        $titles = [
            'atletas' => 'Atletas',
            'tienda' => 'MarketPlace',
            'escuela_club' => 'Escuela/Club',
            'aportes' => 'Aportes',
            'reportes' => 'Reportes',
        ];
        
        return $titles[$module] ?? ucfirst($module);
    }
    
    /**
     * Obtiene el icono del módulo
     * @return string
     */
    private function getModuleIcon()
    {
        $icons = [
            'atletas' => 'people',
            'tienda' => 'shop',
            'escuela_club' => 'building',
            'aportes' => 'wallet2',
            'reportes' => 'file-bar-graph',
        ];
        
        return $icons[$this->module] ?? 'grid';
    }
}