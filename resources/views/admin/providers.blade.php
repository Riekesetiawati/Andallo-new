@extends('layouts.admin')
@section('content')
<h1>Mitra</h1>
@foreach($providers as $provider)
<article class="panel mb-3">
    <strong>{{ $provider->business_name }}</strong> · {{ $provider->verification_status->label() }}
    @if($provider->is_demo)<span class="demo-pill">Demo</span>@endif
    <p>{{ $provider->user->email }} · +{{ $provider->whatsapp }} · {{ $provider->city }}</p>
    @if($provider->revision_note)<p>Catatan: {{ $provider->revision_note }}</p>@endif
    <form method="POST" action="{{ route('admin.providers.verify', $provider) }}" class="inline">
        @csrf
        <select name="decision">
            <option value="approved">Setujui</option>
            <option value="revision_requested">Minta perbaikan</option>
            <option value="rejected">Tolak</option>
        </select>
        <input name="revision_note" placeholder="Alasan, jika perlu">
        <button class="btn btn-andallo" type="submit">Simpan</button>
    </form>
</article>
@endforeach
{{ $providers->links() }}
@endsection
