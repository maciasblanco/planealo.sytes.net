<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use app\services\BecaService;

class BecasController extends Controller
{
    /**
     * @var bool Modo simulación
     */
    public $dryRun = false;

    public function options($actionID)
    {
        return ['dryRun'];
    }

    public function optionAliases()
    {
        return ['d' => 'dryRun'];
    }

    /**
     * Renueva todas las becas activas (debe ejecutarse en marzo).
     *
     * @param int $dryRun Si 1, solo simula sin guardar.
     */
    public function actionRenovar($dryRun = 0)
    {
        $this->dryRun = (bool)$dryRun;

        if ($this->dryRun) {
            $this->stdout("Modo DRY RUN: No se guardarán cambios.\n", Console::FG_YELLOW);
        }

        $resultado = BecaService::renovarBecas($this->dryRun);

        foreach ($resultado as $linea) {
            $this->stdout($linea . "\n");
        }

        return Controller::EXIT_CODE_NORMAL;
    }
}