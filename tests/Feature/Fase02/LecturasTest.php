<?php

use App\Models\LecturaConsumo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$lectura = fn (int $viviendaId, array $cambios = []) => [
    'vivienda_id' => $viviendaId, 'tipo' => 'recibo',
    'periodo_inicio' => '2026-07-01', 'periodo_fin' => '2026-08-30', 'consumo_m3' => 30, ...$cambios,
];

it('registra una lectura en la vivienda elegida (RF-06, RF-17)', function () use ($lectura): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario);

    $this->actingAs($usuario)->post(route('lecturas.store'), $lectura($vivienda->id))
        ->assertRedirect(route('lecturas.index'));

    $this->assertDatabaseHas('lecturas_consumo', ['vivienda_id' => $vivienda->id, 'user_id' => $usuario->id, 'tipo' => 'recibo']);
});

it('rechaza periodos que se traslapan (CU-08 4a)', function () use ($lectura): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario);
    $this->actingAs($usuario)->post(route('lecturas.store'), $lectura($vivienda->id));

    $this->actingAs($usuario)
        ->post(route('lecturas.store'), $lectura($vivienda->id, ['periodo_inicio' => '2026-08-15', 'periodo_fin' => '2026-10-14']))
        ->assertSessionHasErrors('periodo_inicio')
        ->assertSessionHas('traslape_id');

    expect(LecturaConsumo::count())->toBe(1);
});

it('compara contra el periodo anterior en litros y pesos (RF-13)', function () use ($lectura): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario, 'propietario', ['tarifa_m3' => 20]);
    $this->actingAs($usuario)->post(route('lecturas.store'), $lectura($vivienda->id));
    // Mismo número de días (61) y 6 m³ menos: 6,000 L y $120 de ahorro.
    $this->actingAs($usuario)->post(route('lecturas.store'), $lectura($vivienda->id, [
        'periodo_inicio' => '2026-08-31', 'periodo_fin' => '2026-10-30', 'consumo_m3' => 24,
    ]));

    $this->actingAs($usuario)->get(route('lecturas.index'))
        ->assertOk()
        ->assertSee('6,000 L')
        ->assertSee('$120.00');
});

it('no deja registrar lecturas en viviendas ajenas (RNF-31)', function () use ($lectura): void {
    $usuario = User::factory()->create();
    $ajena = viviendaDe(User::factory()->create());

    $this->actingAs($usuario)->post(route('lecturas.store'), $lectura($ajena->id))->assertForbidden();
});
