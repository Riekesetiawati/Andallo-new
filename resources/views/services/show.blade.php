@extends('layouts.public')
@section('title', $service->name)
@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush
@section('content')
@php
    $wa = whatsapp_link($service->provider->whatsapp, "Halo {$service->provider->business_name}, saya tertarik dengan jasa {$service->name} di Andallo.");
    $realReviews = $service->reviews->where('is_demo', false);
    $demoReviews = $service->reviews->where('is_demo', true);
@endphp
<div class="detail">
    <div class="stack">
        <div class="gallery">
            <img class="main" src="{{ $service->image_path ? asset($service->image_path) : asset('images/placeholder.svg') }}" alt="Foto {{ $service->name }}" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.svg') }}'">
            <div class="stack">
                @foreach($service->portfolios->take(2) as $portfolio)
                    <img src="{{ asset($portfolio->image_path) }}" alt="{{ $portfolio->title }}" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.svg') }}'">
                @endforeach
            </div>
        </div>
        <article class="panel">
            <p class="muted">{{ $service->category->name }} · {{ $service->provider->city }}</p>
            <h1>{{ $service->name }}</h1>
            <p>{{ $service->provider->business_name }} @if($service->provider->verification_status->value === 'approved')<span class="badge">Mitra terverifikasi</span>@endif</p>
            <p>{{ $service->description }}</p>
            <p><strong>Area layanan:</strong> {{ $service->provider->service_area }}</p>
            <p><strong>Termasuk:</strong> {{ $service->includes }}</p>
            <p><strong>Tidak termasuk:</strong> {{ $service->excludes ?: 'Tidak ada catatan pengecualian.' }}</p>
            <p><strong>Durasi perkiraan:</strong> {{ $service->duration_minutes }} menit · {{ $service->requires_visit ? 'Perlu kunjungan' : 'Dapat dikerjakan daring' }}</p>
            @if($distance !== null)
                <p>Jarak perkiraan dari lokasi Anda: <strong>{{ format_distance($distance) }}</strong>. Bukan jarak perjalanan.</p>
            @else
                <p class="muted">Jarak tidak ditampilkan karena koordinat lokasi Anda atau usaha belum lengkap.</p>
            @endif
        </article>
        <article class="panel">
            <h2>Portofolio</h2>
            @forelse($service->provider->portfolios as $portfolio)
                <p><strong>{{ $portfolio->title }}</strong> @if($portfolio->is_demo)<span class="demo-pill">Contoh demo</span>@endif<br>{{ $portfolio->description }}</p>
            @empty
                <p class="muted">Portofolio belum diunggah.</p>
            @endforelse
        </article>
        <article class="panel">
            <h2>Ulasan pelanggan</h2>
            @forelse($realReviews as $review)
                <p><strong>{{ $review->rating }}/5</strong> — {{ $review->comment }}<br><span class="muted">{{ $review->customer->name }}</span></p>
            @empty
                <p class="muted">Belum ada ulasan pelanggan untuk jasa ini.</p>
            @endforelse
            @if($demoReviews->isNotEmpty())
                <h3>Contoh ulasan demo</h3>
                <p class="muted">Bagian ini contoh data, bukan ulasan pelanggan nyata.</p>
                @foreach($demoReviews as $review)
                    <p><span class="demo-pill">Demo</span> {{ $review->rating }}/5 — {{ $review->comment }}</p>
                @endforeach
            @endif
        </article>
        <article class="panel">
            <h2>Lokasi usaha</h2>
            <p class="muted">Titik pada peta adalah lokasi usaha yang disimpan, bukan pelacakan langsung.</p>
            @if($service->provider->latitude)
                <div id="peta-usaha" class="map" data-map data-lat="{{ $service->provider->latitude }}" data-lng="{{ $service->provider->longitude }}"></div>
            @else
                <p>Koordinat usaha belum tersedia.</p>
            @endif
        </article>
    </div>
    <aside class="panel sticky-buy">
        <p class="price-lg">{{ rupiah($service->price) }}</p>
        <p class="muted">{{ $service->price_unit }}</p>
        <p>Contoh jadwal besok yang masih kosong:</p>
        @forelse($slots as $slot)
            <span class="badge">{{ $slot['start'] }}–{{ $slot['end'] }}</span>
        @empty
            <p class="muted">Belum ada slot besok. Cek tanggal lain saat memesan.</p>
        @endforelse
        <div class="stack mt-3">
            <a class="btn btn-accent" href="{{ route('bookings.create', $service) }}">Pesan Jasa</a>
            <form method="POST" action="{{ route('compare.toggle') }}">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                <button class="btn btn-outline" type="submit">Bandingkan</button>
            </form>
            <a class="btn btn-outline" href="{{ $wa }}" target="_blank" rel="noopener">Hubungi via WhatsApp</a>
            <p class="muted">Tautan membuka WhatsApp. Pesan belum terkirim sampai Anda menekan kirim, dan tidak mengubah status pesanan.</p>
            <a href="{{ route('providers.show', $service->provider) }}">Lihat profil usaha</a>
        </div>
    </aside>
</div>
@endsection
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
