@props(['service'])
<article class="service-card">
    <a class="service-thumb" href="{{ route('services.show', $service) }}">
        <img src="{{ $service->image_path ? asset($service->image_path) : asset('images/placeholder.svg') }}" alt="Foto layanan {{ $service->name }}" width="640" height="480" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.svg') }}'">
    </a>
    <div class="service-body">
        <h3><a href="{{ route('services.show', $service) }}">{{ $service->name }}</a></h3>
        <p class="provider-line">{{ $service->provider->business_name }}</p>
        <p class="rating-line">
            <x-icon name="star" class="icon icon-star"/>
            @if($service->displayRating() !== null)
                <strong>{{ number_format($service->displayRating(), 1, ',', '.') }}</strong>
                <span>({{ $service->displayReviewCount() }})</span>
            @else
                <span>Belum ada ulasan</span>
            @endif
            @if($service->usesDemoRating())
                <span class="demo-pill">Ulasan demo</span>
            @endif
            @if($service->distance_km !== null)
                <span class="dot">·</span>
                <span>{{ format_distance($service->distance_km) }}</span>
            @endif
        </p>
        <p class="price">Mulai {{ rupiah($service->price) }}</p>
        <div class="service-actions">
            <a class="btn btn-andallo" href="{{ route('services.show', $service) }}">Lihat Jasa</a>
            <form method="POST" action="{{ route('compare.toggle') }}">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                @php $on = in_array($service->id, array_map('intval', session('compare', [])), true); @endphp
                <button type="submit" class="compare-toggle {{ $on ? 'is-on' : '' }}" aria-pressed="{{ $on ? 'true' : 'false' }}">
                    <span class="box" aria-hidden="true">{{ $on ? '✓' : '' }}</span>
                    Bandingkan
                </button>
            </form>
        </div>
    </div>
</article>
