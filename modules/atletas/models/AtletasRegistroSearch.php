<?php

namespace app\modules\atletas\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\AtletasRegistro;

/**
 * AtletasRegistroSearch represents the model behind the search form of `app\models\AtletasRegistro`.
 */
class AtletasRegistroSearch extends AtletasRegistro
{
    public $nombreCompleto;
    public $categoriaNombre;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'id_club', 'id_escuela', 'id_representante', 'id_alergias', 'id_enfermedades', 'id_discapacidad', 'id_nac', 'sexo', 'u_creacion', 'u_update', 'id_categoria', 'user_id', 'id_familia'], 'integer'],
            [['p_nombre', 's_nombre', 'p_apellido', 's_apellido', 'identificacion', 'fn', 'talla_franela', 'talla_short', 'cell', 'telf', 'd_creacion', 'd_update', 'dir_ip', 'nombreEscuelaClub', 'categoria', 'telf_emergencia1', 'telf_emergencia2', 'nombreCompleto', 'categoriaNombre', 'eliminado'], 'safe'],
            [['estatura', 'peso'], 'number'],
            [['asma'], 'boolean'],
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
        $query = AtletasRegistro::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => [
                    'id',
                    'p_nombre',
                    's_nombre',
                    'p_apellido',
                    's_apellido',
                    'identificacion',
                    'cell',
                    'd_creacion',
                    // Campos virtuales
                    'nombreCompleto' => [
                        'asc' => ['p_nombre' => SORT_ASC, 'p_apellido' => SORT_ASC],
                        'desc' => ['p_nombre' => SORT_DESC, 'p_apellido' => SORT_DESC],
                        'default' => SORT_ASC
                    ],
                    'categoriaNombre' => [
                        'asc' => ['categoria' => SORT_ASC],
                        'desc' => ['categoria' => SORT_DESC],
                    ],
                ],
            ],
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
            'id_club' => $this->id_club,
            'id_escuela' => $this->id_escuela,
            'id_representante' => $this->id_representante,
            'id_alergias' => $this->id_alergias,
            'id_enfermedades' => $this->id_enfermedades,
            'id_discapacidad' => $this->id_discapacidad,
            'id_nac' => $this->id_nac,
            'fn' => $this->fn,
            'sexo' => $this->sexo,
            'estatura' => $this->estatura,
            'peso' => $this->peso,
            'asma' => $this->asma,
            'd_creacion' => $this->d_creacion,
            'u_creacion' => $this->u_creacion,
            'd_update' => $this->d_update,
            'u_update' => $this->u_update,
            'eliminado' => $this->eliminado,
            'id_categoria' => $this->id_categoria,
            'user_id' => $this->user_id,
            'id_familia' => $this->id_familia,
        ]);

        $query->andFilterWhere(['ilike', 'p_nombre', $this->p_nombre])
            ->andFilterWhere(['ilike', 's_nombre', $this->s_nombre])
            ->andFilterWhere(['ilike', 'p_apellido', $this->p_apellido])
            ->andFilterWhere(['ilike', 's_apellido', $this->s_apellido])
            ->andFilterWhere(['ilike', 'identificacion', $this->identificacion])
            ->andFilterWhere(['ilike', 'talla_franela', $this->talla_franela])
            ->andFilterWhere(['ilike', 'talla_short', $this->talla_short])
            ->andFilterWhere(['ilike', 'cell', $this->cell])
            ->andFilterWhere(['ilike', 'telf', $this->telf])
            ->andFilterWhere(['ilike', 'dir_ip', $this->dir_ip])
            ->andFilterWhere(['ilike', 'nombreEscuelaClub', $this->nombreEscuelaClub])
            ->andFilterWhere(['ilike', 'categoria', $this->categoria])
            ->andFilterWhere(['ilike', 'telf_emergencia1', $this->telf_emergencia1])
            ->andFilterWhere(['ilike', 'telf_emergencia2', $this->telf_emergencia2]);

        // Filtro para nombre completo (concatenación)
        if (!empty($this->nombreCompleto)) {
            $query->andWhere(
                'CONCAT(p_nombre, \' \', COALESCE(s_nombre,\'\'), \' \', p_apellido, \' \', COALESCE(s_apellido,\'\')) ILIKE :nombre',
                [':nombre' => '%' . $this->nombreCompleto . '%']
            );
        }

        // Filtro para categoría (usa el campo 'categoria' que es calculado y guardado)
        if (!empty($this->categoriaNombre)) {
            $query->andWhere(['ilike', 'categoria', $this->categoriaNombre]);
        }

        // 🔥 FILTRO POR DEFECTO: solo atletas no eliminados, a menos que se solicite explícitamente ver eliminados
        if ($this->eliminado === null || $this->eliminado === '') {
            $query->andWhere(['eliminado' => false]);
        }

        return $dataProvider;
    }
}