<?php
// modules/atletas/models/AsistenciaSearch.php

namespace app\modules\atletas\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * AsistenciaSearch represents the model behind the search form of `app\modules\atletas\models\Asistencia`.
 */
class AsistenciaSearch extends Asistencia
{
    public $nombreAtleta;
    public $nombreEscuela;
    public $fecha_desde;
    public $fecha_hasta;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'id_atleta', 'id_escuela', 'u_creacion', 'u_update'], 'integer'],
            [['fecha_practica', 'hora_entrada', 'hora_salida', 'justificacion', 'tipo_justificacion', 'comentarios', 'd_creacion', 'd_update', 'dir_ip', 'nombreAtleta', 'nombreEscuela', 'fecha_desde', 'fecha_hasta'], 'safe'],
            [['asistio', 'tardanza', 'eliminado'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     */
    public function search($params)
    {
        $query = Asistencia::find()
            ->joinWith(['atleta', 'escuela']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['fecha_practica' => SORT_DESC],
                'attributes' => [
                    'fecha_practica',
                    'hora_entrada',
                    'hora_salida',
                    'asistio',
                    'tardanza',
                    'nombreAtleta' => [
                        'asc' => ['atletas.registro.p_nombre' => SORT_ASC, 'atletas.registro.p_apellido' => SORT_ASC],
                        'desc' => ['atletas.registro.p_nombre' => SORT_DESC, 'atletas.registro.p_apellido' => SORT_DESC],
                    ],
                    'nombreEscuela' => [
                        'asc' => ['atletas.escuela.nombre' => SORT_ASC],
                        'desc' => ['atletas.escuela.nombre' => SORT_DESC],
                    ],
                ],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'id_atleta' => $this->id_atleta,
            'id_escuela' => $this->id_escuela,
            'fecha_practica' => $this->fecha_practica,
            'asistio' => $this->asistio,
            'tardanza' => $this->tardanza,
            'u_creacion' => $this->u_creacion,
            'u_update' => $this->u_update,
        ]);

        // Filtro por nombre de atleta
        if (!empty($this->nombreAtleta)) {
            $query->andFilterWhere(['or',
                ['ilike', 'atletas.registro.p_nombre', $this->nombreAtleta],
                ['ilike', 'atletas.registro.s_nombre', $this->nombreAtleta],
                ['ilike', 'atletas.registro.p_apellido', $this->nombreAtleta],
                ['ilike', 'atletas.registro.s_apellido', $this->nombreAtleta]
            ]);
        }

        // Filtro por nombre de escuela
        if (!empty($this->nombreEscuela)) {
            $query->andFilterWhere(['ilike', 'atletas.escuela.nombre', $this->nombreEscuela]);
        }

        // Filtro por rango de fechas
        if (!empty($this->fecha_desde)) {
            $query->andFilterWhere(['>=', 'fecha_practica', $this->fecha_desde]);
        }
        if (!empty($this->fecha_hasta)) {
            $query->andFilterWhere(['<=', 'fecha_practica', $this->fecha_hasta]);
        }

        return $dataProvider;
    }
}