<?php
namespace app\helpers;

use DateTime;

class QuincenaHelper
{
    const FECHA_INICIO = '2026-01-15';
    const DIAS_QUINCENA = 15;

    /**
     * Calcula las fechas de quincena (días 15 y último día de cada mes) dentro de un período.
     * @param DateTime $fechaInicio
     * @param DateTime $fechaFin
     * @return array Fechas en formato Y-m-d
     */
    public static function calcularFechasQuincenalesPeriodo(DateTime $fechaInicio, DateTime $fechaFin)
    {
        $fechas = [];
        $inicio = clone $fechaInicio;
        $inicio->modify('first day of this month');
        $fin = clone $fechaFin;

        while ($inicio <= $fin) {
            $dia15 = clone $inicio;
            $dia15->setDate($inicio->format('Y'), $inicio->format('m'), 15);
            if ($dia15 >= $fechaInicio && $dia15 <= $fechaFin) {
                $fechas[] = $dia15->format('Y-m-d');
            }

            $ultimo = clone $inicio;
            $ultimo->modify('last day of this month');
            if ($ultimo >= $fechaInicio && $ultimo <= $fechaFin && $ultimo->format('d') != 15) {
                $fechas[] = $ultimo->format('Y-m-d');
            }

            $inicio->modify('+1 month');
        }

        return $fechas;
    }

    /**
     * Calcula el número de quincena exacto (1-24) para una fecha dada.
     * @param string $fecha
     * @return int
     */
    public static function calcularNumeroQuincena($fecha)
    {
        $ts = strtotime($fecha);
        $mes = (int)date('n', $ts);
        $dia = (int)date('j', $ts);
        return ($mes - 1) * 2 + ($dia <= 15 ? 1 : 2);
    }

    /**
     * Calcula la próxima fecha de quincena a partir de una fecha.
     * @param DateTime|string $fecha
     * @return string Y-m-d
     */
    public static function calcularProximaQuincena($fecha)
    {
        if (!$fecha instanceof DateTime) {
            $fecha = new DateTime($fecha);
        }
        $dia = (int)$fecha->format('d');
        $mes = (int)$fecha->format('m');
        $anio = (int)$fecha->format('Y');

        if ($dia < 15) {
            return $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-15';
        } else {
            $fecha->modify('first day of next month');
            return $fecha->format('Y-m-d');
        }
    }

    /**
     * Verifica si una fecha es día de quincena (15 o último día del mes).
     * @param string $fecha
     * @return bool
     */
    public static function esDiaQuincena($fecha)
    {
        $ts = strtotime($fecha);
        $dia = (int)date('j', $ts);
        $ultimoDia = (int)date('t', $ts);
        return ($dia == 15 || $dia == $ultimoDia);
    }
}