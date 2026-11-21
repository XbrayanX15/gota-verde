<?php

use App\Models\Invitacion;
use App\Models\User;
use App\Notifications\InvitacionAVivienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

$datosVivienda = fn (array $cambios = []) => [
    'alias' => 'Casa', 'tipo' => 'Casa', 'habitantes' => 4, 'alcaldia' => 'Tlalpan',
    'codigo_postal' => '14000', 'tarifa_m3' => '25.50', ...$cambios,
];

it('permite al propietario editar el perfil y la tarifa (RF-15, CU-06)', function () use ($datosVivienda): void {
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);

    $this->actingAs($propietario)
        ->put(route('viviendas.update', $vivienda), $datosVivienda(['alias' => 'Casa nueva']))
        ->assertRedirect(route('viviendas.index'));

    expect($vivienda->fresh())->alias->toBe('Casa nueva')->tarifa_m3->toBe('25.50');
});

it('impide que un miembro edite la vivienda (RF-05)', function () use ($datosVivienda): void {
    $miembro = User::factory()->create();
    $vivienda = viviendaDe($miembro, 'miembro');

    $this->actingAs($miembro)->get(route('viviendas.edit', $vivienda))->assertForbidden();
    $this->actingAs($miembro)->put(route('viviendas.update', $vivienda), $datosVivienda())->assertForbidden();
});

it('invita por correo y el invitado acepta como miembro (RF-20, CU-13, CU-14)', function (): void {
    Notification::fake();
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    $invitado = User::factory()->create(['email' => 'Hermana@Example.test']);

    $this->actingAs($propietario)
        ->post(route('viviendas.invitar', $vivienda), ['email' => 'hermana@example.test'])
        ->assertSessionHas('enlace_invitacion');

    $enlace = session('enlace_invitacion');
    $token = basename($enlace);
    $invitacion = Invitacion::first();

    // En la base solo se guarda el hash del token.
    expect($invitacion->token)->toBe(hash('sha256', $token))->not->toBe($token)
        ->and($invitacion->expira_en->diffInHours(now(), true))->toBeGreaterThan(71);
    Notification::assertSentOnDemand(InvitacionAVivienda::class);

    $this->actingAs($invitado)->get(route('invitaciones.mostrar', $token))->assertOk()->assertSee($vivienda->alias);
    $this->actingAs($invitado)->post(route('invitaciones.aceptar', $token))->assertRedirect(route('dashboard'));

    expect($vivienda->rolDe($invitado))->toBe('miembro')
        ->and($invitacion->fresh()->estado)->toBe('aceptada');
});

it('rechaza invitaciones expiradas o aceptadas por otra cuenta', function (): void {
    Notification::fake();
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    $this->actingAs($propietario)->post(route('viviendas.invitar', $vivienda), ['email' => 'primo@example.test']);
    $token = basename(session('enlace_invitacion'));

    $otro = User::factory()->create(['email' => 'otro@example.test']);
    $this->actingAs($otro)->post(route('invitaciones.aceptar', $token))->assertSessionHasErrors('invitacion');
    expect($vivienda->esMiembro($otro))->toBeFalse();

    $primo = User::factory()->create(['email' => 'primo@example.test']);
    $this->travel(73)->hours();
    $this->actingAs($primo)->post(route('invitaciones.aceptar', $token))->assertSessionHasErrors('invitacion');
    expect($vivienda->esMiembro($primo))->toBeFalse();
});

it('no invita a quien ya es miembro y solo el propietario puede invitar', function (): void {
    Notification::fake();
    $propietario = User::factory()->create();
    $miembro = User::factory()->create(['email' => 'miembro@example.test']);
    $vivienda = viviendaDe($propietario);
    $vivienda->usuarios()->attach($miembro->id, ['rol' => 'miembro']);

    $this->actingAs($propietario)->post(route('viviendas.invitar', $vivienda), ['email' => 'miembro@example.test'])
        ->assertSessionHasErrors('email');
    $this->actingAs($miembro)->post(route('viviendas.invitar', $vivienda), ['email' => 'x@example.test'])
        ->assertForbidden();
});

it('el propietario elimina miembros y un miembro puede salir (RF-22)', function (): void {
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    [$uno, $dos] = User::factory()->count(2)->create();
    $vivienda->usuarios()->attach([$uno->id => ['rol' => 'miembro'], $dos->id => ['rol' => 'miembro']]);

    $this->actingAs($propietario)->delete(route('viviendas.miembros.eliminar', [$vivienda, $uno]))->assertSessionHas('exito');
    $this->actingAs($dos)->post(route('viviendas.salir', $vivienda))->assertRedirect(route('viviendas.index'));

    expect($vivienda->usuarios()->count())->toBe(1);

    // El único propietario no puede salir.
    $this->actingAs($propietario)->post(route('viviendas.salir', $vivienda))->assertSessionHasErrors('miembro');
});

it('elimina la vivienda solo al confirmar el alias', function (): void {
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario, 'propietario', ['alias' => 'Depa']);

    $this->actingAs($propietario)->delete(route('viviendas.destroy', $vivienda), ['confirmacion' => 'otra'])
        ->assertSessionHasErrors('confirmacion');
    $this->actingAs($propietario)->delete(route('viviendas.destroy', $vivienda), ['confirmacion' => 'Depa'])
        ->assertRedirect(route('viviendas.index'));

    $this->assertModelMissing($vivienda);
});
