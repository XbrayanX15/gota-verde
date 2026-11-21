@extends('layouts.app')
@section('titulo','Logros y rachas')
@section('contenido')
<header class="gv-header"><div><h1>Logros y rachas</h1><p class="gv-sub">Reconocimiento de tus hábitos responsables.</p></div></header>
@php($enNivel = $usuario->puntos % $puntosPorNivel)
<div class="gv-grid">
<article class="gv-card"><p class="gv-label">Racha actual{{ $vivienda ? ' en '.$vivienda->alias : '' }}</p><p class="gv-big">🔥 {{ $racha?->actual ?? 0 }}</p><p class="gv-label">{{ ($racha?->actual ?? 0) === 1 ? 'día consecutivo' : 'días consecutivos' }} registrando hábitos</p></article>
<article class="gv-card"><p class="gv-label">Mejor racha</p><p class="gv-big">{{ $mejorRachaGlobal }}</p><p class="gv-label">días (todas tus viviendas)</p></article>
<article class="gv-card"><p class="gv-label">Nivel</p><p class="gv-big">{{ $usuario->nivel() }}</p><div class="gv-progress gold"><span style="width:{{ $enNivel / $puntosPorNivel * 100 }}%"></span></div><p class="gv-label">{{ $usuario->puntos }} puntos · faltan {{ $puntosPorNivel - $enNivel }} para el nivel {{ $usuario->nivel() + 1 }}</p></article>
<article class="gv-card"><p class="gv-label">Próxima recompensa</p>@if($proximaRecompensa)<p class="gv-metric">{{ $proximaRecompensa->icono }} {{ $proximaRecompensa->nombre }}</p><div class="gv-progress gold"><span style="width:{{ min(100, $usuario->puntos / max(1,$proximaRecompensa->puntos_requeridos) * 100) }}%"></span></div><p class="gv-label">{{ $usuario->puntos }} / {{ $proximaRecompensa->puntos_requeridos }} puntos</p>@else<p class="gv-metric">🎉</p><p class="gv-label">¡Obtuviste todas las recompensas!</p>@endif</article>
</div>
<section class="gv-card" style="margin-bottom:16px"><h2>Medallas</h2><div class="gv-medals">
@foreach($logros as $fila)@php($logro = $fila['logro'])<div class="gv-medal {{ $fila['obtenido'] ? '' : 'locked' }}" title="{{ $logro->descripcion }}"><span class="ico">{{ $logro->icono }}</span><strong>{{ $logro->nombre }}</strong><small>{{ $logro->descripcion }}</small>@if($fila['obtenido'])<small class="gv-pos">Obtenido el {{ \Illuminate\Support\Carbon::parse($fila['obtenido']->pivot->obtenido_en)->format('d/m/Y') }} · +{{ $logro->puntos }} pts</small>@else<div class="gv-progress"><span style="width:{{ $fila['avance'] }}%"></span></div><small>{{ $fila['avance'] }}% · +{{ $logro->puntos }} pts</small>@endif</div>@endforeach
</div></section>
<section class="gv-card"><h2>Recompensas</h2><div class="gv-medals">
@foreach($recompensas as $recompensa)@php($tiene = $recompensasObtenidas->contains($recompensa->id))<div class="gv-medal {{ $tiene ? '' : 'locked' }}"><span class="ico">{{ $recompensa->icono }}</span><strong>{{ $recompensa->nombre }}</strong><small>{{ $recompensa->descripcion }}</small><small class="{{ $tiene ? 'gv-pos' : '' }}">{{ $tiene ? 'Desbloqueada' : $recompensa->puntos_requeridos.' puntos' }}</small></div>@endforeach
</div><p class="gv-note">Ganas {{ \App\Services\GamificacionService::PUNTOS_POR_REGISTRO }} puntos por cada hábito registrado, más los puntos de cada logro. La racha cuenta los días consecutivos en que registras al menos un hábito.</p></section>
@endsection
