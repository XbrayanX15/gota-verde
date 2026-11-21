<?php

namespace Database\Seeders;

use App\Models\Logro;
use App\Models\Recompensa;
use Illuminate\Database\Seeder;

class GamificacionSeeder extends Seeder
{
    public function run(): void
    {
        $logros = [
            ['clave' => 'primer_registro', 'nombre' => 'Primera gota', 'descripcion' => 'Registra tu primer hábito.', 'criterio' => 'registros', 'umbral' => 1, 'puntos' => 10, 'icono' => '💧'],
            ['clave' => 'registros_10', 'nombre' => 'Constante', 'descripcion' => 'Registra 10 hábitos.', 'criterio' => 'registros', 'umbral' => 10, 'puntos' => 30, 'icono' => '📋'],
            ['clave' => 'registros_50', 'nombre' => 'Hábito formado', 'descripcion' => 'Registra 50 hábitos.', 'criterio' => 'registros', 'umbral' => 50, 'puntos' => 80, 'icono' => '🌱'],
            ['clave' => 'racha_3', 'nombre' => 'Tres en línea', 'descripcion' => 'Mantén una racha de 3 días.', 'criterio' => 'racha', 'umbral' => 3, 'puntos' => 20, 'icono' => '🔥'],
            ['clave' => 'racha_7', 'nombre' => 'Semana verde', 'descripcion' => 'Mantén una racha de 7 días.', 'criterio' => 'racha', 'umbral' => 7, 'puntos' => 50, 'icono' => '🌿'],
            ['clave' => 'racha_30', 'nombre' => 'Mes sin fugas', 'descripcion' => 'Mantén una racha de 30 días.', 'criterio' => 'racha', 'umbral' => 30, 'puntos' => 150, 'icono' => '🏆'],
            ['clave' => 'ahorro_100', 'nombre' => 'Cubeta salvada', 'descripcion' => 'Ahorra 100 litros acumulados.', 'criterio' => 'litros_ahorrados', 'umbral' => 100, 'puntos' => 20, 'icono' => '🪣'],
            ['clave' => 'ahorro_1000', 'nombre' => 'Tinaco protegido', 'descripcion' => 'Ahorra 1,000 litros acumulados.', 'criterio' => 'litros_ahorrados', 'umbral' => 1000, 'puntos' => 100, 'icono' => '🛢️'],
            ['clave' => 'meta_1', 'nombre' => 'Meta cumplida', 'descripcion' => 'Cumple tu primera meta.', 'criterio' => 'metas_cumplidas', 'umbral' => 1, 'puntos' => 40, 'icono' => '🎯'],
            ['clave' => 'meta_5', 'nombre' => 'Estratega del agua', 'descripcion' => 'Cumple 5 metas.', 'criterio' => 'metas_cumplidas', 'umbral' => 5, 'puntos' => 120, 'icono' => '🧭'],
        ];

        foreach ($logros as $logro) {
            Logro::firstOrCreate(['clave' => $logro['clave']], $logro);
        }

        $recompensas = [
            ['clave' => 'gota_bronce', 'nombre' => 'Gota de bronce', 'descripcion' => 'Insignia por alcanzar 100 puntos.', 'puntos_requeridos' => 100, 'icono' => '🥉'],
            ['clave' => 'gota_plata', 'nombre' => 'Gota de plata', 'descripcion' => 'Insignia por alcanzar 300 puntos.', 'puntos_requeridos' => 300, 'icono' => '🥈'],
            ['clave' => 'gota_oro', 'nombre' => 'Gota de oro', 'descripcion' => 'Insignia por alcanzar 600 puntos.', 'puntos_requeridos' => 600, 'icono' => '🥇'],
            ['clave' => 'guardian_agua', 'nombre' => 'Guardián del agua', 'descripcion' => 'Título especial por alcanzar 1,000 puntos.', 'puntos_requeridos' => 1000, 'icono' => '🛡️'],
        ];

        foreach ($recompensas as $recompensa) {
            Recompensa::firstOrCreate(['clave' => $recompensa['clave']], $recompensa);
        }
    }
}
