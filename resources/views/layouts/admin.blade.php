<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — Andallo</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/andallo.css') }}">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-side">
        <a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.svg') }}" alt="" width="28" height="28"><span>andallo</span></a>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Ringkasan</a>
        <a href="{{ route('admin.providers') }}">Verifikasi mitra</a>
        <a href="{{ route('admin.bookings') }}">Pemeriksaan pesanan</a>
        <a href="{{ route('admin.payments') }}">Verifikasi transfer</a>
        <a href="{{ route('admin.cancellations') }}">Pembatalan</a>
        <a href="{{ route('admin.refunds') }}">Refund</a>
        <a href="{{ route('admin.complaints') }}">Komplain</a>
        <a href="{{ route('admin.logs') }}">Notifikasi gagal</a>
        <a href="{{ route('admin.users') }}">Pengguna</a>
        <a href="{{ route('admin.categories') }}">Kategori</a>
        <a href="{{ route('admin.services') }}">Jasa</a>
        <a href="{{ route('admin.settings') }}">Pengaturan</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline mt-3" type="submit">Keluar</button></form>
    </aside>
    <main class="admin-main">
        @include('partials.flash')
        @yield('content')
    </main>
</div>
</body>
</html>
