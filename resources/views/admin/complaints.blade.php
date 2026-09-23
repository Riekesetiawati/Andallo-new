@extends('layouts.admin')
@section('content')
<h1>Komplain</h1>
@foreach($complaints as $complaint)
<article class="panel mb-2">
    <strong>{{ $complaint->subject }}</strong> · {{ $complaint->statusLabel() }}
    <p>{{ $complaint->description }}</p>
    <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}" class="inline">
        @csrf
        <select name="status">@foreach(['open' => 'Terbuka', 'in_review' => 'Ditinjau', 'resolved' => 'Selesai', 'closed' => 'Ditutup'] as $value => $label)<option value="{{ $value }}" @selected($complaint->status === $value)>{{ $label }}</option>@endforeach</select>
        <input name="admin_note" value="{{ $complaint->admin_note }}" placeholder="Catatan">
        <button class="btn btn-andallo">Simpan</button>
    </form>
</article>
@endforeach
{{ $complaints->links() }}
@endsection
