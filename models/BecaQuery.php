<?php

namespace app\models;

use yii\db\ActiveQuery;

class BecaQuery extends ActiveQuery
{
    /**
     * Filtra las becas activas (estado = ACTIVA y fecha de vencimiento no pasada o nula)
     */
    public function activa()
    {
        return $this->andWhere(['estado' => Beca::ESTADO_ACTIVA])
            ->andWhere(['or', 
                ['fecha_vencimiento' => null], 
                ['>=', 'fecha_vencimiento', date('Y-m-d')]
            ]);
    }

    /**
     * Filtra las becas por familia
     */
    public function porFamilia($id_familia)
    {
        return $this->andWhere(['id_familia' => $id_familia]);
    }
}