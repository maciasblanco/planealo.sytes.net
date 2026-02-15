<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use app\services\AporteService;

class AportesController extends Controller
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
     * Genera los aportes para una quincena específica.
     *
     * @param string $fecha Fecha de la quincena (formato YYYY-MM-DD)
     * @param int $dryRun Si 1, solo simula sin guardar.
     */
    public function actionGenerar($fecha, $dryRun = 0)
    {
        $this->dryRun = (bool)$dryRun;

        if ($this->dryRun) {
            $this->stdout("Modo DRY RUN: No se guardarán cambios.\n", Console::FG_YELLOW);
        }

        $this->stdout("Generando aportes para quincena $fecha...\n");

        $resultado = AporteService::generarAportesQuincena($fecha, $this->dryRun);

        foreach ($resultado as $linea) {
            $this->stdout($linea . "\n");
        }

        return Controller::EXIT_CODE_NORMAL;
    }
}