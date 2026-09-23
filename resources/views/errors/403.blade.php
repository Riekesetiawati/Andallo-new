@extends('layouts.public')
@section('title', 'Akses ditolak')
@section('content')
<div class="empty-state"><h1>Akses ditolak</h1><p>Anda tidak dapat membuka data ini.</p><a href="{{ route('home') }}">Kembali ke beranda</a></div>
@endsection
