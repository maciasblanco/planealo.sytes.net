<?php
// [file name]: models/EscuelaRegistroSearch.php

namespace app\modules\escuela_club\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Escuela;

/**
 * EscuelaRegistroSearch represents the model behind the search form of `app\models\Escuela`.
 */
class EscuelaRegistroSearch extends Escuela
{
    // Campos adicionales para búsqueda
    public $estadoNombre;
    public $municipioNombre;
    public $parroquiaNombre;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'id_estado', 'id_municipio', 'id_parroquia', 'tipo_entidad', 'estado_registro', 'eliminado'], 'integer'],
            [['nombre', 'telefono', 'email', 'direccion_administrativa', 'direccion_practicas', 
              'mision', 'vision', 'objetivos', 'historia', 'horarios', 'redes_sociales', 
              'logo', 'lat', 'lng', 'd_creacion', 'd_update', 'comentarios_aprobacion', 
              'fecha_aprobacion', 'id_usuario_aprobacion', 'estadoNombre', 'municipioNombre', 'parroquiaNombre'], 'safe'],
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
        $query = Escuela::find()
            ->alias('e')
            ->where(['e.eliminado' => false]);

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['d_creacion' => SORT_DESC],
                'attributes' => [
                    'id',
                    'nombre',
                    'tipo_entidad',
                    'telefono',
                    'email',
                    'estado_registro',
                    'd_creacion',
                    'estadoNombre' => [
                        'asc' => ['est.estado' => SORT_ASC],
                        'desc' => ['est.estado' => SORT_DESC],
                    ],
                    'municipioNombre' => [
                        'asc' => ['mun.municipio' => SORT_ASC],
                        'desc' => ['mun.municipio' => SORT_DESC],
                    ],
                ],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Joins para búsqueda por estado, municipio, parroquia - CORREGIDOS CON ESQUEMAS
        $query->leftJoin('catalogos.estado est', 'e.id_estado = est.id')
              ->leftJoin('catalogos.municipio mun', 'e.id_municipio = mun.id')
              ->leftJoin('catalogos.parroquia par', 'e.id_parroquia = par.id');

        // grid filtering conditions
        $query->andFilterWhere([
            'e.id' => $this->id,
            'e.id_estado' => $this->id_estado,
            'e.id_municipio' => $this->id_municipio,
            'e.id_parroquia' => $this->id_parroquia,
            'e.tipo_entidad' => $this->tipo_entidad,
            'e.estado_registro' => $this->estado_registro,
            'e.eliminado' => $this->eliminado,
        ]);

        $query->andFilterWhere(['like', 'e.nombre', $this->nombre])
            ->andFilterWhere(['like', 'e.telefono', $this->telefono])
            ->andFilterWhere(['like', 'e.email', $this->email])
            ->andFilterWhere(['like', 'e.direccion_administrativa', $this->direccion_administrativa])
            ->andFilterWhere(['like', 'e.direccion_practicas', $this->direccion_practicas])
            ->andFilterWhere(['like', 'e.mision', $this->mision])
            ->andFilterWhere(['like', 'e.vision', $this->vision])
            ->andFilterWhere(['like', 'e.objetivos', $this->objetivos])
            ->andFilterWhere(['like', 'e.historia', $this->historia])
            ->andFilterWhere(['like', 'est.estado', $this->estadoNombre])
            ->andFilterWhere(['like', 'mun.municipio', $this->municipioNombre])
            ->andFilterWhere(['like', 'par.parroquia', $this->parroquiaNombre]);

        // Filtro por fecha de creación
        if (!empty($this->d_creacion)) {
            $query->andFilterWhere(['>=', 'e.d_creacion', $this->d_creacion . ' 00:00:00'])
                  ->andFilterWhere(['<=', 'e.d_creacion', $this->d_creacion . ' 23:59:59']);
        }

        return $dataProvider;
    }

    /**
     * Búsqueda específica para escuelas pendientes de aprobación - Fase 3
     * @param array $params
     * @return ActiveDataProvider
     */
    public function searchPendientes($params)
    {
        $query = Escuela::find()
            ->alias('e')
            ->where(['e.estado_registro' => Escuela::ESTADO_PENDIENTE])
            ->andWhere(['e.eliminado' => false]);

        // Joins para relaciones - CORREGIDOS CON ESQUEMAS
        $query->leftJoin('catalogos.estado est', 'e.id_estado = est.id')
              ->leftJoin('catalogos.municipio mun', 'e.id_municipio = mun.id')
              ->leftJoin('catalogos.parroquia par', 'e.id_parroquia = par.id')
              ->leftJoin('atletas.encargado_escuela enc', 'e.id = enc.id_escuela AND enc.eliminado = false');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['d_creacion' => SORT_ASC], // Más antiguos primero - CORREGIDO
                'attributes' => [
                    'id',
                    'nombre',
                    'tipo_entidad',
                    'telefono',
                    'email',
                    'd_creacion', // CORREGIDO: sin el 'e.'
                    'estadoNombre' => [
                        'asc' => ['est.estado' => SORT_ASC],
                        'desc' => ['est.estado' => SORT_DESC],
                    ],
                    'municipioNombre' => [
                        'asc' => ['mun.municipio' => SORT_ASC],
                        'desc' => ['mun.municipio' => SORT_DESC],
                    ],
                ],
            ],
            'pagination' => [
                'pageSize' => 15, // Menos registros por página para revisión detallada
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions for pendientes
        $query->andFilterWhere([
            'e.id' => $this->id,
            'e.id_estado' => $this->id_estado,
            'e.id_municipio' => $this->id_municipio,
            'e.id_parroquia' => $this->id_parroquia,
            'e.tipo_entidad' => $this->tipo_entidad,
        ]);

        $query->andFilterWhere(['like', 'e.nombre', $this->nombre])
            ->andFilterWhere(['like', 'e.telefono', $this->telefono])
            ->andFilterWhere(['like', 'e.email', $this->email])
            ->andFilterWhere(['like', 'e.direccion_practicas', $this->direccion_practicas])
            ->andFilterWhere(['like', 'est.estado', $this->estadoNombre])
            ->andFilterWhere(['like', 'mun.municipio', $this->municipioNombre])
            ->andFilterWhere(['like', 'par.parroquia', $this->parroquiaNombre]);

        return $dataProvider;
    }

    /**
     * Búsqueda para escuelas aprobadas (para selección en sesión)
     * @param array $params
     * @return ActiveDataProvider
     */
    public function searchAprobadas($params)
    {
        $query = Escuela::find()
            ->alias('e')
            ->where(['e.estado_registro' => Escuela::ESTADO_APROBADO])
            ->andWhere(['e.eliminado' => false]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['nombre' => SORT_ASC], // CORREGIDO: sin el 'e.'
            ],
            'pagination' => [
                'pageSize' => 50,
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

        $query->andFilterWhere(['like', 'e.nombre', $this->nombre])
            ->andFilterWhere(['like', 'e.telefono', $this->telefono])
            ->andFilterWhere(['like', 'e.email', $this->email]);

        return $dataProvider;
    }

    /**
     * Búsqueda para reportes administrativos
     * @param array $params
     * @return ActiveDataProvider
     */
    public function searchReporte($params)
    {
        $query = Escuela::find()
            ->alias('e')
            ->where(['e.eliminado' => false])
            ->andWhere(['IN', 'e.estado_registro', [Escuela::ESTADO_APROBADO, Escuela::ESTADO_PENDIENTE]]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['estado_registro' => SORT_ASC, 'nombre' => SORT_ASC], // CORREGIDO: sin el 'e.'
            ],
            'pagination' => false, // Sin paginación para reportes
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Joins para relaciones - CORREGIDOS CON ESQUEMAS
        $query->leftJoin('catalogos.estado est', 'e.id_estado = est.id')
              ->leftJoin('catalogos.municipio mun', 'e.id_municipio = mun.id')
              ->leftJoin('catalogos.parroquia par', 'e.id_parroquia = par.id');

        $query->andFilterWhere([
            'e.id' => $this->id,
            'e.id_estado' => $this->id_estado,
            'e.id_municipio' => $this->id_municipio,
            'e.id_parroquia' => $this->id_parroquia,
            'e.tipo_entidad' => $this->tipo_entidad,
            'e.estado_registro' => $this->estado_registro,
        ]);

        $query->andFilterWhere(['like', 'e.nombre', $this->nombre])
            ->andFilterWhere(['like', 'e.telefono', $this->telefono])
            ->andFilterWhere(['like', 'e.email', $this->email])
            ->andFilterWhere(['like', 'est.estado', $this->estadoNombre])
            ->andFilterWhere(['like', 'mun.municipio', $this->municipioNombre]);

        return $dataProvider;
    }

    /**
     * Obtiene estadísticas de escuelas por estado de registro
     * @return array
     */
    public function getEstadisticasEstados()
    {
        return Escuela::find()
            ->select(['estado_registro', 'COUNT(*) as cantidad'])
            ->where(['eliminado' => false])
            ->groupBy('estado_registro')
            ->indexBy('estado_registro')
            ->column();
    }

    /**
     * Obtiene estadísticas de escuelas por tipo de entidad
     * @return array
     */
    public function getEstadisticasTipos()
    {
        return Escuela::find()
            ->select(['tipo_entidad', 'COUNT(*) as cantidad'])
            ->where(['eliminado' => false])
            ->groupBy('tipo_entidad')
            ->indexBy('tipo_entidad')
            ->column();
    }

    /**
     * Obtiene el conteo de escuelas por estado (geográfico)
     * @return array
     */
    public function getConteoPorEstados()
    {
        return Escuela::find()
            ->alias('e')
            ->select(['est.estado as nombre', 'COUNT(*) as cantidad'])
            ->leftJoin('catalogos.estado est', 'e.id_estado = est.id') // CORREGIDO CON ESQUEMA
            ->where(['e.eliminado' => false])
            ->andWhere(['e.estado_registro' => Escuela::ESTADO_APROBADO])
            ->groupBy('e.id_estado, est.estado')
            ->orderBy('cantidad DESC')
            ->indexBy('nombre')
            ->column();
    }
}