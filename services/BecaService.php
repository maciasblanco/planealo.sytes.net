<?php
namespace app\services;

use app\models\Beca;
use app\models\AtletasRegistro;
use app\models\Familia;
use app\models\TipoBeca;
use app\models\BecaHistorial;
use Yii;

class BecaService
{
    /**
     * Renueva las becas que vencen el 31 de marzo del año actual.
     *
     * @param bool $dryRun Si es true, solo simula.
     * @return array
     */
    public static function renovarBecas($dryRun = false)
    {
        $output = [];
        $anioActual = date('Y');
        $fechaLimite = $anioActual . '-03-31';
        $hoy = date('Y-m-d');

        // Buscar becas activas que vencen el 31 de marzo (usando fecha_vencimiento)
        $becasAVencer = Beca::find()
            ->where(['estado' => Beca::ESTADO_ACTIVA])
            ->andWhere(['<=', 'fecha_vencimiento', $fechaLimite])
            ->all();

        if (empty($becasAVencer)) {
            $output[] = "No hay becas para renovar.";
            return $output;
        }

        // Obtener ID del tipo de beca "Entrenador" (ajusta según tu catálogo)
        $tipoEntrenador = TipoBeca::find()->where(['nombre' => 'Entrenador'])->select('id_tipo_beca')->scalar();
        if (!$tipoEntrenador) {
            $tipoEntrenador = 2; // fallback si no se encuentra
        }

        $renovadas = 0;
        $noRenovadas = 0;

        foreach ($becasAVencer as $beca) {
            $atleta = $beca->atleta;
            if (!$atleta) {
                $output[] = "Beca ID {$beca->id_beca}: atleta no encontrado.";
                $noRenovadas++;
                continue;
            }

            $familia = $atleta->familia; // Relación en AtletasRegistro
            if (!$familia) {
                $output[] = "Beca ID {$beca->id_beca}: familia no encontrada.";
                $noRenovadas++;
                continue;
            }

            // --- Validaciones de negocio para la renovación ---
            $puedeRenovar = true;
            $motivo = '';

            // Contar becas activas en la familia (excluyendo la actual, porque aún está activa hasta que se venza)
            $becasActivasFamilia = Beca::findActivas()
                ->where(['id_familia' => $familia->id_familia])
                ->andWhere(['<>', 'id_beca', $beca->id_beca])
                ->count();

            if ($becasActivasFamilia >= 3) {
                $puedeRenovar = false;
                $motivo = "La familia ya tiene 3 becas activas (máximo permitido).";
            }

            // Verificar si es beca de entrenador (solo se permite una por familia)
            if ($beca->id_tipo_beca == $tipoEntrenador) {
                $becasEntrenadorActivas = Beca::findActivas()
                    ->where(['id_familia' => $familia->id_familia])
                    ->andWhere(['id_tipo_beca' => $tipoEntrenador])
                    ->andWhere(['<>', 'id_beca', $beca->id_beca])
                    ->count();
                if ($becasEntrenadorActivas >= 1) {
                    $puedeRenovar = false;
                    $motivo = "La familia ya tiene una beca de Entrenador activa (excluyendo esta).";
                }
            }

            // Regla: al menos un atleta sin beca (excepto si la beca original tenía autorización de excepción)
            if (!$beca->autorizacion_excepcion) {
                $atletasFamilia = AtletasRegistro::find()->where(['id_familia' => $familia->id_familia])->all();
                $atletasSinBeca = 0;
                foreach ($atletasFamilia as $a) {
                    // Excluir al atleta actual de la cuenta (porque su beca se va a renovar)
                    if ($a->id == $atleta->id) {
                        continue;
                    }
                    $tieneBecaActiva = Beca::findActivas()
                        ->where(['id_atleta' => $a->id])
                        ->exists();
                    if (!$tieneBecaActiva) {
                        $atletasSinBeca++;
                    }
                }
                if ($atletasSinBeca == 0) {
                    $puedeRenovar = false;
                    $motivo = "No hay al menos un atleta sin beca en la familia (excluyendo al beneficiario actual).";
                }
            }

            if ($puedeRenovar) {
                // Calcular nueva fecha de vencimiento según el tipo de beca
                $tipoBeca = $beca->tipoBeca;
                $mesesValidez = $tipoBeca->periodo_validez_meses ?? 12; // por defecto 12 meses
                $nuevaFechaAsignacion = $hoy;
                $fecha = new \DateTime($nuevaFechaAsignacion);
                $fecha->modify('+' . $mesesValidez . ' months');
                $nuevaFechaVencimiento = $fecha->format('Y-m-d');

                if (!$dryRun) {
                    // =========================================================
                    // 1. Registrar el vencimiento de la beca actual en historial (si no existe ya)
                    // =========================================================
                    $historialOriginal = BecaHistorial::find()->where(['id_beca' => $beca->id_beca])->one();
                    if (!$historialOriginal) {
                        $historialOriginal = new BecaHistorial();
                        $historialOriginal->attributes = [
                            'id_beca' => $beca->id_beca,
                            'id_atleta' => $beca->id_atleta,
                            'id_tipo_beca' => $beca->id_tipo_beca,
                            'id_familia' => $beca->id_familia,
                            'fecha_asignacion' => $beca->fecha_asignacion,
                            'fecha_vencimiento' => $beca->fecha_vencimiento,
                            'estado' => $beca->estado,
                            'aprobado_por' => $beca->aprobado_por,
                            'observaciones' => $beca->observaciones . ' (Renovada automáticamente)',
                            'autorizacion_excepcion' => $beca->autorizacion_excepcion,
                        ];
                        $historialOriginal->save();
                    }

                    // Marcar beca actual como vencida
                    $beca->estado = Beca::ESTADO_VENCIDA;
                    $beca->save();

                    // Crear nueva beca
                    $nuevaBeca = new Beca();
                    $nuevaBeca->attributes = [
                        'id_atleta' => $beca->id_atleta,
                        'id_tipo_beca' => $beca->id_tipo_beca,
                        'id_familia' => $familia->id_familia,
                        'fecha_asignacion' => $nuevaFechaAsignacion,
                        'fecha_vencimiento' => $nuevaFechaVencimiento,
                        'periodo_validez_meses' => $mesesValidez,
                        'aprobado_por' => null, // podría ser el sistema (user ID 0 o null)
                        'estado' => Beca::ESTADO_ACTIVA,
                        'observaciones' => 'Renovación automática de beca ID ' . $beca->id_beca,
                        'renovada_de' => $beca->id_beca,
                        'autorizacion_excepcion' => $beca->autorizacion_excepcion,
                        'eliminado' => false,
                    ];
                    $nuevaBeca->save();

                    // Registrar la nueva beca en historial
                    $historialNueva = new BecaHistorial();
                    $historialNueva->attributes = [
                        'id_beca' => $nuevaBeca->id_beca,
                        'id_atleta' => $nuevaBeca->id_atleta,
                        'id_tipo_beca' => $nuevaBeca->id_tipo_beca,
                        'id_familia' => $nuevaBeca->id_familia,
                        'fecha_asignacion' => $nuevaBeca->fecha_asignacion,
                        'fecha_vencimiento' => $nuevaBeca->fecha_vencimiento,
                        'estado' => $nuevaBeca->estado,
                        'aprobado_por' => $nuevaBeca->aprobado_por,
                        'observaciones' => $nuevaBeca->observaciones,
                        'autorizacion_excepcion' => $nuevaBeca->autorizacion_excepcion,
                    ];
                    $historialNueva->save();

                    $renovadas++;
                    $nombreAtleta = $atleta->p_nombre . ' ' . $atleta->p_apellido;
                    $output[] = "Renovada beca ID {$beca->id_beca} (atleta {$nombreAtleta}) -> nueva beca ID {$nuevaBeca->id_beca}";
                } else {
                    $nombreAtleta = $atleta->p_nombre . ' ' . $atleta->p_apellido;
                    $output[] = "[DRY RUN] Se renovaría beca ID {$beca->id_beca} (atleta {$nombreAtleta}) con vencimiento {$nuevaFechaVencimiento}";
                    $renovadas++;
                }
            } else {
                $noRenovadas++;
                $output[] = "No se renovó beca ID {$beca->id_beca}: {$motivo}";
            }
        }

        $output[] = "Resumen: Renovadas: $renovadas, No renovadas: $noRenovadas";
        return $output;
    }
}