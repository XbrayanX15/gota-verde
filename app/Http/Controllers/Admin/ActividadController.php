<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU-16: administrar el catálogo de actividades.
 */
class ActividadController extends Controller
{
    public function index(): View
    {
        return view('admin.actividades', [
            'actividades' => Actividad::withCount('registros')->orderBy('nombre')->get(),
            'editando' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Actividad::create([...$this->validar($request), 'activa' => true]);

        return redirect()->route('admin.actividades.index')->with('exito', 'Actividad agregada al catálogo.');
    }

    public function edit(Actividad $actividad): View
    {
        return view('admin.actividades', [
            'actividades' => Actividad::withCount('registros')->orderBy('nombre')->get(),
            'editando' => $actividad,
        ]);
    }

    public function update(Request $request, Actividad $actividad): RedirectResponse
    {
        // Los registros ya guardados conservan sus litros; el cambio aplica a los nuevos.
        $actividad->update($this->validar($request, $actividad));

        return redirect()->route('admin.actividades.index')->with('exito', 'Actividad actualizada. Aplica a los registros nuevos.');
    }

    public function alternar(Actividad $actividad): RedirectResponse
    {
        $actividad->update(['activa' => ! $actividad->activa]);

        return back()->with('exito', $actividad->nombre.($actividad->activa ? ' está activa.' : ' se desactivó.'));
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Actividad $actividad = null): array
    {
        return $request->validate([
            'clave' => ['required', 'alpha_dash', 'max:60', Rule::unique('actividades', 'clave')->ignore($actividad?->id)],
            'nombre' => ['required', 'string', 'max:100'],
            'unidad' => ['required', 'string', 'max:40'],
            'litros_por_unidad' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'referencia_eficiente' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);
    }
}
