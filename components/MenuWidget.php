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
        
        // ✅ DETECCIÓN ROBUSTA DE DISPOSITIVOS MÓVILES - CORREGIDO
        try {
            // CORRECCIÓN: Usar el nombre correcto del componente 'mobileDetect'
            if (Yii::$app->has('mobileDetect')) {
                $this->mobileMode = Yii::$app->mobileDetect->isMobile();
            } 
            // Fallback: detección básica por User-Agent
            else {
                $userAgent = Yii::$app->request->getUserAgent();
                $this->mobileMode = $this->isMobileUserAgent($userAgent);
            }
        } catch (\Exception $e) {
            Yii::error('Error en detección móvil: ' . $e->getMessage());
            $this->mobileMode = false; // Fallback seguro
        }
        
        // Permitir override manual mediante options
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
            $menuItems = $this->getMenuItems($this->parentId);
            
            if (empty($menuItems)) {
                return $this->renderFallbackMenu();
            }
            
            // ✅ RENDERIZAR DIFERENTE PARA MÓVIL
            if ($this->mobileMode) {
                return $this->renderMenuForMobile($menuItems);
            }
            
            return $this->renderMenuForDesktop($menuItems);
        } catch (\Exception $e) {
            Yii::error('Error en MenuWidget: ' . $e->getMessage());
            return $this->renderFallbackMenu();
        }
    }

    /**
     * ✅ DETECCIÓN BÁSICA POR USER-AGENT (FALLBACK)
     */
    private function isMobileUserAgent($userAgent)
    {
        if (empty($userAgent)) {
            return false;
        }

        $mobileKeywords = [
            'mobile', 'android', 'iphone', 'ipod', 'blackberry', 
            'webos', 'opera mini', 'windows phone', 'iemobile'
        ];

        $userAgent = strtolower($userAgent);
        
        foreach ($mobileKeywords as $keyword) {
            if (strpos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ✅ VERIFICAR SI UN ITEM DEL MENÚ ES PÚBLICO (MARKETPLACE)
     * Basado en ID del menú o configuración
     */
    protected function isPublicMenuItem($item)
    {
        // IDs de menús que deben ser públicos
        $publicMenuIds = [
            177, // Marketplace principal
            // Agrega aquí otros IDs públicos si los conoces
        ];
        
        // Verificar por ID
        if (isset($item['id']) && in_array($item['id'], $publicMenuIds)) {
            return true;
        }
        
        // Verificar por ruta (patrones de marketplace)
        if (isset($item['route'])) {
            $route = $item['route'];
            $publicPatterns = [
                'tienda/*',
                'tienda/marketplace/*',
                'marketplace/*',
                'tienda/default/*',
                'tienda/marketplace/index',
                'tienda/marketplace/categoria',
                'tienda/marketplace/producto',
                'tienda/marketplace/buscar',
            ];
            
            foreach ($publicPatterns as $pattern) {
                if (strpos($pattern, '*') !== false) {
                    $regexPattern = str_replace('\*', '.*', preg_quote($pattern, '/'));
                    if (preg_match('/^' . $regexPattern . '$/', $route)) {
                        return true;
                    }
                } elseif ($route === $pattern) {
                    return true;
                }
            }
        }
        
        return false;
    }

    protected function renderFallbackMenu()
    {
        $menuClass = $this->mobileMode ? 'sidebar-menu' : $this->menuClass;
        
        $menuItems = '
        <ul class="' . $menuClass . '">
            <li class="' . ($this->mobileMode ? 'menu-item' : 'nav-item') . '">
                <a class="' . ($this->mobileMode ? 'menu-link' : 'nav-link text-white') . '" href="' . Url::to(['/']) . '">Inicio</a>
            </li>';
            
        // Solo mostrar login si el usuario no está autenticado
        if (Yii::$app->user->isGuest) {
            $menuItems .= '
            <li class="' . ($this->mobileMode ? 'menu-item' : 'nav-item') . '">
                <a class="' . ($this->mobileMode ? 'menu-link' : 'nav-link text-white') . '" href="' . Url::to(['/site/login']) . '">Iniciar Sesión</a>
            </li>';
        }
        
        $menuItems .= '</ul>';
        
        return $menuItems;
    }

    /**
     * ✅ OBTENER ITEMS DEL MENÚ CON CONTROL DE PERMISOS RBAC
     */
    protected function getMenuItems($parentId = null)
    {
        try {
            // ✅ MEJORA: Validación más robusta de la conexión
            $db = Yii::$app->db;
            if (!$db || $db->getIsActive() === false) {
                Yii::warning('Base de datos no disponible para menú');
                return [];
            }
            
            // ✅ CONSULTA OPTIMIZADA PARA RBAC BÁSICO
            $query = new Query();
            $items = $query->select(['m.id', 'm.name', 'm.route', 'm.parent', 'm.order', 'm.data'])
                      ->from('seguridad.menu m')
                      ->where(['m.parent' => $parentId])
                      ->orderBy(['m.order' => SORT_ASC])
                      ->all();
            
        } catch (\Exception $e) {
            Yii::error('Error crítico en getMenuItems: ' . $e->getMessage());
            return [];
        }

        $menuItems = [];

        foreach ($items as $item) {
            // ✅ VERIFICAR SI ES MENÚ PÚBLICO (MARKETPLACE) PRIMERO
            if ($this->isPublicMenuItem($item)) {
                // Para menús públicos, mostrarlos siempre
                $childItems = $this->getMenuItems($item['id']);
                
                // INCLUIR ITEM PADRE AUNQUE NO TENGA HIJOS
                $hasVisibleChildren = !empty($childItems);
                $isContainer = empty($item['route']) || $item['route'] == '#';
                
                if ($isContainer && !$hasVisibleChildren) {
                    continue; // Saltar contenedores vacíos
                }
                
                $menuItem = [
                    'label' => $item['name'],
                    'url' => $item['route'] ? [$item['route']] : '#',
                    'items' => $childItems,
                    'visible' => true // Siempre visible por ser público
                ];

                $menuItems[] = $menuItem;
                continue;
            }
            
            // ✅ VERIFICAR PERMISOS RBAC PARA MENÚS NO PÚBLICOS
            if (!$this->checkMenuItemPermission($item)) {
                continue; // Saltar item si no tiene permisos
            }

            $childItems = $this->getMenuItems($item['id']);
            
            // ✅ INCLUIR ITEM PADRE AUNQUE NO TENGA HIJOS SI TIENE RUTA Y PERMISOS
            $hasVisibleChildren = !empty($childItems);
            $isContainer = empty($item['route']) || $item['route'] == '#';
            
            if ($isContainer && !$hasVisibleChildren) {
                continue; // Saltar contenedores vacíos
            }
            
            $menuItem = [
                'label' => $item['name'],
                'url' => $item['route'] ? [$item['route']] : '#',
                'items' => $childItems,
                'visible' => true // Ya filtramos por permisos, así que siempre visible
            ];

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }

    /**
     * ✅ VERIFICAR PERMISOS RBAC PARA ITEM DEL MENÚ
     * Integrado con el sistema RBAC básico de Yii2
     */
    protected function checkMenuItemPermission($item)
    {
        // ✅ PRIMERO VERIFICAR SI ES UN MENÚ PÚBLICO (MARKETPLACE)
        if ($this->isPublicMenuItem($item)) {
            return true;
        }
        
        // Si no hay ruta definida, es un contenedor - mostrar si tiene hijos con permisos
        if (empty($item['route']) || $item['route'] == '#') {
            return true; // Los contenedores se manejan en getMenuItems
        }

        // ✅ VERIFICAR PERMISOS USANDO EL SISTEMA RBAC DE YII2
        try {
            $route = $item['route'];
            
            // ✅ 1. PRIMERO VERIFICAR RUTAS PÚBLICAS
            if ($this->isPublicRoute($route)) {
                return true;
            }
            
            // ✅ 2. SI ES USUARIO GUEST, SOLO PUEDE VER RUTAS PÚBLICAS
            if (Yii::$app->user->isGuest) {
                return false;
            }

            // ✅ 3. VERIFICAR PERMISO DIRECTAMENTE CON EL SISTEMA RBAC
            // Yii::$app->user->can() verifica automáticamente los roles y permisos del usuario
            if (Yii::$app->user->can($route)) {
                return true;
            }
            
            // ✅ 4. VERIFICAR PERMISOS POR PATRÓN (para módulos completos)
            $routeParts = explode('/', $route);
            if (count($routeParts) >= 2) {
                $modulePattern = $routeParts[0] . '/*';
                if (Yii::$app->user->can($modulePattern)) {
                    return true;
                }
                
                // Verificar también controlador/*
                if (count($routeParts) >= 2) {
                    $controllerPattern = $routeParts[0] . '/' . $routeParts[1] . '/*';
                    if (Yii::$app->user->can($controllerPattern)) {
                        return true;
                    }
                }
            }

            // ✅ 5. VERIFICAR ROLES DE ADMINISTRADOR
            if (Yii::$app->user->can('admin') || 
                Yii::$app->user->can('administrator') || 
                Yii::$app->user->can('superadmin')) {
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Yii::error('Error verificando permisos para ruta: ' . $item['route'] . ' - ' . $e->getMessage());
            return false; // Por seguridad, denegar acceso si hay error
        }
    }

    /**
     * ✅ VERIFICAR SI UNA RUTA ES PÚBLICA (ACCESIBLE SIN LOGIN)
     * Basado en la configuración de 'allowActions' de tu web.php
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
            'ged/*', // Según tu configuración en allowActions
            'site/*', // Según tu configuración en allowActions
            
            // ✅ AGREGAR TODAS LAS RUTAS DEL MARKETPLACE
            'tienda/*',
            'tienda/marketplace/*',
            'tienda/marketplace/index',
            'tienda/marketplace/buscar',
            'tienda/marketplace/categoria',
            'tienda/marketplace/producto',
            'tienda/default/index',
            'tienda/default/registro-vendedor',
            'tienda/default/dashboard-vendedor',
        ];

        // Verificar rutas exactas
        if (in_array($route, $publicRoutes)) {
            return true;
        }

        // Verificar patrones con wildcards
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

    // ✅ RENDERIZAR PARA MÓVIL
    protected function renderMenuForMobile($menuItems)
    {
        $content = $this->renderMobileMenuItems($menuItems);
        return '<ul class="sidebar-menu mobile-menu">' . $content . '</ul>';
    }

    // ✅ RENDERIZAR PARA ESCRITORIO
    protected function renderMenuForDesktop($menuItems)
    {
        $content = $this->renderDesktopMenuItems($menuItems);
        return '<ul class="' . $this->menuClass . ' desktop-menu">' . $content . '</ul>';
    }

    // ✅ RENDERIZAR ITEMS PARA MÓVIL
    protected function renderMobileMenuItems($items, $level = 0)
    {
        $html = '';
        
        foreach ($items as $item) {
            $hasChildren = !empty($item['items']);
            $url = $item['url'] == '#' ? '#' : Url::to($item['url']);
            $label = Html::encode($item['label']);
            
            if ($hasChildren) {
                $childrenHtml = $this->renderMobileMenuItems($item['items'], $level + 1);
                $html .= '
                <li class="menu-item has-children level-' . $level . '">
                    <a href="#" class="menu-link mobile-menu-link">
                        ' . $label . '
                        <span class="submenu-indicator">›</span>
                    </a>
                    <ul class="submenu submenu-level-' . $level . '" style="display: none;">
                        ' . $childrenHtml . '
                    </ul>
                </li>';
            } else {
                $html .= '
                <li class="menu-item level-' . $level . '">
                    <a href="' . $url . '" class="menu-link mobile-menu-link">' . $label . '</a>
                </li>';
            }
        }
        
        return $html;
    }

    // ✅ RENDERIZAR ITEMS PARA ESCRITORIO
    protected function renderDesktopMenuItems($items, $level = 0)
    {
        $html = '';
        
        foreach ($items as $item) {
            $hasChildren = !empty($item['items']);
            
            if ($hasChildren) {
                $html .= $this->renderDropdownItem($item, $level);
            } else {
                $html .= $this->renderSimpleItem($item, $level);
            }
        }
        
        return $html;
    }

    protected function renderSimpleItem($item, $level)
    {
        $url = $item['url'] == '#' ? '#' : Url::to($item['url']);
        $label = Html::encode($item['label']);
        
        if ($level === 0) {
            return '<li class="nav-item">
                <a class="nav-link text-white desktop-nav-link" href="' . $url . '">' . $label . '</a>
            </li>';
        } else {
            return '<li>
                <a class="dropdown-item text-white" href="' . $url . '">' . $label . '</a>
            </li>';
        }
    }

    protected function renderDropdownItem($item, $level)
    {
        $label = Html::encode($item['label']);
        $childrenHtml = $this->renderDesktopMenuItems($item['items'], $level + 1);
        
        if ($level === 0) {
            return '<li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white desktop-nav-link" href="#" role="button" 
                   data-bs-toggle="dropdown" aria-expanded="false" data-level="' . $level . '">
                    ' . $label . '
                </a>
                <ul class="dropdown-menu" data-level="' . $level . '">
                    ' . $childrenHtml . '
                </ul>
            </li>';
        } elseif ($level === 1) {
            return '<li class="dropdown-submenu position-relative" data-level="' . $level . '">
                <a class="dropdown-item dropdown-toggle text-white d-flex justify-content-between align-items-center" 
                   href="#" role="button" data-level="' . $level . '">
                    ' . $label . ' 
                    <span class="submenu-arrow">›</span>
                </a>
                <ul class="dropdown-menu submenu-level-1" data-level="' . $level . '">
                    ' . $childrenHtml . '
                </ul>
            </li>';
        } else {
            return '<li class="dropdown-submenu position-relative" data-level="' . $level . '">
                <a class="dropdown-item dropdown-toggle text-white d-flex justify-content-between align-items-center" 
                   href="#" role="button" data-level="' . $level . '">
                    ' . $label . '
                    <span class="submenu-arrow">›</span>
                </a>
                <ul class="dropdown-menu submenu-level-' . $level . ' deep-level" data-level="' . $level . '">
                    ' . $childrenHtml . '
                </ul>
            </li>';
        }
    }
}