<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SesionController extends Controller
{
    /** CU-02 4b: más de cinco intentos fallidos bloquean el acceso 60 segundos. */
    private const INTENTOS_MAXIMOS = 5;

    private const SEGUNDOS_BLOQUEO = 60;

    public function create(): View
    {
        return view('auth.ingresar');
    }

    public function store(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $llave = $this->llaveDeIntentos($request);

        if (RateLimiter::tooManyAttempts($llave, self::INTENTOS_MAXIMOS)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos fallidos. Intenta de nuevo en '.RateLimiter::availableIn($llave).' segundos.',
            ]);
        }

        if (! Auth::attempt($credenciales, $request->boolean('remember'))) {
            RateLimiter::hit($llave, self::SEGUNDOS_BLOQUEO);

            return back()->withErrors([
                'email' => 'El correo o la contraseña no son correctos.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($llave);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio');
    }

    /** La llave combina correo e IP para no bloquear a otros usuarios. */
    private function llaveDeIntentos(Request $request): string
    {
        return 'login:'.Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip();
    }
}
