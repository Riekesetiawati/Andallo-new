@extends('layouts.admin')
@section('content')
<h1>Log notifikasi</h1>
<p class="muted">Status simulasi berarti pesan belum terkirim ke WhatsApp. Status gagal dapat dicoba ulang tanpa menyalahkan penyedia.</p>
@foreach($logs as $log)
<article class="panel mb-2">
    <strong>{{ $log->template }}</strong> · {{ $log->statusLabel() }} · {{ $log->channel }}
    <p class="mb-1">{{ $log->booking?->code }} · +{{ $log->recipient }}</p>
    <pre style="white-space:pre-wrap">{{ $log->body }}</pre>
    @if($log->error)<p>{{ $log->error }}</p>@endif
    <form method="POST" action="{{ route('admin.logs.retry', $log) }}">@csrf<button class="btn btn-outline">Coba lagi</button></form>
</article>
@endforeach
{{ $logs->links() }}
@endsection
