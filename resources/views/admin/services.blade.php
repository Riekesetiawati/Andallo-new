@extends('layouts.admin')
@section('content')
<h1>Jasa</h1>
<div class="table-wrap"><table class="data"><thead><tr><th>Jasa</th><th>Penyedia</th><th>Harga</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($services as $service)
<tr>
    <td>{{ $service->name }}</td>
    <td>{{ $service->provider->business_name }}</td>
    <td>{{ rupiah($service->price) }} {{ $service->price_unit }}</td>
    <td>{{ $service->is_active ? 'Aktif' : 'Nonaktif' }}</td>
    <td><form method="POST" action="{{ route('admin.services.toggle', $service) }}">@csrf<button class="btn btn-outline">Ubah</button></form></td>
</tr>
@endforeach
</tbody></table></div>
{{ $services->links() }}
@endsection
