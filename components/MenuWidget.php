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
        
        // ✅ DETECCIÓN ROBUSTA DE DISPOSITIVOS MÓVILES
        try {
            // Usar el componente MobileDetect si está disponible
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

    protected function renderFallbackMenu()
    {
        $menuClass = $this->mobileMode ? 'sidebar-menu' : $this->menuClass;
        
        return '
        <ul class="' . $menuClass . '">
            <li class="' . ($this->mobileMode ? 'menu-item' : 'nav-item') . '">
                <a class="' . ($this->mobileMode ? 'menu-link' : 'nav-link text-white') . '" href="' . Url::to(['/']) . '">Inicio</a>
            </li>
            <li class="' . ($this->mobileMode ? 'menu-item' : 'nav-item') . '">
                <a class="' . ($this->mobileMode ? 'menu-link' : 'nav-link text-white') . '" href="#">Acerca de</a>
            </li>
            <li class="' . ($this->mobileMode ? 'menu-item' : 'nav-item') . '">
                <a class="' . ($this->mobileMode ? 'menu-link' : 'nav-link text-white') . '" href="' . Url::to(['/site/login']) . '">Iniciar Sesión</a>
            </li>
        </ul>';
    }

    protected function getMenuItems($parentId = null)
    {
        try {
            if (Yii::$app->db->getIsActive() === false) {
                Yii::$app->db->open();
            }
            
            $query = new Query();
            $query->select(['id', 'name', 'route', 'parent', 'order', 'data'])
                  ->from('seguridad.menu')
                  ->where(['parent' => $parentId])
                  ->orderBy(['order' => SORT_ASC]);

            $items = $query->all();
            
        } catch (\Exception $e) {
            Yii::error('Error en getMenuItems: ' . $e->getMessage());
            return [];
        }

        $menuItems = [];

        foreach ($items as $item) {
            $menuItem = [
                'label' => $item['name'],
                'url' => $item['route'] ? [$item['route']] : '#',
                'items' => $this->getMenuItems($item['id'])
            ];

            $menuItems[] = $menuItem;
        }

        return $menuItems;
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