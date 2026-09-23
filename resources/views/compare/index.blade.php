@extends('layouts.public')
@section('title', 'Bandingkan Jasa')
@section('content')
<h1>Bandingkan Jasa</h1>
<p>Maksimal tiga jasa. Satuan dan cakupan bisa berbeda, jadi harga tidak selalu sebanding satu lawan satu.</p>
@if($unitNote)<p class="note">Satuan harga berbeda. Bandingkan juga durasi dan apa yang termasuk.</p>@endif
@if($coverageNote)<p class="note">Cakupan layanan berbeda. Baca kolom termasuk dan tidak termasuk sebelum memesan.</p>@endif
@if($services->isEmpty())
    <div class="empty-state">Belum ada jasa yang dibandingkan. Tambahkan dari kartu jasa.</div>
@else
    <div class="table-wrap">
        <table class="compare-table">
            <thead>
                <tr>
                    <th>Perbandingan</th>
                    @foreach($services as $service)<th>{{ $service->name }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                <tr><th>Penyedia</th>@foreach($services as $service)<td>{{ $service->provider->business_name }}</td>@endforeach</tr>
                <tr><th>Harga dan satuan</th>@foreach($services as $service)<td>{{ rupiah($service->price) }} {{ $service->price_unit }}</td>@endforeach</tr>
                <tr><th>Termasuk</th>@foreach($services as $service)<td>{{ $service->includes }}</td>@endforeach</tr>
                <tr><th>Tidak termasuk</th>@foreach($services as $service)<td>{{ $service->excludes ?: '—' }}</td>@endforeach</tr>
                <tr><th>Lokasi</th>@foreach($services as $service)<td>{{ $service->provider->city }}@if($service->distance_km !== null)<br>{{ format_distance($service->distance_km) }} jarak perkiraan @endif</td>@endforeach</tr>
                <tr><th>Rating</th>@foreach($services as $service)<td>@if($service->displayRating()){{ number_format($service->displayRating(), 1, ',', '.') }} ({{ $service->displayReviewCount() }}) @if($service->usesDemoRating())<span class="demo-pill">demo</span>@endif @else Belum ada @endif</td>@endforeach</tr>
                <tr><th>Durasi</th>@foreach($services as $service)<td>{{ $service->duration_minutes }} menit</td>@endforeach</tr>
                <tr>
                    <th></th>
                    @foreach($services as $service)
                        <td class="stack">
                            <a class="btn btn-andallo" href="{{ route('bookings.create', $service) }}">Pesan</a>
                            <form method="POST" action="{{ route('compare.toggle') }}">@csrf<input type="hidden" name="service_id" value="{{ $service->id }}"><button class="btn btn-outline" type="submit">Hapus</button></form>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
@endif
@endsection
