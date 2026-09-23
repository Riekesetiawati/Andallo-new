@extends('layouts.admin')
@section('content')
<h1>Pembatalan</h1>
@foreach($requests as $item)
<article class="panel mb-2">
    <strong>{{ $item->booking->code }}</strong> · {{ $item->statusLabel() }} · tahap {{ $item->stage }}
    <p>{{ $item->reason }}</p>
    @if($item->refund_estimate)<p>Estimasi {{ rupiah($item->refund_estimate) }}</p>@endif
    @if($item->status === 'pending_funds_check')
        <form method="POST" action="{{ route('admin.cancellations.resolve', $item) }}" class="inline">@csrf<input type="hidden" name="action" value="funds_missing"><input name="note" placeholder="Catatan"><button class="btn btn-outline">Dana belum masuk</button></form>
        <form method="POST" action="{{ route('admin.cancellations.resolve', $item) }}" class="inline">@csrf<input type="hidden" name="action" value="funds_received"><input name="note" placeholder="Catatan"><button class="btn btn-andallo">Dana sudah masuk</button></form>
    @endif
    @if($item->status === 'pending' && $item->stage === 'before_start')
        <form method="POST" action="{{ route('admin.cancellations.resolve', $item) }}">@csrf<input type="hidden" name="action" value="approve"><input name="note" placeholder="Catatan"><button class="btn btn-andallo">Setujui pembatalan</button></form>
    @endif
    @if($item->status === 'pending' && $item->stage === 'in_progress')
        <form method="POST" action="{{ route('admin.cancellations.resolve', $item) }}" class="inline">@csrf<input type="hidden" name="action" value="propose"><input name="amount" type="number" min="0" step="0.01" placeholder="Nominal refund" required><input name="note" placeholder="Catatan untuk pelanggan" required><button class="btn btn-andallo">Usulkan ke pelanggan</button></form>
    @endif
</article>
@endforeach
{{ $requests->links() }}
@endsection
