@extends('layouts.public')
@section('title', 'Notifikasi')
@section('content')
<h1>Notifikasi</h1>
@forelse($notifications as $item)
    <article class="panel mb-2">
        <strong>{{ $item->title }}</strong>
        <p class="mb-0">{{ $item->body }}</p>
        <small class="muted">{{ $item->created_at->translatedFormat('d M Y H:i') }}</small>
    </article>
@empty
    <div class="empty-state">Belum ada notifikasi.</div>
@endforelse
{{ $notifications->links() }}
@endsection
