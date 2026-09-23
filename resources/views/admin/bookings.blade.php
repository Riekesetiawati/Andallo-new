@extends('layouts.admin')
@section('content')
<h1>Pesanan</h1>
<form method="get" class="mb-3"><select name="status" onchange="this.form.submit()"><option value="">Semua</option>@foreach(\App\Enums\BookingStatus::cases() as $case)<option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>@endforeach</select></form>
@foreach($bookings as $booking)
<article class="panel mb-2">
    <a href="{{ route('bookings.show', $booking) }}"><strong>{{ $booking->code }}</strong></a>
    {{ $booking->status->label() }} · {{ $booking->payment_status->label() }}
    <p>{{ $booking->service_name_snapshot }} · {{ $booking->customer->name }} · {{ $booking->provider->business_name }}</p>
    @if($booking->status === \App\Enums\BookingStatus::PendingReview)
        <form method="POST" action="{{ route('admin.bookings.forward', $booking) }}" class="inline">@csrf<button class="btn btn-andallo">Teruskan ke penyedia</button></form>
        <form method="POST" action="{{ route('admin.bookings.reject', $booking) }}" class="inline">@csrf<input name="reason" placeholder="Alasan" required><button class="btn btn-danger">Tolak</button></form>
    @endif
</article>
@endforeach
{{ $bookings->links() }}
@endsection
