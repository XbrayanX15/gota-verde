<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Logro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU-16: administrar el catálogo de logros.
 */
class LogroController extends Controller
{
    public function index(): View
    {
        return view('admin.logros', [
            'logros' => Logro::withCount('usuarios')->orderBy('criterio')->orderBy('umbral')->get(),
            'editando' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Logro::create($this->validar($request));

        return redirect()->route('admin.logros.index')->with('exito', 'Logro agregado.');
    }

    public function edit(Logro $logro): View
    {
        return view('admin.logros', [
            'logros' => Logro::withCount('usuarios')->orderBy('criterio')->orderBy('umbral')->get(),
            'editando' => $logro,
        ]);
    }

    public function update(Request $request, Logro $logro): RedirectResponse
    {
        $logro->update($this->validar($request, $logro));

        return redirect()->route('admin.logros.index')->with('exito', 'Logro actualizado.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Logro $logro = null): array
    {
        return $request->validate([
            'clave' => ['required', 'alpha_dash', 'max:60', Rule::unique('logros', 'clave')->ignore($logro?->id)],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:255'],
            'criterio' => ['required', Rule::in(array_keys(Logro::CRITERIOS))],
            'umbral' => ['required', 'integer', 'min:1', 'max:1000000'],
            'puntos' => ['required', 'integer', 'min:0', 'max:10000'],
            'icono' => ['required', 'string', 'max:10'],
        ]);
    }
}
