<?php

namespace App\Services;

use App\Models\Actividad;

/**
 * Servicio de cálculo de ahorro (CU-17, RF-12 y RF-13).
 */
class EstimadorConsumo
{
    /**
     * Estimación informativa: NO es una medición real del medidor.
     *
     * @return array{litros_consumidos: float, litros_ahorrados: float}
     */
    public function estimar(Actividad $actividad, float $cantidad): array
    {
        $tasa = (float) $actividad->litros_por_unidad;
        $referencia = (float) $actividad->referencia_eficiente;

        return [
            'litros_consumidos' => round($cantidad * $tasa, 2),
            'litros_ahorrados' => round(max(0, $referencia - $cantidad) * $tasa, 2),
        ];
    }

    /** Convierte litros a pesos con la tarifa por m³ de la vivienda. */
    public function pesos(float $litros, float $tarifaM3): float
    {
        return round($litros / 1000 * $tarifaM3, 2);
    }
}
