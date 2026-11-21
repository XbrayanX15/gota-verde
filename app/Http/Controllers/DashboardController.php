<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ViviendasDelUsuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * RF-19 (CU-12): dashboard individual por vivienda y global sumado.
 */
class DashboardController extends Controller
{
    public function index(Request $request, ViviendasDelUsuario $servicio, DashboardService $dashboard): View
    {
        $viviendas = $servicio->todas($request);
        $vivienda = $servicio->activa($request, $viviendas);
        $modo = $request->query('modo') === 'global' ? 'global' : 'individual';

        $datos = match (true) {
            ! $vivienda => [],
            $modo === 'global' => $dashboard->global($viviendas),
            default => $dashboard->individual($vivienda, $request->user()),
        };

        return view($modo === 'global' && $vivienda ? 'dashboard.global' : 'dashboard.index', [
            'viviendas' => $viviendas,
            'vivienda' => $vivienda,
            'modo' => $modo,
            ...$datos,
        ]);
    }
}
