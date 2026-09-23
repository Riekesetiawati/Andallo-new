@extends('layouts.public')
@section('title', 'Bantuan')
@section('content')
<h1>Bantuan</h1>
<div class="accordion faq" id="faq">
    @foreach($faqs as $index => $faq)
        <div class="accordion-item">
            <h2 class="accordion-header" id="h{{ $index }}">
                <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#c{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="c{{ $index }}">{{ $faq['q'] }}</button>
            </h2>
            <div id="c{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#faq">
                <div class="accordion-body">{{ $faq['a'] }}</div>
            </div>
        </div>
    @endforeach
</div>
<article class="panel mt-4">
    <h2>Kontak bantuan</h2>
    <p>Email: <a href="mailto:{{ $support['email'] }}">{{ $support['email'] }}</a></p>
    <p>WhatsApp: +{{ $support['phone'] }}</p>
    <p>{{ $support['hours'] }}</p>
    <a class="btn btn-outline" href="{{ whatsapp_link($support['phone'], 'Halo Andallo, saya butuh bantuan.') }}" target="_blank" rel="noopener">Buka WhatsApp</a>
    <p class="muted">Anda masih perlu menekan kirim di WhatsApp.</p>
</article>
@endsection
