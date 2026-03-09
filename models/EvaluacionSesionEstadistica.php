<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.evaluacion_sesion_estadistica".
 *
 * @property int $id_sesion
 * @property int $id_estadistica
 *
 * @property VoleibolSesion $sesion
 * @property EvaluacionEstadistica $estadistica
 */
class EvaluacionSesionEstadistica extends ActiveRecord
{
    public static function tableName()
    {
        return 'estadisticas.evaluacion_sesion_estadistica';
    }

    public static function primaryKey()
    {
        return ['id_sesion', 'id_estadistica'];
    }

    public function rules()
    {
        return [
            [['id_sesion', 'id_estadistica'], 'required'],
            [['id_sesion', 'id_estadistica'], 'integer'],
            [['id_sesion', 'id_estadistica'], 'unique', 'targetAttribute' => ['id_sesion', 'id_estadistica']],
            [['id_sesion'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSesion::class, 'targetAttribute' => ['id_sesion' => 'id']],
            [['id_estadistica'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluacionEstadistica::class, 'targetAttribute' => ['id_estadistica' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id_sesion' => 'Sesión',
            'id_estadistica' => 'Estadística',
        ];
    }

    public function getSesion()
    {
        return $this->hasOne(VoleibolSesion::class, ['id' => 'id_sesion']);
    }

    public function getEstadistica()
    {
        return $this->hasOne(EvaluacionEstadistica::class, ['id' => 'id_estadistica']);
    }
}