@extends('layouts.admin')
@section('title', 'Ringkasan')
@section('content')
<h1>Ringkasan</h1>
<div class="stat-grid">
    <article class="stat"><span>Verifikasi mitra</span><strong>{{ $providers }}</strong></article>
    <article class="stat"><span>Pemeriksaan pesanan</span><strong>{{ $reviews }}</strong></article>
    <article class="stat"><span>Transfer menunggu</span><strong>{{ $payments }}</strong></article>
    <article class="stat"><span>Pembatalan</span><strong>{{ $cancellations }}</strong></article>
    <article class="stat"><span>Refund</span><strong>{{ $refunds }}</strong></article>
    <article class="stat"><span>Komplain</span><strong>{{ $complaints }}</strong></article>
    <article class="stat"><span>Notifikasi gagal</span><strong>{{ $failed }}</strong></article>
    <article class="stat"><span>Pembayaran terverifikasi</span><strong>{{ rupiah($paidTotal) }}</strong></article>
</div>
@endsection
