<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "catalogos.categoria_atletas".
 *
 * @property int $id
 * @property string $nombre Nombre internacional de la categoría
 * @property string $nombre_venezuela Nombre de la categoría en Venezuela
 * @property int $edad_minima Edad mínima para la categoría
 * @property int $edad_maxima Edad máxima para la categoría
 * @property string|null $descripcion Descripción de la categoría
 * @property bool|null $activo Indica si la categoría está activa
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class CategoriaAtletas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'catalogos.categoria_atletas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'nombre_venezuela', 'edad_minima', 'edad_maxima'], 'required'],
            [['edad_minima', 'edad_maxima', 'created_by', 'updated_by'], 'default', 'value' => null],
            [['edad_minima', 'edad_maxima', 'created_by', 'updated_by'], 'integer'],
            [['descripcion'], 'string'],
            [['activo'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
            [['nombre', 'nombre_venezuela'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'nombre_venezuela' => 'Nombre Venezuela',
            'edad_minima' => 'Edad Minima',
            'edad_maxima' => 'Edad Maxima',
            'descripcion' => 'Descripcion',
            'activo' => 'Activo',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
        ];
    }
}