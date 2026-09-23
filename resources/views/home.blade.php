@extends('layouts.public')
@section('title', 'Beranda')
@section('content')
<section class="hero-grid">
    <article class="banner-main">
        <div class="banner-copy">
            <p class="eyebrow">UMKM LOKAL, DAMPAK NYATA</p>
            <h1>Jasa andalan, untuk setiap kebutuhan.</h1>
            <p>Temukan UMKM jasa terpercaya di sekitarmu. Bandingkan harga, lihat portofolio, dan pesan dengan status yang tercatat.</p>
            <a class="btn btn-accent" href="{{ route('services.index') }}">Jelajahi Jasa</a>
        </div>
        <img class="banner-photo" src="{{ asset('images/hero-umkm.png') }}" alt="Penjahit berhijab, teknisi bengkel, dan fotografer UMKM" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.svg') }}'">
        <ul class="banner-points">
            <li><x-icon name="check"/> Mitra terverifikasi</li>
            <li><x-icon name="check"/> Harga transparan</li>
            <li><x-icon name="check"/> Ulasan pelanggan</li>
        </ul>
    </article>
    <div class="banner-side">
        <article class="banner-small peach">
            <div>
                <h2>Usaha lokal, layanan andalan</h2>
                <a href="{{ route('services.index') }}">Temukan jasa UMKM →</a>
            </div>
            <img src="{{ asset('images/laundry-umkm.png') }}" alt="Pemilik laundry melipat handuk" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.svg') }}'">
        </article>
        <article class="banner-small mint">
            <div>
                <h2>Punya usaha jasa? Jadi mitra Andallo</h2>
                <a href="{{ route('register.provider') }}">Gabung sebagai mitra →</a>
            </div>
            <img src="{{ asset('images/bengkel-umkm.png') }}" alt="Pemilik bengkel lokal" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.svg') }}'">
        </article>
    </div>
</section>

<section class="section">
    <div class="section-head"><h2>Kategori Layanan Jasa</h2></div>
    <div class="category-grid">
        @foreach($categories as $category)
            <a class="category-card" href="{{ route('services.index', ['category' => $category->slug]) }}">
                <x-icon :name="$category->icon"/>
                <span>{{ $category->name }}</span>
            </a>
        @endforeach
        <a class="category-card all" href="{{ route('services.index') }}">
            <x-icon name="grid"/>
            <span>Semua Kategori</span>
        </a>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <h2>Rekomendasi di dekatmu</h2>
            <form method="POST" action="{{ route('location.update') }}" class="location-inline">
                @csrf
                <input type="hidden" name="source" value="manual">
                <label for="kota-rekomendasi">Lokasi</label>
                <select id="kota-rekomendasi" name="city" onchange="this.form.submit()">
                    @foreach(config('cities') as $city)
                        <option value="{{ $city['name'] }}" @selected($location['label'] === $city['name'])>{{ $city['name'] }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <a href="{{ route('services.index', ['sort' => 'distance']) }}">Lihat semua →</a>
    </div>
    <div class="service-grid">
        @forelse($featured as $service)
            <x-service-card :service="$service"/>
        @empty
            <div class="empty-state">Belum ada jasa terverifikasi di sekitar lokasi ini.</div>
        @endforelse
    </div>
</section>

<section class="section">
    <div class="section-head"><h2>Temukan Jasa yang Pas, Lebih Mudah dengan Andallo</h2></div>
    <div class="steps">
        <article class="step"><strong>1. Cari Jasa</strong><span>Saring kategori, harga, rating, dan lokasi.</span></article>
        <article class="step"><strong>2. Pilih Penyedia</strong><span>Lihat portofolio, cakupan harga, dan ulasan.</span></article>
        <article class="step"><strong>3. Pesan</strong><span>Pilih jadwal kosong, lalu tunggu respons penyedia.</span></article>
        <article class="step"><strong>4. Layanan Selesai</strong><span>Konfirmasi pekerjaan, lalu beri ulasan.</span></article>
    </div>
</section>

<section class="section join-band">
    <div>
        <h2>Usaha jasamu layak lebih mudah ditemukan</h2>
        <p class="mb-0">Daftar sebagai mitra, lengkapi profil, dan tampil setelah diverifikasi admin.</p>
    </div>
    <a class="btn btn-accent" href="{{ route('register.provider') }}">Gabung Jadi Mitra</a>
</section>
@endsection
