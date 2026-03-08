<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use app\models\Beca;

class BecasController extends Controller
{
    /**
     * Renueva automáticamente las becas renovables cada 1 de julio.
     * Debe ejecutarse mediante cron el 1 de julio de cada año.
     */
    public function actionRenovar()
    {
        $this->stdout("Iniciando renovación de becas...\n", Console::FG_YELLOW);

        $becas = Beca::find()
            ->activa()
            ->renovables()
            ->andWhere(['<=', 'fecha_vencimiento', date('Y-m-d')])
            ->all();

        $count = 0;
        foreach ($becas as $beca) {
            if ($beca->renovar()) {
                $count++;
                $this->stdout("Renovada beca ID {$beca->id_beca} para atleta {$beca->atleta->p_nombre}\n", Console::FG_GREEN);
            } else {
                $this->stdout("Error al renovar beca ID {$beca->id_beca}\n", Console::FG_RED);
            }
        }

        // Marcar como vencidas las becas no renovables que hayan expirado
        $vencidas = Beca::find()
            ->activa()
            ->andWhere(['renovable' => false])
            ->andWhere(['<', 'fecha_vencimiento', date('Y-m-d')])
            ->all();

        foreach ($vencidas as $beca) {
            $beca->estado_ciclo = Beca::ESTADO_CICLO_VENCIDA;
            if ($beca->save()) {
                $this->stdout("Marcada como VENCIDA beca ID {$beca->id_beca}\n", Console::FG_YELLOW);
            }
        }

        $this->stdout("Proceso completado. {$count} becas renovadas.\n", Console::FG_GREEN);
        return Controller::EXIT_CODE_NORMAL;
    }
}