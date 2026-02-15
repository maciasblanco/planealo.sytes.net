<?php
namespace app\services;

use app\models\AtletasRegistro;
use app\models\extended\BecaExtended;
use app\models\AportesSemanales; // Ajusta el nombre si es diferente (puede ser AporteSemanal)
use app\models\Sexo; // Para obtener ID del sexo masculino
use Yii;

class AporteService
{
    const MONTO_BASE_PRIMERO = 10.00;
    const MONTO_BASE_SIGUIENTE = 5.00;
    const DESCUENTO_GENERO_MASCULINO = 0.60; // 60%
    const DESCUENTO_BECA_MERITO = 0.80; // 80%
    const DESCUENTO_BECA_ENTRENADOR = 1.00; // 100%

    /**
     * Genera los aportes para una quincena específica.
     *
     * @param string $fecha Fecha de la quincena (YYYY-MM-DD)
     * @param bool $dryRun Modo simulación
     * @return array
     */
    public static function generarAportesQuincena($fecha, $dryRun = false)
    {
        $fechaQuincena = date('Y-m-d', strtotime($fecha));
        $output = [];

        // Obtener todos los atletas activos (ajusta el criterio según tu campo de activo)
        $atletas = AtletasRegistro::find()->where(['activo' => true])->all(); // Cambia 'activo' por el campo real si es necesario

        // Obtener ID del sexo masculino (ajusta según tu catálogo)
        $idSexoMasculino = Sexo::find()->where(['nombre' => 'Masculino'])->select('id_sexo')->scalar();
        if (!$idSexoMasculino) {
            $idSexoMasculino = 1; // fallback
        }

        // Obtener IDs de tipos de beca
        $idTipoMerito = 1; // Ajusta según tu catálogo
        $idTipoEntrenador = 2; // Ajusta según tu catálogo

        $generados = 0;
        $omitidos = 0;

        foreach ($atletas as $atleta) {
            $familia = $atleta->familia;
            if (!$familia) {
                $output[] = "Atleta ID {$atleta->id_atleta} sin familia, se omite.";
                $omitidos++;
                continue;
            }

            // Ordenar atletas de la familia por antigüedad (fecha de ingreso)
            // Ajusta 'fecha_ingreso' por el campo real en tu tabla
            $miembros = $familia->getAtletas()->orderBy(['fecha_ingreso' => SORT_ASC])->all();
            $posicion = 0;
            foreach ($miembros as $index => $m) {
                if ($m->id_atleta == $atleta->id_atleta) {
                    $posicion = $index + 1;
                    break;
                }
            }

            $montoBase = ($posicion == 1) ? self::MONTO_BASE_PRIMERO : self::MONTO_BASE_SIGUIENTE;

            // Descuento por género (masculino)
            if ($atleta->id_sexo == $idSexoMasculino) {
                $montoBase = $montoBase * (1 - self::DESCUENTO_GENERO_MASCULINO);
            }

            // Verificar si tiene beca activa
            $becaActiva = BecaExtended::findActivas()
                ->where(['id_atleta' => $atleta->id_atleta])
                ->one();

            $descuentoBeca = 0;
            if ($becaActiva) {
                if ($becaActiva->id_tipo_beca == $idTipoMerito) {
                    $descuentoBeca = self::DESCUENTO_BECA_MERITO;
                } elseif ($becaActiva->id_tipo_beca == $idTipoEntrenador) {
                    $descuentoBeca = self::DESCUENTO_BECA_ENTRENADOR;
                }
                $montoBase = $montoBase * (1 - $descuentoBeca);
            }

            $montoFinal = round($montoBase, 2);

            // Verificar si ya existe aporte para esta quincena
            $existe = AportesSemanales::find()
                ->where(['atleta_id' => $atleta->id_atleta, 'fecha_quincena' => $fechaQuincena])
                ->exists();

            if ($existe) {
                $output[] = "Atleta ID {$atleta->id_atleta} ya tiene aporte para {$fechaQuincena}. Se omite.";
                $omitidos++;
                continue;
            }

            if (!$dryRun) {
                $aporte = new AportesSemanales();
                $aporte->atleta_id = $atleta->id_atleta;
                $aporte->fecha_quincena = $fechaQuincena;
                $aporte->monto_base = $montoBase; // Ajusta si tu modelo tiene otros nombres
                $aporte->descuento_genero = ($atleta->id_sexo == $idSexoMasculino) ? self::DESCUENTO_GENERO_MASCULINO : 0;
                $aporte->descuento_beca = $descuentoBeca;
                $aporte->monto_final = $montoFinal;
                $aporte->fecha_generacion = date('Y-m-d H:i:s');
                $aporte->save();

                $generados++;
                $output[] = "Generado aporte para atleta {$atleta->nombre} (ID {$atleta->id_atleta}) monto: {$montoFinal}";
            } else {
                $output[] = "[DRY RUN] Se generaría aporte para atleta {$atleta->nombre} (ID {$atleta->id_atleta}) monto: {$montoFinal}";
                $generados++;
            }
        }

        $output[] = "Resumen: Generados: $generados, Omitidos: $omitidos";
        return $output;
    }
}