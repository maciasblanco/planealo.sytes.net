<?php

namespace app\modules\reportes\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Asistencia;

class ReporteAsistenciasSearch extends Asistencia
{
    public $rango_fecha;
    public $nombre_atleta;

    public function rules()
    {
        return [
            [['id_atleta', 'id_escuela'], 'integer'],
            [['rango_fecha', 'nombre_atleta', 'asistio'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Asistencia::find()
            ->where(['asistencia.eliminado' => false])
            ->joinWith(['atleta']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['fecha' => SORT_DESC]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Aplicar filtros
        $query->andFilterWhere([
            'id_atleta' => $this->id_atleta,
            'id_escuela' => $this->id_escuela,
            'asistio' => $this->asistio,
        ]);

        // Filtro por rango de fechas
        if (!empty($this->rango_fecha)) {
            list($fechaInicio, $fechaFin) = explode(' - ', $this->rango_fecha);
            $query->andFilterWhere(['between', 'fecha', $fechaInicio, $fechaFin]);
        }

        // Filtro por nombre de atleta
        if (!empty($this->nombre_atleta)) {
            $query->andWhere(['or',
                ['like', 'atletas.registro.p_nombre', $this->nombre_atleta],
                ['like', 'atletas.registro.p_apellido', $this->nombre_atleta]
            ]);
        }

        return $dataProvider;
    }
}