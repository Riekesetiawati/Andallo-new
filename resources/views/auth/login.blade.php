@extends('layouts.public')
@section('title', 'Masuk')
@section('content')
<form class="auth-card stack" method="POST" action="{{ route('login.store') }}" data-loading>
    @csrf
    <h1>Masuk</h1>
    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
    </div>
    <div class="field">
        <label for="password">Kata sandi</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
    </div>
    <label class="inline"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
    <button class="btn btn-andallo" type="submit">Masuk</button>
    <p>Belum punya akun? <a href="{{ route('register') }}">Daftar</a> atau <a href="{{ route('register.provider') }}">gabung sebagai mitra</a>.</p>
</form>
@endsection
