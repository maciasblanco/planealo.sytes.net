<?php

namespace app\modules\reportes\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\AtletasRegistro;

class ReporteAtletasSearch extends AtletasRegistro
{
    public $rango_fecha;
    public $estado_asistencia;
    public $estado_pago;

    public function rules()
    {
        return [
            [['id_escuela', 'id_categoria', 'sexo'], 'integer'],
            [['rango_fecha', 'estado_asistencia', 'estado_pago'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = AtletasRegistro::find()
            ->where(['eliminado' => false])
            // Hacemos join explícito con alias 'categoria' para poder ordenar correctamente
            ->joinWith(['categoria' => function($q) {
                $q->from(['categoria' => 'catalogos.categoria_atletas']);
            }])
            ->with(['escuela', 'representante']);

        // Aplicar filtros según el rol del usuario
        if (Yii::$app->user->can('representante')) {
            $representante = \app\models\RegistroRepresentantes::find()
                ->where(['user_id' => Yii::$app->user->id])
                ->one();
            if ($representante) {
                $query->andWhere(['id_representante' => $representante->id]);
            }
        } elseif (Yii::$app->user->can('atleta')) {
            $query->andWhere(['user_id' => Yii::$app->user->id]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'categoria.nombre_venezuela' => SORT_ASC,
                    'p_apellido' => SORT_ASC,
                ],
                'attributes' => [
                    'p_nombre',
                    'identificacion',
                    'id_escuela',
                    'id_categoria',
                    'categoria.nombre_venezuela' => [
                        'asc' => ['categoria.nombre_venezuela' => SORT_ASC],
                        'desc' => ['categoria.nombre_venezuela' => SORT_DESC],
                        'label' => 'Categoría',
                    ],
                    'p_apellido',
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

        // Aplicar filtros
        $query->andFilterWhere([
            'id_escuela' => $this->id_escuela,
            'id_categoria' => $this->id_categoria,
            'sexo' => $this->sexo,
        ]);

        return $dataProvider;
    }
}