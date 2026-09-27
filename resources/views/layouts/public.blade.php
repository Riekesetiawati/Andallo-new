<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Andallo') — Marketplace Jasa UMKM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/andallo.css') }}">
    @stack('head')
</head>
<body>
<a class="skip-link" href="#konten">Lewati ke konten</a>
<header class="site-header sticky-top">
    <div class="topbar">
        <div class="container topbar-inner">
            <a href="{{ route('register.provider') }}">Jadi Mitra Andallo</a>
            <div class="topbar-links">
                @auth
                    <a href="{{ route('notifications.index') }}">Notifikasi @if($unreadCount)<span class="badge-count">{{ $unreadCount }}</span>@endif</a>
                @else
                    <a href="{{ route('login') }}">Notifikasi</a>
                @endauth
                <a href="{{ route('help') }}">Bantuan</a>
                @guest
                    <a href="{{ route('register') }}">Daftar</a>
                    <a href="{{ route('login') }}">Masuk</a>
                @else
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isProvider() ? route('provider.dashboard') : route('profile.edit')) }}">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
                @endguest
            </div>
        </div>
    </div>
    <div class="mainbar">
        <div class="container mainbar-inner">
            <a class="brand" href="{{ route('home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="" width="40" height="40">
                <span>andallo</span>
            </a>
            <form class="header-search" action="{{ route('services.index') }}" method="get" role="search">
                <label class="visually-hidden" for="q-header">Cari jasa</label>
                <input id="q-header" name="q" value="{{ request('q') }}" placeholder="Cari jasa yang kamu butuhkan..." autocomplete="off">
                <button type="submit" aria-label="Cari"><x-icon name="search"/></button>
            </form>
            <div class="main-actions">
                <a class="btn-orders" href="{{ route('bookings.index') }}"><x-icon name="calendar"/> Pesanan</a>
                @include('partials.location', ['suffix' => 'desktop'])
            </div>
            <button class="hamburger" type="button" aria-expanded="false" aria-controls="menu-ponsel" id="menu-toggle">
                <span></span><span></span><span></span>
                <span class="visually-hidden">Menu</span>
            </button>
        </div>
        <nav class="center-nav" aria-label="Utama">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
            <a href="{{ route('services.index') }}" class="{{ request()->routeIs('services.*') ? 'active' : '' }}">Cari Jasa</a>
            <a href="{{ route('providers.index') }}" class="{{ request()->routeIs('providers.*') ? 'active' : '' }}">Penyedia Jasa</a>
            <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">Tentang Kami</a>
        </nav>
    </div>
    <div id="menu-ponsel" class="mobile-nav">
        <a href="{{ route('home') }}">Beranda</a>
        <a href="{{ route('services.index') }}">Cari Jasa</a>
        <a href="{{ route('providers.index') }}">Penyedia Jasa</a>
        <a href="{{ route('about') }}">Tentang Kami</a>
        <a href="{{ route('bookings.index') }}">Pesanan</a>
        <a href="{{ route('help') }}">Bantuan</a>
        <a href="{{ route('compare.index') }}">Bandingkan ({{ count($compareIds) }})</a>
        @auth
            @if(auth()->user()->isProvider())<a href="{{ route('provider.dashboard') }}">Dasbor Mitra</a>@endif
            @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}">Dasbor Admin</a>@endif
            <a href="{{ route('profile.edit') }}">Profil</a>
        @else
            <a href="{{ route('login') }}">Masuk</a>
            <a href="{{ route('register') }}">Daftar</a>
        @endguest
        @include('partials.location', ['suffix' => 'mobile'])
    </div>
</header>
<main id="konten" class="page">
    <div class="container">
        @include('partials.flash')
        @yield('content')
    </div>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.png') }}" alt=""><span>andallo</span></a>
            <p>Marketplace jasa yang menghubungkan pelanggan dengan UMKM penyedia jasa lokal. Harga, jadwal, dan status pesanan tercatat di Andallo.</p>
        </div>
        <div>
            <h2>Jelajahi</h2>
            <a href="{{ route('home') }}">Beranda</a>
            <a href="{{ route('services.index') }}">Cari Jasa</a>
            <a href="{{ route('providers.index') }}">Penyedia Jasa</a>
            <a href="{{ route('compare.index') }}">Bandingkan</a>
            <a href="{{ route('bookings.index') }}">Pesanan</a>
        </div>
        <div>
            <h2>Bantuan</h2>
            <a href="{{ route('about') }}">Tentang Kami</a>
            <a href="{{ route('help') }}">Pusat Bantuan</a>
            <a href="{{ route('register.provider') }}">Gabung Jadi Mitra</a>
            <a href="mailto:{{ setting_value('support_email') }}">{{ setting_value('support_email') }}</a>
        </div>
    </div>
    <div class="container legal">© {{ date('Y') }} Andallo. Jarak yang tampil adalah perkiraan garis lurus, bukan pelacakan perjalanan.</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/andallo.js') }}"></script>
@stack('scripts')
</body>
</html>
