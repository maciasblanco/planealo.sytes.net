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
            // IMPORTANTE: joinWith permite ordenar por campos de la tabla relacionada 'categoria'
            ->joinWith(['categoria'])
            ->with(['escuela', 'representante']);

        // Filtros según rol del usuario
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
                    // Ordenar por el nombre de la categoría (campo de la tabla relacionada)
                    'categoria.nombre_venezuela' => SORT_ASC,
                    // Luego por primer apellido
                    'p_apellido' => SORT_ASC,
                ],
                // Definir atributos para que el ordenamiento por estos campos funcione también en clics
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
                    // Podrías agregar otros atributos si los necesitas
                ],
            ],
            'pagination' => [
                'pageSize' => 20, // Ajusta según convenga
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