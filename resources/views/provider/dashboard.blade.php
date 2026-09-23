@extends('layouts.public')
@section('title', 'Dasbor Mitra')
@section('content')
<h1>{{ $provider->business_name }}</h1>
<p><span class="badge">{{ $provider->verification_status->label() }}</span> @if($provider->is_demo)<span class="demo-pill">Data demo</span>@endif</p>
@if($provider->verification_status->value === 'revision_requested')
    <div class="note">Admin meminta perbaikan: {{ $provider->revision_note }}</div>
@endif
@if($provider->verification_status->value !== 'approved')
    <p>Layanan baru tampil dan dapat menerima pesanan setelah admin menyetujui profil.</p>
@endif
<div class="inline mb-3">
    <a class="btn btn-outline" href="{{ route('provider.profile.edit') }}">Profil usaha</a>
    <a class="btn btn-outline" href="{{ route('provider.services.index') }}">Layanan</a>
    <a class="btn btn-outline" href="{{ route('provider.schedule.edit') }}">Jadwal</a>
    <a class="btn btn-andallo" href="{{ route('provider.orders.index') }}">Pesanan masuk</a>
</div>
<h2>Pesanan terbaru</h2>
@forelse($incoming as $booking)
    <article class="panel mb-2"><a href="{{ route('bookings.show', $booking) }}">{{ $booking->code }}</a> · {{ $booking->status->label() }} · {{ $booking->service_name_snapshot }}</article>
@empty
    <div class="empty-state">Belum ada pesanan.</div>
@endforelse
@endsection
