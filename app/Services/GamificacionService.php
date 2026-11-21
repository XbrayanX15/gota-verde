<?php

namespace App\Services;

use App\Models\Logro;
use App\Models\Meta;
use App\Models\Racha;
use App\Models\Recompensa;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Models\Vivienda;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de gamificación (CU-18, RF-09, RF-10 y RF-11):
 * puntos, rachas, logros y recompensas.
 */
class GamificacionService
{
    public const PUNTOS_POR_REGISTRO = 10;

    /**
     * Procesa un hábito recién registrado.
     *
     * @return array{racha: Racha, logros: Collection<int, Logro>, recompensas: Collection<int, Recompensa>}
     */
    public function procesarRegistro(RegistroHabito $registro): array
    {
        $usuario = $registro->usuario;
        $usuario->increment('puntos', self::PUNTOS_POR_REGISTRO);

        $racha = $this->actualizarRacha($usuario, $registro->vivienda);
        $resultado = $this->evaluarUsuario($usuario);

        return ['racha' => $racha, ...$resultado];
    }

    /**
     * Recalcula la racha a partir de los días con registros, así funciona
     * aunque el usuario capture hábitos de días anteriores.
     */
    public function actualizarRacha(User $usuario, Vivienda $vivienda, ?Carbon $hoy = null): Racha
    {
        $hoy ??= today();

        $fechas = RegistroHabito::query()
            ->where('user_id', $usuario->id)
            ->where('vivienda_id', $vivienda->id)
            ->distinct()
            ->orderBy('fecha')
            ->pluck('fecha')
            ->map(fn ($fecha) => Carbon::parse($fecha)->startOfDay())
            ->unique(fn (Carbon $fecha) => $fecha->toDateString())
            ->values();

        $mejor = 0;
        $tramo = 0;
        $anterior = null;

        foreach ($fechas as $fecha) {
            $tramo = ($anterior && (int) $anterior->diffInDays($fecha) === 1) ? $tramo + 1 : 1;
            $mejor = max($mejor, $tramo);
            $anterior = $fecha;
        }

        $ultima = $fechas->last();
        // Si el último día con registro fue antes de ayer, la racha se rompió.
        $actual = ($ultima && $ultima->greaterThanOrEqualTo($hoy->copy()->subDay()->startOfDay())) ? $tramo : 0;

        $racha = Racha::firstOrNew(['user_id' => $usuario->id, 'vivienda_id' => $vivienda->id]);
        $racha->fill([
            'actual' => $actual,
            'mejor' => max((int) $racha->mejor, $mejor),
            'ultima_fecha' => $ultima?->toDateString(),
        ])->save();

        return $racha;
    }

    /**
     * Desbloquea logros y otorga recompensas que el usuario ya alcanzó.
     *
     * @return array{logros: Collection<int, Logro>, recompensas: Collection<int, Recompensa>}
     */
    public function evaluarUsuario(User $usuario): array
    {
        return DB::transaction(function () use ($usuario): array {
            $valores = $this->valoresDeUsuario($usuario);
            $obtenidos = $usuario->logros()->pluck('logros.id');

            $nuevosLogros = Logro::query()
                ->whereNotIn('id', $obtenidos)
                ->get()
                ->filter(fn (Logro $logro) => ($valores[$logro->criterio] ?? 0) >= $logro->umbral)
                ->values();

            foreach ($nuevosLogros as $logro) {
                $usuario->logros()->attach($logro->id, ['obtenido_en' => now()]);
                $usuario->increment('puntos', $logro->puntos);
            }

            $usuario->refresh();

            $nuevasRecompensas = Recompensa::query()
                ->whereNotIn('id', $usuario->recompensas()->pluck('recompensas.id'))
                ->where('puntos_requeridos', '<=', $usuario->puntos)
                ->orderBy('puntos_requeridos')
                ->get();

            foreach ($nuevasRecompensas as $recompensa) {
                $usuario->recompensas()->attach($recompensa->id, ['otorgada_en' => now()]);
            }

            return ['logros' => $nuevosLogros, 'recompensas' => $nuevasRecompensas];
        });
    }

    /**
     * Valor actual del usuario para cada criterio de logro.
     *
     * @return array<string, float>
     */
    public function valoresDeUsuario(User $usuario): array
    {
        $viviendas = $usuario->viviendas()->pluck('viviendas.id');

        return [
            'registros' => (float) RegistroHabito::where('user_id', $usuario->id)->count(),
            'racha' => (float) Racha::where('user_id', $usuario->id)->max('mejor'),
            'litros_ahorrados' => (float) RegistroHabito::where('user_id', $usuario->id)->sum('litros_ahorrados'),
            'metas_cumplidas' => (float) Meta::whereIn('vivienda_id', $viviendas)->where('estado', 'cumplida')->count(),
        ];
    }

    /** Cierre diario (planificador): reinicia las rachas que no tuvieron actividad ayer. */
    public function cerrarRachasVencidas(?Carbon $hoy = null): int
    {
        $hoy ??= today();

        return Racha::query()
            ->where('actual', '>', 0)
            ->whereDate('ultima_fecha', '<', $hoy->copy()->subDay()->toDateString())
            ->update(['actual' => 0]);
    }
}
