<?php

namespace App\Http\Controllers;

use App\Models\LecturaConsumo;
use App\Models\Vivienda;
use App\Services\EstimadorConsumo;
use App\Services\ViviendasDelUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * RF-06, RF-13 y RF-17 (CU-08): recibos y lecturas de medidor por vivienda.
 */
class LecturaController extends Controller
{
    public function index(Request $request, ViviendasDelUsuario $servicio, EstimadorConsumo $estimador): View
    {
        $viviendas = $servicio->todas($request);
        $vivienda = $servicio->activa($request, $viviendas);

        $historial = $vivienda ? $this->historial($vivienda, $estimador) : collect();

        return view('lecturas.index', [
            'viviendas' => $viviendas,
            'vivienda' => $vivienda,
            'historial' => $historial,
            'ultima' => $historial->first(),
            'editando' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $vivienda = Vivienda::findOrFail($datos['vivienda_id']);
        Gate::authorize('registrar', $vivienda);

        $this->verificarTraslape($vivienda, $datos);

        $vivienda->lecturas()->create([...$datos, 'user_id' => $request->user()->id]);
        $request->session()->put('vivienda_activa_id', $vivienda->id);

        return redirect()->route('lecturas.index')->with('exito', 'Lectura guardada.');
    }

    public function edit(Request $request, LecturaConsumo $lectura, ViviendasDelUsuario $servicio, EstimadorConsumo $estimador): View
    {
        Gate::authorize('registrar', $lectura->vivienda);

        $viviendas = $servicio->todas($request);
        $vivienda = $lectura->vivienda;
        $historial = $this->historial($vivienda, $estimador);

        return view('lecturas.index', [
            'viviendas' => $viviendas,
            'vivienda' => $vivienda,
            'historial' => $historial,
            'ultima' => $historial->first(),
            'editando' => $lectura,
        ]);
    }

    public function update(Request $request, LecturaConsumo $lectura): RedirectResponse
    {
        Gate::authorize('registrar', $lectura->vivienda);

        $datos = $this->validar($request);
        $vivienda = Vivienda::findOrFail($datos['vivienda_id']);
        Gate::authorize('registrar', $vivienda);

        $this->verificarTraslape($vivienda, $datos, $lectura->id);
        $lectura->update($datos);

        return redirect()->route('lecturas.index')->with('exito', 'Lectura actualizada.');
    }

    public function destroy(Request $request, LecturaConsumo $lectura): RedirectResponse
    {
        $vivienda = $lectura->vivienda;
        // Puede borrarla quien la capturó o el propietario de la vivienda.
        abort_unless(
            $lectura->user_id === $request->user()->id || $request->user()->can('update', $vivienda),
            403
        );

        $lectura->delete();

        return redirect()->route('lecturas.index')->with('exito', 'Lectura eliminada.');
    }

    /**
     * Historial con la comparación contra el periodo anterior (CU-08 paso 6).
     * Se compara el promedio diario porque los periodos pueden durar distinto.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function historial(Vivienda $vivienda, EstimadorConsumo $estimador): Collection
    {
        $lecturas = $vivienda->lecturas()->with('usuario')->orderBy('periodo_inicio')->get();
        $tarifa = (float) $vivienda->tarifa_m3;

        return $lecturas->map(function (LecturaConsumo $lectura, int $i) use ($lecturas, $tarifa, $estimador): array {
            $anterior = $lecturas->get($i - 1);
            $ahorroLitros = null;

            if ($anterior) {
                $promedioAnterior = $anterior->litros() / $anterior->dias();
                $promedioActual = $lectura->litros() / $lectura->dias();
                $ahorroLitros = round(($promedioAnterior - $promedioActual) * $lectura->dias(), 2);
            }

            return [
                'lectura' => $lectura,
                'anterior' => $anterior,
                'esBase' => $anterior === null,
                'ahorroLitros' => $ahorroLitros,
                'ahorroPesos' => $ahorroLitros === null ? null : $estimador->pesos($ahorroLitros, $tarifa),
                'litrosPorDia' => round($lectura->litros() / $lectura->dias(), 1),
            ];
        })->reverse()->values();
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        return $request->validate([
            'vivienda_id' => ['required', 'integer', 'exists:viviendas,id'],
            'tipo' => ['required', 'in:recibo,medidor'],
            'periodo_inicio' => ['required', 'date_format:Y-m-d'],
            'periodo_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodo_inicio', 'before_or_equal:today'],
            'consumo_m3' => ['required', 'numeric', 'min:0', 'max:100000'],
            'importe' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ], [
            'vivienda_id.required' => 'Selecciona a qué vivienda corresponde la lectura.',
            'periodo_fin.after_or_equal' => 'El fin del periodo no puede ser anterior al inicio.',
            'periodo_fin.before_or_equal' => 'El periodo no puede terminar en una fecha futura.',
        ]);
    }

    /** CU-08 4a: el periodo no puede traslaparse con otra lectura de la misma vivienda. */
    private function verificarTraslape(Vivienda $vivienda, array $datos, ?int $ignorarId = null): void
    {
        $traslape = $vivienda->lecturas()
            ->when($ignorarId, fn ($consulta) => $consulta->whereKeyNot($ignorarId))
            ->whereDate('periodo_inicio', '<=', $datos['periodo_fin'])
            ->whereDate('periodo_fin', '>=', $datos['periodo_inicio'])
            ->first();

        if ($traslape) {
            session()->flash('traslape_id', $traslape->id);

            throw ValidationException::withMessages([
                'periodo_inicio' => 'El periodo se traslapa con la lectura del '
                    .$traslape->periodo_inicio->format('d/m/Y').' al '.$traslape->periodo_fin->format('d/m/Y')
                    .'. Puedes editar ese registro.',
            ]);
        }
    }
}
