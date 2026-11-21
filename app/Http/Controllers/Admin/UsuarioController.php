<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU-04: gestionar roles de usuario (RF-04).
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q'));

        $usuarios = User::query()
            ->withCount(['viviendas', 'registros'])
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(fn ($q) => $q
                ->where('name', 'like', "%{$busqueda}%")
                ->orWhere('email', 'like', "%{$busqueda}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.usuarios', compact('usuarios', 'busqueda'));
    }

    public function actualizarRol(Request $request, User $usuario): RedirectResponse
    {
        $datos = $request->validate(['rol' => ['required', Rule::in([User::ROL_USUARIO, User::ROL_ADMIN])]]);

        // Evita que el último administrador se quite el acceso a sí mismo.
        if ($usuario->is($request->user()) && $datos['rol'] !== User::ROL_ADMIN) {
            return back()->withErrors(['rol' => 'No puedes quitarte el rol de administrador a ti mismo.']);
        }

        $usuario->forceFill(['rol' => $datos['rol']])->save();

        return back()->with('exito', "{$usuario->name} ahora tiene el rol {$usuario->rol}.");
    }
}
