<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.evaluacion_resultado".
 *
 * @property int $id
 * @property int $id_sesion
 * @property int $id_atleta
 * @property int $id_estadistica
 * @property float $valor_numerico
 * @property int|null $set_numero
 * @property string $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool|null $eliminado
 * @property string|null $dir_ip
 *
 * @property VoleibolSesion $sesion
 * @property AtletasRegistro $atleta
 * @property EvaluacionEstadistica $estadistica
 */
class EvaluacionResultado extends ActiveRecord
{
    public static function tableName()
    {
        return 'estadisticas.evaluacion_resultado';
    }

    public function rules()
    {
        return [
            [['id_sesion', 'id_atleta', 'id_estadistica', 'valor_numerico'], 'required'],
            [['id_sesion', 'id_atleta', 'id_estadistica', 'set_numero', 'u_creacion', 'u_update'], 'default', 'value' => null],
            [['id_sesion', 'id_atleta', 'id_estadistica', 'set_numero', 'u_creacion', 'u_update'], 'integer'],
            [['valor_numerico'], 'number'],
            [['eliminado'], 'boolean'],
            [['d_creacion', 'd_update'], 'safe'],
            [['dir_ip'], 'string', 'max' => 45],
            [['id_sesion'], 'exist', 'skipOnError' => true, 'targetClass' => VoleibolSesion::class, 'targetAttribute' => ['id_sesion' => 'id']],
            [['id_atleta'], 'exist', 'skipOnError' => true, 'targetClass' => AtletasRegistro::class, 'targetAttribute' => ['id_atleta' => 'id']],
            [['id_estadistica'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluacionEstadistica::class, 'targetAttribute' => ['id_estadistica' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_sesion' => 'Sesión',
            'id_atleta' => 'Atleta',
            'id_estadistica' => 'Estadística',
            'valor_numerico' => 'Valor',
            'set_numero' => 'Set',
            'd_creacion' => 'Creado',
            'u_creacion' => 'Creado por',
            'd_update' => 'Actualizado',
            'u_update' => 'Actualizado por',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'IP',
        ];
    }

    public function getSesion()
    {
        return $this->hasOne(VoleibolSesion::class, ['id' => 'id_sesion']);
    }

    public function getAtleta()
    {
        return $this->hasOne(AtletasRegistro::class, ['id' => 'id_atleta']);
    }

    public function getEstadistica()
    {
        return $this->hasOne(EvaluacionEstadistica::class, ['id' => 'id_estadistica']);
    }
}