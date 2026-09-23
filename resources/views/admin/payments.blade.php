@extends('layouts.admin')
@section('content')
<h1>Pembayaran</h1>
@foreach($payments as $payment)
<article class="panel mb-2">
    <strong>{{ $payment->booking->code }}</strong> · {{ rupiah($payment->amount) }} · {{ $payment->status->label() }}
    <p>{{ $payment->booking->customer->name }} · {{ $payment->method }}</p>
    @if($payment->proof_path)<a href="{{ route('payments.proof', $payment) }}">Lihat bukti</a>@endif
    @if($payment->status === \App\Enums\PaymentStatus::UnderReview && $payment->booking->status === \App\Enums\BookingStatus::AwaitingPayment)
        <form method="POST" action="{{ route('admin.payments.approve', $payment) }}" class="inline">@csrf<input name="note" placeholder="Catatan"><button class="btn btn-andallo">Setujui</button></form>
        <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="inline">@csrf<input name="reason" placeholder="Alasan" required><button class="btn btn-danger">Tolak bukti</button></form>
    @endif
</article>
@endforeach
{{ $payments->links() }}
@endsection
