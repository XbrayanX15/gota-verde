<?php

namespace App\Http\Controllers;

use App\Events\HabitoRegistrado;
use App\Models\Actividad;
use App\Models\RegistroHabito;
use App\Services\EstimadorConsumo;
use App\Services\ViviendasDelUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistroHabitoController extends Controller
{
    public function create(Request $request, ViviendasDelUsuario $viviendasDelUsuario): View|RedirectResponse
    {
        $viviendas = $viviendasDelUsuario->todas($request);
        $vivienda = $viviendasDelUsuario->activa($request, $viviendas);

        if (! $vivienda) {
            return redirect()->route('viviendas.create')
                ->with('exito', 'Primero registra una vivienda.');
        }

        $actividades = Actividad::query()->where('activa', true)->orderBy('nombre')->get();

        return view('habitos.crear', compact('vivienda', 'viviendas', 'actividades'));
    }

    public function store(
        Request $request,
        ViviendasDelUsuario $viviendasDelUsuario,
        EstimadorConsumo $estimador
    ): RedirectResponse {
        $vivienda = $viviendasDelUsuario->activa($request);

        if (! $vivienda) {
            return redirect()->route('viviendas.create');
        }

        $datos = $request->validate([
            'actividad_id' => ['required', 'integer', Rule::exists('actividades', 'id')->where('activa', true)],
            'cantidad' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'duplicado' => ['nullable', 'in:sumar,separado'],
        ], [
            'cantidad.min' => 'La cantidad debe ser mayor que cero.',
            'cantidad.max' => 'La cantidad está fuera de rango (máximo 1000).',
            'fecha.before_or_equal' => 'No puedes registrar actividades en fechas futuras.',
        ]);

        $actividad = Actividad::findOrFail($datos['actividad_id']);

        // CU-09 5a: ya existe un registro de la misma actividad ese día.
        $existente = RegistroHabito::query()
            ->where('user_id', $request->user()->id)
            ->where('vivienda_id', $vivienda->id)
            ->where('actividad_id', $actividad->id)
            ->whereDate('fecha', $datos['fecha'])
            ->latest('id')
            ->first();

        if ($existente && empty($datos['duplicado'])) {
            return back()->withInput()->with('duplicado', [
                'actividad' => $actividad->nombre,
                'cantidad' => (float) $existente->cantidad,
                'unidad' => $actividad->unidad,
            ]);
        }

        if ($existente && $datos['duplicado'] === 'sumar') {
            $cantidad = min(1000, (float) $existente->cantidad + (float) $datos['cantidad']);
            $estimacion = $estimador->estimar($actividad, $cantidad);
            $existente->update(['cantidad' => $cantidad, ...$estimacion]);
            $registro = $existente;
        } else {
            $estimacion = $estimador->estimar($actividad, (float) $datos['cantidad']);
            $registro = RegistroHabito::create([
                'user_id' => $request->user()->id,
                'vivienda_id' => $vivienda->id,
                'actividad_id' => $actividad->id,
                'fecha' => $datos['fecha'],
                'cantidad' => $datos['cantidad'],
                ...$estimacion,
            ]);
        }

        HabitoRegistrado::dispatch($registro);

        return redirect()->route('dashboard')->with(
            'exito',
            'Hábito registrado: '.number_format($estimacion['litros_consumidos'], 2).' litros estimados'
            .($estimacion['litros_ahorrados'] > 0 ? ' y '.number_format($estimacion['litros_ahorrados'], 2).' litros ahorrados.' : '.')
        );
    }
}
