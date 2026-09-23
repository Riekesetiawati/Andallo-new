@if($results->isEmpty())
    <div class="empty-state">Tidak ada jasa yang cocok. Ubah kata kunci atau reset filter.</div>
@else
    <p class="muted">{{ $results->count() }} jasa ditemukan.</p>
    <div class="service-grid">
        @foreach($results as $service)
            <x-service-card :service="$service"/>
        @endforeach
    </div>
@endif
