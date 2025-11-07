<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "catalogos.sexo".
 *
 * @property int $id
 * @property string $descripcion
 * @property int $fecha_creacion
 * @property int $usuario_creacion
 * @property int $fecha_update
 * @property int $usuario_update
 */
class Sexo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'catalogos.sexo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'fecha_creacion', 'usuario_creacion', 'fecha_update', 'usuario_update'], 'default', 'value' => null],
            [['fecha_creacion', 'usuario_creacion', 'fecha_update', 'usuario_update'], 'integer'],
            [['descripcion'], 'string', 'max' => 100],
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
            'fecha_creacion' => Yii::t('app', 'Fecha Creacion'),
            'usuario_creacion' => Yii::t('app', 'Usuario Creacion'),
            'fecha_update' => Yii::t('app', 'Fecha Update'),
            'usuario_update' => Yii::t('app', 'Usuario Update'),
        ];
    }
}
