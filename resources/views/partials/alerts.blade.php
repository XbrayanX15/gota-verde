@if(session('exito'))<div class="gv-alert" role="status">{{ session('exito') }}</div>@endif
@if(session('gamificacion'))@php($g = session('gamificacion'))
<div class="gv-alert" role="status" style="border-color:#e7d494;background:#fdf8ea">
<strong>🔥 Racha: {{ $g['racha'] }} {{ $g['racha'] === 1 ? 'día' : 'días' }}</strong>
@foreach($g['metas'] as $meta)<p>🎯 ¡Meta cumplida: {{ $meta }}!</p>@endforeach
@foreach($g['logros'] as $logro)<p>Logro desbloqueado: <strong>{{ $logro }}</strong></p>@endforeach
@foreach($g['recompensas'] as $recompensa)<p>Recompensa obtenida: <strong>{{ $recompensa }}</strong></p>@endforeach
</div>@endif
@if($errors->any())<div class="gv-alert error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
