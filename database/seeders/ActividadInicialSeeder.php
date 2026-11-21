<?php

namespace Database\Seeders;

use App\Models\Actividad;
use Illuminate\Database\Seeder;

class ActividadInicialSeeder extends Seeder
{
    /**
     * Catálogo de actividades.
     *
     * IMPORTANTE: los litros por unidad y las referencias eficientes son valores
     * de referencia para la demostración, no mediciones. Deben justificarse en el
     * documento y el administrador puede ajustarlos desde /admin/actividades.
     * Se usa firstOrCreate para no sobrescribir cambios hechos por el administrador.
     */
    public function run(): void
    {
        $actividades = [
            ['clave' => 'ducha', 'nombre' => 'Ducha', 'unidad' => 'minuto', 'litros_por_unidad' => 10, 'referencia_eficiente' => 5],
            ['clave' => 'lavado_trastes', 'nombre' => 'Lavar trastes (llave abierta)', 'unidad' => 'minuto', 'litros_por_unidad' => 8, 'referencia_eficiente' => 5],
            ['clave' => 'cepillado_dientes', 'nombre' => 'Cepillado de dientes (llave abierta)', 'unidad' => 'minuto', 'litros_por_unidad' => 6, 'referencia_eficiente' => 1],
            ['clave' => 'lavado_manos', 'nombre' => 'Lavado de manos', 'unidad' => 'minuto', 'litros_por_unidad' => 6, 'referencia_eficiente' => 1],
            ['clave' => 'riego', 'nombre' => 'Riego con manguera', 'unidad' => 'minuto', 'litros_por_unidad' => 12, 'referencia_eficiente' => 5],
        ];

        foreach ($actividades as $actividad) {
            Actividad::firstOrCreate(
                ['clave' => $actividad['clave']],
                [...$actividad, 'activa' => true],
            );
        }
    }
}
