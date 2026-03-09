<?php

namespace app\modules\atletas\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\EvaluacionResultado;

/**
 * EvaluacionResultadoSearch representa el modelo detrás de la forma de búsqueda de `app\models\EvaluacionResultado`.
 */
class EvaluacionResultadoSearch extends EvaluacionResultado
{
    public $nombreAtleta;
    public $nombreEstadistica;
    public $fechaSesion;

    public function rules()
    {
        return [
            [['id', 'id_sesion', 'id_atleta', 'id_estadistica', 'set_numero', 'u_creacion', 'u_update'], 'integer'],
            [['valor_numerico'], 'number'],
            [['d_creacion', 'd_update', 'eliminado', 'dir_ip', 'nombreAtleta', 'nombreEstadistica', 'fechaSesion'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = EvaluacionResultado::find();

        $query->joinWith(['atleta a', 'estadistica e', 'sesion s']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
        ]);

        $dataProvider->sort->attributes['nombreAtleta'] = [
            'asc' => ['a.apellido' => SORT_ASC, 'a.nombre' => SORT_ASC],
            'desc' => ['a.apellido' => SORT_DESC, 'a.nombre' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['nombreEstadistica'] = [
            'asc' => ['e.nombre' => SORT_ASC],
            'desc' => ['e.nombre' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['fechaSesion'] = [
            'asc' => ['s.fecha' => SORT_ASC],
            'desc' => ['s.fecha' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'id_sesion' => $this->id_sesion,
            'id_atleta' => $this->id_atleta,
            'id_estadistica' => $this->id_estadistica,
            'set_numero' => $this->set_numero,
            'valor_numerico' => $this->valor_numerico,
            'u_creacion' => $this->u_creacion,
            'u_update' => $this->u_update,
            'eliminado' => $this->eliminado,
        ]);

        $query->andFilterWhere(['ilike', 'a.nombre', $this->nombreAtleta])
              ->orFilterWhere(['ilike', 'a.apellido', $this->nombreAtleta])
              ->andFilterWhere(['ilike', 'e.nombre', $this->nombreEstadistica])
              ->andFilterWhere(['ilike', 's.fecha', $this->fechaSesion]);

        return $dataProvider;
    }
}