{{-- RF-16: selector de vivienda activa. Variables: $viviendas, $vivienda, $destino (opcional) --}}
@if($vivienda && $viviendas->count() > 0)
<form method="POST" action="{{ route('viviendas.seleccionar',$vivienda) }}">@csrf<input type="hidden" name="destino" value="{{ $destino ?? 'dashboard' }}"><select class="gv-select" aria-label="Vivienda activa" onchange="this.form.action=this.selectedOptions[0].dataset.url;this.form.submit()">@foreach($viviendas as $item)<option data-url="{{ route('viviendas.seleccionar',$item) }}" @selected($item->id===$vivienda->id)>{{ $item->alias }}</option>@endforeach</select></form>
@endif
