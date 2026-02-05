<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "catalogos.discapacidad".
 *
 * @property int $id
 * @property string|null $descripcion
 * @property string|null $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool|null $eliminado
 * @property string|null $dir_ip
 */
class Discapacidad extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'catalogos.discapacidad';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'dir_ip'], 'string'],
            [['d_creacion', 'd_update'], 'safe'],
            [['u_creacion', 'u_update'], 'default', 'value' => null],
            [['u_creacion', 'u_update'], 'integer'],
            [['eliminado'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'descripcion' => 'Descripcion',
            'd_creacion' => 'D Creacion',
            'u_creacion' => 'U Creacion',
            'd_update' => 'D Update',
            'u_update' => 'U Update',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'Dir Ip',
        ];
    }
}
