@extends('layouts.public')
@section('title', $asProvider ? 'Daftar Mitra' : 'Daftar')
@section('content')
<form class="auth-card stack" method="POST" action="{{ route('register.store') }}" data-loading>
    @csrf
    @if($asProvider)<input type="hidden" name="as_provider" value="1">@endif
    <h1>{{ $asProvider ? 'Gabung jadi mitra' : 'Daftar pelanggan' }}</h1>
    <p class="muted">Setelah daftar, mitra melengkapi profil lalu menunggu verifikasi admin sebelum layanan tampil.</p>
    <div class="field"><label for="name">Nama penanggung jawab</label><input id="name" name="name" value="{{ old('name') }}" required></div>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required></div>
    <div class="field"><label for="phone">Nomor WhatsApp</label><input id="phone" name="phone" value="{{ old('phone') }}" placeholder="08… atau 628…" required></div>
    @if($asProvider)
        <div class="field"><label for="business_name">Nama usaha</label><input id="business_name" name="business_name" value="{{ old('business_name') }}" required></div>
        <div class="field"><label for="city">Kota</label><input id="city" name="city" value="{{ old('city') }}" required></div>
    @endif
    <div class="field"><label for="password">Kata sandi</label><input id="password" type="password" name="password" required minlength="8"></div>
    <div class="field"><label for="password_confirmation">Ulangi kata sandi</label><input id="password_confirmation" type="password" name="password_confirmation" required></div>
    <button class="btn btn-accent" type="submit">Buat akun</button>
</form>
@endsection
