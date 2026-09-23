@extends('layouts.public')
@section('title', 'Penyedia Jasa')
@section('content')
<h1>Penyedia Jasa</h1>
<form method="get" class="inline mb-3">
    <label for="q-penyedia">Cari usaha</label>
    <input id="q-penyedia" name="q" value="{{ request('q') }}" placeholder="Nama usaha">
    <button class="btn btn-andallo" type="submit">Cari</button>
</form>
<div class="service-grid">
    @forelse($providers as $provider)
        <article class="service-card">
            <div class="service-body">
                <h3><a href="{{ route('providers.show', $provider) }}">{{ $provider->business_name }}</a></h3>
                <p class="provider-line">{{ $provider->city }} · {{ $provider->services_count }} layanan</p>
                <p class="rating-line">
                    @if($provider->displayRating())
                        <x-icon name="star" class="icon icon-star"/> {{ number_format($provider->displayRating(), 1, ',', '.') }}
                        ({{ $provider->displayReviewCount() }})
                        @if($provider->usesDemoRating())<span class="demo-pill">Ulasan demo</span>@endif
                    @else
                        Belum ada ulasan
                    @endif
                </p>
                <a class="btn btn-andallo" href="{{ route('providers.show', $provider) }}">Lihat Penyedia</a>
            </div>
        </article>
    @empty
        <div class="empty-state">Belum ada penyedia terverifikasi.</div>
    @endforelse
</div>
<div class="mt-3">{{ $providers->links() }}</div>
@endsection
