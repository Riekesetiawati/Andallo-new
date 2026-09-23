@extends('layouts.public')
@section('title', 'Tindakan pesanan')
@section('content')
<article class="panel stack">
    <h1>Respons pesanan {{ $booking->code }}</h1>
    <p>Membuka halaman ini tidak mengubah status. Status hanya berubah setelah Anda menekan tombol di bawah.</p>
    <p><strong>{{ $booking->service_name_snapshot }}</strong></p>
    <p>{{ $booking->scheduleLabel() }}</p>
    <p>Kota: {{ $booking->city }}</p>
    <p>Total: {{ rupiah($booking->total) }}</p>
    <p>Status sekarang: {{ $booking->status->label() }}</p>
    @if($expired)
        <div class="alert alert-danger">Tautan ini sudah dipakai atau kedaluwarsa.</div>
    @elseif($booking->status !== \App\Enums\BookingStatus::AwaitingProvider)
        <div class="note">Pesanan tidak lagi menunggu respons penyedia.</div>
    @else
        <form method="POST" action="{{ route('actions.store', $token) }}" class="stack" data-loading>
            @csrf
            <button class="btn btn-andallo" name="decision" value="accept" type="submit">Terima pesanan</button>
            <label for="reason">Alasan jika menolak</label>
            <textarea id="reason" name="reason"></textarea>
            <button class="btn btn-danger" name="decision" value="reject" type="submit">Tolak pesanan</button>
        </form>
    @endif
</article>
@endsection
