<?php

namespace App\Http\Controllers;

use App\Services\InvitacionesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU-14: aceptar invitación.
 */
class InvitacionController extends Controller
{
    public function mostrar(Request $request, string $token, InvitacionesService $invitaciones): View
    {
        $invitacion = $invitaciones->buscarPorToken($token);
        abort_unless($invitacion, 404);

        if (! $request->user()) {
            // Al iniciar sesión o registrarse, el usuario regresa a esta página.
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return view('invitaciones.mostrar', compact('invitacion', 'token'));
    }

    public function aceptar(Request $request, string $token, InvitacionesService $invitaciones): RedirectResponse
    {
        $invitacion = $invitaciones->buscarPorToken($token);
        abort_unless($invitacion, 404);

        $invitaciones->aceptar($invitacion, $request->user());
        $request->session()->put('vivienda_activa_id', $invitacion->vivienda_id);

        return redirect()->route('dashboard')->with('exito', 'Ahora eres miembro de '.$invitacion->vivienda->alias.'.');
    }
}
