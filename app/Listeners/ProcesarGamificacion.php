<?php

namespace App\Listeners;

use App\Events\HabitoRegistrado;
use App\Services\GamificacionService;
use App\Services\MetasService;

/**
 * Actualiza racha, metas, logros y recompensas cuando se registra un hábito.
 * Se ejecuta de forma síncrona para mostrar la notificación del logro en la misma respuesta.
 */
class ProcesarGamificacion
{
    public function __construct(
        private readonly GamificacionService $gamificacion,
        private readonly MetasService $metas,
    ) {}

    public function handle(HabitoRegistrado $evento): void
    {
        $registro = $evento->registro->loadMissing(['usuario', 'vivienda']);

        $resultado = $this->gamificacion->procesarRegistro($registro);
        $metasCumplidas = $this->metas->evaluarVivienda($registro->vivienda);

        $nuevosLogros = $resultado['logros'];
        $nuevasRecompensas = $resultado['recompensas'];

        // Si se cumplió una meta, todos los miembros pueden desbloquear logros de metas.
        if ($metasCumplidas->isNotEmpty()) {
            foreach ($registro->vivienda->usuarios as $miembro) {
                $extra = $this->gamificacion->evaluarUsuario($miembro);

                if ($miembro->is($registro->usuario)) {
                    $nuevosLogros = $nuevosLogros->merge($extra['logros']);
                    $nuevasRecompensas = $nuevasRecompensas->merge($extra['recompensas']);
                }
            }
        }

        if (! request()->hasSession()) {
            return;
        }

        session()->flash('gamificacion', [
            'racha' => $resultado['racha']->actual,
            'logros' => $nuevosLogros->map(fn ($logro) => $logro->icono.' '.$logro->nombre)->all(),
            'recompensas' => $nuevasRecompensas->map(fn ($recompensa) => $recompensa->icono.' '.$recompensa->nombre)->all(),
            'metas' => $metasCumplidas->pluck('titulo')->all(),
        ]);
    }
}
