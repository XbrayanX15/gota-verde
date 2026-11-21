<?php

namespace App\Services;

use App\Models\Meta;
use App\Models\Racha;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Models\Vivienda;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de dashboard (RF-12, RF-13, RF-19 y DS-06).
 *
 * Los agregados se guardan en caché con una llave que incluye el último
 * registro de la vivienda: cuando alguien registra un hábito la llave cambia
 * y los datos se recalculan solos.
 */
class DashboardService
{
    private const MINUTOS_CACHE = 10;

    public function __construct(
        private readonly EstimadorConsumo $estimador,
        private readonly MetasService $metas,
    ) {}

    /** Panel de la vivienda seleccionada (todos sus miembros). */
    public function individual(Vivienda $vivienda, User $usuario): array
    {
        $inicio = now()->startOfWeek();
        $fin = $inicio->copy()->addDays(6);
        $tarifa = (float) $vivienda->tarifa_m3;

        $agregados = Cache::remember(
            $this->llave('semana', $vivienda, $inicio->toDateString()),
            now()->addMinutes(self::MINUTOS_CACHE),
            function () use ($vivienda, $inicio, $fin): array {
                $semana = RegistroHabito::query()
                    ->where('vivienda_id', $vivienda->id)
                    ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

                return [
                    'litrosSemana' => (float) (clone $semana)->sum('litros_consumidos'),
                    'ahorradosSemana' => (float) (clone $semana)->sum('litros_ahorrados'),
                    'ahorroAcumulado' => (float) RegistroHabito::where('vivienda_id', $vivienda->id)->sum('litros_ahorrados'),
                    'series' => (clone $semana)
                        ->join('actividades', 'actividades.id', '=', 'registros_habito.actividad_id')
                        ->selectRaw('registros_habito.fecha AS fecha, actividad_id, actividades.nombre AS actividad, SUM(litros_consumidos) AS total')
                        ->groupBy('registros_habito.fecha', 'actividad_id', 'actividades.nombre')
                        ->get()
                        ->map(fn ($fila) => [
                            'fecha' => Carbon::parse($fila->fecha)->toDateString(),
                            'actividad_id' => (int) $fila->actividad_id,
                            'actividad' => $fila->actividad,
                            'total' => (float) $fila->total,
                        ])
                        ->all(),
                ];
            }
        );

        $series = collect($agregados['series']);
        $dias = collect(range(0, 6))->map(fn (int $i) => $inicio->copy()->addDays($i));
        $catalogo = $series->pluck('actividad', 'actividad_id');
        $colores = ['#2E7D6B', '#2B8BC6', '#5FA35A', '#C9A227', '#E08A5A', '#1F5F5B'];
        $paleta = $catalogo->keys()->values()->mapWithKeys(fn ($id, $i) => [$id => $colores[$i % count($colores)]]);
        $maximo = max(1, (float) $series->groupBy('fecha')->map(fn ($dia) => $dia->sum('total'))->max());

        $metasActivas = $vivienda->metas()->where('estado', 'activa')->orderBy('fecha_fin')->limit(3)->get();
        $metasActivas->each(fn (Meta $meta) => $this->metas->evaluar($meta));

        return [
            ...$agregados,
            'inicio' => $inicio,
            'fin' => $fin,
            'dias' => $dias,
            'series' => $series,
            'catalogo' => $catalogo,
            'paleta' => $paleta,
            'maximo' => $maximo,
            'pesosSemana' => $this->estimador->pesos($agregados['ahorradosSemana'], $tarifa),
            'pesosAcumulado' => $this->estimador->pesos($agregados['ahorroAcumulado'], $tarifa),
            'racha' => Racha::where('user_id', $usuario->id)->where('vivienda_id', $vivienda->id)->first(),
            'metasActivas' => $metasActivas->fresh(),
            'ultimoLogro' => $usuario->logros()->orderByPivot('obtenido_en', 'desc')->first(),
            'registros' => RegistroHabito::with(['actividad', 'usuario'])
                ->where('vivienda_id', $vivienda->id)
                ->orderByDesc('fecha')->orderByDesc('id')
                ->limit(12)->get(),
        ];
    }

    /**
     * Vista global: suma todas las viviendas del usuario (RF-19).
     *
     * @param  EloquentCollection<int, Vivienda>  $viviendas
     */
    public function global(EloquentCollection $viviendas): array
    {
        $inicioMeses = now()->startOfMonth()->subMonths(5);
        $meses = collect(range(0, 5))->map(fn (int $i) => $inicioMeses->copy()->addMonths($i));

        $porVivienda = $viviendas->map(function (Vivienda $vivienda) use ($inicioMeses): array {
            $datos = Cache::remember(
                $this->llave('global', $vivienda, $inicioMeses->toDateString()),
                now()->addMinutes(self::MINUTOS_CACHE),
                fn (): array => [
                    'consumidos' => (float) RegistroHabito::where('vivienda_id', $vivienda->id)->sum('litros_consumidos'),
                    'ahorrados' => (float) RegistroHabito::where('vivienda_id', $vivienda->id)->sum('litros_ahorrados'),
                    'mensual' => RegistroHabito::query()
                        ->where('vivienda_id', $vivienda->id)
                        ->where('fecha', '>=', $inicioMeses->toDateString())
                        ->get(['fecha', 'litros_ahorrados', 'litros_consumidos'])
                        ->groupBy(fn (RegistroHabito $registro) => $registro->fecha->format('Y-m'))
                        ->map(fn (Collection $grupo) => [
                            'ahorrados' => (float) $grupo->sum('litros_ahorrados'),
                            'consumidos' => (float) $grupo->sum('litros_consumidos'),
                        ])
                        ->all(),
                ]
            );

            return [
                'vivienda' => $vivienda,
                ...$datos,
                'pesos' => $this->estimador->pesos($datos['ahorrados'], (float) $vivienda->tarifa_m3),
            ];
        });

        $tendencia = $meses->map(fn (Carbon $mes) => [
            'mes' => $mes,
            'ahorrados' => $porVivienda->sum(fn ($fila) => $fila['mensual'][$mes->format('Y-m')]['ahorrados'] ?? 0),
            'consumidos' => $porVivienda->sum(fn ($fila) => $fila['mensual'][$mes->format('Y-m')]['consumidos'] ?? 0),
        ]);

        $totalAhorrados = (float) $porVivienda->sum('ahorrados');

        return [
            'porVivienda' => $porVivienda,
            'tendencia' => $tendencia,
            'maximoMensual' => max(1, (float) $tendencia->max('ahorrados')),
            'totalConsumidos' => (float) $porVivienda->sum('consumidos'),
            'totalAhorrados' => $totalAhorrados,
            'totalPesos' => round((float) $porVivienda->sum('pesos'), 2),
        ];
    }

    private function llave(string $tipo, Vivienda $vivienda, string $periodo): string
    {
        $version = RegistroHabito::where('vivienda_id', $vivienda->id)->max('updated_at') ?? '0';
        $conteo = RegistroHabito::where('vivienda_id', $vivienda->id)->count();

        return "dashboard:{$tipo}:{$vivienda->id}:{$periodo}:".md5($version.'|'.$conteo);
    }
}
