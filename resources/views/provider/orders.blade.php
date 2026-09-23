@extends('layouts.public')
@section('title', 'Pesanan masuk')
@section('content')
<h1>Pesanan masuk</h1>
@forelse($bookings as $booking)
    <article class="panel mb-2">
        <a href="{{ route('bookings.show', $booking) }}">{{ $booking->code }}</a>
        · {{ $booking->status->label() }} · {{ $booking->service_name_snapshot }}
        <p>{{ $booking->scheduleLabel() }} · {{ rupiah($booking->total) }}</p>
    </article>
@empty
    <div class="empty-state">Belum ada pesanan untuk usaha Anda.</div>
@endforelse
{{ $bookings->links() }}
@endsection
