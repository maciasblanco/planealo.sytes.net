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
            ->with(['escuela', 'categoria', 'representante']);

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
                'defaultOrder' => ['p_apellido' => SORT_ASC]
            ]
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