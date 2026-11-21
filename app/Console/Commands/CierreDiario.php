<?php

namespace App\Console\Commands;

use App\Models\Meta;
use App\Services\GamificacionService;
use App\Services\InvitacionesService;
use App\Services\MetasService;
use Illuminate\Console\Command;

/**
 * Planificador diario (CU-19): cierra rachas, evalúa metas y expira invitaciones.
 *   php artisan gotaverde:cierre-diario
 */
class CierreDiario extends Command
{
    protected $signature = 'gotaverde:cierre-diario';

    protected $description = 'Cierra las rachas diarias, evalúa las metas activas y expira invitaciones';

    public function handle(GamificacionService $gamificacion, MetasService $metas, InvitacionesService $invitaciones): int
    {
        $rachas = $gamificacion->cerrarRachasVencidas();
        $cumplidas = $metas->evaluarTodas();

        // Los miembros de las viviendas con metas cumplidas pueden desbloquear logros.
        $cumplidas->load('vivienda.usuarios')
            ->flatMap(fn (Meta $meta) => $meta->vivienda->usuarios)
            ->unique('id')
            ->each(fn ($usuario) => $gamificacion->evaluarUsuario($usuario));

        $expiradas = $invitaciones->expirarVencidas();

        $this->info("Rachas reiniciadas: {$rachas}. Metas cumplidas: {$cumplidas->count()}. Invitaciones expiradas: {$expiradas}.");

        return self::SUCCESS;
    }
}
