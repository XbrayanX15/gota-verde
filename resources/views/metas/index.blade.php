@extends('layouts.app')
@section('titulo','Metas')
@section('contenido')
<header class="gv-header"><div><h1>Metas de {{ $vivienda->alias }}</h1><p class="gv-sub">Las metas son de toda la vivienda: cuenta lo que registra cada miembro.</p></div>@include('partials.selector-vivienda',['destino'=>'metas'])</header>
<div class="gv-columns"><section class="gv-card"><h2>Metas</h2>
@forelse($metas as $fila)@php($meta = $fila['meta'])@php($avance = $fila['avance'])
<div class="gv-meta {{ $meta->estado }}"><div class="gv-meta-head"><div><strong>{{ $meta->titulo }}</strong><p class="gv-label">{{ $meta->tipo === 'cuantitativa' ? 'Cuantitativa' : 'Cualitativa' }}@if($meta->actividad) · {{ $meta->actividad->nombre }}@endif · {{ $meta->fecha_inicio->format('d/m') }} al {{ $meta->fecha_fin->format('d/m/Y') }}</p></div>
<span class="gv-tag {{ ['activa'=>'','cumplida'=>'ok','vencida'=>'warn'][$meta->estado] }}">{{ ucfirst($meta->estado) }}</span></div>
<div class="gv-progress {{ $meta->estado === 'cumplida' ? 'gold' : '' }}"><span style="width:{{ (float)$meta->progreso }}%"></span></div>
<div class="gv-row" style="justify-content:space-between"><small class="gv-label">{{ number_format($avance['actual'], $meta->tipo==='cuantitativa' ? 1 : 0) }} de {{ number_format($avance['objetivo'], $meta->tipo==='cuantitativa' ? 0 : 0) }} {{ $avance['unidad'] }}@if($meta->tipo==='cualitativa') con máximo {{ (float)$meta->limite_cantidad }} {{ $meta->actividad?->unidad }}s por registro @endif · {{ number_format((float)$meta->progreso,0) }}%</small>
@if($meta->creada_por === auth()->id() || auth()->user()->can('update',$vivienda))<form method="POST" action="{{ route('metas.destroy',$meta) }}" onsubmit="return confirm('¿Eliminar esta meta?')">@csrf @method('DELETE')<button class="gv-link danger">Eliminar</button></form>@endif</div></div>
@empty<div class="gv-pending">Todavía no hay metas en esta vivienda. Crea la primera con el formulario.</div>@endforelse
</section>
<section class="gv-card"><h2>Nueva meta</h2>
@if(! $tieneConsumoBase)<div class="gv-confirm" style="font-size:13px">Te recomendamos <a href="{{ route('lecturas.index') }}">registrar un recibo</a> como consumo base para fijar metas realistas.</div>@endif
<form method="POST" action="{{ route('metas.store') }}" id="form-meta">@csrf
<div class="gv-field"><label for="tipo">Tipo</label><select id="tipo" name="tipo" required><option value="cuantitativa" @selected(old('tipo')==='cuantitativa')>Cuantitativa · ahorrar litros</option><option value="cualitativa" @selected(old('tipo')==='cualitativa')>Cualitativa · mejorar una práctica</option></select></div>
<div class="gv-field"><label for="titulo">Nombre de la meta</label><input id="titulo" name="titulo" required maxlength="120" placeholder="Ej. Ahorrar 300 L este mes" value="{{ old('titulo') }}"></div>
<div class="gv-field"><label for="actividad_id">Actividad <span class="gv-label" data-solo="cuantitativa">(opcional: vacío = todas)</span></label><select id="actividad_id" name="actividad_id"><option value="">Todas las actividades</option>@foreach($actividades as $actividad)<option value="{{ $actividad->id }}" data-unidad="{{ $actividad->unidad }}" @selected((int)old('actividad_id')===$actividad->id)>{{ $actividad->nombre }}</option>@endforeach</select></div>
<div class="gv-field" data-solo="cuantitativa"><label for="objetivo_litros">Litros a ahorrar en el periodo</label><input id="objetivo_litros" name="objetivo_litros" type="number" min="1" step="1" value="{{ old('objetivo_litros') }}" placeholder="300"></div>
<div class="gv-forms" data-solo="cualitativa"><div class="gv-field"><label for="limite_cantidad">Máximo por registro</label><input id="limite_cantidad" name="limite_cantidad" type="number" min="0.1" step="0.1" value="{{ old('limite_cantidad') }}" placeholder="5 (minutos)"></div><div class="gv-field"><label for="dias_objetivo">Días que se debe cumplir</label><input id="dias_objetivo" name="dias_objetivo" type="number" min="1" step="1" value="{{ old('dias_objetivo') }}" placeholder="10"></div></div>
<div class="gv-forms"><div class="gv-field"><label for="fecha_inicio">Desde</label><input id="fecha_inicio" name="fecha_inicio" type="date" required value="{{ old('fecha_inicio', now()->toDateString()) }}"></div><div class="gv-field"><label for="fecha_fin">Hasta</label><input id="fecha_fin" name="fecha_fin" type="date" required min="{{ now()->toDateString() }}" value="{{ old('fecha_fin', now()->addDays(29)->toDateString()) }}"></div></div>
<button class="gv-btn" style="width:100%" type="submit">Crear meta</button></form>
<p class="gv-note">Cuantitativa: suma los litros ahorrados por todos los miembros. Cualitativa: cuenta los días en que todos los registros de la actividad respetaron el máximo (p. ej. duchas de 5 minutos o menos).</p>
<script>(() => { const tipo = document.getElementById('tipo'); const mostrar = () => document.querySelectorAll('[data-solo]').forEach(el => { const visible = el.dataset.solo === tipo.value; el.style.display = visible ? '' : 'none'; el.querySelectorAll('input').forEach(i => i.required = visible); }); tipo.addEventListener('change', mostrar); mostrar(); })();</script>
</section></div>
@endsection
