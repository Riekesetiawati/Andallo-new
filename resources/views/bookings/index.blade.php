@extends('layouts.public')
@section('title', 'Pesanan')
@section('content')
<h1>Pesanan saya</h1>
<form method="get" class="inline mb-3">
    <label for="status">Status</label>
    <select id="status" name="status" onchange="this.form.submit()">
        <option value="">Semua</option>
        @foreach(\App\Enums\BookingStatus::cases() as $case)
            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
        @endforeach
    </select>
</form>
@forelse($bookings as $booking)
    <article class="panel mb-3">
        <div class="inline" style="justify-content:space-between">
            <strong>{{ $booking->code }}</strong>
            <span class="badge {{ $booking->status->tone() }}">{{ $booking->status->label() }}</span>
        </div>
        <p class="mb-1">{{ $booking->service_name_snapshot }} · {{ $booking->provider->business_name }}</p>
        <p class="mb-1">{{ $booking->scheduleLabel() }}</p>
        <p class="mb-2">{{ rupiah($booking->total) }} · Pembayaran: {{ $booking->payment_status->label() }}</p>
        <a class="btn btn-andallo" href="{{ route('bookings.show', $booking) }}">Detail pesanan</a>
    </article>
@empty
    <div class="empty-state">Belum ada pesanan. <a href="{{ route('services.index') }}">Cari jasa</a></div>
@endforelse
{{ $bookings->links() }}
@endsection
