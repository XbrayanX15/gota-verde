<?php

use App\Models\Racha;
use App\Models\RegistroHabito;
use App\Models\User;
use Database\Seeders\GamificacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(GamificacionSeeder::class));

function registrarDucha($prueba, User $usuario, float $minutos, string $fecha, array $extra = [])
{
    return $prueba->actingAs($usuario)->post(route('habitos.store'), [
        'actividad_id' => ducha()->id, 'cantidad' => $minutos, 'fecha' => $fecha, ...$extra,
    ]);
}

it('otorga puntos, inicia la racha y desbloquea el primer logro (DS-02)', function (): void {
    $usuario = User::factory()->create();
    viviendaDe($usuario);

    registrarDucha($this, $usuario, 4, today()->toDateString())
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('gamificacion', fn (array $g) => $g['racha'] === 1 && $g['logros'] === ['💧 Primera gota']);

    $usuario->refresh();
    // 10 puntos por registrar + 10 del logro "Primera gota".
    expect($usuario->puntos)->toBe(20)
        ->and($usuario->logros()->pluck('clave')->all())->toBe(['primer_registro']);
});

it('cuenta días consecutivos y desbloquea "Tres en línea" (RF-10)', function (): void {
    $usuario = User::factory()->create();
    viviendaDe($usuario);

    foreach ([2, 1, 0] as $diasAtras) {
        registrarDucha($this, $usuario, 5, today()->subDays($diasAtras)->toDateString());
    }

    $racha = Racha::where('user_id', $usuario->id)->first();
    expect($racha->actual)->toBe(3)->and($racha->mejor)->toBe(3)
        ->and($usuario->logros()->pluck('clave'))->toContain('racha_3');
});

it('reinicia la racha si se salta un día', function (): void {
    $usuario = User::factory()->create();
    viviendaDe($usuario);

    registrarDucha($this, $usuario, 5, today()->subDays(3)->toDateString());
    registrarDucha($this, $usuario, 5, today()->toDateString());

    $racha = Racha::where('user_id', $usuario->id)->first();
    expect($racha->actual)->toBe(1)->and($racha->mejor)->toBe(1);
});

it('pregunta antes de duplicar una actividad del mismo día y permite sumarla (CU-09 5a)', function (): void {
    $usuario = User::factory()->create();
    viviendaDe($usuario);
    $hoy = today()->toDateString();

    registrarDucha($this, $usuario, 3, $hoy);
    registrarDucha($this, $usuario, 4, $hoy)->assertSessionHas('duplicado');
    expect(RegistroHabito::count())->toBe(1);

    registrarDucha($this, $usuario, 4, $hoy, ['duplicado' => 'sumar']);
    $registro = RegistroHabito::sole();
    expect((float) $registro->cantidad)->toBe(7.0)->and((float) $registro->litros_consumidos)->toBe(70.0);

    registrarDucha($this, $usuario, 2, $hoy, ['duplicado' => 'separado']);
    expect(RegistroHabito::count())->toBe(2);
});

it('otorga recompensas al alcanzar los puntos requeridos (RF-11)', function (): void {
    $usuario = User::factory()->create();
    $usuario->forceFill(['puntos' => 95])->save();
    viviendaDe($usuario);

    registrarDucha($this, $usuario, 5, today()->toDateString());

    expect($usuario->recompensas()->pluck('clave'))->toContain('gota_bronce');
});

it('muestra la pantalla de logros con medallas obtenidas y pendientes', function (): void {
    $usuario = User::factory()->create();
    viviendaDe($usuario);
    registrarDucha($this, $usuario, 4, today()->toDateString());

    $this->actingAs($usuario)->get(route('logros.index'))
        ->assertOk()
        ->assertSee('Primera gota')
        ->assertSee('Semana verde')
        ->assertSee('Gota de bronce');
});
