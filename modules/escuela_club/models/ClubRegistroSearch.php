<?php

namespace app\modules\epcSanAgustin\atletas\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Club;

/**
 * ClubRegistroSearch represents the model behind the search form of `app\models\Club`.
 */
class ClubRegistroSearch extends Club
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'id_escuela', 'id_estado', 'id_municipio', 'id_parroquia', 'u_creacion', 'u_update'], 'integer'],
            [['direccion_administrativa', 'direccion_practicas', 'nombre', 'd_creacion', 'd_update', 'dir_ip'], 'safe'],
            [['lat', 'lng'], 'number'],
            [['eliminado'], 'boolean'],
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
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Club::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'id_escuela' => $this->id_escuela,
            'id_estado' => $this->id_estado,
            'id_municipio' => $this->id_municipio,
            'id_parroquia' => $this->id_parroquia,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'd_creacion' => $this->d_creacion,
            'u_creacion' => $this->u_creacion,
            'd_update' => $this->d_update,
            'u_update' => $this->u_update,
            'eliminado' => $this->eliminado,
        ]);

        $query->andFilterWhere(['ilike', 'direccion_administrativa', $this->direccion_administrativa])
            ->andFilterWhere(['ilike', 'direccion_practicas', $this->direccion_practicas])
            ->andFilterWhere(['ilike', 'nombre', $this->nombre])
            ->andFilterWhere(['ilike', 'dir_ip', $this->dir_ip]);

        return $dataProvider;
    }
}
