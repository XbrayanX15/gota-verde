<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RF-04: restringe rutas al rol global "admin".
 */
class EsAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->esAdmin(), 403, 'Acceso denegado.');

        return $next($request);
    }
}
