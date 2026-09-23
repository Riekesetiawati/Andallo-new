@extends('layouts.public')
@section('title', $provider->business_name)
@section('content')
<article class="panel">
    <p class="badge">{{ $provider->verification_status->label() }}</p>
    <h1>{{ $provider->business_name }}</h1>
    <p>{{ $provider->description }}</p>
    <p><strong>Penanggung jawab:</strong> {{ $provider->user->name }}</p>
    <p><strong>Area:</strong> {{ $provider->service_area }} · {{ $provider->city }}</p>
    <p><strong>WhatsApp:</strong> +{{ $provider->whatsapp }}</p>
    @if($provider->is_demo)<p class="demo-pill">Profil contoh untuk lingkungan demo</p>@endif
</article>
<h2 class="mt-4">Layanan</h2>
<div class="service-grid">
    @foreach($provider->services as $service)
        <x-service-card :service="$service"/>
    @endforeach
</div>
@endsection
