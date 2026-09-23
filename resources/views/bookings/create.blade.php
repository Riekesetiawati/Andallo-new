@extends('layouts.public')
@section('title', 'Pesan '.$service->name)
@section('content')
<h1>Pesan {{ $service->name }}</h1>
<p>{{ $service->provider->business_name }} · {{ rupiah($service->price) }} {{ $service->price_unit }}</p>
<form method="POST" action="{{ route('bookings.store', $service) }}" class="panel stack" data-loading>
    @csrf
    <div class="field">
        <label for="scheduled_date">Tanggal</label>
        <input id="scheduled_date" type="date" name="scheduled_date" required min="{{ now()->toDateString() }}" value="{{ old('scheduled_date', $date) }}">
    </div>
    <fieldset>
        <legend>Jam tersedia</legend>
        <div id="slot-list" data-url="{{ route('services.slots', $service) }}">
            @forelse($slots as $slot)
                <label class="inline"><input type="radio" name="start_time" value="{{ $slot['start'] }}" required @checked(old('start_time') === $slot['start'])> {{ $slot['start'] }}–{{ $slot['end'] }}</label>
            @empty
                <p class="empty-state">Tidak ada jadwal kosong pada tanggal ini.</p>
            @endforelse
        </div>
    </fieldset>
    <div class="field">
        <label for="city">Kota layanan</label>
        <input id="city" name="city" required value="{{ old('city', $service->provider->city) }}">
    </div>
    <div class="field">
        <label for="address">Alamat layanan</label>
        <textarea id="address" name="address" required rows="3">{{ old('address') }}</textarea>
        <small class="muted">Alamat lengkap tidak dimasukkan ke pesan WhatsApp.</small>
    </div>
    <div class="field">
        <label for="customer_notes">Catatan kebutuhan</label>
        <textarea id="customer_notes" name="customer_notes" rows="3">{{ old('customer_notes') }}</textarea>
    </div>
    <article class="note">
        <strong>Rincian biaya</strong>
        <p class="mb-0">Harga jasa {{ rupiah($service->price) }} {{ $service->price_unit }}. Total yang disimpan saat pesanan dibuat: {{ rupiah($service->price) }}. Perubahan harga jasa tidak mengubah pesanan ini.</p>
    </article>
    <article class="panel">
        <h2>Kebijakan pembatalan</h2>
        <p>{{ $policy }}</p>
        <p>Jika dibatalkan setelah pembayaran terverifikasi dan sebelum pekerjaan dimulai, estimasi pengembalian mengikuti pengaturan admin saat ini: {{ $percent }}% dari total.</p>
        <label class="inline"><input type="checkbox" name="agree_policy" value="1" required> Saya memahami kebijakan pembatalan.</label>
    </article>
    <button class="btn btn-accent" type="submit">Konfirmasi pesanan</button>
</form>
@endsection
