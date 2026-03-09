<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "estadisticas.voleibol_sesion".
 *
 * @property int $id
 * @property int $escuela_id
 * @property int|null $categoria_id
 * @property string|null $nombre
 * @property string $fecha
 * @property string $estado
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 *
 * @property Escuela $escuela
 * @property CategoriaAtletas $categoria
 * @property User $creador
 * @property VoleibolSet[] $voleibolSets
 * @property VoleibolSesionAtleta[] $voleibolSesionAtletas
 * @property AtletasRegistro[] $atletas
 * @property VoleibolEvento[] $voleibolEventos
 * @property EvaluacionResultado[] $evaluacionResultados
 * @property EvaluacionSesionEstadistica[] $evaluacionSesionEstadisticas
 * @property EvaluacionEstadistica[] $estadisticasSeleccionadas
 */
class VoleibolSesion extends ActiveRecord
{
    public static function tableName()
    {
        return 'estadisticas.voleibol_sesion';
    }

    public function rules()
    {
        return [
            [['escuela_id', 'fecha', 'created_by'], 'required'],
            [['escuela_id', 'categoria_id', 'created_by'], 'default', 'value' => null],
            [['escuela_id', 'categoria_id', 'created_by'], 'integer'],
            [['fecha'], 'safe'],
            [['estado'], 'string'],
            [['nombre'], 'string', 'max' => 255],
            [['estado'], 'in', 'range' => ['A', 'F']],
            [['escuela_id'], 'exist', 'skipOnError' => true, 'targetClass' => Escuela::class, 'targetAttribute' => ['escuela_id' => 'id']],
            [['categoria_id'], 'exist', 'skipOnError' => true, 'targetClass' => CategoriaAtletas::class, 'targetAttribute' => ['categoria_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'escuela_id' => 'Escuela',
            'categoria_id' => 'Categoría',
            'nombre' => 'Nombre',
            'fecha' => 'Fecha',
            'estado' => 'Estado',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
            'created_by' => 'Creado por',
        ];
    }

    public function getEscuela()
    {
        return $this->hasOne(Escuela::class, ['id' => 'escuela_id']);
    }

    public function getCategoria()
    {
        return $this->hasOne(CategoriaAtletas::class, ['id' => 'categoria_id']);
    }

    public function getCreador()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getVoleibolSets()
    {
        return $this->hasMany(VoleibolSet::class, ['sesion_id' => 'id']);
    }

    public function getVoleibolSesionAtletas()
    {
        return $this->hasMany(VoleibolSesionAtleta::class, ['sesion_id' => 'id']);
    }

    public function getAtletas()
    {
        return $this->hasMany(AtletasRegistro::class, ['id' => 'atleta_id'])
            ->via('voleibolSesionAtletas');
    }

    public function getVoleibolEventos()
    {
        return $this->hasMany(VoleibolEvento::class, ['sesion_id' => 'id']);
    }

    public function getEvaluacionResultados()
    {
        return $this->hasMany(EvaluacionResultado::class, ['id_sesion' => 'id']);
    }

    public function getEvaluacionSesionEstadisticas()
    {
        return $this->hasMany(EvaluacionSesionEstadistica::class, ['id_sesion' => 'id']);
    }

    public function getEstadisticasSeleccionadas()
    {
        return $this->hasMany(EvaluacionEstadistica::class, ['id' => 'id_estadistica'])
            ->via('evaluacionSesionEstadisticas');
    }

    // Devuelve el set activo (estado = 'A')
    public function getSetActivo()
    {
        return $this->hasOne(VoleibolSet::class, ['sesion_id' => 'id'])
            ->andWhere(['estado' => 'A']);
    }
}