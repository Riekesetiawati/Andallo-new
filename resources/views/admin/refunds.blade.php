@extends('layouts.admin')
@section('content')
<h1>Refund</h1>
<p class="muted">Status berhasil hanya setelah konfirmasi di halaman ini, bukan saat pembatalan diajukan.</p>
@foreach($refunds as $refund)
<article class="panel mb-2">
    <strong>{{ $refund->booking->code }}</strong> · {{ rupiah($refund->amount) }} · {{ $refund->status->label() }}
    <p>{{ $refund->reason }}</p>
    @if(in_array($refund->status, [\App\Enums\RefundStatus::Processing, \App\Enums\RefundStatus::Failed, \App\Enums\RefundStatus::PendingReview], true))
        <form method="POST" action="{{ route('admin.refunds.confirm', $refund) }}" enctype="multipart/form-data" class="stack">@csrf<textarea name="note" required placeholder="Catatan konfirmasi"></textarea><input type="file" name="proof" accept="image/*,.pdf"><button class="btn btn-andallo">Konfirmasi dana kembali</button></form>
        <form method="POST" action="{{ route('admin.refunds.fail', $refund) }}">@csrf<input name="note" placeholder="Alasan gagal" required><button class="btn btn-danger">Tandai gagal</button></form>
    @endif
</article>
@endforeach
{{ $refunds->links() }}
@endsection
