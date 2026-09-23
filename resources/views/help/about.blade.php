@extends('layouts.public')
@section('title', 'Tentang Kami')
@section('content')
<article class="panel">
    <p class="eyebrow">TENTANG ANDALLO</p>
    <h1>Marketplace jasa untuk UMKM di sekitarmu</h1>
    <p>Andallo membantu pelanggan menemukan jasa lokal, membandingkan harga beserta cakupannya, melihat portofolio dan ulasan, lalu memesan dengan status yang tercatat. Penyedia UMKM mengelola layanan, jadwal, dan progres pekerjaan dari akun mitra.</p>
    <p>WhatsApp dipakai untuk komunikasi. Sumber status pesanan tetap database Andallo. Pembayaran MVP menggunakan transfer manual yang diverifikasi admin. Unggahan bukti belum berarti pembayaran berhasil.</p>
    <a class="btn btn-accent" href="{{ route('services.index') }}">Jelajahi Jasa</a>
</article>
@endsection
