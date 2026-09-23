@extends('layouts.public')
@section('title', 'Tidak ditemukan')
@section('content')
<div class="empty-state"><h1>Halaman tidak ditemukan</h1><a href="{{ route('home') }}">Kembali ke beranda</a></div>
@endsection
