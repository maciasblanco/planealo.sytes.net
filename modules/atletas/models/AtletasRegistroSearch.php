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
    public $nombreCompleto; // Para búsqueda por nombre completo
    public $categoriaNombre; // Para búsqueda por categoría

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'id_club', 'id_escuela', 'id_representante', 'id_alergias', 'id_enfermedades', 'id_discapacidad', 'id_nac', 'identificacion', 'sexo', 'u_creacion', 'u_update', 'id_categoria'], 'integer'],
            [['p_nombre', 's_nombre', 'p_apellido', 's_apellido', 'fn', 'talla_franela', 'talla_short', 'cell', 'telf', 'd_creacion', 'd_update', 'dir_ip', 'nombreCompleto', 'categoriaNombre'], 'safe'],
            [['estatura', 'peso'], 'number'],
            [['asma', 'eliminado'], 'boolean'],
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
        $query = AtletasRegistro::find()->joinWith(['categoria']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // ✅ FILTRADO POR ESCUELA DE LA SESIÓN
        $session = \Yii::$app->session;
        $id_escuela = $session->get('id_escuela');
        if ($id_escuela) {
            $query->andWhere(['atletas.registro.id_escuela' => $id_escuela]);
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
            'identificacion' => $this->identificacion,
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
        ]);

        $query->andFilterWhere(['ilike', 'p_nombre', $this->p_nombre])
            ->andFilterWhere(['ilike', 's_nombre', $this->s_nombre])
            ->andFilterWhere(['ilike', 'p_apellido', $this->p_apellido])
            ->andFilterWhere(['ilike', 's_apellido', $this->s_apellido])
            ->andFilterWhere(['ilike', 'talla_franela', $this->talla_franela])
            ->andFilterWhere(['ilike', 'talla_short', $this->talla_short])
            ->andFilterWhere(['ilike', 'cell', $this->cell])
            ->andFilterWhere(['ilike', 'telf', $this->telf])
            ->andFilterWhere(['ilike', 'dir_ip', $this->dir_ip]);

        // ✅ FILTRO POR NOMBRE COMPLETO (búsqueda en todos los campos de nombre)
        if (!empty($this->nombreCompleto)) {
            $query->andWhere(['or',
                ['ilike', 'p_nombre', $this->nombreCompleto],
                ['ilike', 's_nombre', $this->nombreCompleto],
                ['ilike', 'p_apellido', $this->nombreCompleto],
                ['ilike', 's_apellido', $this->nombreCompleto]
            ]);
        }

        // ✅ FILTRO POR CATEGORÍA (búsqueda en la tabla relacionada)
        if (!empty($this->categoriaNombre)) {
            $query->andWhere(['or',
                ['ilike', 'categoria_atletas.nombre', $this->categoriaNombre],
                ['ilike', 'categoria_atletas.nombre_venezuela', $this->categoriaNombre]
            ]);
        }

        return $dataProvider;
    }
}