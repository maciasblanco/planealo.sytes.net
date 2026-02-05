<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "catalogos.nacionalidad".
 *
 * @property int $id
 * @property string $descripcion
 * @property string $letra
 * @property int $fecha_creacion
 * @property int $usuario_creacion
 * @property int $fecha_update
 * @property int $usuario_update
 */
class Nacionalidad extends \yii\db\ActiveRecord
{
    const V = 1;
    const E = 2;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'catalogos.nacionalidad';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'letra', 'fecha_creacion', 'usuario_creacion', 'fecha_update', 'usuario_update'], 'default', 'value' => null],
            [['descripcion'], 'string'],
            [['fecha_creacion', 'usuario_creacion', 'fecha_update', 'usuario_update'], 'integer'],
            [['letra'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'descripcion' => Yii::t('app', 'Descripcion'),
            'letra' => Yii::t('app', 'Letra'),
            'fecha_creacion' => Yii::t('app', 'Fecha Creacion'),
            'usuario_creacion' => Yii::t('app', 'Usuario Creacion'),
            'fecha_update' => Yii::t('app', 'Fecha Update'),
            'usuario_update' => Yii::t('app', 'Usuario Update'),
        ];
    }
}
