<?php

namespace App\Services;

use App\Models\Invitacion;
use App\Models\User;
use App\Models\Vivienda;
use App\Notifications\InvitacionAVivienda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de invitaciones (RF-20, CU-13 y CU-14).
 */
class InvitacionesService
{
    /**
     * Crea la invitación y envía el correo por la cola.
     *
     * @return array{invitacion: Invitacion, enlace: string}
     */
    public function invitar(Vivienda $vivienda, User $anfitrion, string $email): array
    {
        $email = Str::lower(trim($email));

        // CU-13 4a: el correo ya pertenece a un miembro.
        if ($vivienda->usuarios()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Esa persona ya es miembro de la vivienda.']);
        }

        $tokenPlano = Str::random(64);

        $invitacion = DB::transaction(function () use ($vivienda, $anfitrion, $email, $tokenPlano): Invitacion {
            // Una invitación nueva reemplaza a la pendiente anterior para el mismo correo.
            $vivienda->invitaciones()
                ->where('email', $email)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'cancelada']);

            return $vivienda->invitaciones()->create([
                'invitado_por' => $anfitrion->id,
                'email' => $email,
                'token' => hash('sha256', $tokenPlano),
                'estado' => 'pendiente',
                'expira_en' => now()->addHours(Invitacion::VIGENCIA_HORAS),
            ]);
        });

        $enlace = route('invitaciones.mostrar', $tokenPlano);

        Notification::route('mail', $email)->notify(new InvitacionAVivienda($invitacion, $enlace));

        return ['invitacion' => $invitacion, 'enlace' => $enlace];
    }

    public function buscarPorToken(string $tokenPlano): ?Invitacion
    {
        return Invitacion::with(['vivienda', 'anfitrion'])
            ->where('token', hash('sha256', $tokenPlano))
            ->first();
    }

    /** CU-14: el invitado acepta y queda vinculado con rol de miembro. */
    public function aceptar(Invitacion $invitacion, User $usuario): void
    {
        if (! $invitacion->estaVigente()) {
            throw ValidationException::withMessages(['invitacion' => 'La invitación ya no es válida o expiró.']);
        }

        if (Str::lower($usuario->email) !== Str::lower($invitacion->email)) {
            throw ValidationException::withMessages([
                'invitacion' => 'Esta invitación fue enviada a otro correo. Inicia sesión con la cuenta de '.$invitacion->email.'.',
            ]);
        }

        DB::transaction(function () use ($invitacion, $usuario): void {
            $vivienda = $invitacion->vivienda;

            if (! $vivienda->esMiembro($usuario)) {
                $vivienda->usuarios()->attach($usuario->id, ['rol' => Vivienda::ROL_MIEMBRO]);
            }

            $invitacion->update(['estado' => 'aceptada', 'aceptada_en' => now()]);
        });
    }

    /** Planificador: marca como expiradas las invitaciones vencidas. */
    public function expirarVencidas(): int
    {
        return Invitacion::query()
            ->where('estado', 'pendiente')
            ->where('expira_en', '<', now())
            ->update(['estado' => 'expirada']);
    }
}
