<?php

namespace app\modules\escuela_club\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Escuela;

/**
 * EscuelaRegistroSearch represents the model behind the search form of `app\models\Escuela`.
 */
class EscuelaRegistroSearch extends Escuela
{
    public $estadoNombre;
    public $municipioNombre;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'id_estado', 'id_municipio', 'id_parroquia', 'puntuacion'], 'integer'],
            [['nombre', 'direccion_administrativa', 'direccion_practicas', 'telefono', 'email', 'tipo_entidad', 'eliminado', 'estado_registro', 'estadoNombre', 'municipioNombre'], 'safe'],
            [['lat', 'lng'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     */
    public function search($params)
    {
        $query = Escuela::find()->alias('e');
        $query->joinWith(['estado es', 'municipio mu']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'nombre' => SORT_ASC,
                ],
                'attributes' => [
                    'nombre',
                    'telefono',
                    'email',
                    'estado_registro',
                    'd_creacion',
                    'estadoNombre' => [
                        'asc' => ['es.estado' => SORT_ASC],
                        'desc' => ['es.estado' => SORT_DESC],
                    ],
                    'municipioNombre' => [
                        'asc' => ['mu.municipio' => SORT_ASC],
                        'desc' => ['mu.municipio' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'e.id' => $this->id,
            'e.id_estado' => $this->id_estado,
            'e.id_municipio' => $this->id_municipio,
            'e.id_parroquia' => $this->id_parroquia,
            'e.lat' => $this->lat,
            'e.lng' => $this->lng,
            'e.puntuacion' => $this->puntuacion,
            'e.estado_registro' => $this->estado_registro,
        ]);

        $query->andFilterWhere(['ilike', 'e.nombre', $this->nombre])
            ->andFilterWhere(['ilike', 'e.direccion_administrativa', $this->direccion_administrativa])
            ->andFilterWhere(['ilike', 'e.direccion_practicas', $this->direccion_practicas])
            ->andFilterWhere(['ilike', 'e.telefono', $this->telefono])
            ->andFilterWhere(['ilike', 'e.email', $this->email])
            ->andFilterWhere(['ilike', 'es.estado', $this->estadoNombre])
            ->andFilterWhere(['ilike', 'mu.municipio', $this->municipioNombre]);

        if ($this->tipo_entidad !== '') {
            $query->andFilterWhere(['e.tipo_entidad' => $this->tipo_entidad]);
        }

        if ($this->eliminado !== '') {
            $query->andFilterWhere(['e.eliminado' => $this->eliminado]);
        } else {
            // Por defecto, mostrar solo no eliminados
            $query->andWhere(['e.eliminado' => false]);
        }

        return $dataProvider;
    }

    /**
     * Search para escuelas pendientes
     */
    public function searchPendientes($params)
    {
        $query = Escuela::find()->alias('e');
        $query->joinWith(['estado es', 'municipio mu']);
        $query->where(['e.estado_registro' => Escuela::ESTADO_PENDIENTE, 'e.eliminado' => false]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'e.d_creacion' => SORT_ASC,
                ],
                'attributes' => [
                    'e.nombre',
                    'e.telefono',
                    'e.email',
                    'e.d_creacion',
                    'estadoNombre' => [
                        'asc' => ['es.estado' => SORT_ASC],
                        'desc' => ['es.estado' => SORT_DESC],
                    ],
                    'municipioNombre' => [
                        'asc' => ['mu.municipio' => SORT_ASC],
                        'desc' => ['mu.municipio' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'e.id' => $this->id,
            'e.id_estado' => $this->id_estado,
            'e.id_municipio' => $this->id_municipio,
            'e.id_parroquia' => $this->id_parroquia,
            'e.tipo_entidad' => $this->tipo_entidad,
        ]);

        $query->andFilterWhere(['ilike', 'e.nombre', $this->nombre])
            ->andFilterWhere(['ilike', 'e.telefono', $this->telefono])
            ->andFilterWhere(['ilike', 'e.email', $this->email])
            ->andFilterWhere(['ilike', 'es.estado', $this->estadoNombre])
            ->andFilterWhere(['ilike', 'mu.municipio', $this->municipioNombre]);

        return $dataProvider;
    }

    /**
     * Search para escuelas aprobadas (activas)
     */
    public function searchAprobadas($params)
    {
        $query = Escuela::find()->alias('e');
        $query->joinWith(['estado es', 'municipio mu']);
        $query->where(['e.estado_registro' => Escuela::ESTADO_APROBADO, 'e.eliminado' => false]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'e.nombre' => SORT_ASC,
                ]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'e.id' => $this->id,
            'e.id_estado' => $this->id_estado,
            'e.id_municipio' => $this->id_municipio,
            'e.tipo_entidad' => $this->tipo_entidad,
        ]);

        $query->andFilterWhere(['ilike', 'e.nombre', $this->nombre])
            ->andFilterWhere(['ilike', 'e.telefono', $this->telefono])
            ->andFilterWhere(['ilike', 'e.email', $this->email])
            ->andFilterWhere(['ilike', 'es.estado', $this->estadoNombre])
            ->andFilterWhere(['ilike', 'mu.municipio', $this->municipioNombre]);

        return $dataProvider;
    }
}