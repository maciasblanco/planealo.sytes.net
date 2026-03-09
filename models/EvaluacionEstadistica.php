<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.evaluacion_estadistica".
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $unidad
 * @property bool|null $activo
 * @property int|null $orden
 * @property string $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool|null $eliminado
 * @property string|null $dir_ip
 *
 * @property EvaluacionResultado[] $evaluacionResultados
 * @property EvaluacionSesionEstadistica[] $evaluacionSesionEstadisticas
 * @property VoleibolSesion[] $sesiones
 */
class EvaluacionEstadistica extends ActiveRecord
{
    public static function tableName()
    {
        return 'estadisticas.evaluacion_estadistica';
    }

    public function rules()
    {
        return [
            [['nombre'], 'required'],
            [['descripcion'], 'string'],
            [['activo', 'eliminado'], 'boolean'],
            [['orden', 'u_creacion', 'u_update'], 'default', 'value' => null],
            [['orden', 'u_creacion', 'u_update'], 'integer'],
            [['d_creacion', 'd_update'], 'safe'],
            [['nombre', 'unidad', 'dir_ip'], 'string', 'max' => 255],
            [['nombre'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'unidad' => 'Unidad',
            'activo' => 'Activo',
            'orden' => 'Orden',
            'd_creacion' => 'Creado',
            'u_creacion' => 'Creado por',
            'd_update' => 'Actualizado',
            'u_update' => 'Actualizado por',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'IP',
        ];
    }

    public function getEvaluacionResultados()
    {
        return $this->hasMany(EvaluacionResultado::class, ['id_estadistica' => 'id']);
    }

    public function getEvaluacionSesionEstadisticas()
    {
        return $this->hasMany(EvaluacionSesionEstadistica::class, ['id_estadistica' => 'id']);
    }

    public function getSesiones()
    {
        return $this->hasMany(VoleibolSesion::class, ['id' => 'id_sesion'])
            ->via('evaluacionSesionEstadisticas');
    }
}