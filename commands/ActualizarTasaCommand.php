<?php
// commands/ActualizarTasaCommand.php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\TasaDolar;

/**
 * Comando para actualizar la tasa de cambio del dólar desde fuentes externas.
 * Se recomienda ejecutar periódicamente (cron) para mantener la tasa actualizada.
 */
class ActualizarTasaCommand extends Controller
{
    /**
     * Actualiza la tasa usando la fuente principal (BCV).
     * @return int Exit code
     */
    public function actionIndex()
    {
        echo "🔄 Iniciando actualización programada de tasa...\n";

        $tasa = TasaDolar::obtenerTasaBCV();
        
        if ($tasa && $tasa > 100) {
            // Guardar la tasa en la base de datos
            if ($this->guardarTasa($tasa, 'BCV')) {
                echo "✅ Tasa actualizada: Bs. " . number_format($tasa, 2) . "\n";
                Yii::info("CRON - Tasa actualizada: Bs. {$tasa}", 'tasa-cron');
                return ExitCode::OK;
            } else {
                echo "❌ Error al guardar la tasa en la base de datos.\n";
                Yii::error("CRON - Error guardando tasa: {$tasa}", 'tasa-cron');
                return ExitCode::SOFTWARE;
            }
        } else {
            echo "❌ No se pudo obtener una tasa válida desde la fuente BCV.\n";
            Yii::error("CRON - Error obteniendo tasa (valor: {$tasa})", 'tasa-cron');
            return ExitCode::SOFTWARE;
        }
    }

    /**
     * Prueba todas las fuentes disponibles y muestra los resultados.
     * No guarda automáticamente, solo informa.
     * @return int Exit code
     */
    public function actionForzar()
    {
        echo "🔄 Forzando verificación de todas las fuentes...\n";

        // Verificar si el método existe en el modelo
        if (!method_exists(TasaDolar::class, 'probarTodasLasFuentes')) {
            echo "❌ El método TasaDolar::probarTodasLasFuentes() no está definido.\n";
            return ExitCode::SOFTWARE;
        }

        $resultados = TasaDolar::probarTodasLasFuentes();
        $algunaValida = false;

        foreach ($resultados as $fuente => $tasa) {
            if ($tasa > 100) {
                echo "  {$fuente}: ✅ Bs. " . number_format($tasa, 2) . "\n";
                $algunaValida = true;
            } else {
                echo "  {$fuente}: ❌ Falló\n";
            }
        }

        if ($algunaValida) {
            echo "\nPuede ejecutar 'php yii actualizar-tasa' para guardar la tasa desde la fuente principal.\n";
            return ExitCode::OK;
        } else {
            echo "\n❌ Ninguna fuente proporcionó una tasa válida.\n";
            return ExitCode::SOFTWARE;
        }
    }

    /**
     * Guarda una tasa en la base de datos.
     * @param float $tasa Valor de la tasa
     * @param string $fuente Nombre de la fuente (solo para referencia)
     * @return bool
     */
    private function guardarTasa($tasa, $fuente = 'desconocida')
    {
        $model = new TasaDolar();
        $model->tasa_dia = $tasa;
        $model->fecha_tasa = date('Y-m-d');
        $model->eliminado = false;
        $model->d_creacion = date('Y-m-d H:i:s');
        $model->u_creacion = Yii::$app->has('user') ? Yii::$app->user->id : 1; // Usuario por defecto si no hay sesión

        if ($model->save()) {
            return true;
        } else {
            Yii::error("Error guardando tasa: " . json_encode($model->errors), 'tasa-cron');
            return false;
        }
    }
}