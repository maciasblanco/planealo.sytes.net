<?php

namespace app\modules\atletas\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\VoleibolSesion;

/**
 * VoleibolSesionSearch representa el modelo detrás de la forma de búsqueda de `app\models\VoleibolSesion`.
 */
class VoleibolSesionSearch extends VoleibolSesion
{
    public $nombreEscuela;
    public $nombreCategoria;

    public function rules()
    {
        return [
            [['id', 'escuela_id', 'categoria_id', 'created_by'], 'integer'],
            [['nombre', 'fecha', 'estado', 'created_at', 'updated_at', 'nombreEscuela', 'nombreCategoria'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Crea data provider instance con la query aplicada
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = VoleibolSesion::find();

        // join con escuelas y categorías para filtrar por nombre
        $query->joinWith(['escuela e', 'categoria c']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['fecha' => SORT_DESC, 'id' => SORT_DESC],
            ],
        ]);

        $dataProvider->sort->attributes['nombreEscuela'] = [
            'asc' => ['e.nombre' => SORT_ASC],
            'desc' => ['e.nombre' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['nombreCategoria'] = [
            'asc' => ['c.nombre' => SORT_ASC],
            'desc' => ['c.nombre' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'escuela_id' => $this->escuela_id,
            'categoria_id' => $this->categoria_id,
            'fecha' => $this->fecha,
            'created_by' => $this->created_by,
            'estado' => $this->estado,
        ]);

        $query->andFilterWhere(['ilike', 'nombre', $this->nombre])
              ->andFilterWhere(['ilike', 'e.nombre', $this->nombreEscuela])
              ->andFilterWhere(['ilike', 'c.nombre', $this->nombreCategoria]);

        return $dataProvider;
    }
}