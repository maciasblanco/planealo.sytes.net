<?php

namespace app\models;

use Yii;

/**
 * Modelo extendido de menú que agrega campos personalizados
 * y normaliza la ruta eliminando la barra inicial.
 * 
 * @property int $id
 * @property string $name
 * @property int|null $parent
 * @property string|null $route
 * @property int|null $order
 * @property string|null $data
 * @property bool|null $active
 * @property string|null $permission
 * @property string|null $icon
 * @property int|null $nivel
 * @property bool|null $mega_menu
 * @property int|null $mega_menu_columns
 * @property string|null $description
 * @property bool|null $show_as_public_container
 */
class Menu extends \mdm\admin\models\Menu
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        // Obtenemos las reglas del modelo padre
        $rules = parent::rules();
        
        // Agregamos reglas para los campos adicionales de la tabla seguridad.menu
        $rules[] = [['show_as_public_container', 'mega_menu', 'active'], 'boolean'];
        $rules[] = [['mega_menu_columns', 'nivel'], 'integer'];
        $rules[] = [['description'], 'string', 'max' => 255];
        $rules[] = [['permission'], 'string', 'max' => 100];
        $rules[] = [['icon'], 'string', 'max' => 50];
        
        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        // Obtenemos las etiquetas del modelo padre
        $labels = parent::attributeLabels();
        
        // Agregamos etiquetas para los campos adicionales
        $labels['show_as_public_container'] = 'Mostrar como contenedor público';
        $labels['mega_menu'] = 'Mega menú';
        $labels['mega_menu_columns'] = 'Columnas del mega menú';
        $labels['description'] = 'Descripción';
        $labels['permission'] = 'Permiso requerido';
        $labels['icon'] = 'Icono';
        $labels['nivel'] = 'Nivel';
        $labels['active'] = 'Activo';
        
        return $labels;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Eliminar la barra inicial de la ruta si existe
        if (!empty($this->route) && $this->route[0] === '/') {
            $this->route = ltrim($this->route, '/');
        }

        return true;
    }
}