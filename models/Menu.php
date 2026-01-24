<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "seguridad.menu".
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
 */
class Menu extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seguridad.menu';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent', 'route', 'order', 'data', 'permission', 'icon', 'description'], 'default', 'value' => null],
            [['mega_menu_columns'], 'default', 'value' => 1],
            [['mega_menu'], 'default', 'value' => 0],
            [['name'], 'required'],
            [['parent', 'order', 'nivel', 'mega_menu_columns'], 'default', 'value' => null],
            [['parent', 'order', 'nivel', 'mega_menu_columns'], 'integer'],
            [['active', 'mega_menu'], 'boolean'],
            [['name'], 'string', 'max' => 128],
            [['route', 'data', 'description'], 'string', 'max' => 255],
            [['permission'], 'string', 'max' => 100],
            [['icon'], 'string', 'max' => 50],
            [['parent'], 'exist', 'skipOnError' => true, 'targetClass' => Menu::class, 'targetAttribute' => ['parent' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'parent' => 'Parent',
            'route' => 'Route',
            'order' => 'Order',
            'data' => 'Data',
            'active' => 'Active',
            'permission' => 'Permission',
            'icon' => 'Icon',
            'nivel' => 'Nivel',
            'mega_menu' => 'Mega Menu',
            'mega_menu_columns' => 'Mega Menu Columns',
            'description' => 'Description',
        ];
    }

}
