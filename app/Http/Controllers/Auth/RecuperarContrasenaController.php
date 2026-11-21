<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaPassword;
use Illuminate\View\View;

/**
 * RF-03 / CU-03: recuperación de contraseña mediante enlace por correo.
 */
class RecuperarContrasenaController extends Controller
{
    public function solicitar(): View
    {
        return view('auth.recuperar');
    }

    public function enviarEnlace(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Siempre se responde lo mismo para no revelar qué correos están registrados.
        return back()->with('exito', 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.');
    }

    public function formulario(Request $request, string $token): View
    {
        return view('auth.restablecer', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function restablecer(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', ReglaPassword::min(8)],
        ]);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($usuario, string $password): void {
                $usuario->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($estado !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'El enlace no es válido o ya expiró. Solicita uno nuevo.']);
        }

        return redirect()->route('login')->with('exito', 'Tu contraseña se actualizó. Ya puedes iniciar sesión.');
    }
}
