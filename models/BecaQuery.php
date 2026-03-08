<?php

namespace app\models;

use yii\db\ActiveQuery;

class BecaQuery extends ActiveQuery
{
    public function activa()
    {
        return $this->andWhere(['estado_aprobacion' => Beca::ESTADO_APROB_ACTIVA])
            ->andWhere(['IS', 'estado_ciclo', null])
            ->andWhere(['eliminado' => false]);
    }

    public function pendiente()
    {
        return $this->andWhere(['estado_aprobacion' => Beca::ESTADO_APROB_PENDIENTE])
            ->andWhere(['eliminado' => false]);
    }

    public function porFamilia($id_familia)
    {
        return $this->andWhere(['id_familia' => $id_familia]);
    }

    public function renovables()
    {
        return $this->andWhere(['renovable' => true]);
    }
}