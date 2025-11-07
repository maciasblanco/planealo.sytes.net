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

    public function run()
    {
        try {
            $menuItems = $this->getMenuItems($this->parentId);
            
            if (empty($menuItems)) {
                return $this->renderFallbackMenu();
            }
            
            return $this->renderBootstrapMenu($menuItems);
        } catch (\Exception $e) {
            Yii::error('Error en MenuWidget: ' . $e->getMessage());
            return $this->renderFallbackMenu();
        }
    }

    protected function renderFallbackMenu()
    {
        return '
        <ul class="navbar-nav me-auto">
            <li class="nav-item">
                <a class="nav-link text-white" href="' . Url::to(['/']) . '">Inicio</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="#">Acerca de</a>
            </li>
        </ul>';
    }

    protected function getMenuItems($parentId = null, $level = 0)
    {
        // ✅ LIMITAR a 2 niveles máximo
        if ($level >= 2) {
            return [];
        }
        
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
                'items' => $this->getMenuItems($item['id'], $level + 1) // ✅ Pasar nivel
            ];

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }

    protected function renderBootstrapMenu($menuItems)
    {
        return $this->renderMenuItems($menuItems);
    }

    protected function renderMenuItems($items, $level = 0)
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
                <a class="nav-link text-white" href="' . $url . '">' . $label . '</a>
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
        $childrenHtml = $this->renderMenuItems($item['items'], $level + 1);
        
        if ($level === 0) {
            // ✅ PRIMER NIVEL - Bootstrap nativo
            return '<li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" role="button" 
                   data-bs-toggle="dropdown" aria-expanded="false">
                    ' . $label . '
                </a>
                <ul class="dropdown-menu">
                    ' . $childrenHtml . '
                </ul>
            </li>';
        } else if ($level === 1) {
            // ✅ SEGUNDO NIVEL - Bootstrap nativo (sin submenús más profundos)
            return '<li class="dropdown-submenu">
                <a class="dropdown-item dropdown-toggle text-white" href="#" role="button">
                    ' . $label . '
                </a>
                <ul class="dropdown-menu">
                    ' . $childrenHtml . '
                </ul>
            </li>';
        }
        
        return '';
    }
}